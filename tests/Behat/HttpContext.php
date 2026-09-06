<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\AfterSuite;
use Behat\Hook\BeforeScenario;
use Behat\Hook\BeforeSuite;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use Bookshelf\Domain\Model\Commande\IdentifiantLivre;
use PHPUnit\Framework\Assert;

/**
 * SECOND ADAPTATEUR du meme port entrant : de vraies requetes HTTP contre un vrai
 * serveur, avec une vraie base SQLite.
 *
 * Les scenarios sont IDENTIQUES a ceux du contexte hexagone. Seules changent les
 * definitions d'etapes : la ou l'autre appelait `ApplicationInterface`, celui-ci envoie
 * un POST et lit un code de statut.
 *
 * C'est la demonstration de l'architecture hexagonale au niveau des tests : le meme cas
 * d'usage, deux adaptateurs, aucune ligne de code metier dupliquee.
 *
 * Contrepartie, et elle est reelle : cette suite est des dizaines de fois plus lente, et
 * elle peut echouer parce qu'un port est occupe. On en ecrit peu.
 */
final class HttpContext implements Context
{
    /** Surchargeable par BOOKSHELF_PORT : deux personnes peuvent lancer la suite en meme temps. */
    private static function port(): int
    {
        return (int) (getenv('BOOKSHELF_PORT') ?: 8321);
    }

    private ?IdentifiantEbook $dernierEbook = null;
    private ?IdentifiantLivre $dernierLivre = null;
    private int $statut = 0;
    /** @var array<string, mixed>|list<mixed> */
    private array $corps = [];

    #[BeforeSuite]
    public static function demarrerLeServeur(BeforeSuiteScope $scope): void
    {
        HttpServer::demarrer(self::port());
    }

    #[AfterSuite]
    public static function arreterLeServeur(AfterSuiteScope $scope): void
    {
        HttpServer::arreter();
    }

    #[BeforeScenario]
    public function repartirDUneBaseVierge(): void
    {
        HttpServer::reinitialiser();
        $this->dernierEbook = null;
        $this->dernierLivre = null;
        $this->statut = 0;
        $this->corps = [];
    }

    #[Given('un e-book :titre à :centimes centimes en vente')]
    public function unEbookEnVente(string $titre, int $centimes): void
    {
        $this->dernierEbook = $this->ajouterEbook($titre, $centimes, false);
    }

    #[Given('un e-book :titre à :centimes centimes retiré de la vente')]
    public function unEbookRetireDeLaVente(string $titre, int $centimes): void
    {
        $this->ajouterEbook($titre, $centimes, true);
    }

    #[Given('un livre papier avec :copies exemplaires en stock')]
    public function unLivrePapierEnStock(int $exemplaires): void
    {
        $fixtures = HttpServer::lireFixtures();
        $this->dernierLivre = IdentifiantLivre::generer();

        $fixtures['books'][] = ['id' => $this->dernierLivre->enChaine(), 'copies' => $exemplaires];
        HttpServer::ecrireFixtures($fixtures);
    }

    #[When('je consulte le catalogue')]
    public function jeConsulteLeCatalogue(): void
    {
        $this->appeler('GET', '/ebooks');
    }

    #[When('je commande :quantite exemplaires de cet e-book')]
    public function jeCommandeCetEbook(int $quantite): void
    {
        Assert::assertNotNull($this->dernierEbook, 'Aucun e-book pose par les etapes precedentes.');

        $this->appeler('POST', '/orders', [
            'ebook_id' => $this->dernierEbook->enChaine(),
            'quantity' => $quantite,
            'email_address' => 'paul@exemple.fr',
            'country_code' => 'FR',
        ]);
    }

    #[When('je commande :quantite exemplaires de ce livre papier')]
    public function jeCommandeCeLivrePapier(int $quantite): void
    {
        Assert::assertNotNull($this->dernierLivre, 'Aucun livre papier pose par les etapes precedentes.');

        $this->appeler('POST', '/physical-books/orders', [
            'book_id' => $this->dernierLivre->enChaine(),
            'quantity' => $quantite,
        ]);
    }

    #[Then('le catalogue contient :titre au prix affiché :prix')]
    public function leCatalogueContient(string $titre, string $prix): void
    {
        Assert::assertSame(200, $this->statut);

        foreach ($this->corps as $ebook) {
            if (($ebook['titre'] ?? null) === $titre) {
                Assert::assertSame($prix, $ebook['prix']);

                return;
            }
        }

        Assert::fail(sprintf('« %s » est absent de la reponse.', $titre));
    }

    #[Then('le catalogue compte :nombre titre(s)')]
    public function leCatalogueCompte(int $nombre): void
    {
        Assert::assertSame(200, $this->statut);
        Assert::assertCount($nombre, $this->corps);
    }

    #[Then('la commande est acceptée')]
    public function laCommandeEstAcceptee(): void
    {
        Assert::assertSame(201, $this->statut, 'Le serveur n\'a pas accepte la commande.');
        Assert::assertArrayHasKey('order_id', $this->corps);
    }

    #[Then('l\'opération est refusée avec le code :code')]
    public function lOperationEstRefusee(string $code): void
    {
        Assert::assertSame(422, $this->statut, 'Une erreur metier doit rendre un 422, pas un 500.');
        Assert::assertSame($code, $this->corps['error']['code'] ?? null);
    }

    private function ajouterEbook(string $titre, int $centimes, bool $retire): IdentifiantEbook
    {
        $fixtures = HttpServer::lireFixtures();
        $id = IdentifiantEbook::generer();

        $fixtures['ebooks'][] = [
            'id' => $id->enChaine(),
            'title' => $titre,
            'price' => $centimes,
            'sold' => 0,
            'hidden' => $retire,
        ];
        HttpServer::ecrireFixtures($fixtures);

        return $id;
    }

    /** @param array<string, mixed> $corps */
    private function appeler(string $methode, string $chemin, array $corps = []): void
    {
        $contexte = stream_context_create([
            'http' => [
                'method' => $methode,
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($corps, JSON_THROW_ON_ERROR),
                'ignore_errors' => true,
                'timeout' => 5,
            ],
        ]);

        $reponse = @file_get_contents('http://127.0.0.1:' . self::port() . $chemin, false, $contexte);

        $this->statut = (int) (explode(' ', $http_response_header[0] ?? 'HTTP/1.1 0')[1] ?? 0);
        $decode = json_decode((string) $reponse, true);
        $this->corps = is_array($decode) ? $decode : [];
    }
}
