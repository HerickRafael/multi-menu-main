# SuperAdmin do Multi Menu: Arquitetura SaaS Multi-tenant (Correta)

## 1. Correcao de direcao arquitetural

Este documento foi revisado para evitar ambiguidade: o modelo correto do produto e **SaaS multi-tenant**.

Estrutura correta:

SUPER ADMIN
	-> Empresa A (tenant)
	-> Empresa B (tenant)
	-> Empresa C (tenant)

Cada empresa possui usuarios, dados e configuracoes proprias.
O Super Admin nao pertence a uma empresa especifica; ele opera acima dos tenants.

## 2. Modelo conceitual correto

### 2.1 Super Admin (camada global)
Responsabilidades:
- Criar e administrar empresas/tenants.
- Ativar, suspender e colocar tenant em manutencao.
- Visualizar metricas globais da plataforma.
- Definir governanca (RBAC, auditoria, flags globais).
- Gerenciar limites e faturamento (arquitetura alvo).

### 2.2 Empresa (tenant)
Cada tenant deve ter:
- Usuarios proprios.
- Dados isolados logicamente por `company_id`.
- Permissoes internas.
- Configuracoes independentes.

### 2.3 Usuarios da empresa
Perfis internos tipicos:
- Admin da empresa.
- Operadores.
- Funcionarios.

## 3. Estado atual do codigo (o que ja existe)

### 3.1 Evidencias de estrutura multi-tenant
No backend e no banco, ha forte presenca de `company_id` em tabelas e consultas.

Exemplos de dominio com `company_id`:
- pedidos (`orders`)
- usuarios (`users`)
- clientes (`customers`)
- logs/eventos operacionais
- configuracoes por empresa
- feature flags por tenant (`tenant_features`)

Rotas e funcoes de Super Admin para gerir empresas tambem existem.
Referencias:
- `routes/web.php` (rotas `superadmin/companies`, `superadmin/stores`)
- `app/controllers/SuperAdminController.php` (gestao de empresas/lojas)
- `app/controllers/SuperAdminDashboardApiController.php` (APIs globais do painel)

### 3.2 Pontos que reforcam visao global do Super Admin
- Login do Super Admin separado da operacao de tenant.
- Dashboard com agregacoes globais.
- Permissoes com escopo de administracao da plataforma.

## 4. Lacunas reais para um SaaS multi-tenant profissional

Mesmo com base multi-tenant ja presente, ainda existem lacunas para maturidade SaaS completa.

### 4.1 Billing e assinaturas (gap principal)
Arquitetura alvo pede entidades e fluxo robusto de faturamento:
- `subscriptions`
- `plans`
- `invoices`
- `usage_limits` / `quotas`

Hoje ha referencia funcional a planos em camadas de landing/marketing, mas nao um modulo de billing SaaS completo no nucleo operacional.

### 4.2 Isolamento padrao e obrigatorio por tenant
Objetivo:
- Toda consulta de dados de tenant deve passar por `company_id` por padrao.
- Evitar qualquer endpoint/servico sem escopo explicito.

Existe middleware de escopo (`TenantScopeMiddleware`), mas a aplicacao inteira ainda precisa de padronizacao forte e cobertura total.

### 4.3 RBAC em dois niveis (global x tenant)
Separacao recomendada:
- RBAC global (Super Admin).
- RBAC interno do tenant (admin/operador/funcionario).

Evita mistura de poderes e reduz risco de permissao cruzada.

### 4.4 Observabilidade por tenant + global
Ideal:
- metricas globais para Super Admin
- metricas por tenant para diagnostico fino
- alertas por tenant (SLA, fila, webhook, latencia)

## 5. Regras de modelagem obrigatorias

Para entidades de tenant, padrao minimo:
- `company_id` obrigatorio
- indice composto com `company_id` + campos de busca
- foreign key para `companies(id)` quando aplicavel

Exemplo correto:

`users`
- id
- company_id
- role_id
- name

`projects` (ou qualquer entidade de dominio)
- id
- company_id
- created_by

## 6. Separacao de fronteiras de rota

### 6.1 Rotas Super Admin (globais)
Escopo:
- criacao/gestao de empresas
- metricas cross-tenant
- governanca da plataforma

Prefixo recomendado:
- `/superadmin/*`
- `/api/superadmin/*`

### 6.2 Rotas de tenant (empresa)
Escopo:
- operacao interna da empresa
- dados exclusivamente da propria empresa

Regra:
- nunca retornar dados fora do `company_id` da sessao/contexto.

## 7. Matriz de responsabilidades correta

### 7.1 Super Admin
- cria tenant
- ativa/suspende tenant
- aplica flags globais
- acompanha saude global
- gerencia billing/planos/limites (alvo)

### 7.2 Admin do tenant
- gerencia usuarios da propria empresa
- ajusta configuracoes da empresa
- acompanha operacao local

### 7.3 Operador/funcionario
- executa tarefas operacionais permitidas pelo RBAC interno

## 8. Checklist de conformidade multi-tenant

Use este checklist para validar se um modulo esta correto em SaaS:

1. Toda tabela de tenant tem `company_id`?
2. Toda query filtra por `company_id`?
3. Toda rota de tenant valida escopo da empresa autenticada?
4. RBAC global e RBAC de tenant estao separados?
5. Logs/auditoria guardam `company_id` quando a acao e de tenant?
6. Existe bloqueio de acesso cruzado entre empresas?
7. Limites de plano/billing estao aplicados por tenant?

## 9. Roadmap de refatoracao recomendado

### Fase 1: endurecimento de isolamento
- Padronizar escopo por `company_id` em todos os repositorios/servicos.
- Cobrir endpoints sem escopo com middleware/policy.

### Fase 2: RBAC completo em duas camadas
- Separar claramente permissoes globais e permissoes de tenant.
- Revisar matriz de autorizacao por rota.

### Fase 3: billing SaaS
- Introduzir `plans`, `subscriptions`, `invoices`, `usage_limits`.
- Aplicar limites por tenant em features e consumo.

### Fase 4: observabilidade SaaS
- Dashboards globais + por tenant.
- Alertas por tenant e SLO/SLA.

## 10. Conclusao

Voce esta correto na direcao: a arquitetura correta e **multi-tenant SaaS de verdade**, com Super Admin acima dos tenants.

O projeto ja tem base importante nesse sentido (muito uso de `company_id` e gestao de lojas/empresas), mas ainda precisa evoluir principalmente em:
- isolamento padrao obrigatorio em 100% dos fluxos,
- separacao formal de RBAC global x tenant,
- billing e limites por plano.

Esse e o caminho para escalar com seguranca, governanca e isolamento real entre empresas.
