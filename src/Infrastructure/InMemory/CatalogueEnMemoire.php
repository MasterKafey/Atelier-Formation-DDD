<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\InMemory;

use Bookshelf\Application\ListerEbooksDisponibles\Ebook as EbookViewModel;
use Bookshelf\Application\ListerEbooksDisponibles\ListerEbooksDisponibles;
use Bookshelf\Application\PasserCommande\Ebook as EbookReadModel;
use Bookshelf\Application\PasserCommande\EbookIntrouvable;
use Bookshelf\Application\PasserCommande\EbookRepository;
use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use Bookshelf\Domain\Model\Common\Devise;
use Bookshelf\Domain\Model\Common\Montant;

/**
 * Une seule classe pour DEUX interfaces proches : le repository de read model interne et
 * le port de lecture du catalogue. C'est l'un des leviers pour limiter le nombre de
 * classes quand on decouple : la connaissance du stockage reste a un seul endroit.
 *
 * En production, cette classe serait `CatalogUsingSql` et ferait deux requetes.
 */
final class CatalogueEnMemoire implements EbookRepository, ListerEbooksDisponibles
{
    /** @var array<string, array{title: string, prix: int, sold: int, retire: bool}> */
    private array $ebooks = [];

    public function ajouter(string $titre, int $prixEnCentimes, int $nombreDeVentes = 0, bool $retire = false): IdentifiantEbook
    {
        $id = IdentifiantEbook::generer();
        $this->ajouterAvecIdentifiant($id, $titre, $prixEnCentimes, $nombreDeVentes, $retire);

        return $id;
    }

    /**
     * Meme chose, avec un identifiant impose. Utile des que le catalogue est alimente
     * depuis l'exterieur : jeu d'essai, import, ou fixtures d'un test de bout en bout.
     */
    public function ajouterAvecIdentifiant(
        IdentifiantEbook $id,
        string $titre,
        int $prixEnCentimes,
        int $nombreDeVentes = 0,
        bool $retire = false,
    ): void {
        $this->ebooks[$id->enChaine()] = [
            'title' => $titre,
            'price' => $prixEnCentimes,
            'sold' => $nombreDeVentes,
            'hidden' => $retire,
        ];
    }

    public function parIdentifiant(IdentifiantEbook $identifiantEbook): EbookReadModel
    {
        $row = $this->ebooks[$identifiantEbook->enChaine()] ?? throw EbookIntrouvable::avecIdentifiant($identifiantEbook);

        return new EbookReadModel($identifiantEbook, Montant::depuisCentimes($row['price'], Devise::EUR));
    }

    public function listerTout(): array
    {
        $disponibles = [];

        foreach ($this->ebooks as $id => $row) {
            if ($row['hidden']) {
                continue;
            }

            $disponibles[] = EbookViewModel::depuisDomaine(
                IdentifiantEbook::depuisChaine($id),
                $row['title'],
                Montant::depuisCentimes($row['price'], Devise::EUR),
                $row['sold'],
            );
        }

        return $disponibles;
    }
}
