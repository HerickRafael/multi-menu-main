#!/bin/bash
# Script para executar o ifood_polling_cron.php no container Docker

CONTAINER_ID=$(docker ps --filter 'name=multimenu_multi_menu_app' --format '{{.ID}}' | head -1)

if [ -n "$CONTAINER_ID" ]; then
    docker exec "$CONTAINER_ID" php /var/www/html/scripts/ifood_polling_cron.php >> /home/ubuntu/multi-menu/storage/logs/ifood_polling.log 2>&1
fi
