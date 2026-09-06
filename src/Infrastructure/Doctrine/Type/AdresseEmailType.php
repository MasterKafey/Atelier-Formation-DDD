<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Doctrine\Type;

use Bookshelf\Domain\Model\Common\AdresseEmail;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Type Doctrine pour le value object `AdresseEmail`.
 *
 * C'est ici, et uniquement ici, que le domaine touche a la base : le type reel de la
 * donnee reste un detail interne du value object. Le jour ou `AdresseEmail` change de
 * representation, seule cette classe bouge.
 */
final class AdresseEmailType extends Type
{
    public const NAME = 'email_address';

    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 255]);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->enChaine();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?AdresseEmail
    {
        if ($value === null) {
            return null;
        }

        return AdresseEmail::depuisChaine($value);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
