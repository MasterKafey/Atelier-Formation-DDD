<?php

declare(strict_types=1);

namespace Bookshelf\Application\PasserCommande;

use Bookshelf\Application\EventDispatcher;
use Bookshelf\Domain\Model\Commande\Commande;
use Bookshelf\Domain\Model\Commande\CommandeRepository;
use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Bookshelf\Domain\Model\Commande\Quantite;

/**
 * Service applicatif : un cas d'usage, reutilisable par n'importe quel client.
 *
 * Les regles appliquees ici :
 *   - entree en types primitifs (via le DTO) : n'importe quel client peut appeler ;
 *   - retourne au plus l'identifiant de la nouvelle entite, jamais l'entite elle-meme ;
 *   - un seul agregat enregistre par appel ;
 *   - les effets secondaires passent par des domain events ;
 *   - les evenements sont publies APRES l'enregistrement, jamais avant.
 */
final readonly class PasserCommandeService
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private EbookRepository $ebookRepository,
        private FournisseurDeTauxDeTva $fournisseurDeTauxDeTva,
        private EventDispatcher $eventDispatcher,
    ) {
    }

    public function __invoke(PasserCommande $intention): IdentifiantCommande
    {
        // Valider la relation : si l'e-book n'existe pas, getById leve une exception.
        $ebook = $this->ebookRepository->parIdentifiant($intention->identifiantEbook());

        $tauxDeTva = $this->fournisseurDeTauxDeTva->tauxDeTvaPourEbooksDansLePays(
            $intention->codePays(),
        );

        $identifiantCommande = $this->commandeRepository->prochainIdentifiant();

        $commande = Commande::passer(
            $identifiantCommande,
            $intention->adresseEmail(),
            $intention->codePays(),
            $tauxDeTva,
        );

        $commande->ajouterLigne($ebook->identifiantEbook(), $ebook->prix(), Quantite::depuisEntier($intention->quantite));
        $commande->confirmer();

        $this->commandeRepository->enregistrer($commande);

        // Enregistrer, PUIS publier. Un e-mail parti ne se rattrape pas.
        $this->eventDispatcher->dispatchAll($commande->relacherEvenements());

        return $identifiantCommande;
    }
}
