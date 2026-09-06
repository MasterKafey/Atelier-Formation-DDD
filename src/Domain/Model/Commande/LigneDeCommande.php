<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Bookshelf\Domain\Model\Common\Montant;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entite FILLE de l'agregat. On n'y accede que par la racine.
 *
 * Deux concessions faites a l'ORM, et il faut savoir les nommer :
 *
 *   1. Une CLE DE SUBSTITUTION auto-incrementee. Le metier n'a aucun besoin d'identifier
 *      une ligne de commande ; c'est Doctrine qui exige une identite pour toute entite.
 *      On la garde donc privee et sans accesseur : elle n'existe que pour la persistance.
 *
 *   2. Une association RETOUR vers `Commande`, exigee par le `mappedBy` du cote racine.
 *      Elle ne sert jamais au metier : aucune methode ne l'utilise.
 *
 * En revanche, ce qui compte est intact : la ligne reference l'e-book par son
 * IDENTIFIANT, jamais par une reference a l'entite `Ebook`. Sinon la commande aurait
 * acces a `changePrice()` et `hide()` en plein tunnel de commande, et l'on pourrait
 * naviguer d'agregat en agregat.
 */
#[ORM\Entity]
#[ORM\Table(name: 'order_lines')]
class LigneDeCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'id')]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'lignes')]
        #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false)]
        private Commande $commande,

        #[ORM\Column(type: 'ebook_id', name: 'ebook_id')]
        private IdentifiantEbook $identifiantEbook,

        #[ORM\Column(type: 'money', name: 'unit_price')]
        private Montant $prixUnitaire,

        #[ORM\Column(type: 'quantity', name: 'quantity')]
        private Quantite $quantite,
    ) {
    }

    public function sousTotal(): Montant
    {
        return $this->prixUnitaire->multipliePar($this->quantite->enEntier());
    }

    public function identifiantEbook(): IdentifiantEbook
    {
        return $this->identifiantEbook;
    }
}
