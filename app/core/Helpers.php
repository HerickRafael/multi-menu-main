<?php

declare(strict_types=1);

// Incluir helpers centralizados
require_once __DIR__ . '/../core/CommonHelpers.php';
require_once __DIR__ . '/../helpers/lazy_loading_helper.php';

function config($key = null)
{
    static $cfg = null;

    if (!$cfg) {
        $cfg = require __DIR__ . '/../config/app.php';
    }

    return $key ? ($cfg[$key] ?? null) : $cfg;
}

function is_new_product(array $p): bool
{
    $date = $p['created_at'] ?? $p['date'] ?? null;

    if (!$date) {
        return false;
    }

    $created = strtotime($date);

    if ($created === false) {
        return false;
    }

    return $created > (time() - 7 * 24 * 60 * 60);
}

if (!function_exists('normalizePhone')) {
    /**
     * Normaliza qualquer variante de telefone brasileiro para formato canônico E.164 (apenas dígitos).
     * Aceita: 51920017687, 051920017687, +55 51 92001-7687, 5551920017687, etc.
     * Retorna sempre: 5551920017687 (sem +, sem espaços, com código do país 55).
     *
     * @param string $phone Número em qualquer formato
     * @return string Número normalizado (apenas dígitos, com 55)
     */
    function normalizePhone(string $phone): string
    {
        // 1. Remover tudo que não é dígito
        $phone = preg_replace('/\D/', '', $phone);

        if ($phone === '') {
            return '';
        }

        // 2. Remover zeros à esquerda (ex: 051920017687 → 51920017687)
        $phone = ltrim($phone, '0');

        // 3. Se ficou com mais de 13 dígitos e começa com 55, truncar à direita
        //    (ex: 555551920017687 — duplo 55 colado)
        if (substr($phone, 0, 2) === '55' && strlen($phone) > 13) {
            $phone = substr($phone, -13);
        }

        // 4. Adicionar código do país 55 se ausente
        //    Números com ≤11 dígitos são sempre sem DDI — inclusive DDD 55 (RS),
        //    que sem tratamento seria confundido com o código do país.
        //    Exemplo: (55) 99200-7306 → "55992007306" (11 dígitos) → deve virar "5555992007306"
        if (strlen($phone) <= 11 || substr($phone, 0, 2) !== '55') {
            $phone = '55' . $phone;
        }

        return $phone;
    }
}

if (!function_exists('normalize_whatsapp_e164')) {
    /**
     * Alias de normalizePhone() para compatibilidade retroativa.
     *
     * @param string $raw
     * @param string $defaultCountry (ignorado — sempre usa 55)
     * @return string
     */
    function normalize_whatsapp_e164(string $raw, string $defaultCountry = '55'): string
    {
        return normalizePhone($raw);
    }
}