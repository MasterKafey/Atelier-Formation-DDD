<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\VatApi;

/**
 * Facade : « definit une interface de plus haut niveau qui rend le sous-systeme plus
 * simple a utiliser ». C'est ce qu'un editeur d'API appelle un SDK.
 *
 * La cle d'API est un argument de CONSTRUCTEUR (valeur de configuration) ; les autres
 * parametres sont des arguments de METHODE (donnees du travail).
 */
final readonly class VatApi
{
    public function __construct(
        private VatApiTransport $transport,
        private string $apiKey,
    ) {
    }

    public function vatRateCheck(
        RateType $rateType,
        string $codePays,
        ?string $filter,
    ): VatRateCheckResult {
        return new VatRateCheckResult($this->transport->get(
            '/vat-rate-check',
            [
                'rate_type' => $rateType->value,
                'country_code' => $codePays,
                'filter' => $filter,
            ],
            $this->apiKey,
        ));
    }
}
