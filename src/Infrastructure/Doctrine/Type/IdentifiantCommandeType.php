<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Doctrine\Type;

use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Type Doctrine pour le value object `IdentifiantCommande`.
 *
 * C'est ici, et uniquement ici, que le domaine touche a la base : le type reel de la
 * donnee reste un detail interne du value object. Le jour ou `IdentifiantCommande` change de
 * representation, seule cette classe bouge.
 */
final class IdentifiantCommandeType extends Type
{
    public const NAME = 'order_id';

    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 36]);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->enChaine();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?IdentifiantCommande
    {
        if ($value === null) {
            return null;
        }

        return IdentifiantCommande::depuisChaine($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
