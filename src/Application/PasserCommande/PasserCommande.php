<?php

declare(strict_types=1);

namespace Bookshelf\Application\PasserCommande;

use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use Bookshelf\Domain\Model\Common\AdresseEmail;
use Bookshelf\Domain\Model\Common\CodePays;

/**
 * Command DTO. « Command » n'a rien a voir avec la ligne de commande, ni avec le pattern
 * Command du GoF : c'est un objet qui represente une INTENTION utilisateur.
 *
 * Les proprietes sont des types PRIMITIFS : n'importe quel client peut en construire un.
 * Les accesseurs rendent des value objects, ce qui evite au service applicatif d'etre
 * une longue liste de conversions.
 */
final readonly class PasserCommande
{
    public function __construct(
        public string $identifiantEbook,
        public int $quantite,
        public string $adresseEmail,
        public string $codePays,
    ) {
    }

    /**
     * Ne leve PAS d'exception sur une donnee absente : on se contente de donner une forme
     * et un type. La validation viendra plus loin, dans les value objects.
     *
     * @param array<string, mixed> $data
     */
    public static function depuisDonneesRequete(array $data): self
    {
        return new self(
            isset($data['ebook_id']) ? (string) $data['ebook_id'] : '',
            isset($data['quantity']) ? (int) $data['quantity'] : 0,
            isset($data['email_address']) ? (string) $data['email_address'] : '',
            isset($data['country_code']) ? (string) $data['country_code'] : '',
        );
    }

    public function identifiantEbook(): IdentifiantEbook
    {
        return IdentifiantEbook::depuisChaine($this->identifiantEbook);
    }

    public function adresseEmail(): AdresseEmail
    {
        return AdresseEmail::depuisChaine($this->adresseEmail);
    }

    public function codePays(): CodePays
    {
        return CodePays::depuisChaine($this->codePays);
    }
}
