<?php

declare(strict_types=1);

// Compat partial: delega para o componente novo
// Espera $p definido no escopo com o seguinte shape:
//
//   Obrigatórias:
//     'id'                    int    — ID do produto
//     'name'                  string — Nome exibido no card
//     'price'                 float  — Preço base (R$)
//
//   Opcionais:
//     'image'                 string     — Caminho relativo da imagem (base_url aplicado internamente)
//     'description'           string     — Descrição curta do produto
//     'original_price'        float      — Preço antes de desconto (fallback: price)
//     'embedded_delivery_fee' float      — Taxa de entrega embutida no preço
//     'promo_price'           float|null — Preço promocional
//     'price_mode'            string     — 'fixed' | 'from' (padrão: 'fixed')
//     'promo_start_at'        string     — Datetime ISO; promo não ativa antes desta data
//     'promo_end_at'          string     — Datetime ISO; promo não ativa após esta data

$_cardPath = __DIR__ . '/components/_card.php';

if (isset($p) && is_array($p)) {
    if (!file_exists($_cardPath)) {
        throw new \RuntimeException(
            'partials_card.php: componente não encontrado em ' . $_cardPath
        );
    }
    $company = isset($company) && is_array($company) ? $company : null;
    $slug = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');

    (static function (string $path, array $p, ?array $company, string $slug): void {
        include $path;
    })($_cardPath, $p, $company, $slug);
} else {
    trigger_error(
        'partials_card.php: $p ausente ou inválido (esperado array).',
        E_USER_WARNING
    );
}
