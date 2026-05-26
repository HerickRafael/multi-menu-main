<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../middleware/SuperAdminMiddleware.php';

/**
 * APIs JSON do painel Super Admin (sessão obrigatória).
 */
class SuperAdminApiController extends Controller
{
    private const SYSTEM_PROMPT = <<<'TXT'
TXT;

    /** POST /superadmin/api/chat */
    public function chat(array $params): void
    {
        SuperAdminMiddleware::enforce();

        header('Content-Type: application/json; charset=UTF-8');

        $raw = file_get_contents('php://input');
        $body = json_decode((string)$raw, true);

        if (!is_array($body) || empty($body['messages']) || !is_array($body['messages'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Payload inválido.']);

            return;
        }

        $apiKey = trim((string)($_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY') ?: ''));

        if ($apiKey === '') {
            http_response_code(503);
            echo json_encode([
                'ok' => false,
                'error' => 'Configure ANTHROPIC_API_KEY no servidor para usar o assistente.',
            ]);

            return;
        }

        $model = trim((string)($_ENV['CLAUDE_MODEL'] ?? getenv('CLAUDE_MODEL') ?: 'claude-sonnet-4-20250514'));

        $messages = [];

        foreach ($body['messages'] as $m) {
            if (!is_array($m)) {
                continue;
            }
            $role = $m['role'] ?? '';
            $content = $m['content'] ?? '';

            if (!in_array($role, ['user', 'assistant'], true) || !is_string($content) || $content === '') {
                continue;
            }

            if (strlen($content) > 12000) {
                $content = substr($content, 0, 12000);
            }

            $messages[] = ['role' => $role, 'content' => $content];
        }

        if ($messages === []) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Nenhuma mensagem válida.']);

            return;
        }

        $payload = [
            'model' => $model,
            'max_tokens' => 2048,
            'system' => self::SYSTEM_PROMPT,
            'messages' => $messages,
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'anthropic-version: 2023-06-01',
                'x-api-key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 120,
        ]);

        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || !is_string($resp)) {
            http_response_code(502);
            echo json_encode(['ok' => false, 'error' => 'Falha ao contatar o serviço de IA.']);

            return;
        }

        $data = json_decode($resp, true);

        if ($http < 200 || $http >= 300) {
            $msg = is_array($data) ? ($data['error']['message'] ?? $resp) : $resp;
            error_log('[SuperAdminApi] Claude HTTP ' . $http . ': ' . (is_string($msg) ? $msg : $resp));
            http_response_code(502);
            echo json_encode(['ok' => false, 'error' => 'O serviço de IA retornou erro.']);

            return;
        }

        $text = '';

        if (is_array($data) && !empty($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $block) {
                if (is_array($block) && ($block['type'] ?? '') === 'text') {
                    $text .= (string)($block['text'] ?? '');
                }
            }
        }

        echo json_encode(['ok' => true, 'content' => $text], JSON_UNESCAPED_UNICODE);
    }
}
