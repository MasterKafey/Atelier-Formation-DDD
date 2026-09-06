<?php

declare(strict_types=1);

namespace Bookshelf\Application\CommanderLivrePapier;

use Bookshelf\Application\BaseUserErrorMessage;

/**
 * Exception DESTINEE A L'UTILISATEUR : il n'y a pas de bug, pas d'abus, juste une
 * situation legitime que la personne doit comprendre pour agir.
 *
 * Le constructeur nomme dit la raison ; les parametres permettront a l'adaptateur de
 * formuler un message precis (« il ne reste que 2 exemplaires ») plutot qu'un message
 * generique.
 */
final class CommandeDeLivrePapierImpossible extends BaseUserErrorMessage
{
    public static function carStockInsuffisant(int $disponibles, int $demandes): self
    {
        return new self('order.insufficient_stock', [
            'disponibles' => (string) $disponibles,
            'demandes' => (string) $demandes,
        ]);
    }
}
