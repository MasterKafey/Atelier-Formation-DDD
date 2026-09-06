<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Bookshelf\Domain\Model\Common\Montant;

/**
 * A ECRIRE. Entite fille de l'agregat Commande. On n'y accede que par la racine.
 *
 * Notez que la ligne reference l'e-book par son IDENTIFIANT, jamais par une reference
 * a l'entite `Ebook` : sinon la commande aurait acces a `changePrice()` et `hide()`.
 */
final class LigneDeCommande
{
    public function __construct(
        private readonly IdentifiantEbook $identifiantEbook,
        private readonly Montant $prixUnitaire,
        private readonly Quantite $quantite,
    ) {
    }

    public function sousTotal(): Montant
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function identifiantEbook(): IdentifiantEbook
    {
        throw new \RuntimeException('TODO atelier 2');
    }
}
