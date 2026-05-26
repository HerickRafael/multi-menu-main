# Start do Projeto

## Comando unico (recomendado)

```bash
cd /Users/herick/Downloads/multi-menu-main
npm run start:project
```

## Subir ambiente completo (Docker)

```bash
# 1) Iniciar runtime Docker no macOS (Colima)
colima start

# 2) Ir para a pasta do projeto
cd /Users/herick/Downloads/multi-menu-main

# 3) Subir os containers
docker compose up -d

# 4) Validar status
docker compose ps
```

## Acessos locais

- App: http://localhost:8088
- Admin: http://localhost:8088/admin/{slug}
- phpMyAdmin: http://localhost:8081

## Comandos uteis

```bash
# Ver logs em tempo real
docker compose logs -f

# Parar os containers
docker compose down

# Reiniciar os containers
docker compose restart
```

## Frontend (super-admin em Vite, opcional)

```bash
cd /Users/herick/Downloads/multi-menu-main/frontend
npm install
npm run dev
```