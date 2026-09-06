<?php

declare(strict_types=1);

namespace Bookshelf\Application;

use Bookshelf\Application\ListerEbooksDisponibles\ListerEbooksDisponibles;
use Bookshelf\Application\PasserCommande\PasserCommande;
use Bookshelf\Application\PasserCommande\PasserCommandeService;
use Bookshelf\Domain\Model\Commande\IdentifiantCommande;

/** Implementation standard : un simple proxy vers les services deja existants. */
final readonly class Application implements ApplicationInterface
{
    public function __construct(
        private PasserCommandeService $passerCommande,
        private ListerEbooksDisponibles $listerEbooksDisponibles,
    ) {
    }

    public function passerCommande(PasserCommande $intention): IdentifiantCommande
    {
        return ($this->passerCommande)($intention);
    }

    public function listerEbooksDisponibles(): array
    {
        return $this->listerEbooksDisponibles->listerTout();
    }
}
