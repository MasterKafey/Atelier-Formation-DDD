<?php

declare(strict_types=1);

namespace Bookshelf\Application\CommanderLivrePapier;

use Bookshelf\Domain\Model\Commande\IdentifiantLivre;

/**
 * Port sortant de LECTURE : « pour connaitre le nombre d'exemplaires disponibles ».
 *
 * ATTENTION, et c'est le point le plus important de ce palier : cette methode est une
 * FONCTION IMPURE. Elle ne rend pas la meme reponse a deux instants differents. Sa
 * reponse n'est jamais fausse — elle est juste au moment du calcul — mais elle change
 * tout le temps.
 *
 * On ne peut donc pas batir une VALIDATION dessus, seulement une verification de
 * meilleur effort. Voir `docs/palier-b-erreur-metier.md`.
 */
interface NiveauxDeStock
{
    public function nombreExemplairesDisponibles(IdentifiantLivre $identifiantLivre): int;
}
