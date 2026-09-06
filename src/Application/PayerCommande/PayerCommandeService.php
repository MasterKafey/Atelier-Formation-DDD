<?php

declare(strict_types=1);

namespace Bookshelf\Application\PayerCommande;

use Bookshelf\Application\EventDispatcher;
use Bookshelf\Domain\Model\Commande\CommandeRepository;

/**
 * Le cas d'usage « payer une commande ». FOURNI : il illustre le patron habituel, que
 * vous avez deja vu avec `PasserCommandeService`.
 *
 *   charger l'agregat -> appeler UNE methode metier -> enregistrer -> publier
 *
 * Le service ne decide de rien : c'est `Commande::payer()` qui sait si le paiement est
 * possible. Si vous vous surprenez a ecrire un `if` sur l'etat de la commande ICI, c'est
 * que la regle est au mauvais endroit.
 *
 * Notez ce qui manque : ce service ne sait pas quelle heure il est, et n'en a pas besoin
 * aujourd'hui. C'est le palier D qui le lui apprendra
 * (`docs/atelier-3/palier-d-horloge.md`).
 */
final readonly class PayerCommandeService
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private EventDispatcher $eventDispatcher,
    ) {
    }

    public function __invoke(PayerCommande $intention): void
    {
        $commande = $this->commandeRepository->parIdentifiant($intention->identifiantCommande());

        $commande->payer($intention->referenceDePaiement());

        $this->commandeRepository->enregistrer($commande);

        // Enregistrer, PUIS publier. Un e-mail parti ne se rattrape pas.
        $this->eventDispatcher->dispatchAll($commande->relacherEvenements());
    }
}
