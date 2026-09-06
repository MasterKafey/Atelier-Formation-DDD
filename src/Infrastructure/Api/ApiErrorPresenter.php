<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Api;

use Bookshelf\Application\UserErrorMessage;

/**
 * Adaptateur API : l'erreur devient un code MACHINE, pas une phrase.
 *
 * Un partenaire qui integre notre API veut pouvoir brancher un `switch` dessus et
 * afficher son propre message, dans sa propre langue. Lui envoyer du francais traduit
 * serait lui rendre un mauvais service.
 */
final readonly class ApiErrorPresenter
{
    /** @return array{error: array{code: string, parameters: array<string, string>}} */
    public function present(UserErrorMessage $error): array
    {
        return [
            'error' => [
                'code' => $error->translationKey(),
                'parameters' => $error->translationParameters(),
            ],
        ];
    }
}
