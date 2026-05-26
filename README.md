# 🍔 Multi-Menu - Sistema de Cardápio Digital

Sistema completo de cardápio digital com painel administrativo, integração WhatsApp, sistema de pedidos e gestão multi-tenant.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Tecnologias](#tecnologias)
- [Arquitetura](#arquitetura)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Documentação](#documentação)

## 🎯 Visão Geral

O Multi-Menu é uma plataforma SaaS de cardápio digital que permite:

- **Cardápio Digital Público**: Interface responsiva para clientes navegarem produtos
- **Painel Administrativo**: Gestão completa de produtos, pedidos, categorias e configurações
- **Integração WhatsApp**: Envio automático de notificações via Evolution API
- **Sistema de Pedidos**: Fluxo completo de checkout com carrinho, cupons e múltiplas formas de pagamento
- **KDS (Kitchen Display System)**: Painel de cozinha para gestão de pedidos em tempo real
- **Multi-Tenancy**: Suporte a múltiplas empresas/estabelecimentos

## ✨ Alterações Recentes (Sprint de Otimização)

### Fatia 1: Resiliência Frontend + Cache-Busting
- ✅ **11 wrappers públicos** com fallback HTTP 500 resiliente (home, product, landing, cart, customization, addresses, order, profile, checkout_success)
- ✅ **14 views com cache-busting** usando `filemtime()` para invalidar CSS/JS cacheados
- ✅ Validação: `php -l` e `npm run check:legacy` sem erros

### Fatia 2: Checkout Funcional
- ✅ **Zone preservação**: Estado pré-selecionado mantido durante hidratação inicial
- ✅ **Consolidação de pagamento**: Eliminada duplicidade de inicialização, UI sincronizada com hidden fields
- ✅ Validação: `node --check` sem erros

### Correções de Bugs Críticos
- 🐛 **Topo azul**: Fallback de cor agora herda `welcomeBgColor` ao invés de hardcoded `#4361EE`
- 🐛 **Bairros ausentes**: JSON checkout sanitizado com `JSON_HEX_*` (removido `htmlspecialchars` destrutivo)
- ✅ Validação: `php -l`, `node --check`, `npm run check:legacy` aprovados

### Segurança
- 🔒 `node_modules/`, `dist/`, `build/` adicionados ao `.gitignore`

## 🛠 Tecnologias

| Camada | Tecnologia |
|--------|------------|
| **Backend** | PHP 8.4 |
| **Servidor** | Apache com mod_rewrite |
| **Banco de Dados** | MySQL 8.0 |
| **Cache/Sessões** | Redis 7 |
| **Frontend** | Tailwind CSS (CDN) |
| **Container** | Docker + Docker Compose |
| **WhatsApp** | Evolution API v2.3 |

## 🏗 Arquitetura

```
┌─────────────────────────────────────────────────────────────┐
│                        NGINX/Traefik                         │
│                     (Reverse Proxy + SSL)                    │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                     Apache + PHP 8.4                         │
│                    (Container Docker)                        │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │ Controllers │  │   Models    │  │      Services       │  │
│  │             │  │             │  │                     │  │
│  │ • Admin     │  │ • Product   │  │ • CartService       │  │
│  │ • Public    │  │ • Order     │  │ • RecommendationML  │  │
│  │ • API       │  │ • Customer  │  │ • ImageOptimizer    │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└───────┬─────────────────┬───────────────────┬───────────────┘
        │                 │                   │
   ┌────▼────┐      ┌─────▼─────┐      ┌──────▼──────┐
   │  MySQL  │      │   Redis   │      │ Evolution   │
   │   8.0   │      │     7     │      │    API      │
   └─────────┘      └───────────┘      └─────────────┘
```

## 📦 Requisitos

### Produção (Docker)
- Docker 20.10+
- Docker Compose 2.0+
- 2GB RAM mínimo
- 10GB espaço em disco

### Desenvolvimento Local
- PHP 8.4+
- Composer 2.0+
- MySQL 8.0+
- Redis 7+
- Apache com mod_rewrite

## 🚀 Instalação

### Com Docker (Recomendado)

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/multi-menu.git
cd multi-menu

# Suba os containers
docker compose up -d

# Acesse
# Cardápio: http://localhost:8088/{slug}
# Admin:    http://localhost:8088/admin/{slug}
# PHPMyAdmin: http://localhost:8081
```

### Deploy em VPS

```bash
# Com Traefik (SSL automático)
docker stack deploy -c docker-compose.traefik.yml multimenu
```

Veja [docs/DEPLOY.md](docs/DEPLOY.md) para instruções detalhadas.

## 📁 Estrutura do Projeto

```
multi-menu/
├── app/
│   ├── config/          # Configurações (app.php, database.php)
│   ├── controllers/     # Controllers MVC (28 controllers)
│   ├── core/            # Classes core (Router, Database, Auth)
│   ├── helpers/         # Funções auxiliares
│   ├── middleware/      # Middlewares de segurança (14 middlewares)
│   ├── models/          # Models do banco de dados (14 models)
│   ├── services/        # Serviços de negócio (13 services)
│   └── views/           # Templates PHP
│       ├── admin/       # Views do painel admin
│       └── public/      # Views do cardápio público
├── database/
│   ├── multi_menu.sql   # Schema inicial
│   └── migrations/      # Migrações incrementais
├── docs/                # Documentação técnica
├── public/
│   ├── index.php        # Entry point
│   ├── assets/          # CSS, JS, ícones
│   └── uploads/         # Imagens (produtos, logos)
├── routes/
│   └── web.php          # Definição de rotas
├── storage/
│   ├── logs/            # Logs da aplicação
│   └── cache/           # Cache de arquivos
├── docker-compose.yml   # Configuração Docker
└── README.md
```

## 📚 Documentação

| Documento | Descrição |
|-----------|-----------|
| [docs/SISTEMA-MULTI-MENU-ARQUITETURA-COMPLETA.md](docs/SISTEMA-MULTI-MENU-ARQUITETURA-COMPLETA.md) | **Arquitetura técnica completa** (runtime ativo) - Referência principal |
| [docs/ARQUITETURA.md](docs/ARQUITETURA.md) | Arquitetura detalhada do sistema |
| [docs/CONTROLLERS.md](docs/CONTROLLERS.md) | Referência de controllers e rotas |
| [docs/MODELS-SERVICES.md](docs/MODELS-SERVICES.md) | Models e serviços de negócio |
| [docs/SEGURANCA.md](docs/SEGURANCA.md) | Middlewares de segurança |
| [docs/EVOLUTION-API.md](docs/EVOLUTION-API.md) | Integração WhatsApp (Evolution API) |
| [docs/IFOOD-INTEGRATION.md](docs/IFOOD-INTEGRATION.md) | Integração iFood (polling + webhook) |
| [docs/DEPLOY.md](docs/DEPLOY.md) | Guia de deploy (Docker Compose e Swarm) |
| [docs/API.md](docs/API.md) | Documentação da API REST |
| [docs/START_COMMANDS.md](docs/START_COMMANDS.md) | Comandos de inicialização do projeto |

## 🔐 Segurança

O sistema implementa múltiplas camadas de segurança:

- ✅ Proteção CSRF
- ✅ Prevenção XSS
- ✅ Prevenção SQL Injection
- ✅ Rate Limiting
- ✅ Security Headers (HSTS, CSP)
- ✅ Autenticação com bcrypt/Argon2
- ✅ Sessões seguras com fingerprinting
- ✅ API Security com JWT

## 📄 Licença

Proprietário - Todos os direitos reservados.

---

**Multi-Menu** © 2025 - Sistema de Cardápio Digital
