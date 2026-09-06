<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Support;

use Bookshelf\Application\Application;
use Bookshelf\Application\ApplicationInterface;
use Bookshelf\Application\ConfigurableEventDispatcher;
use Bookshelf\Application\EnvoyerEmailDeConfirmation;
use Bookshelf\Application\EventDispatcher;
use Bookshelf\Application\PasserCommande\PasserCommandeService;
use Bookshelf\Application\PayerCommande\PayerCommandeService;
use Bookshelf\Domain\Model\Commande\CommandePassee;
use Bookshelf\Domain\Model\Commande\CommandeRepository;
use Bookshelf\Infrastructure\InMemory\CatalogueEnMemoire;
use Bookshelf\Infrastructure\InMemory\CommandeRepositoryEnMemoire;
use Bookshelf\Infrastructure\InMemory\TauxDeTvaFixe;

/**
 * A ECRIRE. Conteneur de services ECRIT A LA MAIN, distinct de celui du framework : il ne
 * contient que les services du coeur. Pas de routeur, pas de moteur de gabarits.
 *
 * Regles :
 *   - methodes de fabrication privees par defaut, publiques seulement pour ce que le test
 *     doit inspecter ;
 *   - services SANS etat : une nouvelle instance a chaque appel ;
 *   - services AVEC etat (repositories en memoire, dispatcher, espions) : memorises dans
 *     une propriete, sinon le test observerait un objet different de celui qu'il exerce.
 */
final class TestServiceContainer
{
    private ?EventDispatcher $eventDispatcher = null;
    private ?CommandeRepositoryEnMemoire $commandeRepository = null;
    private ?CatalogueEnMemoire $catalogue = null;
    private ?MailerSpy $mailer = null;

    public function __construct(private readonly int $pourcentageTva = 20)
    {
    }

    public function application(): ApplicationInterface
    {
        throw new \RuntimeException('TODO atelier 3');
    }

    public function eventDispatcher(): EventDispatcher
    {
        throw new \RuntimeException('TODO atelier 3');
    }

    public function mailer(): MailerSpy
    {
        throw new \RuntimeException('TODO atelier 3');
    }

    public function catalogue(): CatalogueEnMemoire
    {
        throw new \RuntimeException('TODO atelier 3');
    }

    public function commandeRepository(): CommandeRepository
    {
        throw new \RuntimeException('TODO atelier 3');
    }
}
