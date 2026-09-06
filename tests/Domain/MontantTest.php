<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Domain;

use Bookshelf\Domain\Model\Common\Devise;
use Bookshelf\Domain\Model\Common\Montant;
use Bookshelf\Domain\Model\Common\MontantsNonComparables;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 4 des 14 tests de l'atelier 2. Aucune infrastructure : ni base, ni conteneur, ni reseau.
 */
final class MontantTest extends TestCase
{
    #[Test]
    public function multiplier_un_montant(): void
    {
        $prix = Montant::depuisCentimes(2500, Devise::EUR);

        self::assertEquals(Montant::depuisCentimes(7500, Devise::EUR), $prix->multipliePar(3));
    }

    #[Test]
    public function le_montant_d_origine_n_est_pas_modifie(): void
    {
        $prix = Montant::depuisCentimes(2500, Devise::EUR);

        $prix->multipliePar(3);

        self::assertSame(2500, $prix->enCentimes(), 'Un value object est immuable.');
    }

    #[Test]
    public function additionner_deux_montants(): void
    {
        $total = Montant::depuisCentimes(2500, Devise::EUR)
            ->plus(Montant::depuisCentimes(1000, Devise::EUR));

        self::assertSame(3500, $total->enCentimes());
    }

    #[Test]
    public function on_ne_peut_pas_additionner_deux_devises_differentes(): void
    {
        $this->expectException(MontantsNonComparables::class);

        Montant::depuisCentimes(2500, Devise::EUR)->plus(Montant::depuisCentimes(1000, Devise::USD));
    }
}
