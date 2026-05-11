<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

class PublicOrderController extends Controller
{
    /**
     * GET /{slug}/order/{id}
     * Mostra detalhes de um pedido específico
     */
    public function show($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        // Empresa
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        // Verificar autenticação do cliente
        $customer = AuthCustomer::current($slug);
        if (!$customer) {
            header('Location: ' . base_url($slug . '?login=1'));
            exit;
        }

        // Buscar pedido
        $db = $this->db();
        $order = Order::findWithItems($db, $id, (int)$company['id']);

        if (!$order) {
            http_response_code(404);
            echo 'Pedido não encontrado';
            return;
        }

        // Verificar se o pedido pertence ao cliente logado
        // Comparar telefones normalizados
        $orderPhone = normalizePhone($order['customer_phone'] ?? '');
        $customerPhone = normalizePhone($customer['whatsapp'] ?? '');
        
        if ($orderPhone !== $customerPhone) {
            http_response_code(403);
            echo 'Você não tem permissão para visualizar este pedido';
            return;
        }

        return $this->view('public/order', [
            'company' => $company,
            'customer' => $customer,
            'order' => $order,
            'slug' => $slug,
        ]);
    }

    /**
     * POST /{slug}/order/{id}/cancel
     * Cancela um pedido
     */
    public function cancel($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        // Empresa
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        // Verificar autenticação do cliente
        $customer = AuthCustomer::current($slug);
        if (!$customer) {
            header('Location: ' . base_url($slug . '?login=1'));
            exit;
        }

        $db = $this->db();

        // Buscar pedido
        $stmt = $db->prepare('SELECT id, customer_phone, status FROM orders WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, (int)$company['id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo 'Pedido não encontrado';
            return;
        }

        // Verificar se o pedido pertence ao cliente logado
        // Comparar telefones normalizados
        $orderPhone = normalizePhone($order['customer_phone'] ?? '');
        $customerPhone = normalizePhone($customer['whatsapp'] ?? '');
        
        if ($orderPhone !== $customerPhone) {
            http_response_code(403);
            echo 'Você não tem permissão para cancelar este pedido';
            return;
        }

        // Verificar se o pedido pode ser cancelado
        $status = $order['status'] ?? '';
        if ($status !== 'pending' && $status !== 'paid') {
            header('Location: ' . base_url($slug . '/profile?error=cancel_not_allowed'));
            exit;
        }

        // Cancelar pedido
        $stmt = $db->prepare('UPDATE orders SET status = ? WHERE id = ? AND company_id = ?');
        $stmt->execute(['canceled', $id, (int)$company['id']]);

        // Redirecionar com mensagem de sucesso
        header('Location: ' . base_url($slug . '/profile?canceled=1'));
        exit;
    }
}
