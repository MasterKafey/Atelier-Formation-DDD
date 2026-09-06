<?php

declare(strict_types=1);

namespace Bookshelf\Application;

use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Bookshelf\Domain\Model\Common\AdresseEmail;

/**
 * Port sortant : « pour envoyer un e-mail de confirmation ».
 *
 * L'interface est du code coeur, ses implementations sont de l'infrastructure. C'est
 * elle qui rend le test de cas d'usage possible : on y injecte un espion.
 */
interface Mailer
{
    public function envoyerEmailDeConfirmation(IdentifiantCommande $identifiantCommande, AdresseEmail $to): void;
}
