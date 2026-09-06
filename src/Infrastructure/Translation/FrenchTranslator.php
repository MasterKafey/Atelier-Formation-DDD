<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Translation;

/**
 * Catalogue minimal. En production ce serait le composant Translation de Symfony ; le
 * principe ne change pas, seule l'implementation du port bouge.
 */
final class FrenchTranslator implements Translator
{
    private const CATALOGUE = [
        'order.insufficient_stock' =>
            'Il ne reste que %disponibles% exemplaire(s) de ce livre, vous en avez demande %demandes%.',
    ];

    public function trans(string $key, array $parameters = []): string
    {
        $message = self::CATALOGUE[$key] ?? $key;

        foreach ($parameters as $nom => $valeur) {
            $message = str_replace('%' . $nom . '%', $valeur, $message);
        }

        return $message;
    }
}
