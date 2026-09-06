<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Adapter;

use Bookshelf\Domain\Model\Common\CodePays;
use Bookshelf\Domain\Model\Common\TauxDeTva;
use Bookshelf\Infrastructure\VatApi\FournisseurDeTauxDeTvaAvecVatApi;
use Bookshelf\Infrastructure\VatApi\VatApi;
use Bookshelf\Tests\Support\FakeVatApiTransport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * TEST D'ADAPTATEUR. Il prouve que la couche anticorruption traduit correctement le
 * vocabulaire du fournisseur vers le notre.
 */
final class FournisseurDeTauxDeTvaAvecVatApiTest extends TestCase
{
    #[Test]
    public function traduit_la_reponse_de_l_api_en_taux_de_tva(): void
    {
        $provider = $this->fournisseurRendant([
            'status' => 200,
            'filter_match' => true,
            'rate' => 9,
            'rates' => ['electronic' => ['rate' => 21]],
        ]);

        self::assertEquals(
            TauxDeTva::depuisPourcentage(9),
            $provider->tauxDeTvaPourEbooksDansLePays(CodePays::depuisChaine('NL')),
        );
    }

    #[Test]
    public function retombe_sur_le_taux_generique_quand_le_filtre_ne_trouve_rien(): void
    {
        $provider = $this->fournisseurRendant([
            'status' => 200,
            'filter_match' => false,
            'rate' => 0,
            'rates' => ['electronic' => ['rate' => 21]],
        ]);

        self::assertEquals(
            TauxDeTva::depuisPourcentage(21),
            $provider->tauxDeTvaPourEbooksDansLePays(CodePays::depuisChaine('NL')),
        );
    }

    /**
     * Test d'architecture. La contrainte de l'atelier : le vocabulaire de vatapi.com ne
     * doit apparaitre nulle part en dehors de `src/Infrastructure/VatApi/`.
     */
    #[Test]
    public function le_vocabulaire_de_vatapi_ne_sort_pas_de_l_infrastructure(): void
    {
        $racine = dirname(__DIR__, 2) . '/src';
        /*
         * Uniquement des jetons NON AMBIGUS. La valeur du filtre vatapi.com est
         * litteralement 'ebooks' : impossible de la chercher sans attraper au passage
         * tout code parlant legitimement d'e-books. Un test d'architecture qui crie au
         * loup finit desactive, donc inutile. Les quatre jetons restants n'ont, eux,
         * aucune raison d'exister ailleurs que dans l'adaptateur.
         */
        $interdits = ['TBE', 'electronic', 'rate_type', 'filter_match'];

        $fuites = [];
        $fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));

        foreach ($fichiers as $fichier) {
            if ($fichier->getExtension() !== 'php') {
                continue;
            }

            $chemin = str_replace('\\', '/', $fichier->getPathname());

            if (str_contains($chemin, '/Infrastructure/VatApi/')) {
                continue;
            }

            $contenu = $this->codeSansCommentaires((string) file_get_contents($chemin));

            foreach ($interdits as $mot) {
                if (str_contains($contenu, $mot)) {
                    $fuites[] = basename($chemin) . ' contient ' . $mot;
                }
            }
        }

        self::assertSame([], $fuites, "L'abstraction fuit : le vocabulaire du fournisseur s'echappe.");
    }

    /** La contrainte porte sur le code, pas sur la prose des commentaires. */
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

    /** @param array<string, mixed> $response */
    private function fournisseurRendant(array $response): FournisseurDeTauxDeTvaAvecVatApi
    {
        return new FournisseurDeTauxDeTvaAvecVatApi(
            new VatApi(new FakeVatApiTransport($response), 'CLE-DE-TEST'),
        );
    }
}
