<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Doctrine\Type;

use Bookshelf\Domain\Model\Common\TauxDeTva;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class TauxDeTvaType extends Type
{
    public const NAME = 'vat_rate';

    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getSmallIntTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        return $value?->enPourcentage();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?TauxDeTva
    {
        return $value === null ? null : TauxDeTva::depuisPourcentage((int) $value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
