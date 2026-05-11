<?php

declare(strict_types=1);

/**
 * Nome de arquivo SVG em /assets/card-brands/ por palavra-chave de bandeira.
 *
 * @return array<string, string>
 */
function payment_card_brand_filenames(): array
{
    return [
        'visa' => 'visa.svg',
        'mastercard' => 'mastercard.svg',
        'master' => 'mastercard.svg',
        'elo' => 'elo.svg',
        'hipercard' => 'hipercard.svg',
        'hiper' => 'hipercard.svg',
        'diners' => 'diners.svg',
        'american express' => 'others.svg',
        'amex' => 'others.svg',
    ];
}
