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
use DateTimeImmutable;

/**
 * Un builder permet de ne mentionner dans un test QUE ce qui est particulier a ce test.
 * Tout le reste prend une valeur par defaut raisonnable.
 *
 * PALIER D : les dates sont des valeurs FIXES, pas `new DateTimeImmutable('now')`. Un
 * builder de test qui lit l'horloge systeme rendrait toute la suite non deterministe, et
 * produirait ces echecs du vendredi soir que personne n'arrive a reproduire le lundi.
 */
final class CommandeBuilder
{
    public const PASSEE_LE = '2026-03-01 10:00:00';

    private IdentifiantCommande $identifiantCommande;
    private AdresseEmail $adresseEmail;
    private CodePays $pays;
    private TauxDeTva $tauxDeTva;
    private DateTimeImmutable $passeeLe;
    private ?DateTimeImmutable $payeeLe = null;
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
        $this->passeeLe = new DateTimeImmutable(self::PASSEE_LE);
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

    public function passeeLe(string $date): self
    {
        $clone = clone $this;
        $clone->passeeLe = new DateTimeImmutable($date);

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

    /** Payee dans les delais, sauf si vous precisez une autre date. */
    public function payee(?string $payeeLe = null): self
    {
        $clone = $this->confirmee();
        $clone->payee = true;
        $clone->payeeLe = new DateTimeImmutable($payeeLe ?? self::PASSEE_LE);

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
        $commande = $this->construireEnGardantLesEvenements();
        $commande->relacherEvenements();

        return $commande;
    }

    /** Comme `construire()`, mais sans vider les evenements enregistres. */
    public function construireEnGardantLesEvenements(): Commande
    {
        $commande = Commande::passer(
            $this->identifiantCommande,
            $this->adresseEmail,
            $this->pays,
            $this->tauxDeTva,
            $this->passeeLe,
        );

        foreach ($this->lignes as [$identifiantEbook, $prixUnitaire, $quantite]) {
            $commande->ajouterLigne($identifiantEbook, $prixUnitaire, $quantite);
        }

        if ($this->confirmee) {
            $commande->confirmer();
        }

        if ($this->payee) {
            $commande->payer(
                ReferenceDePaiement::depuisChaine('PAY-TEST-001'),
                $this->payeeLe ?? $this->passeeLe,
            );
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
