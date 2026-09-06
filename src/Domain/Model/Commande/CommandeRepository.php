<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

/**
 * Port sortant : « pour enregistrer une commande ».
 *
 * L'interface vit dans le Domaine, ses implementations dans l'Infrastructure. C'est la
 * regle de dependance : le domaine declare ce dont il a besoin, l'infrastructure fournit.
 *
 * Le contrat, que la signature ne peut pas exprimer : si vous enregistrez une commande
 * avec un identifiant donne, vous pouvez a tout moment en recuperer un objet equivalent
 * avec ce meme identifiant. Vous l'ecrirez sous forme de test de contrat a l'atelier 3.
 */
interface CommandeRepository
{
    public function prochainIdentifiant(): IdentifiantCommande;

    public function enregistrer(Commande $commande): void;

    /** @throws CommandeIntrouvable */
    public function parIdentifiant(IdentifiantCommande $identifiantCommande): Commande;
}
