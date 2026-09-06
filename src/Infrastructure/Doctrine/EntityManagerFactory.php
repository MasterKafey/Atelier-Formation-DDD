<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Doctrine;

use Bookshelf\Infrastructure\Doctrine\Type\AdresseEmailType;
use Bookshelf\Infrastructure\Doctrine\Type\CodePaysType;
use Bookshelf\Infrastructure\Doctrine\Type\IdentifiantCommandeType;
use Bookshelf\Infrastructure\Doctrine\Type\IdentifiantEbookType;
use Bookshelf\Infrastructure\Doctrine\Type\MontantType;
use Bookshelf\Infrastructure\Doctrine\Type\QuantiteType;
use Bookshelf\Infrastructure\Doctrine\Type\ReferenceDePaiementType;
use Bookshelf\Infrastructure\Doctrine\Type\TauxDeTvaType;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;

/**
 * Racine de composition de la couche de persistance.
 *
 * En production, c'est le conteneur du framework qui fait ce travail (DoctrineBundle).
 * Ici on l'ecrit a la main : c'est plus court que la configuration equivalente, et surtout
 * ca rend le test d'adaptateur executable sans demarrer Symfony.
 */
final class EntityManagerFactory
{
    private const CUSTOM_TYPES = [
        IdentifiantCommandeType::NAME => IdentifiantCommandeType::class,
        IdentifiantEbookType::NAME => IdentifiantEbookType::class,
        AdresseEmailType::NAME => AdresseEmailType::class,
        CodePaysType::NAME => CodePaysType::class,
        ReferenceDePaiementType::NAME => ReferenceDePaiementType::class,
        TauxDeTvaType::NAME => TauxDeTvaType::class,
        QuantiteType::NAME => QuantiteType::class,
        MontantType::NAME => MontantType::class,
    ];

    /** @param array<string, mixed> $connectionParams */
    public static function create(array $connectionParams): EntityManagerInterface
    {
        self::registerCustomTypes();

        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [dirname(__DIR__, 2) . '/Domain/Model'],
            isDevMode: true,
        );

        return new EntityManager(DriverManager::getConnection($connectionParams, $config), $config);
    }

    /** Base SQLite en memoire : de quoi executer un vrai test d'adaptateur sans serveur. */
    public static function createInMemorySqlite(): EntityManagerInterface
    {
        return self::create([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
    }

    private static function registerCustomTypes(): void
    {
        foreach (self::CUSTOM_TYPES as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }
    }
}
