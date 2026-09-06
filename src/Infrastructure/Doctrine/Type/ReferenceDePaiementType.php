<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Doctrine\Type;

use Bookshelf\Domain\Model\Commande\ReferenceDePaiement;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Type Doctrine pour le value object `ReferenceDePaiement`.
 *
 * C'est ici, et uniquement ici, que le domaine touche a la base : le type reel de la
 * donnee reste un detail interne du value object. Le jour ou `ReferenceDePaiement` change de
 * representation, seule cette classe bouge.
 */
final class ReferenceDePaiementType extends Type
{
    public const NAME = 'payment_reference';

    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 64]);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->enChaine();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ReferenceDePaiement
    {
        if ($value === null) {
            return null;
        }

        return ReferenceDePaiement::depuisChaine($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
