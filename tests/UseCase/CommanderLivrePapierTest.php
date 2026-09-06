<?php

declare(strict_types=1);

namespace Bookshelf\Tests\UseCase;

use Bookshelf\Application\CommanderLivrePapier\CommandeDeLivrePapierImpossible;
use Bookshelf\Application\CommanderLivrePapier\CommanderLivrePapier;
use Bookshelf\Application\UserErrorMessage;
use Bookshelf\Tests\Support\TestServiceContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * PALIER B. Le stock d'un livre papier est fini et non reapprovisionnable : on ne vend
 * que ce qu'on a.
 */
final class CommanderLivrePapierTest extends TestCase
{
    #[Test]
    public function on_peut_commander_ce_qui_est_en_stock(): void
    {
        $container = new TestServiceContainer();
        $identifiantLivre = $container->stock()->ajouter(exemplaires: 3);

        $container->application()->commanderLivrePapier(new CommanderLivrePapier($identifiantLivre->enChaine(), 2));

        self::assertSame(1, $container->stock()->nombreExemplairesDisponibles($identifiantLivre));
    }

    #[Test]
    public function on_peut_commander_exactement_le_dernier_exemplaire(): void
    {
        $container = new TestServiceContainer();
        $identifiantLivre = $container->stock()->ajouter(exemplaires: 1);

        $container->application()->commanderLivrePapier(new CommanderLivrePapier($identifiantLivre->enChaine(), 1));

        self::assertSame(0, $container->stock()->nombreExemplairesDisponibles($identifiantLivre));
    }

    #[Test]
    public function on_ne_vend_pas_un_livre_qu_on_n_a_plus(): void
    {
        $container = new TestServiceContainer();
        $identifiantLivre = $container->stock()->ajouter(exemplaires: 2);

        $this->expectException(CommandeDeLivrePapierImpossible::class);

        $container->application()->commanderLivrePapier(new CommanderLivrePapier($identifiantLivre->enChaine(), 3));
    }

    #[Test]
    public function l_erreur_est_destinee_a_l_utilisateur_et_porte_de_quoi_la_formuler(): void
    {
        $container = new TestServiceContainer();
        $identifiantLivre = $container->stock()->ajouter(exemplaires: 2);

        try {
            $container->application()->commanderLivrePapier(new CommanderLivrePapier($identifiantLivre->enChaine(), 3));
            self::fail('Aurait du lever CommandeDeLivrePapierImpossible.');
        } catch (CommandeDeLivrePapierImpossible $erreur) {
            self::assertInstanceOf(UserErrorMessage::class, $erreur);
            self::assertSame('order.insufficient_stock', $erreur->translationKey());
            self::assertSame(
                ['disponibles' => '2', 'demandes' => '3'],
                $erreur->translationParameters(),
                'Les parametres permettent un message precis, pas un message generique.',
            );
        }
    }

    /**
     * LE POINT DU PALIER, et il est contre-intuitif.
     *
     * `nombreExemplairesDisponibles()` est une fonction IMPURE : elle ne rend pas la meme
     * reponse a deux instants differents. Sa reponse n'est jamais fausse, elle est juste
     * au moment du calcul — mais elle change tout le temps.
     *
     * Une validation batie dessus n'est donc PAS une garantie. Entre la verification et
     * l'enregistrement, un autre client peut avoir pris le dernier exemplaire.
     *
     * Regle a retenir : ne validez qu'avec des fonctions PURES. Pour le reste, prevoyez
     * de vous en remettre plutot que de prevenir.
     */
    #[Test]
    public function la_verification_du_stock_ne_peut_pas_etre_une_garantie(): void
    {
        $container = new TestServiceContainer();
        $identifiantLivre = $container->stock()->ajouter(exemplaires: 1);

        // Deux clients consultent le stock au meme instant : il reste un exemplaire.
        $vuParClientA = $container->stock()->nombreExemplairesDisponibles($identifiantLivre);
        $vuParClientB = $container->stock()->nombreExemplairesDisponibles($identifiantLivre);

        self::assertSame(1, $vuParClientA);
        self::assertSame(1, $vuParClientB, 'Les deux voient le meme stock, et les deux commanderont.');

        // Le client A commande. Le stock a change sous les pieds du client B.
        $container->application()->commanderLivrePapier(new CommanderLivrePapier($identifiantLivre->enChaine(), 1));

        self::assertSame(
            0,
            $container->stock()->nombreExemplairesDisponibles($identifiantLivre),
            'Meme question, autre reponse : on ne batit pas une validation la-dessus.',
        );
    }
}
