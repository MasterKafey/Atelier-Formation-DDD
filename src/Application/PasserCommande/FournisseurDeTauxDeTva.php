<?php

declare(strict_types=1);

namespace Bookshelf\Application\PasserCommande;

use Bookshelf\Domain\Model\Common\CodePays;
use Bookshelf\Domain\Model\Common\TauxDeTva;

/**
 * Port sortant : « pour determiner le taux de TVA applicable ».
 *
 * C'est une COUCHE ANTICORRUPTION. Le vocabulaire du fournisseur (`TBE`, `filter`,
 * `rate_type`, `electronic`) s'arrete a la frontiere de l'infrastructure : il n'apparait
 * nulle part au-dela de `Infrastructure\VatApi`.
 *
 * Test de l'abstraction : pourriez-vous en ecrire une implementation qui lit un fichier
 * JSON local, sans que le nom de la methode devienne absurde ? Oui. C'est donc une vraie
 * abstraction, et non un deguisement.
 *
 * Notez qu'elle n'est PAS plus generique que necessaire : une methode
 * `vatRateForCountry(CodePays, string $filter)` paraitrait plus reutilisable, mais son
 * parametre `$filter` n'a de sens que pour vatapi.com. L'abstraction fuirait.
 */
interface FournisseurDeTauxDeTva
{
    public function tauxDeTvaPourEbooksDansLePays(CodePays $pays): TauxDeTva;
}
