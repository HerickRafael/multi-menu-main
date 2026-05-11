<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Invoice
{
    public static function createDraft(array $data): int
    {
        $invoiceNumber = trim((string)($data['invoice_number'] ?? ''));
        if ($invoiceNumber === '') {
            $invoiceNumber = 'INV-' . date('YmdHis') . '-' . random_int(100, 999);
        }

        $payload = $data['payload_json'] ?? null;
        $payloadJson = null;
        if ($payload !== null) {
            $payloadJson = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        $sql = 'INSERT INTO invoices
                (company_id, subscription_id, invoice_number, status, currency, amount_subtotal, amount_tax, amount_discount, amount_total,
                 due_date, external_invoice_id, payload_json)
                VALUES
                (:company_id, :subscription_id, :invoice_number, :status, :currency, :amount_subtotal, :amount_tax, :amount_discount, :amount_total,
                 :due_date, :external_invoice_id, :payload_json)';

        $st = db()->prepare($sql);
        $st->execute([
            'company_id' => (int)$data['company_id'],
            'subscription_id' => $data['subscription_id'] ?? null,
            'invoice_number' => $invoiceNumber,
            'status' => (string)($data['status'] ?? 'draft'),
            'currency' => strtoupper((string)($data['currency'] ?? 'BRL')),
            'amount_subtotal' => number_format((float)($data['amount_subtotal'] ?? 0), 2, '.', ''),
            'amount_tax' => number_format((float)($data['amount_tax'] ?? 0), 2, '.', ''),
            'amount_discount' => number_format((float)($data['amount_discount'] ?? 0), 2, '.', ''),
            'amount_total' => number_format((float)($data['amount_total'] ?? 0), 2, '.', ''),
            'due_date' => $data['due_date'] ?? null,
            'external_invoice_id' => $data['external_invoice_id'] ?? null,
            'payload_json' => $payloadJson,
        ]);

        return (int)db()->lastInsertId();
    }

    public static function listByCompany(int $companyId, int $limit = 100): array
    {
        $st = db()->prepare('SELECT * FROM invoices WHERE company_id = ? ORDER BY id DESC LIMIT ?');
        $st->bindValue(1, $companyId, PDO::PARAM_INT);
        $st->bindValue(2, max(1, min(500, $limit)), PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function markPaid(int $invoiceId, ?string $paidAt = null): bool
    {
        $sql = 'UPDATE invoices
                SET status = "paid",
                    paid_at = :paid_at,
                    updated_at = NOW()
                WHERE id = :id';
        $st = db()->prepare($sql);
        return $st->execute([
            'paid_at' => $paidAt ?? date('Y-m-d H:i:s'),
            'id' => $invoiceId,
        ]);
    }
}
