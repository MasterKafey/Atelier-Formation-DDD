<?php

declare(strict_types=1);

namespace Bookshelf\Application;

use Bookshelf\Domain\Model\Commande\CommandePassee;
use Bookshelf\Infrastructure\Mailer\SymfonyMailer;

/**
 * A CORRIGER.
 *
 * Cet abonne est du code coeur : il vit dans la couche Application. Pourtant il depend
 * d'une classe CONCRETE de la couche Infrastructure. C'est l'erreur la plus courante du
 * DDD de surface : les repertoires sont bien nommes, mais la dependance pointe vers
 * l'exterieur.
 *
 * Deux symptomes, et ils sont lies :
 *   - `composer deptrac` signale une violation Application -> Infrastructure ;
 *   - le test de cas d'usage ne peut pas injecter d'espion a la place du mailer.
 *
 * La correction tient en une ligne.
 */
final readonly class EnvoyerEmailDeConfirmation
{
    public function __construct(private SymfonyMailer $mailer)
    {
    }

    /** La classe dit ce qu'elle fait, la methode dit quand elle le fait. */
    public function quandCommandePassee(CommandePassee $evenement): void
    {
        $this->mailer->envoyerEmailDeConfirmation($evenement->identifiantCommande, $evenement->adresseEmail);
    }
}
