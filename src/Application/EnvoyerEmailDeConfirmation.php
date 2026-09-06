<?php

declare(strict_types=1);

namespace Bookshelf\Application;

use Bookshelf\Domain\Model\Commande\CommandePassee;

/**
 * Abonne de code coeur : il depend de l'INTERFACE `Mailer`, jamais d'une implementation.
 * C'est ce qui permet d'y injecter un espion dans le test de cas d'usage, et ce qui fait
 * passer deptrac.
 *
 * Un abonne de code coeur delegue a une abstraction ou a un service applicatif ; un
 * abonne d'infrastructure (journalisation, mise en file) fait son travail directement.
 */
final readonly class EnvoyerEmailDeConfirmation
{
    public function __construct(private Mailer $mailer)
    {
    }

    /** La classe dit ce qu'elle fait, la methode dit quand elle le fait. */
    public function quandCommandePassee(CommandePassee $evenement): void
    {
        $this->mailer->envoyerEmailDeConfirmation($evenement->identifiantCommande, $evenement->adresseEmail);
    }
}
