<?php

declare(strict_types=1);

namespace Bookshelf\Application\PayerCommande;

use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Bookshelf\Domain\Model\Commande\ReferenceDePaiement;

/**
 * Command DTO. Aucune date : le client ne decide pas quand il paie, c'est l'application
 * qui le constate. Une date fournie par l'appelant serait une donnee CONTEXTUELLE dont il
 * ne faut jamais lui confier la responsabilite.
 */
final readonly class PayerCommande
{
    public function __construct(
        public string $identifiantCommande,
        public string $referenceDePaiement,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function depuisDonneesRequete(array $data): self
    {
        return new self(
            isset($data['order_id']) ? (string) $data['order_id'] : '',
            isset($data['payment_reference']) ? (string) $data['payment_reference'] : '',
        );
    }

    public function identifiantCommande(): IdentifiantCommande
    {
        return IdentifiantCommande::depuisChaine($this->identifiantCommande);
    }

    public function referenceDePaiement(): ReferenceDePaiement
    {
        return ReferenceDePaiement::depuisChaine($this->referenceDePaiement);
    }
}
