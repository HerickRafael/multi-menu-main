<?php

declare(strict_types=1);

class CustomerGuard
{
    /**
     * Valida empresa ativa e sessao do cliente.
     *
     * @return array{company: array, customer: array}
     */
    public static function requireCustomer(string $slug): array
    {
        $company = Company::findBySlug($slug);
        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa nao encontrada';
            exit;
        }

        $customer = AuthCustomer::current($slug);
        if (!$customer) {
            header('Location: ' . base_url($slug . '?login=1'));
            exit;
        }

        return [
            'company' => $company,
            'customer' => $customer,
        ];
    }
}
