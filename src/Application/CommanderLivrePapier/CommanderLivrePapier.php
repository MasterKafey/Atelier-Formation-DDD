<?php

declare(strict_types=1);

namespace Bookshelf\Application\CommanderLivrePapier;

use Bookshelf\Domain\Model\Commande\IdentifiantLivre;

/** Command DTO du cas d'usage « commander un livre papier ». */
final readonly class CommanderLivrePapier
{
    public function __construct(
        public string $identifiantLivre,
        public int $quantite,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function depuisDonneesRequete(array $data): self
    {
        return new self(
            isset($data['book_id']) ? (string) $data['book_id'] : '',
            isset($data['quantity']) ? (int) $data['quantity'] : 0,
        );
    }

    public function identifiantLivre(): IdentifiantLivre
    {
        return IdentifiantLivre::depuisChaine($this->identifiantLivre);
    }
}
