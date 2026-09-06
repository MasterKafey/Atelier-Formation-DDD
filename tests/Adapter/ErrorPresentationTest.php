<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Adapter;

use Bookshelf\Application\CommanderLivrePapier\CommandeDeLivrePapierImpossible;
use Bookshelf\Infrastructure\Api\ApiErrorPresenter;
use Bookshelf\Infrastructure\Cli\ConsoleErrorPresenter;
use Bookshelf\Infrastructure\Http\FormErrorPresenter;
use Bookshelf\Infrastructure\Translation\FrenchTranslator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * PALIER B. UNE exception, TROIS rendus.
 *
 * C'est la demonstration de la separation : le coeur a dit ce qui s'est mal passe, et
 * trois adaptateurs en tirent trois choses differentes sans qu'aucun code metier ne
 * change. Ajouter un quatrieme canal — un SMS, un webhook — ne toucherait pas davantage
 * au coeur.
 */
final class ErrorPresentationTest extends TestCase
{
    #[Test]
    public function le_web_en_fait_un_message_de_formulaire_traduit(): void
    {
        $presenter = new FormErrorPresenter(new FrenchTranslator());

        self::assertSame(
            ['general' => ['Il ne reste que 2 exemplaire(s) de ce livre, vous en avez demande 3.']],
            $presenter->present($this->uneErreurDeStock()),
        );
    }

    #[Test]
    public function l_api_en_fait_un_code_machine_non_traduit(): void
    {
        $presenter = new ApiErrorPresenter();

        self::assertSame(
            [
                'error' => [
                    'code' => 'order.insufficient_stock',
                    'parameters' => ['disponibles' => '2', 'demandes' => '3'],
                ],
            ],
            $presenter->present($this->uneErreurDeStock()),
            'Un partenaire veut brancher un switch dessus, pas afficher notre francais.',
        );
    }

    #[Test]
    public function la_cli_en_fait_une_ligne_pour_stderr(): void
    {
        $presenter = new ConsoleErrorPresenter(new FrenchTranslator());

        self::assertSame(
            '[erreur] Il ne reste que 2 exemplaire(s) de ce livre, vous en avez demande 3.',
            $presenter->present($this->uneErreurDeStock()),
        );
    }

    #[Test]
    public function le_rendu_json_de_l_api_est_serialisable_en_une_etape(): void
    {
        $donnees = (new ApiErrorPresenter())->present($this->uneErreurDeStock());

        self::assertJsonStringEqualsJsonString(
            '{"error":{"code":"order.insufficient_stock","parameters":{"disponibles":"2","demandes":"3"}}}',
            json_encode($donnees, JSON_THROW_ON_ERROR),
        );
    }

    private function uneErreurDeStock(): CommandeDeLivrePapierImpossible
    {
        return CommandeDeLivrePapierImpossible::carStockInsuffisant(disponibles: 2, demandes: 3);
    }
}
