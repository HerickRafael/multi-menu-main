<?php

declare(strict_types=1);

require_once __DIR__ . '/OrderItemData.php';
require_once __DIR__ . '/MoneyFormatter.php';

/**
 * DTO com todos os dados da tela de sucesso do checkout.
 *
 * Centraliza extração de campos, limpeza de strings, lógica de notas,
 * validação do WhatsApp e conversão float → centavos.
 * A view recebe apenas este objeto — sem arrays brutos.
 *
 * @property-read int          $orderId
 * @property-read int          $totalCents
 * @property-read int          $deliveryCents
 * @property-read int          $subtotalCents
 * @property-read int          $cashAmountCents
 * @property-read int          $cashChangeCents
 * @property-read string       $payment
 * @property-read string       $paymentType
 * @property-read string       $instructions
 * @property-read string       $address
 * @property-read string       $userNotes
 * @property-read string       $customer
 * @property-read string       $phone
 * @property-read string       $companyWhatsApp
 * @property-read string       $companyName
 * @property-read string       $companyLogo
 * @property-read string       $slug
 * @property-read OrderItemData[] $items
 */
final class CheckoutSuccessOrder
{
    /** @var int */
    public $orderId;

    /** @var int */
    public $totalCents;

    /** @var int */
    public $deliveryCents;

    /** @var int */
    public $subtotalCents;

    /** @var int */
    public $cashAmountCents;

    /** @var int */
    public $cashChangeCents;

    /** @var string */
    public $payment;

    /** @var string */
    public $paymentType;

    /** @var string */
    public $instructions;

    /** @var string */
    public $address;

    /** @var string */
    public $userNotes;

    /** @var string */
    public $customer;

    /** @var string */
    public $phone;

    /** @var string */
    public $companyWhatsApp;

    /** @var string */
    public $companyName;

    /** @var string */
    public $companyLogo;

    /** @var string */
    public $slug;

    /** @var OrderItemData[] */
    public $items;

    private function __construct(
        int    $orderId,
        float  $total,
        float  $delivery,
        float  $subtotal,
        string $payment,
        string $paymentType,
        string $instructions,
        string $address,
        string $userNotes,
        string $customer,
        string $phone,
        float  $cashAmount,
        float  $cashChange,
        string $companyWhatsApp,
        string $companyName,
        string $companyLogo,
        string $slug,
        array  $rawItems
    ) {
        $this->orderId         = $orderId;
        $this->payment         = $payment;
        $this->paymentType     = $paymentType;
        $this->instructions    = $instructions;
        $this->address         = $address;
        $this->userNotes       = $userNotes;
        $this->customer        = $customer;
        $this->phone           = $phone;
        $this->companyWhatsApp = $companyWhatsApp;
        $this->companyName     = $companyName;
        $this->companyLogo     = $companyLogo;
        $this->slug            = $slug;
        $this->totalCents      = (int) round($total      * 100);
        $this->deliveryCents   = (int) round($delivery   * 100);
        $this->subtotalCents   = (int) round($subtotal   * 100);
        $this->cashAmountCents = (int) round($cashAmount * 100);
        $this->cashChangeCents = (int) round($cashChange  * 100);
        $this->items           = array_map(
            static function (array $i): OrderItemData {
                return OrderItemData::fromArray($i);
            },
            $rawItems
        );
    }

    // ── Formatters ───────────────────────────────────────────────────────────

    public function formatTotal(): string
    {
        return MoneyFormatter::format($this->totalCents / 100);
    }

    public function formatDelivery(): string
    {
        return MoneyFormatter::format($this->deliveryCents / 100);
    }

    public function formatSubtotal(): string
    {
        return MoneyFormatter::format($this->subtotalCents / 100);
    }

    public function formatCashAmount(): string
    {
        return MoneyFormatter::format($this->cashAmountCents / 100);
    }

    public function formatCashChange(): string
    {
        return MoneyFormatter::format($this->cashChangeCents / 100);
    }

    // ── Predicados ───────────────────────────────────────────────────────────

    public function hasItems(): bool
    {
        return $this->items !== [];
    }

    public function hasPayment(): bool
    {
        return $this->payment !== '';
    }

    public function hasAddress(): bool
    {
        return $this->address !== '';
    }

    public function hasNotes(): bool
    {
        return $this->userNotes !== '';
    }

    public function hasCashPayment(): bool
    {
        return $this->cashAmountCents > 0;
    }

    public function isPix(): bool
    {
        return $this->paymentType === 'pix';
    }

    /**
     * WhatsApp válido: dígitos suficientes para DDI + DDD + número.
     * Evita gerar URL wa.me/ com número inválido ou vazio.
     */
    public function hasValidWhatsApp(): bool
    {
        $len = strlen($this->companyWhatsApp);
        return $len >= 10 && $len <= 15;
    }

    /**
     * URL base do cardápio da empresa.
     */
    public function baseLink(): string
    {
        $path = $this->slug !== '' ? $this->slug : '';
        return function_exists('base_url') ? base_url($path) : '#';
    }

    // ── Factory ──────────────────────────────────────────────────────────────

    /**
     * Cria um CheckoutSuccessOrder a partir dos arrays brutos da sessão.
     *
     * @param array<string, mixed> $company
     * @param array<string, mixed> $order
     */
    public static function fromArrays(array $company, array $order, string $slug): self
    {
        $notes     = trim((string) ($order['notes'] ?? ''));
        $userNotes = self::extractUserNotes($notes);

        $rawWhatsApp   = trim((string) ($company['whatsapp'] ?? ''));
        // preg_replace retorna ?string — coalescência protege contra null (PHP 7.4+)
        $cleanWhatsApp = preg_replace('/[^0-9]/', '', $rawWhatsApp) ?? '';

        $rawItems = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];

        return new self(
            (int)   ($order['order_id']              ?? 0),
            (float) ($order['total']                 ?? 0),
            (float) ($order['delivery_fee']          ?? 0),
            (float) ($order['subtotal']              ?? 0),
            trim((string) ($order['payment_method']      ?? '')),
            trim((string) ($order['payment_type']         ?? '')),
            trim((string) ($order['payment_instructions'] ?? '')),
            trim((string) ($order['address']              ?? '')),
            $userNotes,
            trim((string) ($order['customer_name']        ?? '')),
            trim((string) ($order['customer_phone']       ?? '')),
            (float) ($order['cash_amount'] ?? 0),
            (float) ($order['cash_change'] ?? 0),
            $cleanWhatsApp,
            trim((string) ($company['name'] ?? '')),
            trim((string) ($company['logo'] ?? '')),
            $slug,
            $rawItems
        );
    }

    // ── Helpers privados ─────────────────────────────────────────────────────

    /**
     * Remove linhas de sistema das observações, retornando só o texto do cliente.
     *
     * Corrige bug: preg_replace() retorna ?string; trim(null) lança TypeError no PHP 8+.
     * O operador ?? garante string não-nula em cada etapa.
     */
    private static function extractUserNotes(string $notes): string
    {
        $result = (string) (preg_replace('/^Pagamento:[^\n]*/mi',       '', $notes)   ?? $notes);
        $result = (string) (preg_replace('/^Troco para:[^\n]*/mi',      '', $result)  ?? $result);
        $result = (string) (preg_replace('/^Observa[çc][õo]es:\s*/mi', '', $result)  ?? $result);
        $result = (string) (preg_replace('/\n{2,}/',                "\n\n", $result)  ?? $result);
        return trim($result);
    }
}
