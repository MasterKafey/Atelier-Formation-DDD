<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Mailer;

use Bookshelf\Application\Mailer;
use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Bookshelf\Domain\Model\Common\AdresseEmail;

/**
 * Adaptateur de production du port « pour envoyer un e-mail de confirmation ».
 *
 * Le corps est volontairement laisse en commentaire : envoyer un vrai e-mail depuis un
 * depot de formation serait une mauvaise idee. Ce qui compte ici, c'est qu'aucun code
 * coeur ne dependra jamais de cette classe, mais de l'interface `Mailer`.
 */
final class SymfonyMailer implements Mailer
{
    public function envoyerEmailDeConfirmation(IdentifiantCommande $identifiantCommande, AdresseEmail $to): void
    {
        // $message = (new Email())
        //     ->from($this->systemEmailAddress)
        //     ->to($to->enChaine())
        //     ->html($this->twig->render('email/order_confirmation.html.twig', [...]));
        //
        // $this->mailer->send($message);
    }
}
