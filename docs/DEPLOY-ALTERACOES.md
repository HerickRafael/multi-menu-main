# Processo de Deploy de Alterações

Este documento descreve o processo completo para que alterações no código reflitam no ambiente de produção.

---

## 📋 Visão Geral da Arquitetura

O Multi-Menu roda em **Docker Swarm** com a seguinte estrutura:

```
┌─────────────────────────────────────────────────────────────┐
│                    Docker Swarm                              │
├─────────────────────────────────────────────────────────────┤
│  Serviço: multimenu_multi_menu_app                          │
│  Imagem: multi-menu-working:latest                          │
│                                                              │
│  Volumes Persistentes:                                       │
│  - multi_menu_uploads → /var/www/html/public/uploads        │
│  - multi_menu_logs    → /var/www/html/storage/logs          │
│  - multi_menu_cache   → /var/www/html/storage/cache         │
│                                                              │
│  ⚠️ NOTA: O código PHP NÃO é montado como volume!           │
│  O código está DENTRO da imagem Docker.                     │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Processo de Deploy (Passo a Passo)

### 1. Fazer as Alterações no Código

Edite os arquivos PHP no diretório local:

```bash
cd /home/ubuntu/multi-menu
# Editar arquivos em app/, public/, routes/, etc.
```

### 2. Rebuild da Imagem Docker

As alterações só entram em produção após rebuild da imagem:

```bash
cd /home/ubuntu/multi-menu

# Build da nova imagem
docker build -t multi-menu-working:latest -f documentations/Dockerfile .
```

**Tempo estimado:** ~10-30 segundos (usa cache das layers anteriores)

### 3. Atualizar o Serviço no Swarm

Forçar o serviço a usar a nova imagem:

```bash
docker service update --force multimenu_multi_menu_app
```

**Tempo estimado:** ~5-10 segundos

### 4. Verificar se o Deploy foi Bem-Sucedido

```bash
# Ver status do serviço
docker service ps multimenu_multi_menu_app

# Ver logs em tempo real
docker service logs -f multimenu_multi_menu_app --tail 50
```

---

## ⚡ Comando Único (Deploy Rápido)

Para fazer todo o processo de uma vez:

```bash
cd /home/ubuntu/multi-menu && \
docker build -t multi-menu-working:latest -f documentations/Dockerfile . && \
docker service update --force multimenu_multi_menu_app
```

### Alias Sugerido

Adicione ao `~/.bashrc` para facilitar:

```bash
alias deploy-menu='cd /home/ubuntu/multi-menu && docker build -t multi-menu-working:latest -f documentations/Dockerfile . && docker service update --force multimenu_multi_menu_app'
```

Depois é só executar:

```bash
deploy-menu
```

---

## 📁 Estrutura de Arquivos Importantes

| Caminho | Descrição |
|---------|-----------|
| `/home/ubuntu/multi-menu/app/` | Código PHP principal (controllers, models, views) |
| `/home/ubuntu/multi-menu/public/` | Assets públicos (CSS, JS, imagens) |
| `/home/ubuntu/multi-menu/routes/web.php` | Definição de rotas |
| `/home/ubuntu/multi-menu/documentations/Dockerfile` | Dockerfile para build |
| `/home/ubuntu/multi-menu/docker-compose.traefik.yml` | Configuração do Swarm |

---

## 🔍 Verificação de Alterações

### Testar se a alteração foi aplicada

```bash
# Ver conteúdo de um arquivo dentro do container
docker exec $(docker ps -q -f name=multimenu_multi_menu_app) cat /var/www/html/app/views/public/product.php | grep "TEXTO_QUE_VOCE_ALTEROU"

# Ou testar via curl
curl -sL "https://wollburger.online/wollburger/produto/1" | grep "TEXTO_ESPERADO"
```

### Ver diferenças antes de fazer deploy

```bash
cd /home/ubuntu/multi-menu
git diff
git status
```

---

## ⚠️ Pontos de Atenção

### 1. Volumes NÃO são afetados pelo rebuild

Os seguintes diretórios **persistem entre deploys** (não são substituídos):

- `public/uploads/` - Imagens enviadas pelos usuários
- `storage/logs/` - Logs da aplicação
- `storage/cache/` - Cache de dados

### 2. Sessões podem ser perdidas

Se o Redis estiver configurado para sessões, elas persistem. Caso contrário, um deploy pode deslogar usuários.

### 3. Cache do navegador

Após alterações em CSS/JS, pode ser necessário:
- Limpar cache do navegador (Ctrl+Shift+R)
- Ou usar versionamento de assets

### 4. Erros no build

Se o build falhar, verifique:

```bash
# Ver erro completo
docker build -t multi-menu-working:latest -f documentations/Dockerfile . 2>&1 | tail -50

# Verificar sintaxe PHP
php -l app/arquivo_modificado.php
```

---

## 🛠️ Troubleshooting

### Alteração não aparece após deploy

1. **Verifique se o build foi feito:**
   ```bash
   docker images | grep multi-menu-working
   ```
   A coluna "CREATED" deve mostrar tempo recente.

2. **Verifique se o serviço foi atualizado:**
   ```bash
   docker service ps multimenu_multi_menu_app
   ```
   Deve mostrar uma task nova com "Running".

3. **Limpe cache do navegador:**
   - Chrome: Ctrl+Shift+R
   - Firefox: Ctrl+F5

4. **Verifique dentro do container:**
   ```bash
   docker exec $(docker ps -q -f name=multimenu_multi_menu_app) cat /var/www/html/app/views/public/product.php | head -20
   ```

### Serviço não inicia

```bash
# Ver logs de erro
docker service logs multimenu_multi_menu_app --tail 100

# Verificar status
docker service ps multimenu_multi_menu_app --no-trunc
```

### Rollback para versão anterior

Se algo der errado, você pode reverter:

```bash
# Ver histórico de commits
cd /home/ubuntu/multi-menu
git log --oneline -10

# Reverter para commit anterior
git checkout <commit_hash> -- app/arquivo.php

# Fazer novo deploy
docker build -t multi-menu-working:latest -f documentations/Dockerfile . && \
docker service update --force multimenu_multi_menu_app
```

---

## 📊 Resumo Visual do Fluxo

```
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│  1. EDITAR       │ ──► │  2. BUILD        │ ──► │  3. DEPLOY       │
│  Código local    │     │  docker build    │     │  service update  │
│  /home/ubuntu/   │     │  Cria nova       │     │  Swarm atualiza  │
│  multi-menu/     │     │  imagem          │     │  o container     │
└──────────────────┘     └──────────────────┘     └──────────────────┘
                                                           │
                                                           ▼
                                                  ┌──────────────────┐
                                                  │  4. VERIFICAR    │
                                                  │  Testar no site  │
                                                  │  wollburger.online│
                                                  └──────────────────┘
```

---

## 📝 Checklist de Deploy

- [ ] Alterações testadas localmente (sintaxe PHP válida)
- [ ] Commit das alterações (opcional, mas recomendado)
- [ ] Build da imagem Docker executado
- [ ] Service update executado
- [ ] Verificação no site de produção
- [ ] Testes funcionais básicos realizados

---

*Última atualização: Dezembro 2025*
