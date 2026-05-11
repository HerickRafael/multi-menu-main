<?php

declare(strict_types=1);

require_once __DIR__ . '/MoneyFormatter.php';

/**
 * DTO para um item do pedido na tela de sucesso.
 *
 * Converte arrays brutos em tipos seguros.
 * Valores monetários armazenados em centavos (int) — sem erros de float.
 *
 * @property-read string     $name
 * @property-read int        $quantity
 * @property-read int        $unitPriceCents
 * @property-read int        $lineTotalCents
 * @property-read int        $customExtrasCents
 * @property-read array|null $customization
 * @property-read array|null $combo
 * @property-read array      $componentCustomizations
 */
final class OrderItemData
{
    /** @var string */
    public $name;

    /** @var int */
    public $quantity;

    /** @var int */
    public $unitPriceCents;

    /** @var int */
    public $lineTotalCents;

    /** @var int */
    public $customExtrasCents;

    /** @var array|null */
    public $customization;

    /** @var array|null */
    public $combo;

    /** @var array */
    public $componentCustomizations;

    private function __construct(
        string $name,
        int    $quantity,
        float  $unitPrice,
        float  $lineTotal,
        float  $customExtras,
        ?array $customization,
        ?array $combo,
        array  $componentCustomizations
    ) {
        $this->name                    = $name;
        $this->quantity                = $quantity;
        $this->customization           = $customization;
        $this->combo                   = $combo;
        $this->componentCustomizations = $componentCustomizations;
        $this->unitPriceCents          = (int) round($unitPrice    * 100);
        $this->lineTotalCents          = (int) round($lineTotal    * 100);
        $this->customExtrasCents       = (int) round($customExtras * 100);
    }

    // ── Derivados ────────────────────────────────────────────────────────────

    /** Preço base por unidade (sem extras), em centavos. */
    public function basePriceCents(): int
    {
        return $this->unitPriceCents - $this->customExtrasCents;
    }

    /** Preço exibido: preço base × quantidade, em centavos. */
    public function displayPriceCents(): int
    {
        return $this->basePriceCents() * $this->quantity;
    }

    /** Preço exibido formatado (ex: "R$ 45,90"). */
    public function formatDisplayPrice(): string
    {
        return MoneyFormatter::format($this->displayPriceCents() / 100);
    }

    /** Subtotal real do item formatado (com extras). */
    public function formatLineTotal(): string
    {
        return MoneyFormatter::format($this->lineTotalCents / 100);
    }

    /** True quando o item tem extras com custo adicional. */
    public function hasCustomExtras(): bool
    {
        return $this->customExtrasCents > 0;
    }

    // ── Factory ──────────────────────────────────────────────────────────────

    /**
     * Cria a partir de um array bruto do pedido.
     *
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        $customization = isset($item['customization']) && is_array($item['customization'])
            ? $item['customization']
            : null;

        $combo = isset($item['combo']) && is_array($item['combo'])
            ? $item['combo']
            : null;

        $componentCustomizations = isset($item['component_customizations']) && is_array($item['component_customizations'])
            ? $item['component_customizations']
            : [];

        $customExtras = ($customization !== null && isset($customization['total_delta']))
            ? (float) $customization['total_delta']
            : 0.0;

        return new self(
            (string) ($item['name']      ?? 'Item'),
            max(1, (int) ($item['quantity'] ?? 1)),
            (float)  ($item['unit_price'] ?? 0),
            (float)  ($item['line_total'] ?? 0),
            $customExtras,
            $customization,
            $combo,
            $componentCustomizations
        );
    }
}
