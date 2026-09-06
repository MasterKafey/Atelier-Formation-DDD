<?php

declare(strict_types=1);

namespace Bookshelf\Tests\UseCase;

use Bookshelf\Application\PasserCommande\PasserCommande;
use Bookshelf\Application\PayerCommande\PayerCommande;
use Bookshelf\Domain\Model\Commande\IdentifiantCommande;
use Bookshelf\Domain\Model\Commande\PaiementImpossible;
use Bookshelf\Tests\Support\TestServiceContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Le cas d'usage « payer une commande », exerce de bout en bout dans l'hexagone interne.
 *
 * Ni base de donnees, ni serveur de paiement : `ReferenceDePaiement` est la trace d'un
 * paiement deja encaisse ailleurs. Notre systeme ne fait que constater.
 *
 * PALIER D : les deux derniers tests portent sur une regle qui s'etale sur deux jours, et
 * ils s'executent en une milliseconde. Le temps y est une DONNEE, pas un effet de bord.
 * Personne n'attend, personne ne dort, et le resultat sera le meme dans dix ans.
 */
final class PayerCommandeTest extends TestCase
{
    #[Test]
    public function une_commande_confirmee_peut_etre_payee(): void
    {
        $container = new TestServiceContainer();
        $identifiantCommande = $this->uneCommande($container);

        $container->application()->payerCommande(new PayerCommande($identifiantCommande->enChaine(), 'PAY-001'));

        $this->expectException(PaiementImpossible::class);

        // La preuve que l'etat a bien change : on ne peut pas payer deux fois.
        $container->application()->payerCommande(new PayerCommande($identifiantCommande->enChaine(), 'PAY-002'));
    }

    #[Test]
    public function une_commande_peut_etre_payee_dans_les_quarante_huit_heures(): void
    {
        $container = new TestServiceContainer();
        $identifiantCommande = $this->uneCommande($container);

        $container->horloge()->modify('+47 hours');

        $container->application()->payerCommande(new PayerCommande($identifiantCommande->enChaine(), 'PAY-003'));

        self::assertNotNull(
            $container->commandeRepository()->parIdentifiant($identifiantCommande)->payeeLe(),
            'La date de paiement doit etre enregistree.',
        );
    }

    #[Test]
    public function une_commande_non_payee_depuis_plus_de_quarante_huit_heures_expire(): void
    {
        $container = new TestServiceContainer();
        $identifiantCommande = $this->uneCommande($container);

        $container->horloge()->modify('+49 hours');

        $this->expectException(PaiementImpossible::class);

        $container->application()->payerCommande(new PayerCommande($identifiantCommande->enChaine(), 'PAY-004'));
    }

    private function uneCommande(TestServiceContainer $container): IdentifiantCommande
    {
        $identifiantEbook = $container->catalogue()->ajouter('Architecture hexagonale', 2500);

        return $container->application()->passerCommande(
            new PasserCommande($identifiantEbook->enChaine(), 1, 'paul@exemple.fr', 'FR'),
        );
    }
}
