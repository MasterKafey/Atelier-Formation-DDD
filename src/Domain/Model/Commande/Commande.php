<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Bookshelf\Domain\Model\Common\AdresseEmail;
use Bookshelf\Domain\Model\Common\CodePays;
use Bookshelf\Domain\Model\Common\Devise;
use Bookshelf\Domain\Model\Common\Montant;
use Bookshelf\Domain\Model\Common\TauxDeTva;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * La racine de l'agregat Commande, desormais persistable.
 *
 * CE QUI A CHANGE PAR RAPPORT A L'ATELIER 2, et pourquoi :
 *
 *   1. Des attributs `#[ORM\...]`. L'entite reste du code coeur au sens des deux regles :
 *      on peut l'instancier et appeler ses methodes sans base de donnees, sans contexte.
 *      Les attributs contiennent des details techniques (noms de colonnes, types) ; c'est
 *      un compromis assume, et il vaut mieux que d'exposer les proprietes privees a
 *      l'exterieur pour qu'un mapper les lise.
 *
 *   2. `$lignes` est une `Collection` Doctrine et non plus un `array`. C'est la
 *      concession la plus visible : utiliser les associations de l'ORM fait entrer
 *      `Doctrine\Common\Collections` dans le domaine. On l'accepte pour les entites
 *      FILLES de l'agregat (regle 2 : associations un-a-plusieurs uniquement). On ne
 *      l'accepterait pas pour referencer un autre agregat, qui reste designe par son
 *      identifiant.
 *
 *   3. `$lignes` est bidirectionnelle : `LigneDeCommande` connait sa commande. C'est une
 *      exigence de l'ORM pour un `mappedBy`, pas un choix de modelisation.
 *
 * CE QUI N'A PAS CHANGE, et c'est l'essentiel :
 *   - aucun setter, aucune methode dont le nom commence par `set` ;
 *   - les memes cinq invariants, protteges par les memes gardes ;
 *   - les evenements ne sont PAS mappes : ils sont transitoires, ils ne survivent pas a
 *     un rechargement, et c'est voulu.
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Commande
{
    #[ORM\Column(type: 'string', name: 'status', length: 20, enumType: EtatCommande::class)]
    private EtatCommande $etat = EtatCommande::EnAttente;

    #[ORM\Column(type: 'payment_reference', name: 'payment_reference', nullable: true)]
    private ?ReferenceDePaiement $referenceDePaiement = null;

    /** @var Collection<int, LigneDeCommande> */
    #[ORM\OneToMany(
        targetEntity: LigneDeCommande::class,
        mappedBy: 'commande',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $lignes;

    /**
     * Non mappe : Doctrine ignore les proprietes sans attribut. Les evenements sont
     * transitoires, relaches par le service applicatif juste apres l'enregistrement.
     *
     * @var object[]
     */
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
    ) {
        $this->lignes = new ArrayCollection();
    }

    public static function passer(
        IdentifiantCommande $identifiantCommande,
        AdresseEmail $adresseEmail,
        CodePays $pays,
        TauxDeTva $tauxDeTva,
    ): self {
        return new self($identifiantCommande, $adresseEmail, $pays, $tauxDeTva);
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

    public function payer(ReferenceDePaiement $reference): void
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

        $this->etat = EtatCommande::Payee;
        $this->referenceDePaiement = $reference;

        $this->evenements[] = new CommandePayee($this->identifiantCommande, $this->totalTtc(), $reference);
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

    /** @return object[] */
    public function relacherEvenements(): array
    {
        $evenements = $this->evenements;
        $this->evenements = [];

        return $evenements;
    }
}
