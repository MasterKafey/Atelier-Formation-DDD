<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Support;

use Bookshelf\Application\Application;
use Bookshelf\Application\ApplicationInterface;
use Bookshelf\Application\CommanderLivrePapier\CommanderLivrePapierService;
use Bookshelf\Application\ConfigurableEventDispatcher;
use Bookshelf\Application\EnvoyerEmailDeConfirmation;
use Bookshelf\Application\EventDispatcher;
use Bookshelf\Application\PasserCommande\PasserCommandeService;
use Bookshelf\Application\PayerCommande\PayerCommandeService;
use Bookshelf\Domain\Model\Commande\CommandePassee;
use Bookshelf\Domain\Model\Commande\CommandeRepository;
use Bookshelf\Infrastructure\InMemory\CatalogueEnMemoire;
use Bookshelf\Infrastructure\InMemory\CommandeRepositoryEnMemoire;
use Bookshelf\Infrastructure\InMemory\StockEnMemoire;
use Bookshelf\Infrastructure\InMemory\TauxDeTvaFixe;
use Symfony\Component\Clock\MockClock;

/**
 * Conteneur de services ECRIT A LA MAIN, distinct de celui du framework : il ne contient
 * que les services du coeur. Pas de routeur, pas de moteur de gabarits.
 *
 * Regles :
 *   - methodes de fabrication privees par defaut, publiques seulement pour ce que le test
 *     doit inspecter ou piloter ;
 *   - services SANS etat : une nouvelle instance a chaque appel ;
 *   - services AVEC etat (repositories en memoire, dispatcher, espions, HORLOGE) :
 *     memorises dans une propriete, sinon le test piloterait un objet different de celui
 *     que le code exerce.
 *
 * PALIER D : l'horloge est une `MockClock` figee a une date lisible. Les tests la font
 * avancer avec `modify()` ; ils n'attendent jamais.
 */
final class TestServiceContainer
{
    public const MAINTENANT = '2026-03-01 10:00:00';

    private ?EventDispatcher $eventDispatcher = null;
    private ?CommandeRepositoryEnMemoire $commandeRepository = null;
    private ?CatalogueEnMemoire $catalogue = null;
    private ?MailerSpy $mailer = null;
    private ?MockClock $horloge = null;
    private ?StockEnMemoire $stock = null;

    public function __construct(private readonly int $pourcentageTva = 20)
    {
    }

    public function application(): ApplicationInterface
    {
        return new Application(
            new PasserCommandeService(
                $this->commandeRepository(),
                $this->catalogue(),
                TauxDeTvaFixe::avecPourcentage($this->pourcentageTva),
                $this->eventDispatcher(),
                $this->horloge(),
            ),
            new PayerCommandeService(
                $this->commandeRepository(),
                $this->eventDispatcher(),
                $this->horloge(),
            ),
            new CommanderLivrePapierService(
                $this->stock(),
                $this->stock(),
            ),
            $this->catalogue(),
        );
    }

    /** Publique : les tests y posent le stock initial et l'inspectent ensuite. */
    public function stock(): StockEnMemoire
    {
        return $this->stock ??= new StockEnMemoire();
    }

    public function eventDispatcher(): EventDispatcher
    {
        if ($this->eventDispatcher === null) {
            $dispatcher = new ConfigurableEventDispatcher();
            $dispatcher->addSubscriber(
                CommandePassee::class,
                [new EnvoyerEmailDeConfirmation($this->mailer()), 'quandCommandePassee'],
            );

            $this->eventDispatcher = $dispatcher;
        }

        return $this->eventDispatcher;
    }

    /** Publique : c'est le test qui decide quelle heure il est, et quand elle avance. */
    public function horloge(): MockClock
    {
        return $this->horloge ??= new MockClock(self::MAINTENANT);
    }

    public function mailer(): MailerSpy
    {
        return $this->mailer ??= new MailerSpy();
    }

    public function catalogue(): CatalogueEnMemoire
    {
        return $this->catalogue ??= new CatalogueEnMemoire();
    }

    public function commandeRepository(): CommandeRepository
    {
        return $this->commandeRepository ??= new CommandeRepositoryEnMemoire();
    }
}
