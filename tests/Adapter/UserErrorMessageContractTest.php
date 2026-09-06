<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Adapter;

use Bookshelf\Application\UserErrorMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * PALIER B, contrainte verifiee automatiquement.
 *
 * Le coeur dit CE QUI s'est mal passe, sous forme de cle. Il ne formule jamais de phrase
 * destinee a un humain : ni traduction, ni ponctuation, ni majuscule. Formuler, c'est
 * savoir a qui l'on parle et dans quelle langue — un travail d'adaptateur.
 *
 * Sans ce garde-fou, la premiere phrase francaise glissee dans une exception du domaine
 * passe inapercue, et le jour ou l'API doit repondre a un partenaire neerlandais il est
 * trop tard : la traduction est figee dans le coeur.
 */
final class UserErrorMessageContractTest extends TestCase
{
    /**
     * Une cle de traduction : minuscules, points, tirets bas. Pas d'espace, donc pas de
     * phrase.
     */
    private const FORMAT_DE_CLE = '/^[a-z0-9_]+(\.[a-z0-9_]+)+$/';

    /** @return iterable<string, array{class-string<UserErrorMessage>}> */
    public static function exceptionsUtilisateur(): iterable
    {
        $racine = str_replace('\\', '/', dirname(__DIR__, 2)) . '/src';
        $fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));

        foreach ($fichiers as $fichier) {
            if ($fichier->getExtension() !== 'php') {
                continue;
            }

            $chemin = str_replace('\\', '/', $fichier->getPathname());
            $classe = 'Bookshelf\\' . str_replace('/', '\\', substr($chemin, strlen($racine) + 1, -4));

            if (!class_exists($classe)) {
                continue;
            }

            $reflection = new ReflectionClass($classe);

            if ($reflection->implementsInterface(UserErrorMessage::class) && !$reflection->isAbstract()) {
                yield $reflection->getShortName() => [$classe];
            }
        }
    }

    /**
     * @param class-string<UserErrorMessage> $classe
     */
    #[Test]
    #[DataProvider('exceptionsUtilisateur')]
    public function une_exception_utilisateur_porte_une_cle_et_non_une_phrase(string $classe): void
    {
        $reflection = new ReflectionClass($classe);

        $constructeursNommes = array_filter(
            $reflection->getMethods(),
            static fn ($m): bool => $m->isStatic() && $m->isPublic() && $m->getDeclaringClass()->getName() === $classe,
        );

        self::assertNotEmpty(
            $constructeursNommes,
            sprintf('%s doit exposer au moins un constructeur nomme.', $reflection->getShortName()),
        );

        foreach ($constructeursNommes as $methode) {
            $erreur = $methode->invokeArgs(null, $this->argumentsFactices($methode));

            self::assertMatchesRegularExpression(
                self::FORMAT_DE_CLE,
                $erreur->translationKey(),
                sprintf(
                    '%s::%s() rend « %s » : cela ressemble a une phrase, pas a une cle. '
                    . 'Formuler est le travail de l\'adaptateur.',
                    $reflection->getShortName(),
                    $methode->getName(),
                    $erreur->translationKey(),
                ),
            );

            foreach ($erreur->translationParameters() as $nom => $valeur) {
                self::assertIsString($nom);
                self::assertIsString(
                    $valeur,
                    'Les parametres doivent etre des chaines : l\'adaptateur les injecte tels quels.',
                );
            }
        }
    }

    /** @return list<mixed> */
    private function argumentsFactices(\ReflectionMethod $methode): array
    {
        return array_map(
            static fn (\ReflectionParameter $p): mixed => match ((string) $p->getType()) {
                'int' => 1,
                'string' => 'x',
                default => null,
            },
            $methode->getParameters(),
        );
    }
}
