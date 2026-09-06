<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Bookshelf\Domain\Model\Common\AdresseEmail;
use Bookshelf\Domain\Model\Common\CodePays;
use Bookshelf\Domain\Model\Common\Devise;
use Bookshelf\Domain\Model\Common\Montant;
use Bookshelf\Domain\Model\Common\TauxDeTva;

/**
 * A ECRIRE. La racine de l'agregat Commande.
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

    public static function passer(
        IdentifiantCommande $identifiantCommande,
        AdresseEmail $adresseEmail,
        CodePays $pays,
        TauxDeTva $tauxDeTva,
    ): self {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function ajouterLigne(IdentifiantEbook $identifiantEbook, Montant $prixUnitaire, Quantite $quantite): void
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function confirmer(): void
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function payer(ReferenceDePaiement $reference): void
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function annuler(): void
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function totalHt(): Montant
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function totalTtc(): Montant
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function identifiantCommande(): IdentifiantCommande
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    /** @return object[] */
    public function relacherEvenements(): array
    {
        throw new \RuntimeException('TODO atelier 2');
    }
}
