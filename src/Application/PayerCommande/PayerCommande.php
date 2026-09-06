<?php

declare(strict_types=1);

namespace Bookshelf\Application\PayerCommande;

use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Bookshelf\Domain\Model\Commande\ReferenceDePaiement;

/**
 * Command DTO du cas d'usage « payer une commande ».
 *
 * Types primitifs en entree, value objects en sortie : n'importe quel client peut en
 * construire un, et le service applicatif n'a pas a etre une liste de conversions.
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
