#!/bin/bash

# 🚀 Script de Deploy Multi-Menu no Portainer
# Uso: ./deploy.sh [ambiente]
# Exemplo: ./deploy.sh production

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}╔══════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   Multi-Menu Deploy Script          ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════╝${NC}"
echo ""

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker não encontrado. Instale o Docker primeiro.${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose não encontrado. Instale o Docker Compose primeiro.${NC}"
    exit 1
fi

# Verificar se .env existe
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  Arquivo .env não encontrado. Criando a partir de .env.docker...${NC}"
    cp .env.docker .env
    echo -e "${GREEN}✅ Arquivo .env criado. Por favor, edite-o com suas configurações.${NC}"
    echo -e "${YELLOW}📝 Execute: nano .env${NC}"
    exit 0
fi

echo -e "${GREEN}📋 Verificando configurações...${NC}"

# Criar diretórios necessários
echo -e "${GREEN}📁 Criando diretórios...${NC}"
mkdir -p public/uploads
mkdir -p storage/logs
mkdir -p storage/cache
chmod -R 755 storage
chmod -R 755 public/uploads

# Parar containers existentes
echo -e "${YELLOW}🛑 Parando containers existentes...${NC}"
docker-compose down 2>/dev/null || true

# Build e iniciar
echo -e "${GREEN}🔨 Construindo e iniciando containers...${NC}"
docker-compose up -d --build

# Aguardar containers iniciarem
echo -e "${YELLOW}⏳ Aguardando containers iniciarem...${NC}"
sleep 10

# Verificar status
echo -e "${GREEN}📊 Status dos containers:${NC}"
docker-compose ps

# Verificar se app está rodando
if docker-compose ps | grep -q "multi_menu_app.*Up"; then
    echo -e "${GREEN}✅ Aplicação rodando com sucesso!${NC}"
    echo ""
    echo -e "${GREEN}🌐 Acesse a aplicação:${NC}"
    echo -e "   - App: http://localhost:8088"
    echo -e "   - PHPMyAdmin: http://localhost:8081"
    echo ""
    echo -e "${GREEN}📝 Comandos úteis:${NC}"
    echo -e "   - Ver logs: docker-compose logs -f"
    echo -e "   - Parar: docker-compose down"
    echo -e "   - Reiniciar: docker-compose restart"
    echo -e "   - Entrar no container: docker exec -it multi_menu_app bash"
    echo ""
else
    echo -e "${RED}❌ Erro ao iniciar a aplicação!${NC}"
    echo -e "${YELLOW}📋 Veja os logs:${NC}"
    docker-compose logs app
    exit 1
fi

# Verificar se banco está acessível
echo -e "${YELLOW}🔍 Testando conexão com banco de dados...${NC}"
sleep 5
if docker exec multi_menu_app php -r "try { new PDO('mysql:host=db;dbname=multi_menu', 'multi_menu_user', 'multi_menu_password_2025'); echo 'OK'; } catch(Exception \$e) { throw \$e; }" 2>/dev/null | grep -q "OK"; then
    echo -e "${GREEN}✅ Conexão com banco OK!${NC}"
else
    echo -e "${RED}❌ Erro na conexão com banco de dados!${NC}"
    echo -e "${YELLOW}Verifique as credenciais no .env e docker-compose.yml${NC}"
fi

echo ""
echo -e "${GREEN}🎉 Deploy concluído!${NC}"
