<?php

declare(strict_types=1);

namespace Bookshelf\Tests\UseCase;

use Bookshelf\Tests\Support\TestServiceContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ListerEbooksDisponiblesTest extends TestCase
{
    #[Test]
    public function le_view_model_expose_un_prix_deja_formate(): void
    {
        $container = new TestServiceContainer();
        $container->catalogue()->ajouter('Architecture hexagonale', 2500, nombreDeVentes: 12);

        $ebooks = $container->application()->listerEbooksDisponibles();

        self::assertCount(1, $ebooks);
        self::assertSame('25,00 €', $ebooks[0]->prix, 'Le gabarit ne doit rien avoir a formater.');
        self::assertSame(12, $ebooks[0]->nombreDeVentes);
    }

    #[Test]
    public function un_titre_retire_de_la_vente_n_apparait_pas_au_catalogue(): void
    {
        $container = new TestServiceContainer();
        $container->catalogue()->ajouter('En vente', 2500);
        $container->catalogue()->ajouter('Retire de la vente', 3000, retire: true);

        self::assertCount(1, $container->application()->listerEbooksDisponibles());
    }

    #[Test]
    public function le_view_model_est_serialisable_en_une_seule_etape(): void
    {
        $container = new TestServiceContainer();
        $container->catalogue()->ajouter('Architecture hexagonale', 2500, nombreDeVentes: 12);

        $json = json_encode($container->application()->listerEbooksDisponibles(), JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(
            ['identifiantEbook', 'titre', 'prix', 'nombreDeVentes'],
            array_keys($decoded[0]),
            'Un view model doit pouvoir devenir une reponse JSON sans travail du controleur.',
        );
    }
}
