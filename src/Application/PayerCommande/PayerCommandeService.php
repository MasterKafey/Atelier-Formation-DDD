<?php

declare(strict_types=1);

namespace Bookshelf\Application\PayerCommande;

use Bookshelf\Application\EventDispatcher;
use Bookshelf\Domain\Model\Commande\CommandeRepository;
use Psr\Clock\ClockInterface;

/**
 * Le cas d'usage « payer une commande ».
 *
 * Le patron habituel : charger l'agregat, appeler UNE methode metier, enregistrer,
 * publier. Le service ne decide de rien ; c'est `Commande::payer()` qui sait si le paiement
 * est encore possible.
 */
final readonly class PayerCommandeService
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private EventDispatcher $eventDispatcher,
        private ClockInterface $horloge,
    ) {
    }

    public function __invoke(PayerCommande $intention): void
    {
        $commande = $this->commandeRepository->parIdentifiant($intention->identifiantCommande());

        $commande->payer($intention->referenceDePaiement(), $this->horloge->now());

        $this->commandeRepository->enregistrer($commande);

        $this->eventDispatcher->dispatchAll($commande->relacherEvenements());
    }
}
