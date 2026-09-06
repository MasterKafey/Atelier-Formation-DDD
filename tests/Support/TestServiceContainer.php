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
 * CORRIGE. Conteneur de services ECRIT A LA MAIN, distinct de celui du framework : il ne
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
        return new Application(
            new PasserCommandeService(
                $this->commandeRepository(),
                $this->catalogue(),
                TauxDeTvaFixe::avecPourcentage($this->pourcentageTva),
                $this->eventDispatcher(),
            ),
            new PayerCommandeService(
                $this->commandeRepository(),
                $this->eventDispatcher(),
            ),
            $this->catalogue(),
        );
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
