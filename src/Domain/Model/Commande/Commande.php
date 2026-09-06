<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Bookshelf\Domain\Model\Common\AdresseEmail;
use Bookshelf\Domain\Model\Common\CodePays;
use Bookshelf\Domain\Model\Common\Devise;
use Bookshelf\Domain\Model\Common\Montant;
use Bookshelf\Domain\Model\Common\TauxDeTva;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * La racine de l'agregat Commande.
 *
 * PALIER D : le temps est entre dans le modele, et il y est entre PAR LES ARGUMENTS.
 *
 * `passer()` et `payer()` recoivent une date ; l'entite ne connait aucune horloge. C'est ce
 * qui lui permet de rester une fonction pure de ses entrees : donnez-lui les memes
 * arguments, elle se comportera toujours de la meme facon. Les tests unitaires de cette
 * classe n'ont donc aucune horloge a configurer, et ils restent instantanes.
 *
 * L'horloge, elle, vit dans les services applicatifs : eux ont le droit de dependre
 * d'une abstraction d'infrastructure (`Psr\Clock\ClockInterface`).
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Commande
{
    /**
     * Delai de paiement. Nomme d'apres le metier, pas d'apres sa valeur : le jour ou
     * Bookshelf passera a 72 heures, on ne cherchera pas ou est le « 48 » dans le code.
     */
    private const DELAI_DE_PAIEMENT = 'PT48H';

    #[ORM\Column(type: 'string', name: 'status', length: 20, enumType: EtatCommande::class)]
    private EtatCommande $etat = EtatCommande::EnAttente;

    #[ORM\Column(type: 'payment_reference', name: 'payment_reference', nullable: true)]
    private ?ReferenceDePaiement $referenceDePaiement = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'paid_at', nullable: true)]
    private ?DateTimeImmutable $payeeLe = null;

    /** @var Collection<int, LigneDeCommande> */
    #[ORM\OneToMany(
        targetEntity: LigneDeCommande::class,
        mappedBy: 'commande',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $lignes;

    /** @var object[] */
    private array $evenements = [];

    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'order_id', name: 'id')]
        private IdentifiantCommande $identifiantCommande,

        #[ORM\Column(type: 'email_address', name: 'email')]
        private AdresseEmail $adresseEmail,

        #[ORM\Column(type: 'country_code', name: 'country')]
        private CodePays $pays,

        #[ORM\Column(type: 'vat_rate', name: 'vat_rate')]
        private TauxDeTva $tauxDeTva,

        #[ORM\Column(type: 'datetime_immutable', name: 'placed_at')]
        private DateTimeImmutable $passeeLe,
    ) {
        $this->lignes = new ArrayCollection();
    }

    public static function passer(
        IdentifiantCommande $identifiantCommande,
        AdresseEmail $adresseEmail,
        CodePays $pays,
        TauxDeTva $tauxDeTva,
        DateTimeImmutable $passeeLe,
    ): self {
        return new self($identifiantCommande, $adresseEmail, $pays, $tauxDeTva, $passeeLe);
    }

    public function ajouterLigne(IdentifiantEbook $identifiantEbook, Montant $prixUnitaire, Quantite $quantite): void
    {
        if ($this->etat !== EtatCommande::EnAttente) {
            throw AjoutDeLigneImpossible::carCommandeNonEnAttente($this->identifiantCommande);
        }

        $this->lignes->add(new LigneDeCommande($this, $identifiantEbook, $prixUnitaire, $quantite));
    }

    public function confirmer(): void
    {
        if ($this->etat !== EtatCommande::EnAttente) {
            throw ConfirmationImpossible::carCommandeNonEnAttente($this->identifiantCommande);
        }

        if ($this->lignes->isEmpty()) {
            throw ConfirmationImpossible::carAucuneLigne($this->identifiantCommande);
        }

        $this->etat = EtatCommande::Confirmee;

        $this->evenements[] = new CommandePassee(
            $this->identifiantCommande,
            $this->adresseEmail,
            $this->totalTtc(),
        );
    }

    public function payer(ReferenceDePaiement $reference, DateTimeImmutable $payeeLe): void
    {
        if ($this->etat === EtatCommande::Annulee) {
            throw PaiementImpossible::carAnnulee($this->identifiantCommande);
        }

        if ($this->etat === EtatCommande::Payee) {
            throw PaiementImpossible::carDejaPayee($this->identifiantCommande);
        }

        if ($this->etat === EtatCommande::EnAttente) {
            throw PaiementImpossible::carNonConfirmee($this->identifiantCommande);
        }

        if ($payeeLe > $this->derniereDateDePaiement()) {
            throw PaiementImpossible::carDelaiDepasse($this->identifiantCommande, $this->passeeLe, $payeeLe);
        }

        $this->etat = EtatCommande::Payee;
        $this->referenceDePaiement = $reference;
        $this->payeeLe = $payeeLe;

        $this->evenements[] = new CommandePayee(
            $this->identifiantCommande,
            $this->totalTtc(),
            $reference,
            $payeeLe,
        );
    }

    public function annuler(): void
    {
        if ($this->etat === EtatCommande::Payee) {
            throw AnnulationImpossible::carDejaPayee($this->identifiantCommande);
        }

        if ($this->etat === EtatCommande::Annulee) {
            throw AnnulationImpossible::carDejaAnnulee($this->identifiantCommande);
        }

        $this->etat = EtatCommande::Annulee;

        $this->evenements[] = new CommandeAnnulee($this->identifiantCommande);
    }

    public function totalHt(): Montant
    {
        return array_reduce(
            $this->lignes->toArray(),
            static fn (Montant $total, LigneDeCommande $ligne): Montant => $total->plus($ligne->sousTotal()),
            Montant::zero(Devise::EUR),
        );
    }

    public function totalTtc(): Montant
    {
        return $this->totalHt()->avecTva($this->tauxDeTva);
    }

    public function identifiantCommande(): IdentifiantCommande
    {
        return $this->identifiantCommande;
    }

    /**
     * Expose pour les tests d'adaptateur : c'est la seule facon de verifier qu'une date
     * survit a un aller-retour en base. On accepte ce getter comme on a accepte celui de
     * l'identifiant ; s'il en fallait beaucoup d'autres, il faudrait un read model.
     */
    public function payeeLe(): ?DateTimeImmutable
    {
        return $this->payeeLe;
    }

    /** @return object[] */
    public function relacherEvenements(): array
    {
        $evenements = $this->evenements;
        $this->evenements = [];

        return $evenements;
    }

    private function derniereDateDePaiement(): DateTimeImmutable
    {
        return $this->passeeLe->add(new DateInterval(self::DELAI_DE_PAIEMENT));
    }
}
