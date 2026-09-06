<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

/**
 * Fourni. Notez qu'il n'y a pas de setter d'etat sur `Commande` : chaque transition passe
 * par une methode nommee d'apres l'action metier.
 *
 *                      passer()
 *                         |
 *                         v
 *                  [ EnAttente ] ---------- annuler() ---------> [ Annulee ]
 *                         |                                           ^
 *                   confirmer()                                       |
 *                         |                                       annuler()
 *                         v                                           |
 *                  [ Confirmee ] ------------------------------------ +
 *                         |
 *                     payer()
 *                         v
 *                    [ Payee ]
 *
 * Ce qui est INTERDIT, et que les tests verifient :
 *   - ajouter une ligne ailleurs qu'en `EnAttente` ;
 *   - payer une commande `EnAttente` : rien ne dit encore ce qu'elle contient ;
 *   - payer ou confirmer une commande `Annulee` ;
 *   - annuler une commande `Payee` ;
 *   - annuler deux fois : `CommandeAnnulee` partirait deux fois, et un abonne
 *     agirait deux fois. L'idempotence serait un choix, pas un oubli.
 *
 * Les valeurs de backing restent en anglais : c'est la colonne `status` de la base,
 * donc un contrat de sortie. Voir `docs/langue-du-code.md`.
 */
enum EtatCommande: string
{
    case EnAttente = 'pending';
    case Confirmee = 'confirmed';
    case Payee = 'paid';
    case Annulee = 'cancelled';
}
