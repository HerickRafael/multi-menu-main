<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

/**
 * Acesso a dados de clientes com escopo de empresa.
 */
final class CustomerRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function findByCompanyAndId(int $companyId, int $customerId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM customers WHERE id = ? AND company_id = ?');
        $stmt->execute([$customerId, $companyId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        return $customer ?: null;
    }

    public function findDuplicateWhatsappE164(int $companyId, string $whatsappE164, ?int $ignoreCustomerId = null): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM customers WHERE company_id = ? AND whatsapp_e164 = ? AND id != ? LIMIT 1');
        $stmt->execute([$companyId, $whatsappE164, $ignoreCustomerId ?? 0]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(int $companyId, array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO customers (company_id, name, whatsapp, whatsapp_e164, email, cpf, birth_date, notes, created_at, updated_at, last_login_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $companyId,
            $data['name'],
            $data['whatsapp'],
            $data['whatsapp_e164'],
            $data['email'] ?? null,
            $data['cpf'] ?? null,
            $data['birth_date'] ?? null,
            $data['notes'] ?? null,
            $data['created_at'],
            $data['updated_at'],
            $data['last_login_at'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $companyId, int $customerId, array $data): bool
    {
        $stmt = $this->pdo->prepare('UPDATE customers SET name = ?, whatsapp = ?, whatsapp_e164 = ?, email = ?, cpf = ?, birth_date = ?, notes = ?, updated_at = ? WHERE id = ? AND company_id = ?');
        $stmt->execute([
            $data['name'],
            $data['whatsapp'],
            $data['whatsapp_e164'],
            $data['email'] ?? null,
            $data['cpf'] ?? null,
            $data['birth_date'] ?? null,
            $data['notes'] ?? null,
            $data['updated_at'],
            $customerId,
            $companyId,
        ]);

        return $stmt->rowCount() >= 0;
    }

    public function countOrdersByCustomerPhones(int $companyId, string $whatsapp, string $whatsappE164): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM orders WHERE (customer_phone = ? OR customer_phone = ?) AND company_id = ?');
        $stmt->execute([$whatsapp, $whatsappE164, $companyId]);

        return (int)$stmt->fetchColumn();
    }

    public function anonymize(int $companyId, int $customerId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE customers SET name = 'Cliente Removido', whatsapp = '0000000000', whatsapp_e164 = '0000000000', updated_at = NOW() WHERE id = ? AND company_id = ?");
        $stmt->execute([$customerId, $companyId]);

        return $stmt->rowCount() >= 0;
    }

    public function hardDeleteCascade(int $companyId, int $customerId): bool
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('DELETE FROM customer_addresses WHERE customer_id = ?')->execute([$customerId]);
            $this->pdo->prepare('DELETE FROM customer_order_history WHERE customer_id = ?')->execute([$customerId]);
            $this->pdo->prepare('DELETE FROM customers WHERE id = ? AND company_id = ?')->execute([$customerId, $companyId]);
            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function search(int $companyId, string $search, int $limit = 10): array
    {
        $searchTerm = '%' . trim($search) . '%';
        $stmt = $this->pdo->prepare('SELECT id, name, whatsapp, whatsapp_e164 FROM customers WHERE company_id = ? AND (name LIKE ? OR whatsapp LIKE ? OR whatsapp_e164 LIKE ?) ORDER BY name ASC LIMIT ?');
        $stmt->bindValue(1, $companyId, PDO::PARAM_INT);
        $stmt->bindValue(2, $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(3, $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(4, $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(5, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}