<?php
/**
 * AdminPauseController
 * 
 * Controller para gerenciar pausa programada da loja
 * Similar ao sistema do iFood
 * 
 * @package MultiMenu\Controllers
 */

declare(strict_types=1);

// 🚀 Bootstrap centralizado
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../services/ScheduledPauseService.php';

class AdminPauseController extends Controller
{
    private ScheduledPauseService $pauseService;

    public function __construct()
    {
        $this->pauseService = new ScheduledPauseService(db());
    }

    /**
     * Garante autenticação e retorna contexto
     */
    private function guard(string $slug): array
    {
        Auth::start();

        if (!Auth::checkAdmin()) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Não autorizado'], 401);
                exit;
            }
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }

        $company = Company::findBySlug($slug);

        if (!$company || empty($company['id'])) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Empresa não encontrada'], 404);
                exit;
            }
            http_response_code(404);
            echo 'Empresa não encontrada';
            exit;
        }

        $u = Auth::user();
        $isRoot = ($u['role'] === 'root');

        if (!$isRoot && (int)$u['company_id'] !== (int)$company['id']) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Acesso negado'], 403);
                exit;
            }
            http_response_code(403);
            echo 'Acesso negado';
            exit;
        }

        return [$u, $company];
    }

    /**
     * Verifica se é uma requisição AJAX
     */
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * GET /admin/{slug}/pause/status
     * Retorna status atual da pausa
     */
    public function status(array $params): void
    {
        $slug = $params['slug'] ?? '';
        [$u, $company] = $this->guard($slug);

        $status = $this->pauseService->getPauseStatus((int)$company['id']);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $status,
            'predefined_durations' => ScheduledPauseService::getPredefinedDurations(),
            'predefined_reasons' => ScheduledPauseService::getPredefinedReasons()
        ]);
    }

    /**
     * POST /admin/{slug}/pause/enable
     * Ativa a pausa programada
     */
    public function enable(array $params): void
    {
        $slug = $params['slug'] ?? '';
        [$u, $company] = $this->guard($slug);

        $input = $this->getJsonInput();
        
        $type = $input['type'] ?? 'timed';
        $minutes = (int)($input['minutes'] ?? 30);
        $untilDateTime = $input['until'] ?? null;
        $reason = trim($input['reason'] ?? 'Estamos em pausa no momento');

        if (empty($reason)) {
            $reason = 'Estamos em pausa no momento';
        }

        $companyId = (int)$company['id'];
        $success = false;

        switch ($type) {
            case 'indefinite':
                $success = $this->pauseService->enableIndefinitePause($companyId, $reason);
                break;
            
            case 'scheduled':
                if (!$untilDateTime) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Data/hora de término é obrigatória para pausa agendada'
                    ], 400);
                    return;
                }
                $success = $this->pauseService->enableScheduledPause($companyId, $untilDateTime, $reason);
                break;
            
            case 'timed':
            default:
                if ($minutes < 1 || $minutes > 1440) { // Max 24h
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Duração inválida. Deve ser entre 1 e 1440 minutos (24 horas)'
                    ], 400);
                    return;
                }
                $success = $this->pauseService->enableTimedPause($companyId, $minutes, $reason);
                break;
        }

        if ($success) {
            $status = $this->pauseService->getPauseStatus($companyId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Pausa ativada com sucesso',
                'data' => $status
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erro ao ativar pausa'
            ], 500);
        }
    }

    /**
     * POST /admin/{slug}/pause/disable
     * Desativa a pausa programada
     */
    public function disable(array $params): void
    {
        $slug = $params['slug'] ?? '';
        [$u, $company] = $this->guard($slug);

        $companyId = (int)$company['id'];
        $success = $this->pauseService->disablePause($companyId);

        if ($success) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Pausa desativada com sucesso',
                'data' => $this->pauseService->getPauseStatus($companyId)
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erro ao desativar pausa'
            ], 500);
        }
    }

    /**
     * POST /admin/{slug}/pause/extend
     * Estende a pausa atual por mais X minutos
     */
    public function extend(array $params): void
    {
        $slug = $params['slug'] ?? '';
        [$u, $company] = $this->guard($slug);

        $input = $this->getJsonInput();
        $extraMinutes = (int)($input['minutes'] ?? 30);

        if ($extraMinutes < 1 || $extraMinutes > 480) { // Max 8h extra
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tempo adicional inválido. Deve ser entre 1 e 480 minutos (8 horas)'
            ], 400);
            return;
        }

        $companyId = (int)$company['id'];
        $currentStatus = $this->pauseService->getPauseStatus($companyId);

        if (!$currentStatus['is_paused']) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Não há pausa ativa para estender'
            ], 400);
            return;
        }

        if ($currentStatus['pause_type'] === 'indefinite') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Não é possível estender uma pausa indefinida'
            ], 400);
            return;
        }

        // Calcula novo horário de término
        $currentUntil = new DateTime($currentStatus['pause_until'], new \DateTimeZone('America/Sao_Paulo'));
        $newUntil = $currentUntil->modify("+{$extraMinutes} minutes");

        $success = $this->pauseService->enableScheduledPause(
            $companyId, 
            $newUntil->format('Y-m-d H:i:s'),
            $currentStatus['pause_reason']
        );

        if ($success) {
            $this->jsonResponse([
                'success' => true,
                'message' => "Pausa estendida por mais {$extraMinutes} minutos",
                'data' => $this->pauseService->getPauseStatus($companyId)
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erro ao estender pausa'
            ], 500);
        }
    }

    /**
     * Obtém input JSON da requisição
     */
    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        
        if (!empty($raw)) {
            $data = json_decode($raw, true);
            if (is_array($data)) {
                return $data;
            }
        }
        
        return $_POST;
    }

    /**
     * Resposta JSON helper
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
