<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\VatApi;

/**
 * Vocabulaire de vatapi.com. Il ne doit JAMAIS sortir de ce namespace : c'est ce que
 * verifie le test `le_vocabulaire_de_vatapi_ne_sort_pas_de_l_infrastructure`.
 */
enum RateType: string
{
    case Goods = 'GOODS';

    /** Telecommunications, broadcasting and electronic services. */
    case Tbe = 'TBE';
}
