<?php

declare(strict_types=1);

namespace Bookshelf\Tests\UseCase;

use Bookshelf\Application\PasserCommande\EbookIntrouvable;
use Bookshelf\Application\PasserCommande\PasserCommande;
use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use Bookshelf\Domain\Model\Common\Devise;
use Bookshelf\Domain\Model\Common\Montant;
use Bookshelf\Tests\Support\TestServiceContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * TESTS DE CAS D'USAGE : le niveau qui apporte le plus, et celui qui manque partout.
 *
 * Ils exercent tout l'hexagone interne (Application + Domaine), avec des adaptateurs
 * sortants remplaces par des doubles. Pas de base, pas de serveur SMTP, pas de noyau
 * Symfony demarre. Quelques millisecondes.
 */
final class PasserCommandeTest extends TestCase
{
    #[Test]
    public function le_client_recoit_un_email_de_confirmation(): void
    {
        $container = new TestServiceContainer();
        $identifiantEbook = $container->catalogue()->ajouter('Architecture hexagonale', 2500);

        $identifiantCommande = $container->application()->passerCommande(
            new PasserCommande($identifiantEbook->enChaine(), 1, 'paul@exemple.fr', 'FR'),
        );

        self::assertEquals([$identifiantCommande], $container->mailer()->emailsEnvoyesPour());
    }

    #[Test]
    public function la_commande_porte_le_taux_de_tva_du_pays_de_l_acheteur(): void
    {
        $container = new TestServiceContainer(pourcentageTva: 20);
        $identifiantEbook = $container->catalogue()->ajouter('Architecture hexagonale', 2500);

        $identifiantCommande = $container->application()->passerCommande(
            new PasserCommande($identifiantEbook->enChaine(), 2, 'paul@exemple.fr', 'FR'),
        );

        $commande = $container->commandeRepository()->parIdentifiant($identifiantCommande);

        self::assertEquals(Montant::depuisCentimes(5000, Devise::EUR), $commande->totalHt());
        self::assertEquals(Montant::depuisCentimes(6000, Devise::EUR), $commande->totalTtc());
    }

    #[Test]
    public function commander_un_ebook_inconnu_est_refuse(): void
    {
        $container = new TestServiceContainer();

        $this->expectException(EbookIntrouvable::class);

        $container->application()->passerCommande(
            new PasserCommande(IdentifiantEbook::generer()->enChaine(), 1, 'paul@exemple.fr', 'FR'),
        );
    }
}
