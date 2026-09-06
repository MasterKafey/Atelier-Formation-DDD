<?php

declare(strict_types=1);

namespace Bookshelf\Application\PasserCommande;

use Bookshelf\Domain\Model\Commande\IdentifiantEbook;

/**
 * Repository de READ MODEL, pas d'entite.
 *
 * Le service applicatif a besoin du prix d'un e-book. Il ne charge donc pas l'entite
 * `Ebook` (qui lui donnerait acces a `changePrice()` et `hide()` en plein tunnel de
 * commande) : il charge un objet concu pour repondre a sa question.
 *
 * Benefice cache : charger le read model valide au passage l'existence de l'e-book.
 */
interface EbookRepository
{
    /** @throws EbookIntrouvable */
    public function parIdentifiant(IdentifiantEbook $identifiantEbook): Ebook;
}
