<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Doctrine\Type;

use Bookshelf\Domain\Model\Commande\Quantite;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class QuantiteType extends Type
{
    public const NAME = 'quantity';

    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        return $value?->enEntier();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Quantite
    {
        return $value === null ? null : Quantite::depuisEntier((int) $value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
