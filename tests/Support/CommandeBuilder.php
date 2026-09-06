<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Support;

use Bookshelf\Domain\Model\Commande\Commande;
use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use Bookshelf\Domain\Model\Commande\Quantite;
use Bookshelf\Domain\Model\Commande\ReferenceDePaiement;
use Bookshelf\Domain\Model\Common\AdresseEmail;
use Bookshelf\Domain\Model\Common\CodePays;
use Bookshelf\Domain\Model\Common\Devise;
use Bookshelf\Domain\Model\Common\Montant;
use Bookshelf\Domain\Model\Common\TauxDeTva;

/**
 * Fourni. Un builder permet de ne mentionner dans un test QUE ce qui est particulier a
 * ce test. Tout le reste prend une valeur par defaut raisonnable.
 */
final class CommandeBuilder
{
    private IdentifiantCommande $identifiantCommande;
    private AdresseEmail $adresseEmail;
    private CodePays $pays;
    private TauxDeTva $tauxDeTva;
    /** @var array{IdentifiantEbook, Montant, Quantite}[] */
    private array $lignes = [];
    private bool $confirmee = false;
    private bool $payee = false;
    private bool $annulee = false;

    private function __construct()
    {
        $this->identifiantCommande = IdentifiantCommande::generer();
        $this->adresseEmail = AdresseEmail::depuisChaine('paul@exemple.fr');
        $this->pays = CodePays::depuisChaine('FR');
        $this->tauxDeTva = TauxDeTva::depuisPourcentage(20);
    }

    public static function creer(): self
    {
        return new self();
    }

    public function avecIdentifiant(IdentifiantCommande $identifiantCommande): self
    {
        $clone = clone $this;
        $clone->identifiantCommande = $identifiantCommande;

        return $clone;
    }

    public function avecTauxDeTva(int $pourcentage): self
    {
        $clone = clone $this;
        $clone->tauxDeTva = TauxDeTva::depuisPourcentage($pourcentage);

        return $clone;
    }

    public function avecLigne(int $prixUnitaireEnCentimes, int $quantite): self
    {
        $clone = clone $this;
        $clone->lignes[] = [
            IdentifiantEbook::generer(),
            Montant::depuisCentimes($prixUnitaireEnCentimes, Devise::EUR),
            Quantite::depuisEntier($quantite),
        ];

        return $clone;
    }

    public function confirmee(): self
    {
        $clone = $this->avecLigneParDefautSiVide();
        $clone->confirmee = true;

        return $clone;
    }

    public function payee(): self
    {
        $clone = $this->confirmee();
        $clone->payee = true;

        return $clone;
    }

    public function annulee(): self
    {
        $clone = $this->avecLigneParDefautSiVide();
        $clone->annulee = true;

        return $clone;
    }

    public function construire(): Commande
    {
        $commande = Commande::passer($this->identifiantCommande, $this->adresseEmail, $this->pays, $this->tauxDeTva);

        foreach ($this->lignes as [$identifiantEbook, $prixUnitaire, $quantite]) {
            $commande->ajouterLigne($identifiantEbook, $prixUnitaire, $quantite);
        }

        if ($this->confirmee) {
            $commande->confirmer();
        }

        if ($this->payee) {
            $commande->payer(ReferenceDePaiement::depuisChaine('PAY-TEST-001'));
        }

        if ($this->annulee) {
            $commande->annuler();
        }

        $commande->relacherEvenements();

        return $commande;
    }

    /** Comme `construire()`, mais sans vider les evenements enregistres. */
    public function construireEnGardantLesEvenements(): Commande
    {
        $commande = Commande::passer($this->identifiantCommande, $this->adresseEmail, $this->pays, $this->tauxDeTva);

        foreach ($this->lignes as [$identifiantEbook, $prixUnitaire, $quantite]) {
            $commande->ajouterLigne($identifiantEbook, $prixUnitaire, $quantite);
        }

        if ($this->confirmee) {
            $commande->confirmer();
        }

        if ($this->payee) {
            $commande->payer(ReferenceDePaiement::depuisChaine('PAY-TEST-001'));
        }

        if ($this->annulee) {
            $commande->annuler();
        }

        return $commande;
    }

    private function avecLigneParDefautSiVide(): self
    {
        return $this->lignes === [] ? $this->avecLigne(2500, 1) : clone $this;
    }
}
