<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Cli;

use Bookshelf\Application\UserErrorMessage;
use Bookshelf\Infrastructure\Translation\Translator;

/** Adaptateur CLI : une ligne pour stderr, sans balisage. */
final readonly class ConsoleErrorPresenter
{
    public function __construct(private Translator $translator) {}

    public function present(UserErrorMessage $error): string
    {
        return '[erreur] ' . $this->translator->trans(
            $error->translationKey(),
            $error->translationParameters(),
        );
    }
}
