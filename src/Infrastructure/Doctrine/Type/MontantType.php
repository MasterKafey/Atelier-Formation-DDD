<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\Doctrine\Type;

use Bookshelf\Domain\Model\Common\Devise;
use Bookshelf\Domain\Model\Common\Montant;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

/**
 * Le cas interessant : `Montant` porte DEUX valeurs, un montant et une devise.
 *
 * Trois options, et il faut assumer le compromis :
 *
 *   1. un `#[ORM\Embedded]`, qui donne deux colonnes propres mais que la regle « mapping
 *      simple uniquement » nous demande d'eviter ;
 *   2. deux proprietes primitives dans l'entite, ce qui casse l'encapsulation du value
 *      object : le domaine se remettrait a manipuler des entiers ;
 *   3. une seule colonne texte, `"2500 EUR"`, avec un type personnalise.
 *
 * On retient la 3, pour une raison precise : les totaux ne sont JAMAIS persistes, ils sont
 * calcules par l'agregat. Nous n'avons donc aucun besoin d'agreger des montants en SQL,
 * et c'est la seule chose que cette option nous interdirait.
 *
 * Si un jour vous devez faire un `SUM()` sur ces montants, ce compromis ne tient plus :
 * il faudra passer a l'embeddable, ou construire un read model dedie alimente par
 * evenements. Notez-le, c'est exactement le genre de decision qu'on oublie d'ecrire.
 */
final class MontantType extends Type
{
    public const NAME = 'money';

    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 32]);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return sprintf('%d %s', $value->enCentimes(), $value->devise()->value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Montant
    {
        if ($value === null) {
            return null;
        }

        $parts = explode(' ', (string) $value);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException(sprintf('Montant illisible en base : %s', $value));
        }

        [$cents, $devise] = $parts;

        return Montant::depuisCentimes((int) $cents, Devise::from($devise));
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
