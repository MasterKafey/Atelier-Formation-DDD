<?php

declare(strict_types=1);

/**
 * Point d'entree HTTP. Le premier code a nous que le serveur execute.
 *
 * Demarrage :
 *   php -S 127.0.0.1:8321 -t public
 *
 * Deux variables d'environnement pilotent l'etat :
 *   BOOKSHELF_DB        chemin du fichier SQLite ou vivent les commandes
 *   BOOKSHELF_FIXTURES  chemin du JSON decrivant le catalogue et le stock
 *
 * C'est ici, et seulement ici, que le conteneur est appele : au point d'entree, il joue
 * le role de RACINE DE COMPOSITION et non de localisateur de services. Une fois dans
 * l'application, plus personne ne va y chercher quoi que ce soit.
 */

use Bookshelf\Infrastructure\Http\HttpServiceContainer;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = new HttpServiceContainer(
    databasePath: getenv('BOOKSHELF_DB') ?: sys_get_temp_dir() . '/bookshelf.sqlite',
    fixturesPath: getenv('BOOKSHELF_FIXTURES') ?: sys_get_temp_dir() . '/bookshelf-fixtures.json',
);

$chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$corps = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];

[$statut, $donnees] = $container->router()->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $chemin,
    is_array($corps) ? $corps : [],
);

http_response_code($statut);

if ($donnees === null) {
    exit;
}

header('Content-Type: application/json');
echo json_encode($donnees, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
