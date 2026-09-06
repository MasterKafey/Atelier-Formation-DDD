<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Webmozart\Assert\Assert;

/** Reference fournie par le prestataire de paiement. Fourni. */
final readonly class ReferenceDePaiement
{
    private function __construct(private string $reference)
    {
    }

    public static function depuisChaine(string $reference): self
    {
        Assert::stringNotEmpty(trim($reference), 'Reference de paiement vide');

        return new self(trim($reference));
    }

    public function enChaine(): string
    {
        return $this->reference;
    }
}
