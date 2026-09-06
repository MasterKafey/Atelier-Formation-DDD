<?php

declare(strict_types=1);

namespace Bookshelf\Application\CommanderLivrePapier;

/**
 * Le stock ne peut PAS etre verifie par l'entite : une commande ne voit qu'elle-meme et
 * ignore tout du stock. La verification revient donc au service applicatif — c'est le
 * troisieme emplacement de validation vu au jour 2, apres le value object et l'entite.
 *
 * Le service ne rend pas d'erreur : il en LEVE une, marquee `UserErrorMessage`. Il ne
 * sait pas a qui il parle, et n'a donc pas a choisir entre un message de formulaire, un
 * code JSON et une ligne sur stderr.
 */
final readonly class CommanderLivrePapierService
{
    public function __construct(
        private NiveauxDeStock $niveauxDeStock,
        private Stock $stock,
    ) {
    }

    /** @throws CommandeDeLivrePapierImpossible */
    public function __invoke(CommanderLivrePapier $intention): void
    {
        $disponibles = $this->niveauxDeStock->nombreExemplairesDisponibles($intention->identifiantLivre());

        if ($disponibles < $intention->quantite) {
            throw CommandeDeLivrePapierImpossible::carStockInsuffisant($disponibles, $intention->quantite);
        }

        $this->stock->reserver($intention->identifiantLivre(), $intention->quantite);
    }
}
