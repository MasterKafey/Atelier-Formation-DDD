<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Adapter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * La contrainte du palier C : le mapping a le droit d'entrer dans le domaine, mais RIEN
 * d'autre de Doctrine.
 *
 * Les attributs `#[ORM\Column]` contiennent des details techniques (noms de colonnes,
 * types). C'est un compromis assume : l'entite reste du code coeur au sens des deux
 * regles, puisqu'on peut l'instancier et appeler ses methodes sans base de donnees. En
 * revanche, un `EntityManagerInterface` ou un `Doctrine\ORM\Query` dans le domaine
 * serait une faute.
 */
final class MappingContaminationTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function fichiersDuDomaine(): iterable
    {
        $racine = dirname(__DIR__, 2) . '/src/Domain';
        $fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));

        foreach ($fichiers as $fichier) {
            if ($fichier->getExtension() === 'php') {
                yield basename($fichier->getPathname()) => [$fichier->getPathname()];
            }
        }
    }

    #[Test]
    #[DataProvider('fichiersDuDomaine')]
    public function le_domaine_ne_reference_que_le_mapping_doctrine(string $chemin): void
    {
        preg_match_all('/^use\s+([^;]+);/m', (string) file_get_contents($chemin), $matches);

        $interdits = array_filter(
            $matches[1],
            static fn (string $import): bool =>
                str_starts_with($import, 'Doctrine')
                && !str_starts_with($import, 'Doctrine\\ORM\\Mapping')
                && !str_starts_with($import, 'Doctrine\\Common\\Collections'),
        );

        self::assertSame(
            [],
            array_values($interdits),
            'Seuls le mapping et les collections Doctrine ont le droit d\'entrer dans le domaine.',
        );
    }
}
