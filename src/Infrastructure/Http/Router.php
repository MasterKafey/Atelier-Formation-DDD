<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Http;

use Bookshelf\Application\ApplicationInterface;
use Bookshelf\Application\CommanderLivrePapier\CommanderLivrePapier;
use Bookshelf\Application\PasserCommande\EbookIntrouvable;
use Bookshelf\Application\PasserCommande\PasserCommande;
use Bookshelf\Application\UserErrorMessage;
use Bookshelf\Infrastructure\Api\ApiErrorPresenter;
use Throwable;

/**
 * ADAPTATEUR ENTRANT. Tout ce qui est ici est de l'infrastructure : la methode HTTP, les
 * chemins, les codes de statut, la serialisation JSON. Rien de tout cela n'existe dans le
 * coeur, et c'est pourquoi le meme cas d'usage est appelable depuis une CLI ou un test.
 *
 * Il n'y a volontairement pas de framework : le but est de montrer que l'adaptateur est
 * une simple traduction, pas une architecture. En production ce serait un controleur
 * Symfony, avec le meme corps de methode.
 *
 * Notez le traitement des erreurs, qui applique la regle du palier B :
 *   - `UserErrorMessage` -> 422 et un code machine, l'utilisateur peut agir ;
 *   - tout le reste -> 500 sans detail, c'est un bug ou un abus, ca va dans les logs.
 */
final readonly class Router
{
    public function __construct(
        private ApplicationInterface $application,
        private ApiErrorPresenter $errorPresenter,
    ) {
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array{0: int, 1: array<string, mixed>|list<mixed>|null}
     */
    public function dispatch(string $method, string $path, array $body): array
    {
        try {
            return match (true) {
                $method === 'GET' && $path === '/ebooks' => [200, $this->listerEbooks()],
                $method === 'POST' && $path === '/orders' => [201, $this->passerCommande($body)],
                $method === 'POST' && $path === '/physical-books/orders' => [204, $this->commanderLivrePapier($body)],
                default => [404, ['error' => ['code' => 'route.not_found', 'parameters' => []]]],
            };
        } catch (UserErrorMessage $erreur) {
            // Destine a l'utilisateur : il peut corriger ou choisir autre chose.
            return [422, $this->errorPresenter->present($erreur)];
        } catch (EbookIntrouvable) {
            return [404, ['error' => ['code' => 'ebook.not_found', 'parameters' => []]]];
        } catch (Throwable) {
            // Bug ou abus : aucun detail ne sort, tout part dans les logs.
            return [500, ['error' => ['code' => 'internal_error', 'parameters' => []]]];
        }
    }

    /** @return list<array<string, mixed>> */
    private function listerEbooks(): array
    {
        return array_map(
            static fn (object $ebook): array => (array) $ebook,
            $this->application->listerEbooksDisponibles(),
        );
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array{order_id: string}
     */
    private function passerCommande(array $body): array
    {
        $identifiantCommande = $this->application->passerCommande(PasserCommande::depuisDonneesRequete($body));

        return ['order_id' => $identifiantCommande->enChaine()];
    }

    /** @param array<string, mixed> $body */
    private function commanderLivrePapier(array $body): null
    {
        $this->application->commanderLivrePapier(CommanderLivrePapier::depuisDonneesRequete($body));

        return null;
    }
}
