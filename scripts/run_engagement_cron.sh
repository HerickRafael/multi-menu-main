#!/bin/bash
# Supervisor para engagement_cron.php — auto-restart com backoff + crash alerting
# Uso: ./run_engagement_cron.sh
#   - Modo crontab (padrão): roda uma vez (para uso com crontab a cada 2-5 min)
#   - Modo supervisor: ENGAGEMENT_MODE=supervisor ./run_engagement_cron.sh
#     Roda em loop contínuo com sleep entre ciclos e backoff em falha

set -euo pipefail

CONTAINER_ID=$(docker ps --filter 'name=multimenu_multi_menu_app' --format '{{.ID}}' | head -1)
LOG_FILE="/home/ubuntu/multi-menu/storage/logs/engagement_cron_host.log"
CRASH_LOG="/home/ubuntu/multi-menu/storage/logs/engagement_crashes.log"
SLEEP_INTERVAL="${ENGAGEMENT_INTERVAL:-120}"    # 2 min entre ciclos (padrão)
MAX_FAILURES=5                                    # Máx falhas consecutivas antes de backoff longo
BACKOFF_SECONDS=300                               # 5 min backoff após MAX_FAILURES

if [ -z "$CONTAINER_ID" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Container não encontrado" >> "$LOG_FILE"
    exit 1
fi

run_once() {
    docker exec "$CONTAINER_ID" php /var/www/html/scripts/engagement_cron.php >> "$LOG_FILE" 2>&1
    return $?
}

# Modo crontab (padrão): executa uma vez e sai
if [ "${ENGAGEMENT_MODE:-crontab}" = "crontab" ]; then
    run_once
    exit $?
fi

# Modo supervisor: loop contínuo com auto-restart
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Supervisor iniciado (intervalo=${SLEEP_INTERVAL}s)" >> "$LOG_FILE"

failures=0
total_crashes=0
while true; do
    if run_once; then
        failures=0
    else
        exit_code=$?
        failures=$((failures + 1))
        total_crashes=$((total_crashes + 1))
        
        # Log detalhado do crash — arquivo separado para nunca se perder entre logs normais
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] CRASH #${total_crashes} (consecutivo: ${failures}) exit_code=${exit_code}" >> "$CRASH_LOG"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Falha #${failures} (total: ${total_crashes}) exit=${exit_code}" >> "$LOG_FILE"
        
        if [ "$failures" -ge "$MAX_FAILURES" ]; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] ALERTA CRÍTICO: ${failures} falhas consecutivas (${total_crashes} total) — backoff ${BACKOFF_SECONDS}s" >> "$LOG_FILE"
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] ALERTA CRÍTICO: ${failures} falhas consecutivas (${total_crashes} total)" >> "$CRASH_LOG"
            
            # Gravar alerta no banco para visibilidade no admin
            docker exec "$CONTAINER_ID" php -r "
                require '/var/www/html/app/bootstrap.php';
                \$pdo = db();
                \$pdo->exec(\"INSERT INTO system_heartbeat (service_name, last_run_at, duration_seconds, status, metadata) 
                    VALUES ('engagement_supervisor', NOW(), 0, 'critical', '{\\\"consecutive_failures\\\": ${failures}, \\\"total_crashes\\\": ${total_crashes}}')
                    ON DUPLICATE KEY UPDATE last_run_at = NOW(), status = 'critical', 
                    metadata = '{\\\"consecutive_failures\\\": ${failures}, \\\"total_crashes\\\": ${total_crashes}}'\");
            " 2>/dev/null || true
            
            sleep "$BACKOFF_SECONDS"
            failures=0
            continue
        fi
    fi
    
    sleep "$SLEEP_INTERVAL"
done
