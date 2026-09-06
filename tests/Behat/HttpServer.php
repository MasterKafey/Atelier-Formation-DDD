<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Behat;

use RuntimeException;

/**
 * Demarre et arrete le serveur web integre de PHP pour la suite HTTP.
 *
 * C'est exactement ce que le jour 3 annonce des tests de bout en bout : ils sont lents,
 * ils demandent une infrastructure, et ils echouent parfois pour des raisons qui n'ont
 * rien a voir avec le code. On en ecrit peu, et on les assume.
 */
final class HttpServer
{
    private const HOTE = '127.0.0.1';

    /** @var resource|null */
    private static $processus = null;
    private static ?string $fichierBase = null;
    private static ?string $fichierFixtures = null;

    public static function demarrer(int $port): void
    {
        /*
         * Refuser de demarrer si le port est deja pris. Sans ce garde-fou, la suite se
         * connecte silencieusement au serveur de quelqu'un d'autre : elle passe au vert
         * en testant un code qui n'est pas le votre. C'est arrive pendant l'ecriture de
         * ce corrige, et rien dans le rapport ne le signalait.
         */
        $occupe = @fsockopen(self::HOTE, $port, $errno, $errstr, 0.2);

        if (is_resource($occupe)) {
            fclose($occupe);

            throw new RuntimeException(sprintf(
                'Le port %d est deja utilise. Arretez le processus qui l\'occupe, ou '
                . 'choisissez un autre port via BOOKSHELF_PORT.',
                $port,
            ));
        }

        self::$fichierBase = tempnam(sys_get_temp_dir(), 'bookshelf-') . '.sqlite';
        self::$fichierFixtures = tempnam(sys_get_temp_dir(), 'bookshelf-') . '.json';
        self::ecrireFixtures(['ebooks' => [], 'books' => []]);

        $racine = dirname(__DIR__, 2);

        self::$processus = proc_open(
            [PHP_BINARY, '-S', self::HOTE . ':' . $port, '-t', $racine . '/public'],
            [1 => ['file', $racine . '/var/serveur.log', 'a'], 2 => ['file', $racine . '/var/serveur.log', 'a']],
            $pipes,
            $racine,
            [
                'BOOKSHELF_DB' => self::$fichierBase,
                'BOOKSHELF_FIXTURES' => self::$fichierFixtures,
                'PATH' => getenv('PATH') ?: '',
                'SystemRoot' => getenv('SystemRoot') ?: '',
            ],
        );

        if (!is_resource(self::$processus)) {
            throw new RuntimeException('Impossible de demarrer le serveur de test.');
        }

        self::attendreLePort($port);
    }

    public static function arreter(): void
    {
        if (is_resource(self::$processus)) {
            proc_terminate(self::$processus);
            proc_close(self::$processus);
            self::$processus = null;
        }

        foreach ([self::$fichierBase, self::$fichierFixtures] as $fichier) {
            if ($fichier !== null && is_file($fichier)) {
                @unlink($fichier);
            }
        }
    }

    /** Remet la base et le jeu d'essai a zero entre deux scenarios. */
    public static function reinitialiser(): void
    {
        if (self::$fichierBase !== null && is_file(self::$fichierBase)) {
            @unlink(self::$fichierBase);
        }

        self::ecrireFixtures(['ebooks' => [], 'books' => []]);
    }

    /** @param array<string, list<array<string, mixed>>> $fixtures */
    public static function ecrireFixtures(array $fixtures): void
    {
        file_put_contents((string) self::$fichierFixtures, json_encode($fixtures, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, list<array<string, mixed>>> */
    public static function lireFixtures(): array
    {
        return json_decode((string) file_get_contents((string) self::$fichierFixtures), true) ?: [];
    }

    private static function attendreLePort(int $port): void
    {
        for ($essai = 0; $essai < 100; $essai++) {
            $socket = @fsockopen(self::HOTE, $port, $errno, $errstr, 0.1);

            if (is_resource($socket)) {
                fclose($socket);

                return;
            }

            usleep(100_000);
        }

        throw new RuntimeException(sprintf('Le serveur de test n\'ecoute pas sur le port %d.', $port));
    }
}
