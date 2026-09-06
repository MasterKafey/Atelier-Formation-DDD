<?php

declare(strict_types=1);

namespace Bookshelf\Application;

use RuntimeException;

/**
 * Classe de base : evite de reecrire le meme constructeur dans chaque exception
 * utilisateur. Une interface ne peut pas etendre `Exception` ; c'est pourquoi le couple
 * interface + classe abstraite est necessaire ici.
 */
abstract class BaseUserErrorMessage extends RuntimeException implements UserErrorMessage
{
    /** @param array<string, string> $parameters */
    public function __construct(
        private readonly string $translationKey,
        private readonly array $parameters = [],
    ) {
        parent::__construct($translationKey);
    }

    public function translationKey(): string
    {
        return $this->translationKey;
    }

    public function translationParameters(): array
    {
        return $this->parameters;
    }
}
