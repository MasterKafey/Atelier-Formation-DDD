<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Http;

use Bookshelf\Application\Application;
use Bookshelf\Application\ApplicationInterface;
use Bookshelf\Application\CommanderLivrePapier\CommanderLivrePapierService;
use Bookshelf\Application\ConfigurableEventDispatcher;
use Bookshelf\Application\EnvoyerEmailDeConfirmation;
use Bookshelf\Application\PasserCommande\PasserCommandeService;
use Bookshelf\Application\PayerCommande\PayerCommandeService;
use Bookshelf\Domain\Model\Commande\Commande;
use Bookshelf\Domain\Model\Commande\CommandePassee;
use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use Bookshelf\Domain\Model\Commande\IdentifiantLivre;
use Bookshelf\Domain\Model\Commande\LigneDeCommande;
use Bookshelf\Infrastructure\Api\ApiErrorPresenter;
use Bookshelf\Infrastructure\Doctrine\CommandeRepositoryAvecDoctrine;
use Bookshelf\Infrastructure\Doctrine\EntityManagerFactory;
use Bookshelf\Infrastructure\InMemory\CatalogueEnMemoire;
use Bookshelf\Infrastructure\InMemory\StockEnMemoire;
use Bookshelf\Infrastructure\InMemory\TauxDeTvaFixe;
use Bookshelf\Infrastructure\Mailer\SymfonyMailer;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Clock\NativeClock;

/**
 * RACINE DE COMPOSITION du processus web : l'unique endroit ou le graphe d'objets est
 * assemble. En Symfony, ce travail est fait par le conteneur du framework ; l'ecrire a la
 * main ici rend visible ce qu'il fait, et surtout ce qu'il ne fait pas.
 *
 * Les commandes sont persistees dans un vrai fichier SQLite, pour que l'etat survive
 * d'une requete HTTP a l'autre. Le catalogue et le stock sont charges depuis un fichier
 * de jeu d'essai relu a chaque requete : c'est suffisant pour un test de bout en bout, et
 * cela evite d'inventer une administration du catalogue qui n'existe pas encore.
 */
final class HttpServiceContainer
{
    public function __construct(
        private readonly string $databasePath,
        private readonly string $fixturesPath,
    ) {
    }

    public function router(): Router
    {
        return new Router($this->application(), new ApiErrorPresenter());
    }

    public function application(): ApplicationInterface
    {
        $entityManager = EntityManagerFactory::create([
            'driver' => 'pdo_sqlite',
            'path' => $this->databasePath,
        ]);

        $this->createSchemaIfMissing($entityManager);

        $commandeRepository = new CommandeRepositoryAvecDoctrine($entityManager);
        $dispatcher = new ConfigurableEventDispatcher();
        $horloge = new NativeClock();

        $dispatcher->addSubscriber(
            CommandePassee::class,
            [new EnvoyerEmailDeConfirmation(new SymfonyMailer()), 'quandCommandePassee'],
        );

        $catalogue = $this->catalogue();
        $stock = $this->stock();

        return new Application(
            new PasserCommandeService(
                $commandeRepository,
                $catalogue,
                TauxDeTvaFixe::avecPourcentage(20),
                $dispatcher,
                $horloge,
            ),
            new PayerCommandeService($commandeRepository, $dispatcher, $horloge),
            new CommanderLivrePapierService($stock, $stock),
            $catalogue,
        );
    }

    private function catalogue(): CatalogueEnMemoire
    {
        $catalogue = new CatalogueEnMemoire();

        foreach ($this->fixtures()['ebooks'] ?? [] as $ebook) {
            $catalogue->ajouterAvecIdentifiant(
                IdentifiantEbook::depuisChaine($ebook['id']),
                $ebook['title'],
                $ebook['price'],
                $ebook['sold'] ?? 0,
                $ebook['hidden'] ?? false,
            );
        }

        return $catalogue;
    }

    private function stock(): StockEnMemoire
    {
        $stock = new StockEnMemoire();

        foreach ($this->fixtures()['books'] ?? [] as $livre) {
            $stock->ajouterAvecIdentifiant(IdentifiantLivre::depuisChaine($livre['id']), $livre['copies']);
        }

        return $stock;
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function fixtures(): array
    {
        if (!is_file($this->fixturesPath)) {
            return [];
        }

        return json_decode((string) file_get_contents($this->fixturesPath), true) ?: [];
    }

    private function createSchemaIfMissing(\Doctrine\ORM\EntityManagerInterface $entityManager): void
    {
        $tables = $entityManager->getConnection()
            ->createSchemaManager()
            ->listTableNames();

        if (in_array('orders', $tables, true)) {
            return;
        }

        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(Commande::class),
            $entityManager->getClassMetadata(LigneDeCommande::class),
        ]);
    }
}
