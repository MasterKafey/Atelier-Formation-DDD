<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Http;

use Bookshelf\Application\UserErrorMessage;
use Bookshelf\Infrastructure\Translation\Translator;

/**
 * Adaptateur web : l'erreur devient un message de formulaire, dans la langue de
 * l'utilisateur.
 *
 * En Symfony, ce code vivrait dans le controleur, autour de l'appel au service :
 *
 *     try {
 *         ($this->commanderLivrePapier)($intention);
 *     } catch (UserErrorMessage $e) {
 *         $this->addFlash('error', $this->presenter->present($e)['general'][0]);
 *     }
 */
final readonly class FormErrorPresenter
{
    public function __construct(private Translator $translator) {}

    /** @return array<string, list<string>> */
    public function present(UserErrorMessage $error): array
    {
        return [
            'general' => [
                $this->translator->trans($error->translationKey(), $error->translationParameters()),
            ],
        ];
    }
}
