<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Doctrine;

use Bookshelf\Domain\Model\Commande\Commande;
use Bookshelf\Domain\Model\Commande\CommandeIntrouvable;
use Bookshelf\Domain\Model\Commande\CommandeRepository;
use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Adaptateur de production du port « pour enregistrer une commande ».
 *
 * Il tient en trente lignes parce que tout le travail difficile est ailleurs : dans les
 * types personnalises, qui savent traduire chaque value object, et dans l'agregat, qui
 * garantit qu'aucune donnee incoherente n'arrivera jusqu'ici.
 *
 * Notez ce qu'il ne fait PAS :
 *   - il ne connait pas les evenements : c'est le service applicatif qui les publie,
 *     apres l'enregistrement ;
 *   - il n'expose aucune methode de recherche « au cas ou » : un repository d'ecriture
 *     sert a enregistrer et a recharger, rien de plus. Les besoins de lecture passent
 *     par des read models.
 */
final readonly class CommandeRepositoryAvecDoctrine implements CommandeRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function prochainIdentifiant(): IdentifiantCommande
    {
        // Aucun aller-retour en base : l'identite ne depend pas de la persistance.
        return IdentifiantCommande::generer();
    }

    public function enregistrer(Commande $commande): void
    {
        $this->entityManager->persist($commande);
        $this->entityManager->flush();
    }

    public function parIdentifiant(IdentifiantCommande $identifiantCommande): Commande
    {
        $commande = $this->entityManager->find(Commande::class, $identifiantCommande);

        if (!$commande instanceof Commande) {
            throw CommandeIntrouvable::avecIdentifiant($identifiantCommande);
        }

        return $commande;
    }
}
