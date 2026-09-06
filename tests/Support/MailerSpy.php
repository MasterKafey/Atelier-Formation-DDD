<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Support;

use Bookshelf\Application\Mailer;
use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Bookshelf\Domain\Model\Common\AdresseEmail;

/**
 * CORRIGE. Un ESPION, pas un mock.
 *
 * Un mock PHPUnit vous lie au framework de test et verifie mal les arguments. Un espion
 * est une implementation de votre propre interface qui note ce qu'on lui a fait, et sur
 * laquelle le test fait ensuite ses assertions. Il survit a un changement de lanceur de
 * tests (vous passerez a Behat au palier C).
 */
final class MailerSpy implements Mailer
{
    /** @var IdentifiantCommande[] */
    private array $emailsEnvoyesPour = [];

    public function envoyerEmailDeConfirmation(IdentifiantCommande $identifiantCommande, AdresseEmail $to): void
    {
        $this->emailsEnvoyesPour[] = $identifiantCommande;
    }

    /** @return IdentifiantCommande[] */
    public function emailsEnvoyesPour(): array
    {
        return $this->emailsEnvoyesPour;
    }
}
