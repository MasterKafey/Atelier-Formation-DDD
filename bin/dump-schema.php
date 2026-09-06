<?php

declare(strict_types=1);

/**
 * Genere le DDL a partir du mapping, pour la plateforme demandee.
 *
 *   php bin/dump-schema.php sqlite   > migrations/001-orders.sqlite.sql
 *   php bin/dump-schema.php mysql    > migrations/001-orders.mysql.sql
 *
 * En projet reel vous utiliseriez doctrine/migrations. Ce script evite d'ajouter une
 * dependance de plus a un depot de formation, tout en montrant que le schema se DEDUIT
 * du modele : on ne le maintient pas a la main en parallele.
 */

use Bookshelf\Domain\Model\Commande\Commande;
use Bookshelf\Domain\Model\Commande\LigneDeCommande;
use Bookshelf\Infrastructure\Doctrine\EntityManagerFactory;
use Doctrine\ORM\Tools\SchemaTool;

require dirname(__DIR__) . '/vendor/autoload.php';

$platform = $argv[1] ?? 'sqlite';

$params = match ($platform) {
    'sqlite' => ['driver' => 'pdo_sqlite', 'memory' => true],
    'mysql' => ['driver' => 'pdo_mysql', 'host' => 'localhost', 'dbname' => 'bookshelf',
                'user' => 'root', 'password' => '', 'serverVersion' => '8.0'],
    default => throw new InvalidArgumentException('Plateforme inconnue : ' . $platform),
};

$entityManager = EntityManagerFactory::create($params);
$schemaTool = new SchemaTool($entityManager);

$metadata = [
    $entityManager->getClassMetadata(Commande::class),
    $entityManager->getClassMetadata(LigneDeCommande::class),
];

echo implode(";\n\n", $schemaTool->getCreateSchemaSql($metadata)), ";\n";
