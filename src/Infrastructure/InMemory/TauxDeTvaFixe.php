<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\InMemory;

use Bookshelf\Application\PasserCommande\FournisseurDeTauxDeTva;
use Bookshelf\Domain\Model\Common\CodePays;
use Bookshelf\Domain\Model\Common\TauxDeTva;

/**
 * A ECRIRE. Second adaptateur du meme port, a taux fixe, pour les tests.
 *
 * Avoir deux adaptateurs n'est pas un luxe : c'est la preuve que l'abstraction en est
 * une. Si vous ne pouvez pas en ecrire un second sans que les noms deviennent absurdes,
 * c'est que le port etait un deguisement.
 */
final readonly class TauxDeTvaFixe implements FournisseurDeTauxDeTva
{
    public function __construct(private TauxDeTva $rate)
    {
    }

    public static function avecPourcentage(int $pourcentage): self
    {
        throw new \RuntimeException('TODO atelier 3');
    }

    public function tauxDeTvaPourEbooksDansLePays(CodePays $pays): TauxDeTva
    {
        throw new \RuntimeException('TODO atelier 3');
    }
}
