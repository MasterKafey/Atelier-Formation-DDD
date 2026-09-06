<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Translation;

/**
 * Traduire est un travail d'INFRASTRUCTURE : cela depend de qui lit, dans quelle langue,
 * sur quel media. Le coeur ne connait que des cles.
 */
interface Translator
{
    /** @param array<string, string> $parameters */
    public function trans(string $key, array $parameters = []): string;
}
