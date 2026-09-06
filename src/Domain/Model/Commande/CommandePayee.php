<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Bookshelf\Domain\Model\Common\Montant;
use DateTimeImmutable;

/** Domain event. Immuable, nomme au passe, ne contient que des objets de domaine. */
final readonly class CommandePayee
{
    public function __construct(
        public IdentifiantCommande $identifiantCommande,
        public Montant $total,
        public ReferenceDePaiement $reference,
        public DateTimeImmutable $payeeLe,
    ) {
    }
}
