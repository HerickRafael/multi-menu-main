# 🚀 Guia de Deploy

Instruções para deploy do Multi-Menu em diferentes ambientes.

## Índice

- [Docker Compose (Desenvolvimento)](#docker-compose-desenvolvimento)
- [Docker Compose (Produção)](#docker-compose-produção)
- [Docker Swarm + Traefik](#docker-swarm--traefik)
- [VPS Manual](#vps-manual)
- [Variáveis de Ambiente](#variáveis-de-ambiente)
- [Checklist de Deploy](#checklist-de-deploy)

---

## Docker Compose (Desenvolvimento)

### Requisitos
- Docker 20.10+
- Docker Compose 2.0+

### Passos

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/multi-menu.git
cd multi-menu

# Inicie os containers
docker compose up -d

# Verifique os logs
docker compose logs -f app
```

### Serviços disponíveis

| Serviço | URL | Descrição |
|---------|-----|-----------|
| App | http://localhost:8088 | Aplicação principal |
| PHPMyAdmin | http://localhost:8081 | Gerenciador MySQL |
| MySQL | localhost:3307 | Banco de dados |
| Redis | localhost:6380 | Cache/Sessões |

### Estrutura de Volumes

```yaml
volumes:
  - ./public/uploads:/var/www/html/public/uploads
  - ./storage/logs:/var/www/html/storage/logs
  - ./storage/cache:/var/www/html/storage/cache
  - ./app:/var/www/html/app  # Hot reload em dev
```

---

## Docker Compose (Produção)

### docker-compose.prod.yml

```yaml
services:
  app:
    build:
      context: .
      dockerfile: documentations/Dockerfile
    container_name: multimenu_app
    restart: always
    environment:
      - APP_ENV=production
      - DB_HOST=db
      - DB_PORT=3306
      - DB_NAME=multimenu
      - DB_USER=multimenu
      - DB_PASS=${DB_PASSWORD}
      - REDIS_HOST=redis
      - REDIS_PORT=6379
      - REDIS_PASSWORD=${REDIS_PASSWORD}
      - BASE_URL=https://seu-dominio.com
    volumes:
      - uploads:/var/www/html/public/uploads
      - logs:/var/www/html/storage/logs
    depends_on:
      - db
      - redis
    networks:
      - multimenu

  db:
    image: mysql:8.0
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: multimenu
      MYSQL_USER: multimenu
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - multimenu

  redis:
    image: redis:7-alpine
    restart: always
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
    networks:
      - multimenu

volumes:
  uploads:
  logs:
  mysql_data:
  redis_data:

networks:
  multimenu:
    driver: bridge
```

### Deploy

```bash
# Crie o arquivo .env
cat > .env << EOF
DB_ROOT_PASSWORD=senha-root-segura
DB_PASSWORD=senha-app-segura
REDIS_PASSWORD=senha-redis-segura
EOF

# Deploy
docker compose -f docker-compose.prod.yml up -d
```

---

## Docker Swarm + Traefik

Para deploy com SSL automático via Let's Encrypt.

### 1. Inicializar Swarm

```bash
docker swarm init
```

### 2. Criar rede overlay

```bash
docker network create --driver=overlay traefik-public
```

### 3. Deploy Traefik

```yaml
# traefik.yml
services:
  traefik:
    image: traefik:v2.10
    command:
      - --providers.docker.swarmMode=true
      - --providers.docker.exposedbydefault=false
      - --entrypoints.web.address=:80
      - --entrypoints.websecure.address=:443
      - --certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=web
      - --certificatesresolvers.letsencrypt.acme.email=seu@email.com
      - --certificatesresolvers.letsencrypt.acme.storage=/letsencrypt/acme.json
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - traefik-certificates:/letsencrypt
    networks:
      - traefik-public
    deploy:
      placement:
        constraints:
          - node.role == manager

volumes:
  traefik-certificates:

networks:
  traefik-public:
    external: true
```

### 4. Deploy Multi-Menu

```yaml
# docker-compose.traefik.yml
services:
  app:
    image: seu-registry/multimenu:latest
    environment:
      - APP_ENV=production
      - BASE_URL=https://cardapio.seudominio.com
    deploy:
      labels:
        - traefik.enable=true
        - traefik.http.routers.multimenu.rule=Host(`cardapio.seudominio.com`)
        - traefik.http.routers.multimenu.entrypoints=websecure
        - traefik.http.routers.multimenu.tls.certresolver=letsencrypt
        - traefik.http.services.multimenu.loadbalancer.server.port=80
    networks:
      - traefik-public
      - backend

networks:
  traefik-public:
    external: true
  backend:
```

```bash
# Deploy
docker stack deploy -c docker-compose.traefik.yml multimenu
```

---

## VPS Manual

### Requisitos

- Ubuntu 22.04 LTS
- 2GB RAM
- 20GB SSD

### 1. Instalar dependências

```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP 8.4
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install php8.4 php8.4-fpm php8.4-mysql php8.4-redis php8.4-gd php8.4-curl php8.4-xml php8.4-mbstring -y

# Instalar Apache
sudo apt install apache2 libapache2-mod-php8.4 -y
sudo a2enmod rewrite

# Instalar MySQL
sudo apt install mysql-server -y

# Instalar Redis
sudo apt install redis-server -y

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Configurar MySQL

```bash
sudo mysql_secure_installation

mysql -u root -p << EOF
CREATE DATABASE multimenu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'multimenu'@'localhost' IDENTIFIED BY 'sua-senha-segura';
GRANT ALL PRIVILEGES ON multimenu.* TO 'multimenu'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### 3. Clonar e configurar

```bash
cd /var/www
sudo git clone https://github.com/seu-usuario/multi-menu.git
cd multi-menu

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Permissões
sudo chown -R www-data:www-data /var/www/multi-menu
sudo chmod -R 755 /var/www/multi-menu
sudo chmod -R 775 /var/www/multi-menu/storage
```

### 4. Configurar Apache

```apache
# /etc/apache2/sites-available/multimenu.conf
<VirtualHost *:80>
    ServerName cardapio.seudominio.com
    DocumentRoot /var/www/multi-menu/public
    
    <Directory /var/www/multi-menu/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/multimenu_error.log
    CustomLog ${APACHE_LOG_DIR}/multimenu_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite multimenu.conf
sudo systemctl reload apache2
```

### 5. SSL com Certbot

```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d cardapio.seudominio.com
```

---

## Variáveis de Ambiente

### Obrigatórias

```env
# Aplicação
APP_ENV=production
BASE_URL=https://cardapio.seudominio.com

# Banco de Dados
DB_HOST=localhost
DB_PORT=3306
DB_NAME=multimenu
DB_USER=multimenu
DB_PASS=senha-segura

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=

# Sessão
SESSION_NAME=MULTIMENU_SESS
```

### Opcionais

```env
# Evolution API (WhatsApp)
EVOLUTION_API_URL=https://api.evolution.com
EVOLUTION_API_KEY=sua-api-key

# Debug
APP_DEBUG=false

# Timezone
APP_TIMEZONE=America/Sao_Paulo
```

---

## Checklist de Deploy

### Pré-deploy

- [ ] Backup do banco de dados atual
- [ ] Testar aplicação localmente
- [ ] Verificar variáveis de ambiente
- [ ] Gerar nova APP_KEY

### Deploy

- [ ] Executar migrations do banco
- [ ] Limpar caches
- [ ] Verificar permissões de diretórios
- [ ] Testar uploads de imagem
- [ ] Verificar logs de erro

### Pós-deploy

- [ ] Testar fluxo de pedido completo
- [ ] Verificar envio de WhatsApp
- [ ] Testar painel admin
- [ ] Monitorar logs por 24h
- [ ] Verificar SSL válido

### Segurança

- [ ] Senhas fortes em todos os serviços
- [ ] Firewall configurado (apenas 80/443)
- [ ] SSH apenas por chave
- [ ] Backups automáticos configurados
- [ ] Fail2ban ativo

---

## Atualização

### Com Docker

```bash
# Pull nova imagem
docker pull seu-registry/multimenu:latest

# Atualizar serviço
docker service update --image seu-registry/multimenu:latest multimenu_app
```

### Sem Docker

```bash
cd /var/www/multi-menu

# Backup
cp -r public/uploads /tmp/uploads_backup

# Atualizar código
git pull origin main

# Dependências
composer install --no-dev --optimize-autoloader

# Limpar cache
php artisan cache:clear  # se aplicável

# Permissões
sudo chown -R www-data:www-data .
```

---

## Troubleshooting

### Erro 500

```bash
# Verificar logs
tail -f storage/logs/app.log
tail -f /var/log/apache2/error.log

# Permissões
sudo chmod -R 775 storage
sudo chown -R www-data:www-data storage
```

### Redis não conecta

```bash
# Verificar serviço
systemctl status redis

# Testar conexão
redis-cli ping
```

### Uploads não funcionam

```bash
# Verificar limite PHP
php -i | grep upload_max_filesize

# Verificar permissões
ls -la public/uploads/
```
