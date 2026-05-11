<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

// Modelos específicos
require_once __DIR__ . '/../models/DeliveryCity.php';
require_once __DIR__ . '/../models/DeliveryZone.php';

class PublicAddressController extends Controller
{
    /**
     * Lista os endereços do cliente
     */
    public function index($params)
    {
        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        $customer = AuthCustomer::current($slug);

        if (!$customer) {
            header('Location: ' . base_url($slug . ''));
            exit;
        }

        $customerId = (int)($customer['id'] ?? 0);
        $companyId = (int)($company['id'] ?? 0);

        $addresses = CustomerAddress::getByCustomer($customerId, $companyId);

        return $this->view('public/addresses', [
            'company' => $company,
            'slug' => $slug,
            'customer' => $customer,
            'addresses' => $addresses,
        ]);
    }

    /**
     * Exclui um endereço
     */
    public function delete($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';
            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        $customer = AuthCustomer::current($slug);

        if (!$customer) {
            header('Location: ' . base_url($slug . ''));
            exit;
        }

        $addressId = (int)($_POST['address_id'] ?? 0);
        $customerId = (int)($customer['id'] ?? 0);

        $success = CustomerAddress::deleteAddress($addressId, $customerId);

        // Redirecionar de volta ao perfil
        $redirect = base_url($slug . '/profile' . ($success ? '?deleted=1' : '?error=1'));
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Define um endereço como padrão
     */
    public function setDefault($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';
            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        $customer = AuthCustomer::current($slug);

        if (!$customer) {
            header('Location: ' . base_url($slug . ''));
            exit;
        }

        $addressId = (int)($_POST['address_id'] ?? 0);
        $customerId = (int)($customer['id'] ?? 0);
        $companyId = (int)($company['id'] ?? 0);

        $success = CustomerAddress::setAsDefault($addressId, $customerId, $companyId);

        // Redirecionar de volta ao perfil
        $redirect = base_url($slug . '/profile' . ($success ? '?default=1' : '?error=1'));
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Atualiza o label de um endereço
     */
    public function updateLabel($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';
            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        $customer = AuthCustomer::current($slug);

        if (!$customer) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }

        $addressId = (int)($_POST['address_id'] ?? 0);
        $label = trim($_POST['label'] ?? '');
        $customerId = (int)($customer['id'] ?? 0);

        $success = CustomerAddress::updateAddress($addressId, ['label' => $label], $customerId);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    /**
     * Exibe formulário para criar novo endereço
     */
    public function create($params)
    {
        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        $customer = AuthCustomer::current($slug);

        if (!$customer) {
            header('Location: ' . base_url($slug . ''));
            exit;
        }

        $companyId = (int)($company['id'] ?? 0);
        $cities = DeliveryCity::allByCompany($companyId);
        $zonesRaw = DeliveryZone::allByCompany($companyId);

        $zonesByCity = [];
        foreach ($zonesRaw as $zoneRow) {
            $cityId = (int)($zoneRow['city_id'] ?? 0);
            if (!isset($zonesByCity[$cityId])) {
                $zonesByCity[$cityId] = [];
            }
            $zonesByCity[$cityId][] = [
                'id' => (int)($zoneRow['id'] ?? 0),
                'city_id' => $cityId,
                'name' => (string)($zoneRow['neighborhood'] ?? ''), // O campo é 'neighborhood' na tabela
                'city_name' => (string)($zoneRow['city_name'] ?? ''),
            ];
        }

        return $this->view('public/address-form', [
            'company' => $company,
            'slug' => $slug,
            'customer' => $customer,
            'address' => [],
            'cities' => $cities,
            'zonesByCity' => $zonesByCity,
        ]);
    }

    /**
     * Salva um novo endereço
     */
    public function store($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';
            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        $customer = AuthCustomer::current($slug);

        if (!$customer) {
            header('Location: ' . base_url($slug . ''));
            exit;
        }

        $customerId = (int)($customer['id'] ?? 0);
        $companyId = (int)($company['id'] ?? 0);

        $data = [
            'customer_id' => $customerId,
            'company_id' => $companyId,
            'label' => trim($_POST['label'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'city_id' => (int)($_POST['city_id'] ?? 0),
            'zone_id' => (int)($_POST['zone_id'] ?? 0),
            'city' => trim($_POST['city'] ?? ''),
            'neighborhood' => trim($_POST['neighborhood'] ?? ''),
            'street' => trim($_POST['street'] ?? ''),
            'number' => trim($_POST['number'] ?? ''),
            'complement' => trim($_POST['complement'] ?? ''),
            'reference' => trim($_POST['reference'] ?? ''),
        ];

        $addressId = CustomerAddress::createAddress($data);

        if ($addressId) {
            $redirect = base_url($slug . '/profile?address_created=1');
        } else {
            $redirect = base_url($slug . '/profile?error=1');
        }

        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Exibe formulário para editar endereço
     */
    public function edit($params)
    {
        $slug = $params['slug'] ?? null;
        $addressId = (int)($params['id'] ?? 0);

        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        $customer = AuthCustomer::current($slug);

        if (!$customer) {
            header('Location: ' . base_url($slug . ''));
            exit;
        }

        $customerId = (int)($customer['id'] ?? 0);
        $companyId = (int)($company['id'] ?? 0);

        $address = CustomerAddress::getAddress($addressId, $customerId);

        if (!$address) {
            header('Location: ' . base_url($slug . '/profile?error=1'));
            exit;
        }

        $cities = DeliveryCity::allByCompany($companyId);
        $zonesRaw = DeliveryZone::allByCompany($companyId);

        $zonesByCity = [];
        foreach ($zonesRaw as $zoneRow) {
            $cityId = (int)($zoneRow['city_id'] ?? 0);
            if (!isset($zonesByCity[$cityId])) {
                $zonesByCity[$cityId] = [];
            }
            $zonesByCity[$cityId][] = [
                'id' => (int)($zoneRow['id'] ?? 0),
                'city_id' => $cityId,
                'name' => (string)($zoneRow['neighborhood'] ?? ''), // O campo é 'neighborhood' na tabela
                'city_name' => (string)($zoneRow['city_name'] ?? ''),
            ];
        }

        return $this->view('public/address-form', [
            'company' => $company,
            'slug' => $slug,
            'customer' => $customer,
            'address' => $address,
            'cities' => $cities,
            'zonesByCity' => $zonesByCity,
        ]);
    }

    /**
     * Atualiza um endereço existente
     */
    public function update($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';
            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company) {
            http_response_code(404);
            echo 'Empresa não encontrada';
            return;
        }

        $customer = AuthCustomer::current($slug);

        if (!$customer) {
            header('Location: ' . base_url($slug . ''));
            exit;
        }

        $addressId = (int)($_POST['address_id'] ?? 0);
        $customerId = (int)($customer['id'] ?? 0);

        $data = [
            'label' => trim($_POST['label'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'city_id' => (int)($_POST['city_id'] ?? 0),
            'zone_id' => (int)($_POST['zone_id'] ?? 0),
            'city' => trim($_POST['city'] ?? ''),
            'neighborhood' => trim($_POST['neighborhood'] ?? ''),
            'street' => trim($_POST['street'] ?? ''),
            'number' => trim($_POST['number'] ?? ''),
            'complement' => trim($_POST['complement'] ?? ''),
            'reference' => trim($_POST['reference'] ?? ''),
        ];

        $success = CustomerAddress::updateAddress($addressId, $data, $customerId);

        if ($success) {
            $redirect = base_url($slug . '/profile?address_updated=1');
        } else {
            $redirect = base_url($slug . '/profile?error=1');
        }

        header('Location: ' . $redirect);
        exit;
    }
}
