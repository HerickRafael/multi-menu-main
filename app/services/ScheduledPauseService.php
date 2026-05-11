<?php
/**
 * ScheduledPauseService
 * 
 * Serviço para gerenciar pausa programada da loja
 * Similar ao sistema do iFood
 * 
 * @package MultiMenu\Services
 */

declare(strict_types=1);

class ScheduledPauseService
{
    private \PDO $db;
    private ?bool $hasPauseColumns = null;
    
    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    private function hasPauseColumns(): bool
    {
        if ($this->hasPauseColumns !== null) {
            return $this->hasPauseColumns;
        }

        $requiredColumns = [
            'pause_enabled',
            'pause_until',
            'pause_reason',
            'pause_created_at',
            'pause_type',
        ];

        try {
            foreach ($requiredColumns as $column) {
                $st = $this->db->prepare('SHOW COLUMNS FROM companies LIKE ?');
                $st->execute([$column]);
                if (!$st->fetch(\PDO::FETCH_ASSOC)) {
                    $this->hasPauseColumns = false;

                    return false;
                }
            }
        } catch (\Throwable $e) {
            $this->hasPauseColumns = false;

            return false;
        }

        $this->hasPauseColumns = true;

        return true;
    }

    private function defaultPauseStatus(): array
    {
        return [
            'is_paused' => false,
            'pause_enabled' => false,
            'pause_until' => null,
            'pause_reason' => null,
            'pause_type' => null,
            'remaining_minutes' => null,
            'remaining_text' => null,
        ];
    }
    
    /**
     * Verifica se a empresa está em pausa
     * Retorna informações sobre a pausa se ativa
     */
    public function getPauseStatus(int $companyId): array
    {
        if (!$this->hasPauseColumns()) {
            return $this->defaultPauseStatus();
        }

        $stmt = $this->db->prepare('
            SELECT id, pause_enabled, pause_until, pause_reason, pause_created_at, pause_type
            FROM companies 
            WHERE id = ?
        ');
        $stmt->execute([$companyId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$row) {
            return $this->defaultPauseStatus();
        }
        
        $isPaused = $this->isPaused($row);
        $remainingMinutes = null;
        $remainingText = null;
        
        if ($isPaused && $row['pause_until']) {
            $now = new DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
            $until = new DateTime($row['pause_until'], new \DateTimeZone('America/Sao_Paulo'));
            $diff = $now->diff($until);
            
            if ($diff->invert === 0) {
                $remainingMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
                $remainingText = $this->formatRemainingTime($diff);
            }
        }
        
        return [
            'is_paused' => $isPaused,
            'pause_enabled' => (bool)$row['pause_enabled'],
            'pause_until' => $row['pause_until'],
            'pause_reason' => $row['pause_reason'],
            'pause_type' => $row['pause_type'] ?? 'timed',
            'pause_created_at' => $row['pause_created_at'],
            'remaining_minutes' => $remainingMinutes,
            'remaining_text' => $remainingText
        ];
    }
    
    /**
     * Verifica se a pausa está realmente ativa
     */
    private function isPaused(array $row): bool
    {
        if (empty($row['pause_enabled'])) {
            return false;
        }
        
        $pauseType = $row['pause_type'] ?? 'timed';
        
        // Pausa indefinida - sempre ativa enquanto pause_enabled=1
        if ($pauseType === 'indefinite') {
            return true;
        }
        
        // Pausa temporizada - verifica se não expirou
        if ($row['pause_until']) {
            $now = new DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
            $until = new DateTime($row['pause_until'], new \DateTimeZone('America/Sao_Paulo'));
            
            if ($now < $until) {
                return true;
            }
            
            // Expirou - desativa automaticamente
            $this->disablePause((int)($row['id'] ?? 0));
            return false;
        }
        
        return false;
    }
    
    /**
     * Formata o tempo restante para exibição
     */
    private function formatRemainingTime(\DateInterval $diff): string
    {
        if ($diff->days > 0) {
            return sprintf('%d dia(s) e %d hora(s)', $diff->days, $diff->h);
        }
        
        if ($diff->h > 0) {
            return sprintf('%d hora(s) e %d minuto(s)', $diff->h, $diff->i);
        }
        
        if ($diff->i > 0) {
            return sprintf('%d minuto(s)', $diff->i);
        }
        
        return 'menos de 1 minuto';
    }
    
    /**
     * Ativa pausa temporizada (X minutos)
     */
    public function enableTimedPause(int $companyId, int $minutes, ?string $reason = null): bool
    {
        if (!$this->hasPauseColumns()) {
            return false;
        }

        $now = new DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        $until = (clone $now)->modify("+{$minutes} minutes");
        
        $stmt = $this->db->prepare('
            UPDATE companies SET 
                pause_enabled = 1,
                pause_until = ?,
                pause_reason = ?,
                pause_created_at = ?,
                pause_type = "timed"
            WHERE id = ?
        ');
        
        return $stmt->execute([
            $until->format('Y-m-d H:i:s'),
            $reason ?? 'Estamos em pausa no momento',
            $now->format('Y-m-d H:i:s'),
            $companyId
        ]);
    }
    
    /**
     * Ativa pausa até horário específico
     */
    public function enableScheduledPause(int $companyId, string $untilDateTime, ?string $reason = null): bool
    {
        if (!$this->hasPauseColumns()) {
            return false;
        }

        $now = new DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        
        $stmt = $this->db->prepare('
            UPDATE companies SET 
                pause_enabled = 1,
                pause_until = ?,
                pause_reason = ?,
                pause_created_at = ?,
                pause_type = "scheduled"
            WHERE id = ?
        ');
        
        return $stmt->execute([
            $untilDateTime,
            $reason ?? 'Estamos em pausa no momento',
            $now->format('Y-m-d H:i:s'),
            $companyId
        ]);
    }
    
    /**
     * Ativa pausa indefinida (manual)
     */
    public function enableIndefinitePause(int $companyId, ?string $reason = null): bool
    {
        if (!$this->hasPauseColumns()) {
            return false;
        }

        $now = new DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        
        $stmt = $this->db->prepare('
            UPDATE companies SET 
                pause_enabled = 1,
                pause_until = NULL,
                pause_reason = ?,
                pause_created_at = ?,
                pause_type = "indefinite"
            WHERE id = ?
        ');
        
        return $stmt->execute([
            $reason ?? 'Estamos em pausa no momento',
            $now->format('Y-m-d H:i:s'),
            $companyId
        ]);
    }
    
    /**
     * Desativa a pausa
     */
    public function disablePause(int $companyId): bool
    {
        if (!$this->hasPauseColumns()) {
            return false;
        }

        $stmt = $this->db->prepare('
            UPDATE companies SET 
                pause_enabled = 0,
                pause_until = NULL,
                pause_reason = NULL,
                pause_created_at = NULL,
                pause_type = "timed"
            WHERE id = ?
        ');
        
        return $stmt->execute([$companyId]);
    }
    
    /**
     * Verifica se está em pausa (método rápido)
     */
    public function isCompanyPaused(int $companyId): bool
    {
        $status = $this->getPauseStatus($companyId);
        return $status['is_paused'];
    }
    
    /**
     * Obtém opções predefinidas de duração para o frontend
     */
    public static function getPredefinedDurations(): array
    {
        return [
            ['minutes' => 15, 'label' => '15 minutos'],
            ['minutes' => 30, 'label' => '30 minutos'],
            ['minutes' => 60, 'label' => '1 hora'],
            ['minutes' => 120, 'label' => '2 horas'],
            ['minutes' => 180, 'label' => '3 horas'],
            ['minutes' => 240, 'label' => '4 horas'],
            ['minutes' => 360, 'label' => '6 horas'],
        ];
    }
    
    /**
     * Obtém motivos predefinidos para pausa
     */
    public static function getPredefinedReasons(): array
    {
        return [
            'Alta demanda no momento',
            'Problemas técnicos temporários',
            'Preparando pedidos em andamento',
            'Em manutenção',
            'Fora do horário de funcionamento',
            'Estoque limitado',
            'Intervalo para descanso',
        ];
    }
}
