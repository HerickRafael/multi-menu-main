<?php

declare(strict_types=1);

require_once __DIR__ . '/OrderItemData.php';
require_once __DIR__ . '/CheckoutSuccessOrder.php';
require_once __DIR__ . '/MoneyFormatter.php';
require_once __DIR__ . '/OrderMessageBuilder.php';

/**
 * Constrói a mensagem WhatsApp exibida na tela de sucesso do checkout.
 *
 * Responsabilidade única: formatar a string da mensagem e a URL wa.me.
 * Isolado: sem acesso a HTTP, BD ou sessão — testável via PHPUnit.
 *
 * Composição: reutiliza OrderMessageBuilder::processComboItemsFormatted()
 * para combos complexos, evitando duplicação de lógica.
 */
final class CheckoutSuccessMessageBuilder
{
    // Emojis como constantes — sem magic strings espalhadas no código
    const BULLET   = '•';
    const COMBO    = '↳';
    const ADDED    = '➕';
    const REMOVED  = '❌';
    const INCLUDED = '✅';

    /** @var CheckoutSuccessOrder */
    private $order;

    public function __construct(CheckoutSuccessOrder $order)
    {
        $this->order = $order;
    }

    // ── API pública ──────────────────────────────────────────────────────────

    /**
     * Gera a URL wa.me completa com a mensagem encodada via rawurlencode (RFC 3986).
     * rawurlencode é mais correto que urlencode para query strings.
     */
    public function buildUrl(): string
    {
        return 'https://wa.me/' . $this->order->companyWhatsApp
            . '?text=' . rawurlencode($this->build());
    }

    /** Monta a mensagem completa em formato WhatsApp Markdown. */
    public function build(): string
    {
        return $this->buildHeader()
            . $this->buildItems()
            . $this->buildSummary()
            . $this->buildPayment()
            . $this->buildAddress()
            . $this->buildNotes()
            . 'Aguardo confirmação! 😊';
    }

    // ── Seções da mensagem ───────────────────────────────────────────────────

    private function buildHeader(): string
    {
        $orderId = $this->order->orderId > 0
            ? ' - #' . $this->order->orderId
            : '';

        $msg  = "🛒 *NOVO PEDIDO{$orderId}*\n\n";
        $msg .= '👤 *Cliente:* ' . $this->order->customer . "\n";

        if ($this->order->phone !== '') {
            $msg .= '📱 *Telefone:* ' . $this->order->phone . "\n";
        }

        return $msg . "\n";
    }

    private function buildItems(): string
    {
        if (!$this->order->hasItems()) {
            return '';
        }

        $msg = "📦 *ITENS DO PEDIDO:*\n";

        foreach ($this->order->items as $item) {
            $msg .= $this->buildItem($item);
        }

        return $msg;
    }

    private function buildItem(OrderItemData $item): string
    {
        $msg = self::BULLET . ' '
            . $item->quantity . 'x ' . $item->name
            . ' - ' . $item->formatDisplayPrice() . "\n";

        // Itens de combo no formato simples (selected_items)
        if ($item->combo !== null && !empty($item->combo['selected_items'])) {
            foreach ($item->combo['selected_items'] as $comboItem) {
                $comboName = (string) ($comboItem['simple_name'] ?? $comboItem['name'] ?? '');
                if ($comboName !== '') {
                    $msg .= '   ' . self::COMBO . ' ' . $comboName . "\n";
                }
            }
        }

        // Customizações de combo com grupos — reutiliza OrderMessageBuilder (composição)
        // Corrige bug: $componentCustomizations era ignorado na view original
        if ($item->combo !== null && !empty($item->combo['groups'])) {
            $msg .= OrderMessageBuilder::processComboItemsFormatted(
                $item->combo,
                $item->componentCustomizations
            );
        } elseif ($item->customization !== null && !empty($item->customization['groups'])) {
            $msg .= $this->buildCustomizationGroups($item->customization['groups']);
        }

        // Subtotal real quando há extras com custo
        if ($item->hasCustomExtras()) {
            $msg .= '   *Subtotal item: ' . $item->formatLineTotal() . "*\n";
        }

        return $msg;
    }

    /**
     * Renderiza grupos de customização no formato WhatsApp.
     * Early-return por tipo elimina nested ifs da view original.
     *
     * @param array<int, array<string, mixed>> $groups
     */
    private function buildCustomizationGroups(array $groups): string
    {
        $msg = '';

        foreach ($groups as $group) {
            if (empty($group['items'])) {
                continue;
            }

            $groupType     = (string) ($group['type'] ?? 'extra');
            $isChoiceGroup = in_array($groupType, ['single', 'addon', 'choice'], true);
            $isPoolGroup   = $groupType === 'pool';

            foreach ($group['items'] as $custItem) {
                $msg .= $this->buildCustomizationItem($custItem, $isChoiceGroup, $isPoolGroup);
            }
        }

        return $msg;
    }

    /**
     * Renderiza uma linha de customização.
     * Early-returns por caso eliminam nesting excessivo.
     *
     * @param array<string, mixed> $custItem
     */
    private function buildCustomizationItem(
        array $custItem,
        bool  $isChoiceGroup,
        bool  $isPoolGroup
    ): string {
        $name = (string) ($custItem['name'] ?? '');
        if ($name === '') {
            return '';
        }

        $qty        = isset($custItem['qty'])         ? (int)   $custItem['qty']         : null;
        $deltaQty   = isset($custItem['delta_qty'])   ? (int)   $custItem['delta_qty']   : null;
        $defaultQty = isset($custItem['default_qty']) ? (int)   $custItem['default_qty'] : null;
        $price      = (float) ($custItem['price'] ?? 0);

        $isRemoved = !empty($custItem['removed'])
            || ($defaultQty !== null && $defaultQty > 0 && ($qty === 0 || $qty === null));

        // Calcular delta quando ausente
        if ($deltaQty === null && $defaultQty !== null && $qty !== null) {
            $deltaQty = $qty - $defaultQty;
        }

        // ── Remoção explícita ────────────────────────────────────────────────
        if ($isRemoved) {
            return '   ' . self::REMOVED . ' Sem ' . $name . "\n";
        }

        // ── Pool (açaí/montagem) ─────────────────────────────────────────────
        if ($isPoolGroup) {
            $effectiveQty = $qty ?? 0;
            if ($effectiveQty <= 0) {
                return '';
            }
            $paidQty   = (int)   ($custItem['paid_qty']   ?? 0);
            $unitPrice = (float) ($custItem['unit_price'] ?? 0);
            $qtyPrefix = $effectiveQty > 1 ? "{$effectiveQty}x " : '';
            $priceStr  = ($paidQty > 0 && $unitPrice > 0)
                ? ' (+' . MoneyFormatter::format($paidQty * $unitPrice) . ')'
                : '';
            return '   ' . self::INCLUDED . ' ' . $qtyPrefix . $name . $priceStr . "\n";
        }

        // ── Escolha (single / choice / addon) ────────────────────────────────
        if ($isChoiceGroup) {
            $effectiveQty = $qty ?? 0;
            if ($effectiveQty <= 0) {
                return '';
            }
            $priceStr = $price > 0.009 ? ' (+' . MoneyFormatter::format($price) . ')' : '';
            return '   ' . self::INCLUDED . ' ' . $name . $priceStr . "\n";
        }

        // ── Remoção por delta negativo ────────────────────────────────────────
        if ($deltaQty !== null && $deltaQty < 0) {
            return '   ' . self::REMOVED . ' Sem ' . $name . "\n";
        }

        // ── Extra adicionado (delta positivo) ────────────────────────────────
        if ($deltaQty !== null && $deltaQty > 0) {
            $displayQty = abs($deltaQty);
            $prefix     = $displayQty > 1 ? "{$displayQty}x " : '';
            $priceStr   = $price > 0.009 ? ' (+' . MoneyFormatter::format($price) . ')' : '';
            return '   ' . self::ADDED . ' ' . $prefix . $name . $priceStr . "\n";
        }

        // ── Sem delta, mas com preço > 0 (extra implícito) ───────────────────
        if ($price > 0.009) {
            $effectiveQty = $qty ?? 0;
            $qtyPrefix    = $effectiveQty > 1 ? "{$effectiveQty}x " : '';
            return '   ' . self::ADDED . ' ' . $qtyPrefix . $name
                . ' (+' . MoneyFormatter::format($price) . ')' . "\n";
        }

        return '';
    }

    private function buildSummary(): string
    {
        return "\n💰 *RESUMO:*\n"
            . 'Subtotal: ' . $this->order->formatSubtotal() . "\n"
            . 'Entrega: '  . $this->order->formatDelivery()  . "\n"
            . '*Total: '   . $this->order->formatTotal()     . "*\n\n";
    }

    private function buildPayment(): string
    {
        if (!$this->order->hasPayment()) {
            return '';
        }

        // Ternário em vez de match (PHP 7.4 compat)
        $paymentLine = $this->order->isPix()
            ? '💳 *Pagamento:* Pix - mandar o comprovante após pagamento'
            : '💳 *Pagamento:* ' . $this->order->payment;

        $msg = $paymentLine . "\n";

        if ($this->order->hasCashPayment()) {
            $msg .= '💰 *Troco para:* ' . $this->order->formatCashAmount() . "\n";
            if ($this->order->cashChangeCents > 0) {
                $msg .= '   Troco: ' . $this->order->formatCashChange() . "\n";
            }
        }

        if ($this->order->instructions !== '' && !$this->order->isPix()) {
            $msg .= '_' . str_replace("\n", ' ', $this->order->instructions) . "_\n";
        }

        return $msg . "\n";
    }

    private function buildAddress(): string
    {
        if (!$this->order->hasAddress()) {
            return '';
        }

        return "📍 *Endereço de entrega:*\n"
            . str_replace("\n", ', ', $this->order->address) . "\n\n";
    }

    private function buildNotes(): string
    {
        if (!$this->order->hasNotes()) {
            return '';
        }

        return "📝 *Observações:*\n"
            . $this->order->userNotes . "\n\n";
    }
}
