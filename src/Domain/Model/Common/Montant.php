<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Common;

/**
 * CORRIGE. Le value object le plus utile du projet.
 *
 * Un value object est IMMUABLE : chaque operation retourne une NOUVELLE instance.
 * C'est le piege classique de cet atelier ; le test `le_montant_d_origine_n_est_pas_modifie`
 * est la pour ca.
 *
 * `plus()` et `estSuperieurA()` doivent refuser deux devises differentes :
 * levez `MontantsNonComparables::carDevisesDifferentes()`.
 */
final readonly class Montant
{
    private function __construct(
        private int $montantEnCentimes,
        private Devise $devise,
    ) {
    }

    public static function depuisCentimes(int $montantEnCentimes, Devise $devise): self
    {
        return new self($montantEnCentimes, $devise);
    }

    public static function zero(Devise $devise): self
    {
        return new self(0, $devise);
    }

    public function multipliePar(int $facteur): self
    {
        return new self($this->montantEnCentimes * $facteur, $this->devise);
    }

    public function plus(self $autre): self
    {
        $this->verifierMemeDevise($autre);

        return new self($this->montantEnCentimes + $autre->montantEnCentimes, $this->devise);
    }

    public function avecTva(TauxDeTva $taux): self
    {
        return new self($taux->appliquerA($this->montantEnCentimes), $this->devise);
    }

    public function estSuperieurA(self $autre): bool
    {
        $this->verifierMemeDevise($autre);

        return $this->montantEnCentimes > $autre->montantEnCentimes;
    }

    public function enCentimes(): int
    {
        return $this->montantEnCentimes;
    }

    public function devise(): Devise
    {
        return $this->devise;
    }

    public function formate(): string
    {
        return sprintf(
            '%s %s',
            number_format($this->montantEnCentimes / 100, 2, ',', ' '),
            $this->devise->symbole(),
        );
    }

    private function verifierMemeDevise(self $autre): void
    {
        if ($this->devise !== $autre->devise) {
            throw MontantsNonComparables::carDevisesDifferentes($this->devise, $autre->devise);
        }
    }
}
