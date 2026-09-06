<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\VatApi;

use Bookshelf\Application\PasserCommande\FournisseurDeTauxDeTva;
use Bookshelf\Domain\Model\Common\CodePays;
use Bookshelf\Domain\Model\Common\TauxDeTva;

/**
 * A ECRIRE. La couche anticorruption.
 *
 * C'est le seul endroit de l'application ou l'on a le droit d'ecrire `RateType::Tbe`,
 * `'ebooks'` ou `'electronic'`. Au-dela, on ne parle plus que de `CodePays` et de
 * `TauxDeTva`.
 *
 * Un e-book est un service electronique : le type de taux est TBE, le filtre est
 * `'ebooks'`, et si le filtre ne trouve rien on retombe sur le taux generique
 * `'electronic'`.
 */
final readonly class FournisseurDeTauxDeTvaAvecVatApi implements FournisseurDeTauxDeTva
{
    public function __construct(private VatApi $vatApi)
    {
    }

    public function tauxDeTvaPourEbooksDansLePays(CodePays $pays): TauxDeTva
    {
        throw new \RuntimeException('TODO atelier 3');
    }
}
