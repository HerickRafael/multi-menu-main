<?php

declare(strict_types=1);

namespace App\Services\IFood;

/**
 * Centraliza URLs e endpoints da API do iFood.
 *
 * Embora hoje a API de produção e a de homologação usem a mesma base URL
 * (`merchant-api.ifood.com.br`) e a separação seja feita por credenciais/merchant,
 * mantemos o switch por env aqui para futuro-proofing caso o iFood publique
 * URLs dedicadas (já aconteceu com a v1 do catalog).
 *
 * Cada constante MODULE_* nomeia o módulo para fins de logging/observability.
 */
final class IFoodEndpoints
{
    public const ENV_SANDBOX = 'sandbox';
    public const ENV_PRODUCTION = 'production';

    private const BASE_URL_PRODUCTION = 'https://merchant-api.ifood.com.br';
    private const BASE_URL_SANDBOX = 'https://merchant-api.ifood.com.br';

    // Nomes de módulo (usados no campo ifood_api_logs.module)
    public const MODULE_AUTH = 'auth';
    public const MODULE_ORDER = 'order';
    public const MODULE_EVENTS = 'events';
    public const MODULE_CATALOG = 'catalog';
    public const MODULE_REVIEW = 'review';
    public const MODULE_SHIPPING = 'shipping';
    public const MODULE_MERCHANT = 'merchant';
    public const MODULE_LOGISTICS = 'logistics';

    public static function baseUrl(string $environment): string
    {
        return $environment === self::ENV_SANDBOX
            ? self::BASE_URL_SANDBOX
            : self::BASE_URL_PRODUCTION;
    }

    public static function normalizeEnvironment(?string $env): string
    {
        $env = strtolower(trim((string)$env));
        return $env === self::ENV_SANDBOX ? self::ENV_SANDBOX : self::ENV_PRODUCTION;
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    public static function authToken(string $env): string
    {
        return self::baseUrl($env) . '/authentication/v1.0/oauth/token';
    }

    // ── Order ────────────────────────────────────────────────────────────────

    public static function orderEventsPolling(string $env): string
    {
        return self::baseUrl($env) . '/order/v1.0/events:polling';
    }

    public static function orderEventsAcknowledgment(string $env): string
    {
        return self::baseUrl($env) . '/order/v1.0/events/acknowledgment';
    }

    public static function order(string $env, string $orderId): string
    {
        return self::baseUrl($env) . '/order/v1.0/orders/' . rawurlencode($orderId);
    }

    public static function orderAction(string $env, string $orderId, string $action): string
    {
        // actions: confirm, readyToPickup, dispatch, requestCancellation, requestDriver
        return self::order($env, $orderId) . '/' . $action;
    }

    public static function orderCancellationReasons(string $env, string $orderId): string
    {
        return self::order($env, $orderId) . '/cancellationReasons';
    }

    // ── Catalog (estoque/preço) ──────────────────────────────────────────────

    public static function catalogItemStatus(string $env, string $merchantId, string $itemId): string
    {
        return self::baseUrl($env)
            . '/catalog/v2.0/merchants/' . rawurlencode($merchantId)
            . '/items/' . rawurlencode($itemId) . '/status';
    }

    public static function catalogItemPrice(string $env, string $merchantId, string $itemId): string
    {
        return self::baseUrl($env)
            . '/catalog/v2.0/merchants/' . rawurlencode($merchantId)
            . '/items/' . rawurlencode($itemId) . '/price';
    }

    public static function catalogItemInventory(string $env, string $merchantId, string $itemId): string
    {
        return self::baseUrl($env)
            . '/catalog/v2.0/merchants/' . rawurlencode($merchantId)
            . '/items/' . rawurlencode($itemId) . '/inventory';
    }

    // ── Reviews ──────────────────────────────────────────────────────────────

    public static function reviews(string $env, string $merchantId): string
    {
        return self::baseUrl($env) . '/review/v1.0/merchants/' . rawurlencode($merchantId) . '/reviews';
    }

    // ── Shipping (pedidos externos com logística iFood) ──────────────────────

    public static function shippingQuote(string $env): string
    {
        return self::baseUrl($env) . '/shipping/v1.0/quote';
    }

    public static function shippingOrders(string $env): string
    {
        return self::baseUrl($env) . '/shipping/v1.0/orders';
    }

    public static function shippingOrder(string $env, string $orderId): string
    {
        return self::shippingOrders($env) . '/' . rawurlencode($orderId);
    }

    // ── Merchant ─────────────────────────────────────────────────────────────

    public static function merchantStatus(string $env, string $merchantId): string
    {
        return self::baseUrl($env) . '/merchant/v1.0/merchants/' . rawurlencode($merchantId) . '/status';
    }
}
