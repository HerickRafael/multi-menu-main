<?php

declare(strict_types=1);

require_once __DIR__ . '/../modules/customers/CustomerListService.php';

use App\Repositories\CustomerRepository;

class CustomerService
{
    private static function repo(PDO $pdo): CustomerRepository
    {
        return new CustomerRepository($pdo);
    }

    public static function listForAdmin(PDO $pdo, int $companyId, string $search, int $page, int $perPage): array
    {
        return CustomerListService::listWithStats($pdo, $companyId, $search, $page, $perPage);
    }

    public static function listForMobile(PDO $pdo, int $companyId, string $search, int $page, int $perPage): array
    {
        return CustomerListService::listWithStats($pdo, $companyId, $search, $page, $perPage);
    }

    public static function findForCompany(PDO $pdo, int $companyId, int $customerId): ?array
    {
        return self::repo($pdo)->findByCompanyAndId($companyId, $customerId);
    }

    public static function save(PDO $pdo, int $companyId, array $payload, ?int $customerId = null): array
    {
        $isEdit = $customerId !== null && $customerId > 0;
        $name = trim((string)($payload['name'] ?? ''));
        $whatsapp = preg_replace('/\D/', '', (string)($payload['whatsapp'] ?? ''));
        $whatsappE164 = normalizePhone($whatsapp);
        $email = trim((string)($payload['email'] ?? ''));
        $cpf = trim((string)($payload['cpf'] ?? ''));
        $birthDate = trim((string)($payload['birth_date'] ?? ''));
        $notes = trim((string)($payload['notes'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors[] = 'O nome é obrigatório';
        }
        if ($whatsapp === '') {
            $errors[] = 'O WhatsApp é obrigatório';
        } elseif (strlen($whatsapp) < 10 || strlen($whatsapp) > 15) {
            $errors[] = 'WhatsApp inválido. Use o formato com DDD';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido';
        }
        if ($cpf !== '' && !preg_match('/^\d{3}\.??\d{3}\.??\d{3}-?\d{2}$/', $cpf)) {
            $errors[] = 'CPF inválido. Use o formato 000.000.000-00';
        }
        if ($birthDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) {
            $errors[] = 'Data de nascimento inválida';
        }

        if (empty($errors)) {
            if (self::repo($pdo)->findDuplicateWhatsappE164($companyId, $whatsappE164, $customerId) !== null) {
                $errors[] = 'Já existe um cliente cadastrado com este número de WhatsApp';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $now = date('Y-m-d H:i:s');
        if ($isEdit) {
            $existing = self::findForCompany($pdo, $companyId, (int)$customerId);
            if (!$existing) {
                return ['success' => false, 'errors' => ['Cliente não encontrado']];
            }

            self::repo($pdo)->update($companyId, (int)$customerId, [
                'name' => $name,
                'whatsapp' => $whatsapp,
                'whatsapp_e164' => $whatsappE164,
                'email' => $email !== '' ? $email : null,
                'cpf' => $cpf !== '' ? $cpf : ($existing['cpf'] ?? null),
                'birth_date' => $birthDate !== '' ? $birthDate : ($existing['birth_date'] ?? null),
                'notes' => $notes !== '' ? $notes : null,
                'updated_at' => $now,
            ]);

            return ['success' => true, 'customerId' => (int)$customerId, 'mode' => 'updated'];
        }

        return [
            'success' => true,
            'customerId' => self::repo($pdo)->create($companyId, [
                'name' => $name,
                'whatsapp' => $whatsapp,
                'whatsapp_e164' => $whatsappE164,
                'email' => $email !== '' ? $email : null,
                'cpf' => $cpf !== '' ? $cpf : null,
                'birth_date' => $birthDate !== '' ? $birthDate : null,
                'notes' => $notes !== '' ? $notes : null,
                'created_at' => $now,
                'updated_at' => $now,
                'last_login_at' => $now,
            ]),
            'mode' => 'created',
        ];
    }

    public static function delete(PDO $pdo, int $companyId, int $customerId): array
    {
        $customer = self::findForCompany($pdo, $companyId, $customerId);
        if (!$customer) {
            return ['success' => false, 'message' => 'Cliente não encontrado'];
        }

        if (self::repo($pdo)->countOrdersByCustomerPhones($companyId, (string)$customer['whatsapp'], (string)$customer['whatsapp_e164']) > 0) {
            self::repo($pdo)->anonymize($companyId, $customerId);
            return ['success' => true, 'mode' => 'anonymized'];
        }

        self::repo($pdo)->hardDeleteCascade($companyId, $customerId);
        return ['success' => true, 'mode' => 'deleted'];
    }

    public static function search(PDO $pdo, int $companyId, string $search, int $limit = 10): array
    {
        return self::repo($pdo)->search($companyId, $search, $limit);
    }
}