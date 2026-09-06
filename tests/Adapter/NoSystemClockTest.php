<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Adapter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * PALIER D, contrainte verifiee automatiquement.
 *
 * `new DateTimeImmutable('now')`, `time()` et `date()` sont des appels a un systeme
 * externe : l'horloge de la machine. Au sens de la regle n° 1 du jour 1, tout code qui
 * les appelle est du code d'infrastructure.
 *
 * Ce test interdit donc leur presence dans le Domaine et l'Application. Il est
 * volontairement grossier — une simple recherche de texte, commentaires exclus — mais il
 * attrape la regression qui compte : celle du developpeur presse qui reintroduit
 * `new DateTimeImmutable()` au fond d'un service parce que « c'est plus simple ».
 */
final class NoSystemClockTest extends TestCase
{
    /**
     * Des expressions regulieres, pas des `str_contains` : une recherche de texte brute
     * sur `date(` matcherait aussi `validate(`, et le test deviendrait un faux positif
     * que l'equipe finirait par desactiver. Un test d'architecture qui crie au loup ne
     * sert plus a rien.
     */
    private const INTERDITS = [
        'new DateTime' => '/\bnew\s+\\\\?DateTime(?:Immutable)?\s*\(/',
        'time()'       => '/(?<![\w>$])time\s*\(/',
        'date()'       => '/(?<![\w>$])date\s*\(/',
        'mktime()'     => '/(?<![\w>$])mktime\s*\(/',
    ];

    /** @return iterable<string, array{string}> */
    public static function fichiersDuCoeur(): iterable
    {
        // dirname() rend des antislashs sous Windows : on normalise des le depart.
        $racine = str_replace('\\', '/', dirname(__DIR__, 2)) . '/src';

        foreach (['Domain', 'Application'] as $couche) {
            $fichiers = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($racine . '/' . $couche),
            );

            foreach ($fichiers as $fichier) {
                if ($fichier->getExtension() !== 'php') {
                    continue;
                }

                /*
                 * Cle lisible : le chemin relatif a src/. On normalise d'abord les
                 * separateurs, sinon rien ne correspond sous Windows. La cle ne peut
                 * pas etre le simple nom de fichier : deux Ebook.php coexistent dans
                 * Application, et PHPUnit refuse les cles dupliquees.
                 */
                $chemin = str_replace('\\', '/', $fichier->getPathname());
                $cle = str_replace($racine . '/', '', $chemin);

                yield $cle => [$fichier->getPathname()];
            }
        }
    }

    #[Test]
    #[DataProvider('fichiersDuCoeur')]
    public function le_coeur_ne_lit_jamais_l_horloge_systeme(string $chemin): void
    {
        $code = $this->codeSansCommentaires((string) file_get_contents($chemin));

        $trouves = array_keys(array_filter(
            self::INTERDITS,
            static fn (string $motif): bool => preg_match($motif, $code) === 1,
        ));

        self::assertSame(
            [],
            $trouves,
            "Le temps doit arriver par un argument ou par Psr\\Clock\\ClockInterface.",
        );
    }

    private function codeSansCommentaires(string $source): string
    {
        $code = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }
}
