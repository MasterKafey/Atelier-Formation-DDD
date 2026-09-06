<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Domain;

use Bookshelf\Domain\Model\Commande\AjoutDeLigneImpossible;
use Bookshelf\Domain\Model\Commande\AnnulationImpossible;
use Bookshelf\Domain\Model\Commande\CommandeAnnulee;
use Bookshelf\Domain\Model\Commande\CommandePassee;
use Bookshelf\Domain\Model\Commande\CommandePayee;
use Bookshelf\Domain\Model\Commande\ConfirmationImpossible;
use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use Bookshelf\Domain\Model\Commande\PaiementImpossible;
use Bookshelf\Domain\Model\Commande\Quantite;
use Bookshelf\Domain\Model\Commande\ReferenceDePaiement;
use Bookshelf\Domain\Model\Common\Devise;
use Bookshelf\Domain\Model\Common\Montant;
use Bookshelf\Tests\Support\CommandeBuilder;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 13 des 17 tests de l'atelier 2.
 *
 * Un test par transition d'etat, Y COMPRIS les transitions interdites : c'est le
 * meilleur usage des tests unitaires sur une entite. Ajoutez-en au moins 4 autres.
 */
final class CommandeTest extends TestCase
{
    #[Test]
    public function on_ne_peut_pas_confirmer_une_commande_sans_ligne(): void
    {
        $commande = CommandeBuilder::creer()->construire();

        $this->expectException(ConfirmationImpossible::class);

        $commande->confirmer();
    }

    #[Test]
    public function confirmer_une_commande_enregistre_l_evenement_correspondant(): void
    {
        $commande = CommandeBuilder::creer()->avecLigne(2500, 1)->construireEnGardantLesEvenements();

        $commande->confirmer();

        self::assertContainsOnlyInstancesOf(CommandePassee::class, $commande->relacherEvenements());
    }

    #[Test]
    public function le_total_hors_taxe_est_la_somme_des_lignes(): void
    {
        $commande = CommandeBuilder::creer()
            ->avecLigne(2500, 2)
            ->avecLigne(1000, 1)
            ->construire();

        self::assertEquals(Montant::depuisCentimes(6000, Devise::EUR), $commande->totalHt());
    }

    #[Test]
    public function le_total_ttc_applique_la_tva_du_pays_de_l_acheteur(): void
    {
        $commande = CommandeBuilder::creer()
            ->avecTauxDeTva(20)
            ->avecLigne(2500, 2)
            ->construire();

        self::assertEquals(Montant::depuisCentimes(5000, Devise::EUR), $commande->totalHt());
        self::assertEquals(Montant::depuisCentimes(6000, Devise::EUR), $commande->totalTtc());
    }

    #[Test]
    public function payer_une_commande_enregistre_l_evenement_correspondant(): void
    {
        $commande = CommandeBuilder::creer()->confirmee()->construire();

        $commande->payer(ReferenceDePaiement::depuisChaine('PAY-001'), $this->dansLesDelais());

        self::assertContainsOnlyInstancesOf(CommandePayee::class, $commande->relacherEvenements());
    }

    #[Test]
    public function annuler_une_commande_enregistre_l_evenement_correspondant(): void
    {
        $commande = CommandeBuilder::creer()->confirmee()->construire();

        $commande->annuler();

        self::assertContainsOnlyInstancesOf(CommandeAnnulee::class, $commande->relacherEvenements());
    }

    #[Test]
    public function une_commande_payee_ne_peut_plus_etre_annulee(): void
    {
        $commande = CommandeBuilder::creer()->payee()->construire();

        $this->expectException(AnnulationImpossible::class);

        $commande->annuler();
    }

    #[Test]
    public function une_commande_annulee_ne_peut_plus_etre_payee(): void
    {
        $commande = CommandeBuilder::creer()->annulee()->construire();

        $this->expectException(PaiementImpossible::class);

        $commande->payer(ReferenceDePaiement::depuisChaine('PAY-002'), $this->dansLesDelais());
    }

    #[Test]
    public function on_n_ajoute_pas_de_ligne_a_une_commande_annulee(): void
    {
        $commande = CommandeBuilder::creer()->annulee()->construire();

        $this->expectException(AjoutDeLigneImpossible::class);

        $commande->ajouterLigne(IdentifiantEbook::generer(), Montant::depuisCentimes(2500, Devise::EUR), Quantite::depuisEntier(1));
    }

    #[Test]
    public function on_n_ajoute_pas_de_ligne_a_une_commande_confirmee(): void
    {
        $commande = CommandeBuilder::creer()->confirmee()->construire();

        $this->expectException(AjoutDeLigneImpossible::class);

        $commande->ajouterLigne(IdentifiantEbook::generer(), Montant::depuisCentimes(9900, Devise::EUR), Quantite::depuisEntier(1));
    }

    #[Test]
    public function une_commande_non_confirmee_ne_peut_pas_etre_payee(): void
    {
        $commande = CommandeBuilder::creer()->avecLigne(2500, 1)->construire();

        $this->expectException(PaiementImpossible::class);

        $commande->payer(ReferenceDePaiement::depuisChaine('PAY-005'), $this->dansLesDelais());
    }

    #[Test]
    public function une_commande_annulee_ne_peut_plus_etre_annulee(): void
    {
        $commande = CommandeBuilder::creer()->annulee()->construire();

        $this->expectException(AnnulationImpossible::class);

        $commande->annuler();
    }

    #[Test]
    public function relacher_les_evenements_vide_la_liste(): void
    {
        $commande = CommandeBuilder::creer()->confirmee()->construireEnGardantLesEvenements();

        self::assertNotEmpty($commande->relacherEvenements());
        self::assertEmpty($commande->relacherEvenements(), 'Un evenement ne doit etre relache qu\'une fois.');
    }

    /*
     * PALIER D. Ces deux tests ne connaissent aucune horloge : les dates sont des
     * arguments, donc de simples valeurs. C'est ce qui les garde instantanes et
     * parfaitement reproductibles.
     */

    #[Test]
    public function une_commande_payee_dans_les_delais_enregistre_sa_date_de_paiement(): void
    {
        $commande = CommandeBuilder::creer()->confirmee()->construire();

        $commande->payer(ReferenceDePaiement::depuisChaine('PAY-003'), $this->dansLesDelais());

        self::assertEquals($this->dansLesDelais(), $commande->payeeLe());
    }

    #[Test]
    public function une_commande_ne_peut_plus_etre_payee_apres_quarante_huit_heures(): void
    {
        $commande = CommandeBuilder::creer()->confirmee()->construire();

        $this->expectException(PaiementImpossible::class);

        $commande->payer(ReferenceDePaiement::depuisChaine('PAY-004'), $this->horsDelais());
    }

    private function dansLesDelais(): DateTimeImmutable
    {
        return new DateTimeImmutable(CommandeBuilder::PASSEE_LE . ' +47 hours');
    }

    private function horsDelais(): DateTimeImmutable
    {
        return new DateTimeImmutable(CommandeBuilder::PASSEE_LE . ' +49 hours');
    }
}
