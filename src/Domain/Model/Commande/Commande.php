<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Bookshelf\Domain\Model\Common\AdresseEmail;
use Bookshelf\Domain\Model\Common\CodePays;
use Bookshelf\Domain\Model\Common\Devise;
use Bookshelf\Domain\Model\Common\Montant;
use Bookshelf\Domain\Model\Common\TauxDeTva;

/**
 * CORRIGE. La racine de l'agregat Commande.
 *
 * Les cinq invariants a proteger :
 *   1. une commande confirmee a au moins une ligne ;
 *   2. le total est toujours la somme des lignes, TVA appliquee ;
 *   3. une commande payee ne peut pas etre annulee ;
 *   4. une commande annulee ne peut pas etre payee ;
 *   5. on n'ajoute pas de ligne a une commande qui n'est plus en attente ;
 *   6. on ne paie que ce qui a ete confirme.
 *
 * L'etat `Confirmee` est ce qui rend l'invariant 1 vrai EN PERMANENCE : sans lui, la
 * confirmation n'etait qu'un instant, et on pouvait ajouter une ligne juste apres,
 * rendant faux le total deja annonce par `CommandePassee`.
 *
 * Contraintes :
 *   - aucun setter, aucune methode dont le nom commence par `set` ;
 *   - aucun getter autre que `identifiantCommande()`, `relacherEvenements()` et les deux totaux ;
 *   - `payer()`, `annuler()`, `confirmer()` et `ajouterLigne()` retournent `void` : ce sont des
 *     commandes, pas des requetes.
 *
 * Les evenements sont ENREGISTRES ici et RELACHES par le service applicatif apres
 * l'enregistrement en base (jour 2, section 7).
 */
final class Commande
{
    private EtatCommande $etat = EtatCommande::EnAttente;

    private ?ReferenceDePaiement $referenceDePaiement = null;

    /** @var LigneDeCommande[] */
    private array $lignes = [];

    /** @var object[] */
    private array $evenements = [];

    private function __construct(
        private readonly IdentifiantCommande $identifiantCommande,
        private readonly AdresseEmail $adresseEmail,
        private readonly CodePays $pays,
        private readonly TauxDeTva $tauxDeTva,
    ) {
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

        $this->lignes[] = new LigneDeCommande($identifiantEbook, $prixUnitaire, $quantite);
    }

    public function confirmer(): void
    {
        if ($this->etat !== EtatCommande::EnAttente) {
            throw ConfirmationImpossible::carCommandeNonEnAttente($this->identifiantCommande);
        }

        if ($this->lignes === []) {
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
            $this->lignes,
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
