<?php

declare(strict_types=1);

namespace Bookshelf\Application\ListerEbooksDisponibles;

use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use Bookshelf\Domain\Model\Common\Montant;

/**
 * VIEW MODEL. Sa donnee est destinee a etre affichee.
 *
 * Regle : UNIQUEMENT des types primitifs, DEJA prets a afficher. Le prix est une chaine
 * formatee, pas un `Montant`. Si le formatage etait dans le gabarit Twig, il faudrait le
 * reecrire pour la version JSON, pour le PDF de facture, pour l'e-mail. Faites-le une
 * fois, ici.
 *
 * Proprietes publiques `readonly` plutot que des getters : l'immuabilite est garantie par
 * le langage, et `json_encode()` fonctionne directement, en une seule etape.
 */
final readonly class Ebook
{
    public function __construct(
        public string $identifiantEbook,
        public string $titre,
        public string $prix,
        public int $nombreDeVentes,
    ) {
    }

    public static function depuisDomaine(
        IdentifiantEbook $identifiantEbook,
        string $titre,
        Montant $prix,
        int $nombreDeVentes,
    ): self {
        return new self(
            $identifiantEbook->enChaine(),
            $titre,
            $prix->formate(),
            $nombreDeVentes,
        );
    }
}
