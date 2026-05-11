<?php

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';

class AdminLoyaltyProgramController extends Controller
{
    private function guard($slug)
    {
        Auth::start();
        $u = Auth::user();

        if (!$u) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }
        $company = Company::findBySlug($slug);

        if (!$company) {
            echo 'Empresa inválida';
            exit;
        }

        if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
            echo 'Acesso negado';
            exit;
        }

        return [$u, $company];
    }

    /**
     * Página principal — exibe programa existente ou formulário de criação
     */
    public function index($params)
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $db = db();
        $program = LoyaltyProgram::getActiveByCompany($db, (int)$company['id']);

        // Se não tem programa ativo, buscar qualquer programa (mesmo inativo)
        if (!$program) {
            $stmt = $db->prepare('SELECT * FROM loyalty_programs WHERE company_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$company['id']]);
            $program = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $stats = null;
        if ($program) {
            $stats = LoyaltyProgram::getDashboardStats($db, (int)$program['id']);
        }

        $this->view('admin/loyalty-program/index', [
            'slug'    => $slug,
            'company' => $company,
            'user'    => $user,
            'program' => $program,
            'stats'   => $stats,
        ]);
    }

    /**
     * Salvar (criar ou atualizar) programa
     */
    public function save($params)
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $db = db();

        // Validação
        $name = trim($_POST['name'] ?? '');
        $requiredOrders = (int)($_POST['required_orders'] ?? 0);
        $rewardType = $_POST['reward_type'] ?? '';
        $rewardValue = (float)($_POST['reward_value'] ?? 0);
        $rewardDescription = trim($_POST['reward_description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $validTypes = ['discount_percentage', 'discount_fixed', 'free_delivery', 'free_item'];

        if ($name === '' || $requiredOrders < 2 || !in_array($rewardType, $validTypes, true) || $rewardDescription === '') {
            $_SESSION['loyalty_error'] = 'Preencha todos os campos obrigatórios. Mínimo de pedidos: 2.';
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-program'));
            exit;
        }

        if (in_array($rewardType, ['discount_percentage', 'discount_fixed'], true) && $rewardValue <= 0) {
            $_SESSION['loyalty_error'] = 'Informe o valor da recompensa.';
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-program'));
            exit;
        }

        if ($rewardType === 'discount_percentage' && $rewardValue > 100) {
            $_SESSION['loyalty_error'] = 'O percentual de desconto não pode ser maior que 100%.';
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-program'));
            exit;
        }

        $data = [
            'company_id'         => (int)$company['id'],
            'name'               => $name,
            'required_orders'    => $requiredOrders,
            'reward_type'        => $rewardType,
            'reward_value'       => $rewardValue,
            'reward_product_id'  => null,
            'reward_description' => $rewardDescription,
            'is_active'          => $isActive,
        ];

        $programId = (int)($_POST['program_id'] ?? 0);

        if ($programId > 0) {
            // Verificar se pertence a esta empresa
            $existing = LoyaltyProgram::findById($db, $programId);
            if (!$existing || (int)$existing['company_id'] !== (int)$company['id']) {
                $_SESSION['loyalty_error'] = 'Programa não encontrado.';
                header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-program'));
                exit;
            }
            LoyaltyProgram::update($db, $programId, $data);
            $successMsg = 'updated';
        } else {
            // Desativar programas anteriores da empresa antes de criar novo
            $db->prepare('UPDATE loyalty_programs SET is_active = 0 WHERE company_id = ?')->execute([$company['id']]);
            LoyaltyProgram::create($db, $data);
            $successMsg = 'created';
        }

        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-program?success=' . $successMsg));
        exit;
    }

    /**
     * Toggle ativar/desativar programa
     */
    public function toggle($params)
    {
        $slug = $params['slug'] ?? '';
        $id = (int)($params['id'] ?? 0);
        [$user, $company] = $this->guard($slug);

        $db = db();
        $program = LoyaltyProgram::findById($db, $id);

        if (!$program || (int)$program['company_id'] !== (int)$company['id']) {
            $_SESSION['loyalty_error'] = 'Programa não encontrado.';
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-program'));
            exit;
        }

        $newStatus = (int)$program['is_active'] === 1 ? 0 : 1;
        
        // Ao ativar, desativar todos os outros programas da mesma empresa
        if ($newStatus === 1) {
            $db->prepare('UPDATE loyalty_programs SET is_active = 0 WHERE company_id = ? AND id != ?')->execute([(int)$company['id'], $id]);
        }
        
        $db->prepare('UPDATE loyalty_programs SET is_active = ? WHERE id = ?')->execute([$newStatus, $id]);

        $msg = $newStatus ? 'activated' : 'deactivated';
        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/loyalty-program?success=' . $msg));
        exit;
    }

    /**
     * API JSON — estatísticas para dashboard
     */
    public function stats($params)
    {
        $slug = $params['slug'] ?? '';
        [$user, $company] = $this->guard($slug);

        $db = db();
        $program = LoyaltyProgram::getActiveByCompany($db, (int)$company['id']);

        if (!$program) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Nenhum programa ativo']);
            exit;
        }

        $stats = LoyaltyProgram::getDashboardStats($db, (int)$program['id']);

        // Top participantes
        $stmt = $db->prepare('
            SELECT c.name, c.phone, clp.current_count, clp.times_completed
            FROM customer_loyalty_progress clp
            JOIN customers c ON c.id = clp.customer_id
            WHERE clp.program_id = ?
            ORDER BY clp.times_completed DESC, clp.current_count DESC
            LIMIT 10
        ');
        $stmt->execute([$program['id']]);
        $topParticipants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'ok'               => true,
            'stats'            => $stats,
            'top_participants' => $topParticipants,
        ]);
        exit;
    }
}
