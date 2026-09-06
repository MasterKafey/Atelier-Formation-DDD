<?php

declare(strict_types=1);

namespace Bookshelf\Application\PasserCommande;

use Bookshelf\Application\EventDispatcher;
use Bookshelf\Domain\Model\Commande\Commande;
use Bookshelf\Domain\Model\Commande\CommandeRepository;
use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Bookshelf\Domain\Model\Commande\Quantite;
use Psr\Clock\ClockInterface;

/**
 * Service applicatif : un cas d'usage, reutilisable par n'importe quel client.
 *
 * PALIER D : c'est ICI que vit l'horloge, pas dans l'entite. La couche application a le
 * droit de dependre d'une abstraction d'infrastructure ; le domaine, non.
 */
final readonly class PasserCommandeService
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private EbookRepository $ebookRepository,
        private FournisseurDeTauxDeTva $fournisseurDeTauxDeTva,
        private EventDispatcher $eventDispatcher,
        private ClockInterface $horloge,
    ) {
    }

    public function __invoke(PasserCommande $intention): IdentifiantCommande
    {
        $ebook = $this->ebookRepository->parIdentifiant($intention->identifiantEbook());

        $tauxDeTva = $this->fournisseurDeTauxDeTva->tauxDeTvaPourEbooksDansLePays(
            $intention->codePays(),
        );

        $identifiantCommande = $this->commandeRepository->prochainIdentifiant();

        /*
         * UNE SEULE lecture de l'horloge par cas d'usage. Appeler now() plusieurs fois
         * donnerait des instants differents : invisible sur une commande, ruineux sur un
         * traitement par lots, et impossible a reproduire quand le bug remonte.
         */
        $maintenant = $this->horloge->now();

        $commande = Commande::passer(
            $identifiantCommande,
            $intention->adresseEmail(),
            $intention->codePays(),
            $tauxDeTva,
            $maintenant,
        );

        $commande->ajouterLigne($ebook->identifiantEbook(), $ebook->prix(), Quantite::depuisEntier($intention->quantite));
        $commande->confirmer();

        $this->commandeRepository->enregistrer($commande);

        $this->eventDispatcher->dispatchAll($commande->relacherEvenements());

        return $identifiantCommande;
    }
}
