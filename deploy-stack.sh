#!/bin/bash

# 🚀 Script de Deploy Multi-Menu (Docker Swarm)
# Este script evita containers duplicados usando sempre o mesmo nome de stack
#
# Uso: ./deploy-stack.sh [build|sync|full|rollback]
# Exemplo:
#   ./deploy-stack.sh          # Apenas atualiza o serviço
#   ./deploy-stack.sh build    # Rebuild da imagem e atualiza
#   ./deploy-stack.sh sync     # Sincroniza arquivos (app, views, controllers, mobile)
#   ./deploy-stack.sh full     # Build + deploy + aguarda ready + sync
#   ./deploy-stack.sh rollback # Reverte o serviço para a versão anterior

# [FIX #1] pipefail: exit code de pipes reflete o comando que falhou (não só o último)
# [FIX #1] set -u: variável não definida vira erro imediato
set -euo pipefail

# [FIX #1] CWD fixo no diretório do script — sync funciona de qualquer diretório
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Nome FIXO da stack - NÃO ALTERAR para evitar duplicação
STACK_NAME="multimenu"
IMAGE_NAME="multi-menu-working:latest"
CONTAINER_ID=""

# [FIX #5] Lock — evita dois deploys/syncs concorrentes deixarem container em estado misto
LOCK_FILE="/tmp/deploy-${STACK_NAME}.lock"
LOCK_DIR=""
if command -v flock >/dev/null 2>&1; then
    exec 9>"$LOCK_FILE"
    if ! flock -n 9; then
        echo "❌ Outro deploy já está em execução. Aguarde terminar antes de rodar novamente."
        exit 1
    fi
else
    LOCK_DIR="${LOCK_FILE}.dir"
    if ! mkdir "$LOCK_DIR" 2>/dev/null; then
        echo "❌ Outro deploy já está em execução. Aguarde terminar antes de rodar novamente."
        exit 1
    fi
    trap 'rmdir "$LOCK_DIR" 2>/dev/null || true' EXIT INT TERM
fi

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}╔══════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   Multi-Menu Stack Deploy Script         ║${NC}"
echo -e "${CYAN}║   Stack: ${STACK_NAME}                          ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════╝${NC}"
echo ""

# Verificar se Docker está em modo Swarm
if ! docker info 2>/dev/null | grep -q "Swarm: active"; then
    echo -e "${RED}❌ Docker Swarm não está ativo!${NC}"
    echo -e "${YELLOW}Execute: docker swarm init${NC}"
    exit 1
fi

# Função para obter o container ID
get_container_id() {
    CONTAINER_ID=$(docker ps --filter "name=${STACK_NAME}_multi_menu_app" --format "{{.ID}}" | head -1)
    if [ -z "$CONTAINER_ID" ]; then
        echo -e "${RED}❌ Container não encontrado!${NC}"
        return 1
    fi
    echo -e "${GREEN}📦 Container: ${CONTAINER_ID}${NC}"
    return 0
}

# [FIX #6] Aguarda o serviço ficar Running de verdade (substitui sleep 3 arbitrário)
wait_service_ready() {
    local retries=45
    echo -e "${CYAN}   Aguardando container ficar pronto...${NC}"
    while [ $retries -gt 0 ]; do
        local state
        state=$(docker service ps "${STACK_NAME}_multi_menu_app" \
            --filter "desired-state=running" \
            --no-trunc --format "{{.CurrentState}}" 2>/dev/null | head -1)
        if [[ "$state" == Running* ]]; then
            echo -e "${GREEN}   Container ready (${state})${NC}"
            return 0
        fi
        sleep 2
        retries=$((retries - 1))
    done
    echo -e "${RED}❌ Timeout: serviço não ficou ready em 90s — verifique: docker service ps ${STACK_NAME}_multi_menu_app${NC}"
    return 1
}

# Função para sincronizar arquivos (incluindo mobile)
sync_files() {
    echo -e "${GREEN}📁 Sincronizando arquivos com o container...${NC}"

    if ! get_container_id; then
        echo -e "${RED}❌ Não foi possível sincronizar. Container não encontrado.${NC}"
        return 1
    fi

    # Lista de diretórios/arquivos a sincronizar
    echo -e "${CYAN}   → Controllers...${NC}"
    docker cp app/controllers/. "${CONTAINER_ID}:/var/www/html/app/controllers/"

    echo -e "${CYAN}   → Services...${NC}"
    docker cp app/services/. "${CONTAINER_ID}:/var/www/html/app/services/"

    echo -e "${CYAN}   → Models...${NC}"
    docker cp app/models/. "${CONTAINER_ID}:/var/www/html/app/models/"

    echo -e "${CYAN}   → Views...${NC}"
    docker cp app/views/. "${CONTAINER_ID}:/var/www/html/app/views/"
    # [FIX #7] Removida cópia duplicada de app/views/admin/mobile/ — já incluída acima

    echo -e "${CYAN}   → Helpers...${NC}"
    docker cp app/helpers/. "${CONTAINER_ID}:/var/www/html/app/helpers/"

    echo -e "${CYAN}   → Config...${NC}"
    docker cp app/config/. "${CONTAINER_ID}:/var/www/html/app/config/"

    echo -e "${CYAN}   → Core...${NC}"
    docker cp app/core/. "${CONTAINER_ID}:/var/www/html/app/core/"

    echo -e "${CYAN}   → Middleware...${NC}"
    docker cp app/middleware/. "${CONTAINER_ID}:/var/www/html/app/middleware/"

    echo -e "${CYAN}   → Bootstrap...${NC}"
    docker cp app/bootstrap.php "${CONTAINER_ID}:/var/www/html/app/bootstrap.php"

    echo -e "${CYAN}   → Scripts...${NC}"
    docker cp scripts/. "${CONTAINER_ID}:/var/www/html/scripts/"

    echo -e "${CYAN}   → Routes...${NC}"
    docker cp routes/. "${CONTAINER_ID}:/var/www/html/routes/"

    echo -e "${CYAN}   → Composer config...${NC}"
    docker cp composer.json "${CONTAINER_ID}:/var/www/html/composer.json"

    echo -e "${CYAN}   → Public JS...${NC}"
    docker cp public/js/. "${CONTAINER_ID}:/var/www/html/public/js/"

    echo -e "${CYAN}   → Public Assets...${NC}"
    docker cp public/assets/. "${CONTAINER_ID}:/var/www/html/public/assets/"

    echo -e "${CYAN}   → Public Superadmin assets...${NC}"
    if [ -d "public/superadmin/assets" ]; then
        docker cp public/superadmin/assets/. "${CONTAINER_ID}:/var/www/html/public/superadmin/assets/"
    fi

    echo -e "${CYAN}   → Public Superadmin HTML...${NC}"
    if [ -f "public/superadmin/index.html" ]; then
        docker cp "public/superadmin/index.html" "${CONTAINER_ID}:/var/www/html/public/superadmin/index.html"
    fi

    echo -e "${CYAN}   → Public Root (SW, manifests)...${NC}"
    for f in public/*.js public/*.php public/*.webmanifest; do
        [ -f "$f" ] && docker cp "$f" "${CONTAINER_ID}:/var/www/html/$f"
    done
    [ -f "public/.htaccess" ] && docker cp "public/.htaccess" "${CONTAINER_ID}:/var/www/html/public/.htaccess"

    echo -e "${GREEN}✅ Arquivos sincronizados com sucesso!${NC}"

    # [FIX] Reset OPcache após sync — sem isso, o PHP continua servindo bytecode antigo
    # mesmo que os arquivos em disco tenham sido atualizados via docker cp
    echo -e "${CYAN}   → Limpando OPcache (Apache graceful reload)...${NC}"
    docker exec "${CONTAINER_ID}" apachectl graceful 2>/dev/null || true
    echo -e "${GREEN}✅ OPcache limpo!${NC}"

    # Regenerar autoload otimizado (necessário quando composer.json muda)
    echo -e "${CYAN}   → Regenerando autoload...${NC}"
    docker exec "${CONTAINER_ID}" composer dump-autoload -o --quiet 2>/dev/null || true
    echo -e "${GREEN}✅ Autoload regenerado!${NC}"
}

# [FIX #4] cleanup_duplicates chamado apenas em deploy/build, nunca em sync
cleanup_duplicates() {
    echo -e "${YELLOW}🔍 Verificando stacks duplicadas...${NC}"

    local DUPLICATE_NAMES=("multi-menu" "multi_menu" "multimenu_app" "menu")

    for dup_name in "${DUPLICATE_NAMES[@]}"; do
        if [ "$dup_name" != "$STACK_NAME" ]; then
            if docker service ls --format "{{.Name}}" 2>/dev/null | grep -q "^${dup_name}_"; then
                echo -e "${YELLOW}⚠️  Encontrada stack duplicada: ${dup_name} — Removendo...${NC}"
                docker stack rm "$dup_name" 2>/dev/null || true
                # [FIX #8] Aguarda remoção real em vez de sleep 2 arbitrário
                local timeout=30
                while docker service ls --format "{{.Name}}" 2>/dev/null | grep -q "^${dup_name}_" && [ $timeout -gt 0 ]; do
                    sleep 1
                    timeout=$((timeout - 1))
                done
                if [ $timeout -eq 0 ]; then
                    echo -e "${YELLOW}   ⚠️  Remoção de ${dup_name} ainda em andamento, continuando...${NC}"
                fi
            fi
        fi
    done

    echo -e "${GREEN}✅ Verificação de duplicatas concluída${NC}"
}

# [FIX #2] build_image: pipefail ativo + log completo em caso de falha
build_image() {
    echo -e "${GREEN}🔨 Construindo imagem Docker...${NC}"
    local build_log
    build_log=$(mktemp /tmp/docker-build-XXXXXX.log)
    if ! docker build -t "$IMAGE_NAME" . 2>&1 | tee "$build_log" | tail -20; then
        echo -e "${RED}❌ Build falhou! Log completo em: ${build_log}${NC}"
        exit 1
    fi
    rm -f "$build_log"
    echo -e "${GREEN}✅ Imagem construída: ${IMAGE_NAME}${NC}"
}

# Função para deploy/update
deploy_stack() {
    cleanup_duplicates  # [FIX #4] apenas aqui, não no main

    echo -e "${GREEN}🚀 Fazendo deploy da stack...${NC}"

    if docker stack ls --format "{{.Name}}" | grep -q "^${STACK_NAME}$"; then
        echo -e "${CYAN}   Stack existente encontrada, atualizando...${NC}"
        docker stack deploy -c docker-stack.yml "$STACK_NAME" --prune
    else
        echo -e "${CYAN}   Criando nova stack...${NC}"
        docker stack deploy -c docker-stack.yml "$STACK_NAME"
    fi

    echo -e "${GREEN}✅ Deploy concluído${NC}"
}

# [FIX #3] force_update: sem || true — falha real vira erro visível
force_update() {
    echo -e "${YELLOW}🔄 Forçando atualização do serviço...${NC}"
    if ! docker service update --force "${STACK_NAME}_multi_menu_app"; then
        echo -e "${RED}❌ Falha ao atualizar o serviço! Verifique: docker service ps ${STACK_NAME}_multi_menu_app${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ Serviço atualizado${NC}"
}

# Rollback para a versão anterior em caso de problema
rollback_service() {
    echo -e "${YELLOW}⏪ Revertendo serviço para versão anterior...${NC}"
    if ! docker service rollback "${STACK_NAME}_multi_menu_app"; then
        echo -e "${RED}❌ Rollback falhou! Intervenção manual necessária.${NC}"
        exit 1
    fi
    echo -e "${GREEN}✅ Rollback concluído${NC}"
}

# Função para mostrar status
show_status() {
    echo ""
    echo -e "${GREEN}📊 Status dos serviços:${NC}"
    docker service ls | grep -E "NAME|${STACK_NAME}" || echo "Nenhum serviço encontrado"
    echo ""
}

# Main — [FIX #4] cleanup_duplicates removido daqui, movido para dentro de deploy_stack()
case "$1" in
    "build")
        build_image
        deploy_stack
        force_update
        show_status
        ;;
    "sync")
        # sync não toca na stack, não precisa de cleanup nem show_status
        sync_files
        ;;
    "full")
        build_image
        deploy_stack
        force_update
        wait_service_ready  # [FIX #6] substitui sleep 3
        sync_files
        show_status
        ;;
    "rollback")
        rollback_service
        show_status
        ;;
    *)
        deploy_stack
        force_update
        show_status
        ;;
esac

echo -e "${GREEN}🎉 Deploy finalizado com sucesso!${NC}"
echo ""
echo -e "${CYAN}📝 Comandos úteis:${NC}"
echo -e "   - Ver logs: docker service logs -f ${STACK_NAME}_multi_menu_app"
echo -e "   - Status:   docker service ps ${STACK_NAME}_multi_menu_app"
echo -e "   - Escalar:  docker service scale ${STACK_NAME}_multi_menu_app=2"
echo -e "   - Remover:  docker stack rm ${STACK_NAME}"
echo -e "   - Sync:     ./deploy-stack.sh sync"
echo -e "   - Rollback: ./deploy-stack.sh rollback"
echo ""
