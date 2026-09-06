<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Adapter;

use Bookshelf\Domain\Model\Commande\Commande;
use Bookshelf\Domain\Model\Commande\CommandeRepository;
use Bookshelf\Domain\Model\Commande\LigneDeCommande;
use Bookshelf\Domain\Model\Commande\PaiementImpossible;
use Bookshelf\Domain\Model\Commande\ReferenceDePaiement;
use Bookshelf\Infrastructure\Doctrine\CommandeRepositoryAvecDoctrine;
use Bookshelf\Infrastructure\Doctrine\EntityManagerFactory;
use Bookshelf\Infrastructure\InMemory\CommandeRepositoryEnMemoire;
use Bookshelf\Tests\Support\CommandeBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * TEST DE CONTRAT.
 *
 * L'interface `CommandeRepository` porte une promesse que sa signature ne peut pas exprimer :
 * si vous enregistrez une commande avec un identifiant donne, vous pouvez a tout moment en
 * recuperer un objet equivalent avec ce meme identifiant.
 *
 * On ecrit cette promesse UNE FOIS, et on l'execute contre TOUTES les implementations.
 * C'est ce qui prouve qu'elles sont interchangeables (Liskov), et donc que le double
 * utilise dans les tests de cas d'usage ne ment pas.
 *
 * Trois regles, dont une qu'on decouvre en ecrivant le test :
 *
 *   1. Tout doit etre reel : vraie base, aucun double. Ici SQLite en memoire pour que la
 *      suite reste executable partout ; en projet reel, la meme base qu'en production.
 *
 *   2. N'appelez QUE des methodes de l'interface, sinon vous testez une implementation
 *      particuliere et vous ne prouvez plus qu'elles sont equivalentes.
 *
 *   3. VIDEZ LA CARTE D'IDENTITE entre l'ecriture et la lecture. Sans cela, Doctrine vous
 *      rend l'objet qu'il a deja en memoire et le test passe sans avoir rien traverse.
 *      C'est le piege classique du test de repository : il est vert, et il ne prouve rien.
 *      Le `oublier()` ci-dessous est un no-op pour l'implementation en memoire, ce qui est
 *      honnete : ce double ne serialise rien, et cette limite doit etre connue.
 */
final class CommandeRepositoryContractTest extends TestCase
{
    #[Test]
    #[DataProvider('commandes')]
    public function une_commande_enregistree_peut_etre_rechargee_a_l_identique(Commande $attendue): void
    {
        foreach ($this->implementations() as [$nom, $repository, $oublier]) {
            $repository->enregistrer($attendue);
            $oublier();

            $rechargee = $repository->parIdentifiant($attendue->identifiantCommande());

            self::assertTrue(
                $attendue->identifiantCommande()->estEgalA($rechargee->identifiantCommande()),
                sprintf('%s : l\'identite ne survit pas a l\'aller-retour.', $nom),
            );
            self::assertEquals(
                $attendue->totalHt(),
                $rechargee->totalHt(),
                sprintf('%s : les lignes ne survivent pas a l\'aller-retour.', $nom),
            );
            self::assertEquals(
                $attendue->totalTtc(),
                $rechargee->totalTtc(),
                sprintf('%s : le taux de TVA ne survit pas a l\'aller-retour.', $nom),
            );
        }
    }

    #[Test]
    public function une_modification_reenregistree_est_bien_persistee(): void
    {
        foreach ($this->implementations() as [$nom, $repository, $oublier]) {
            $commande = CommandeBuilder::creer()->avecLigne(2500, 1)->confirmee()->construire();
            $repository->enregistrer($commande);

            $commande->annuler();
            $repository->enregistrer($commande);
            $oublier();

            $rechargee = $repository->parIdentifiant($commande->identifiantCommande());

            try {
                $rechargee->payer(ReferenceDePaiement::depuisChaine('PAY-999'));
                self::fail(sprintf('%s : l\'annulation n\'a pas ete persistee.', $nom));
            } catch (PaiementImpossible) {
                self::assertTrue(true, sprintf('%s : l\'etat annule a bien traverse.', $nom));
            }
        }
    }

    #[Test]
    public function chercher_une_commande_inexistante_leve_une_exception(): void
    {
        foreach ($this->implementations() as [$nom, $repository, $oublier]) {
            $inconnue = $repository->prochainIdentifiant();

            try {
                $repository->parIdentifiant($inconnue);
                self::fail(sprintf('%s : aurait du lever CommandeIntrouvable.', $nom));
            } catch (\Bookshelf\Domain\Model\Commande\CommandeIntrouvable) {
                self::assertTrue(true);
            }
        }
    }

    /**
     * Chaque implementation vient avec le moyen de lui faire oublier ce qu'elle garde en
     * memoire. Sans ca, le test de round-trip ne traverse rien.
     *
     * @return Generator<array{string, CommandeRepository, callable(): void}>
     */
    private function implementations(): Generator
    {
        yield ['en memoire', new CommandeRepositoryEnMemoire(), static function (): void {}];

        $entityManager = $this->sqliteEntityManager();

        yield [
            'Doctrine / SQLite',
            new CommandeRepositoryAvecDoctrine($entityManager),
            static fn () => $entityManager->clear(),
        ];
    }

    /** @return Generator<string, array{Commande}> */
    public static function commandes(): Generator
    {
        yield 'une ligne' => [CommandeBuilder::creer()->avecLigne(2500, 1)->construire()];
        yield 'confirmee' => [CommandeBuilder::creer()->avecLigne(2500, 2)->confirmee()->construire()];
        yield 'payee' => [CommandeBuilder::creer()->avecLigne(1000, 3)->payee()->construire()];
        yield 'annulee' => [CommandeBuilder::creer()->avecLigne(1500, 1)->annulee()->construire()];
        yield 'plusieurs lignes' => [
            CommandeBuilder::creer()->avecLigne(2500, 2)->avecLigne(1000, 1)->confirmee()->construire(),
        ];
        yield 'TVA a 9 %' => [
            CommandeBuilder::creer()->avecTauxDeTva(9)->avecLigne(2500, 1)->confirmee()->construire(),
        ];
    }

    private function sqliteEntityManager(): EntityManagerInterface
    {
        $entityManager = EntityManagerFactory::createInMemorySqlite();

        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(Commande::class),
            $entityManager->getClassMetadata(LigneDeCommande::class),
        ]);

        return $entityManager;
    }
}
