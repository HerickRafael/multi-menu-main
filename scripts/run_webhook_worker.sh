#!/bin/bash
# Worker da fila de webhooks WhatsApp — roda via cron a cada minuto.
# O PHP faz loop contínuo por 55s com polling a cada 2s (latência ~2s).
# Lock via flock para evitar overlap se o ciclo anterior ainda estiver rodando.
#
# Crontab:
#   * * * * * /home/ubuntu/multi-menu/scripts/run_webhook_worker.sh

LOCKFILE="/tmp/webhook_worker.lock"

# flock -n = non-blocking: se já está rodando, sai silenciosamente
exec 200>"$LOCKFILE"
flock -n 200 || exit 0

CONTAINER_ID=$(docker ps -q -f "name=multimenu_multi_menu_app" | head -1)

if [[ -n "$CONTAINER_ID" ]]; then
    docker exec "$CONTAINER_ID" php /var/www/html/scripts/webhook_queue_worker.php >> /home/ubuntu/multi-menu/storage/logs/webhook_worker.log 2>&1
fi
