<?php
/**
 * Reset completo de um número de teste.
 * Remove TODOS os dados de interação WhatsApp para permitir teste limpo.
 * 
 * Uso: php scripts/dev/reset_test_number.php [número]
 * Ex:  php scripts/dev/reset_test_number.php 51920017687
 */

declare(strict_types=1);

require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/core/Helpers.php';

// ============================================================================
// Configuração
// ============================================================================

$phone = $argv[1] ?? '51920017687';

// Normalizar para formato canônico
$phone = normalizePhone($phone);

if (strlen($phone) < 12 || strlen($phone) > 15) {
    echo "ERRO: Número inválido: '{$phone}'\n";
    echo "Uso: php scripts/dev/reset_test_number.php [número]\n";
    exit(1);
}

// Variantes do número que podem estar armazenadas
// A Evolution API pode armazenar com ou sem o código de país (55)
$phoneVariants = [$phone];
if (strlen($phone) >= 12 && substr($phone, 0, 2) === '55') {
    $phoneVariants[] = substr($phone, 2); // sem código país
}

$jidVariants = array_map(fn($p) => $p . '@s.whatsapp.net', $phoneVariants);
$phoneLike = '%' . substr($phone, -9) . '%'; // últimos 9 dígitos para LIKE

echo "================================================================\n";
echo " RESET DE NÚMERO DE TESTE\n";
echo "================================================================\n";
echo " Número:    {$phone}\n";
echo " Variantes: " . implode(', ', $phoneVariants) . "\n";
echo " Data:      " . date('Y-m-d H:i:s') . "\n";
echo "================================================================\n\n";

try {
    $db = db();
} catch (\Throwable $e) {
    echo "ERRO: Falha ao conectar ao banco: " . $e->getMessage() . "\n";
    exit(1);
}

$totalDeleted = 0;

// Helper para executar DELETE com tratamento de tabela inexistente
function safeDelete(PDO $db, string $table, string $whereClause, array $params, string $description): int
{
    try {
        $stmt = $db->prepare("DELETE FROM `{$table}` WHERE {$whereClause}");
        $stmt->execute($params);
        $count = $stmt->rowCount();
        $status = $count > 0 ? "✓ {$count} registro(s)" : "- nenhum";
        echo "  {$status}  ← {$description}\n";
        return $count;
    } catch (\PDOException $e) {
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            echo "  ⊘ tabela não existe  ← {$description}\n";
        } else {
            echo "  ✗ ERRO: {$e->getMessage()}  ← {$description}\n";
        }
        return 0;
    }
}

// Helper: gera placeholders IN (?,?) para N variantes
function phonePlaceholders(array $variants): string
{
    return implode(',', array_fill(0, count($variants), '?'));
}

// ============================================================================
// 1. Mensagens recebidas
// ============================================================================
echo "[1/10] Mensagens recebidas (whatsapp_received_messages)\n";
$p = phonePlaceholders($phoneVariants);
$jidP = phonePlaceholders($jidVariants);
$totalDeleted += safeDelete($db, 'whatsapp_received_messages', 
    "phone IN ({$p}) OR remote_jid IN ({$jidP}) OR remote_jid LIKE ?", 
    array_merge($phoneVariants, $jidVariants, [$phoneLike]),
    'Histórico de mensagens recebidas do número'
);

// ============================================================================
// 2. Logs de envio
// ============================================================================
echo "\n[2/10] Logs de envio (whatsapp_send_log)\n";
$totalDeleted += safeDelete($db, 'whatsapp_send_log', 
    "phone IN ({$p}) OR remote_jid IN ({$jidP}) OR remote_jid LIKE ?", 
    array_merge($phoneVariants, $jidVariants, [$phoneLike]),
    'Todas as tentativas de envio registradas'
);

// ============================================================================
// 3. Fila de retry/fallback
// ============================================================================
echo "\n[3/10] Fila de retry (whatsapp_failed_queue)\n";
$totalDeleted += safeDelete($db, 'whatsapp_failed_queue', 
    "remote_jid IN ({$jidP}) OR remote_jid LIKE ?", 
    array_merge($jidVariants, [$phoneLike]),
    'Mensagens pendentes de reprocessamento'
);

// ============================================================================
// 4. Respostas fora do horário (cooldown)
// ============================================================================
echo "\n[4/10] Respostas fora do horário (out_of_hours_responses)\n";
$totalDeleted += safeDelete($db, 'out_of_hours_responses', 
    "phone IN ({$p})", 
    $phoneVariants,
    'Controle de cooldown de auto-resposta'
);

// ============================================================================
// 5. Mensagens pendentes fora do horário (LID)
// ============================================================================
echo "\n[5/10] Pendentes fora do horário (pending_out_of_hours_messages)\n";
$lids = [];
try {
    $stmt = $db->prepare("SELECT lid FROM whatsapp_lid_mapping WHERE phone IN ({$p})");
    $stmt->execute($phoneVariants);
    $lids = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) {}

if (!empty($lids)) {
    $lidP = phonePlaceholders($lids);
    $totalDeleted += safeDelete($db, 'pending_out_of_hours_messages', 
        "lid IN ({$lidP})", 
        $lids,
        'Mensagens pendentes para LIDs mapeados'
    );
} else {
    echo "  - nenhum LID mapeado encontrado\n";
}

// ============================================================================
// 6. Takeover humano
// ============================================================================
echo "\n[6/10] Takeover humano (whatsapp_human_takeover)\n";
$totalDeleted += safeDelete($db, 'whatsapp_human_takeover', 
    "phone IN ({$p}) OR remote_jid IN ({$jidP}) OR remote_jid LIKE ?", 
    array_merge($phoneVariants, $jidVariants, [$phoneLike]),
    'Sessões de atendimento humano ativas/expiradas'
);

// ============================================================================
// 7. Fila de engagement
// ============================================================================
echo "\n[7/10] Fila de engagement (customer_engagement_queue)\n";
$engagementPhones = array_merge($phoneVariants, array_map(fn($p) => '+' . $p, $phoneVariants));
$engP = phonePlaceholders($engagementPhones);
$totalDeleted += safeDelete($db, 'customer_engagement_queue', 
    "customer_phone IN ({$engP})", 
    $engagementPhones,
    'Mensagens de engajamento pendentes/enviadas'
);

// ============================================================================
// 8. Log de engagement
// ============================================================================
echo "\n[8/10] Log de engagement (customer_engagement_log)\n";
$totalDeleted += safeDelete($db, 'customer_engagement_log', 
    "customer_phone IN ({$engP})", 
    $engagementPhones,
    'Histórico de engajamento enviado'
);

// ============================================================================
// 9. Mapeamento LID → Phone
// ============================================================================
echo "\n[9/10] Mapeamento LID (whatsapp_lid_mapping)\n";
$totalDeleted += safeDelete($db, 'whatsapp_lid_mapping', 
    "phone IN ({$p})", 
    $phoneVariants,
    'Mapeamentos LID → telefone real'
);

// ============================================================================
// 10. Mapeamento PushName → Phone
// ============================================================================
echo "\n[10/10] Mapeamento PushName (whatsapp_pushname_mapping)\n";
$totalDeleted += safeDelete($db, 'whatsapp_pushname_mapping', 
    "phone IN ({$p})", 
    $phoneVariants,
    'Mapeamentos pushName → telefone'
);

// ============================================================================
// Resumo
// ============================================================================
echo "\n================================================================\n";
echo " RESUMO\n";
echo "================================================================\n";
echo " Total de registros removidos: {$totalDeleted}\n";

if ($totalDeleted === 0) {
    echo " Nenhum dado encontrado — número já estava limpo.\n";
} else {
    echo " Número {$phone} resetado com sucesso.\n";
}

echo "\n NOTA: Dados estruturais (customers, orders) NÃO foram removidos.\n";
echo " Para resetar a sessão na Evolution API, use o painel admin.\n";
echo "================================================================\n";
