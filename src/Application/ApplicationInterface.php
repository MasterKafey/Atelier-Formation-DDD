<?php

declare(strict_types=1);

namespace Bookshelf\Application;

use Bookshelf\Application\CommanderLivrePapier\CommanderLivrePapier;
use Bookshelf\Application\ListerEbooksDisponibles\Ebook;
use Bookshelf\Application\PasserCommande\PasserCommande;
use Bookshelf\Application\PayerCommande\PayerCommande;
use Bookshelf\Domain\Model\Commande\IdentifiantCommande;

/**
 * Le port ENTRANT de l'hexagone : l'API complete de l'application.
 *
 * Les adaptateurs entrants (controleur web, commande CLI, code de test) UTILISENT cette
 * interface ; ils ne l'implementent pas. C'est l'asymetrie entrant / sortant.
 *
 * Lisez-la comme la table des matieres fonctionnelle du systeme : ce qu'un acteur peut
 * FAIRE et ce qu'il peut APPRENDRE. Aucun projet organise par le framework ne sait
 * produire cette liste.
 *
 * Si elle finit par compter cinquante methodes, ce n'est pas un defaut de l'interface :
 * c'est le signal que l'application fait trop de choses et devrait etre decoupee en
 * modules. Ne masquez pas ce signal derriere un bus generique.
 */
interface ApplicationInterface
{
    public function passerCommande(PasserCommande $intention): IdentifiantCommande;

    public function payerCommande(PayerCommande $intention): void;

    /** @throws \Bookshelf\Application\CommanderLivrePapier\CommandeDeLivrePapierImpossible */
    public function commanderLivrePapier(CommanderLivrePapier $intention): void;

    /** @return Ebook[] */
    public function listerEbooksDisponibles(): array;
}
