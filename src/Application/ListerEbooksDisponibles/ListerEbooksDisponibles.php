<?php

declare(strict_types=1);

namespace Bookshelf\Application\ListerEbooksDisponibles;

/**
 * Port entrant de LECTURE : « pour lister les e-books disponibles ».
 *
 * Test pour savoir si une fonctionnalite de lecture merite d'etre du code coeur : si
 * l'application devenait une application en ligne de commande, ce besoin existerait-il
 * encore ? Lister le catalogue avant d'acheter : evidemment. Donc ce n'est pas juste un
 * controleur avec une requete SQL, c'est un cas d'usage.
 */
interface ListerEbooksDisponibles
{
    /** @return Ebook[] */
    public function listerTout(): array;
}
