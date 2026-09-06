<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Bookshelf\Application\CommanderLivrePapier\CommanderLivrePapier;
use Bookshelf\Application\PasserCommande\PasserCommande;
use Bookshelf\Application\UserErrorMessage;
use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use Bookshelf\Domain\Model\Commande\IdentifiantLivre;
use Bookshelf\Tests\Support\TestServiceContainer;
use PHPUnit\Framework\Assert;

/**
 * PREMIER ADAPTATEUR du port entrant : le code de test lui-meme.
 *
 * Il appelle `ApplicationInterface` directement, avec des doubles en memoire. Pas de
 * serveur, pas de base, pas de reseau : la suite complete s'execute en quelques
 * millisecondes et ne peut echouer que pour une raison metier.
 *
 * C'est le contexte du quotidien, celui qu'on lance a chaque sauvegarde.
 */
final class HexagoneContext implements Context
{
    private TestServiceContainer $container;
    private ?IdentifiantEbook $dernierEbook = null;
    private ?IdentifiantLivre $dernierLivre = null;
    /** @var list<object> */
    private array $catalogue = [];
    private ?UserErrorMessage $erreur = null;
    private bool $commandeAcceptee = false;

    public function __construct()
    {
        $this->container = new TestServiceContainer();
    }

    #[Given('un e-book :titre à :centimes centimes en vente')]
    public function unEbookEnVente(string $titre, int $centimes): void
    {
        $this->dernierEbook = $this->container->catalogue()->ajouter($titre, $centimes);
    }

    #[Given('un e-book :titre à :centimes centimes retiré de la vente')]
    public function unEbookRetireDeLaVente(string $titre, int $centimes): void
    {
        $this->container->catalogue()->ajouter($titre, $centimes, retire: true);
    }

    #[Given('un livre papier avec :copies exemplaires en stock')]
    public function unLivrePapierEnStock(int $exemplaires): void
    {
        $this->dernierLivre = $this->container->stock()->ajouter($exemplaires);
    }

    #[When('je consulte le catalogue')]
    public function jeConsulteLeCatalogue(): void
    {
        $this->catalogue = $this->container->application()->listerEbooksDisponibles();
    }

    #[When('je commande :quantite exemplaires de cet e-book')]
    public function jeCommandeCetEbook(int $quantite): void
    {
        Assert::assertNotNull($this->dernierEbook, 'Aucun e-book pose par les etapes precedentes.');

        $this->container->application()->passerCommande(
            new PasserCommande($this->dernierEbook->enChaine(), $quantite, 'paul@exemple.fr', 'FR'),
        );

        $this->commandeAcceptee = true;
    }

    #[When('je commande :quantite exemplaires de ce livre papier')]
    public function jeCommandeCeLivrePapier(int $quantite): void
    {
        Assert::assertNotNull($this->dernierLivre, 'Aucun livre papier pose par les etapes precedentes.');

        try {
            $this->container->application()->commanderLivrePapier(
                new CommanderLivrePapier($this->dernierLivre->enChaine(), $quantite),
            );
            $this->commandeAcceptee = true;
        } catch (UserErrorMessage $erreur) {
            $this->erreur = $erreur;
        }
    }

    #[Then('le catalogue contient :titre au prix affiché :prix')]
    public function leCatalogueContient(string $titre, string $prix): void
    {
        foreach ($this->catalogue as $ebook) {
            if ($ebook->titre === $titre) {
                Assert::assertSame($prix, $ebook->prix);

                return;
            }
        }

        Assert::fail(sprintf('« %s » est absent du catalogue.', $titre));
    }

    #[Then('le catalogue compte :nombre titre(s)')]
    public function leCatalogueCompte(int $nombre): void
    {
        Assert::assertCount($nombre, $this->catalogue);
    }

    #[Then('la commande est acceptée')]
    public function laCommandeEstAcceptee(): void
    {
        Assert::assertTrue($this->commandeAcceptee, 'La commande a ete refusee.');
    }

    #[Then('l\'opération est refusée avec le code :code')]
    public function lOperationEstRefusee(string $code): void
    {
        Assert::assertNotNull($this->erreur, 'L\'operation a ete acceptee alors qu\'elle devait echouer.');
        Assert::assertSame($code, $this->erreur->translationKey());
    }
}
