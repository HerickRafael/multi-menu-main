<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia WhatsApp';
$pageDescription = 'Instâncias, conexão, notificações e engajamento automático';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>';
$breadcrumbs = [
    ['label' => 'WhatsApp', 'url' => base_url('admin/' . $slug . '/evolution/instances')],
    ['label' => 'Guia']
];
$actions = [
    ['label' => 'Gerenciar Instâncias', 'url' => base_url('admin/' . $slug . '/evolution/instances'), 'icon' => '<svg class="h-4 w-4"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18.01"/></svg>', 'primary' => true]
];

ob_start();
?>

<style>
.gc-nav{position:sticky;top:80px}
.gc-nav a{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:10px;font-size:13px;color:#64748b;text-decoration:none;transition:all .2s;border-left:3px solid transparent}
.gc-nav a:hover{color:#334155;background:#f1f5f9}
.gc-nav a.active{color:#fff;background:var(--admin-primary-color);border-left-color:var(--admin-primary-color);font-weight:600}
.gc-nav a.active svg{color:#fff}
.gc-nav .nav-group{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;padding:16px 12px 4px}
.gc-cta{display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--admin-primary-gradient,var(--admin-primary-color))!important;background-color:var(--admin-primary-color)!important;border-radius:12px;color:#fff!important;text-decoration:none;font-size:14px;font-weight:600;margin-top:16px;transition:opacity .2s}
.gc-cta:hover{opacity:.9;color:#fff!important}
.gc-cta svg{color:#fff!important}
.gc-sec{scroll-margin-top:100px}
.gc-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;margin-bottom:24px}
.gc-card h2{font-size:20px;font-weight:700;color:#0f172a;margin-bottom:6px;display:flex;align-items:center;gap:10px}
.gc-card h2 svg{color:var(--admin-primary-color)}
.gc-card h2 .ic{width:38px;height:38px;border-radius:10px;background:var(--admin-primary-color,#6366f1);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.gc-card h2 .ic svg{color:#fff}
.gc-card h3{font-size:16px;font-weight:600;color:#1e293b;margin:20px 0 8px;display:flex;align-items:center;gap:8px}
.gc-card p{font-size:14px;color:#475569;line-height:1.7;margin-bottom:12px}
.gc-sec > h2{font-size:20px;font-weight:700;color:#0f172a;margin-bottom:14px;display:flex;align-items:center;gap:10px}
.gc-sec > h2 svg{color:var(--admin-primary-color)}
.gc-sec > h2 .gc-icon{width:28px;height:28px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--admin-primary-color,#6366f1) 12%,white);color:var(--admin-primary-color,#6366f1);font-size:15px}
.gc-table{width:100%;border-collapse:collapse;font-size:.85rem;margin-top:8px}
.gc-table th{text-align:left;padding:8px 10px;background:color-mix(in srgb,var(--admin-primary-color,#6366f1) 8%,white);color:#334155;font-weight:600;border-bottom:2px solid #e2e8f0}
.gc-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9;color:#475569}
.gc-table tr:last-child td{border-bottom:none}
.gc-tip{background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:14px 16px;font-size:.86rem;color:#92400e;margin-top:10px}
.gc-tip strong{color:#78350f}
.gc-warn{background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 16px;font-size:.86rem;color:#991b1b;margin-top:10px}
.gc-warn strong{color:#7f1d1d}
.gc-code{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-family:monospace;font-size:.82rem;color:#334155;margin-top:8px;white-space:pre-wrap}
</style>

<div class="mx-auto max-w-7xl p-4">
<?php include __DIR__ . '/../components/page-header.php'; ?>

<div style="display:grid;grid-template-columns:240px 1fr;gap:32px;align-items:start;">

<!-- Sidebar -->
<nav class="gc-nav" style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:16px 10px;">
    <div class="nav-group">Guia</div>
    <a href="#overview" class="active" data-section="overview">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        Visão Geral
    </a>
    <a href="#instances" data-section="instances">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
        Instâncias
    </a>
    <a href="#connection" data-section="connection">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
        Conexão
    </a>
    <a href="#settings" data-section="settings">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06"/></svg>
        Configurações
    </a>
    <div class="nav-group">Detalhes</div>
    <a href="#notifications" data-section="notifications">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        Notificações
    </a>
    <a href="#engagement" data-section="engagement">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
        Engajamento
    </a>
    <a href="#auto-reply" data-section="auto-reply">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
        Respostas Auto
    </a>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/evolution/instances')) ?>" class="gc-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18.01"/></svg>
        Gerenciar WhatsApp
    </a>
</nav>

<!-- Main -->
<div>

<!-- Visão Geral -->
  <section class="gc-sec" id="overview">
    <h2><span class="gc-icon">📱</span> Visão Geral</h2>
    <div class="gc-card">
      <p>A integração com a <strong>Evolution API</strong> permite enviar mensagens automáticas pelo WhatsApp. A arquitetura é:</p>
      <table class="gc-table" style="margin-top:10px">
        <thead><tr><th>Componente</th><th>Função</th></tr></thead>
        <tbody>
          <tr><td><strong>Evolution API</strong></td><td>Servidor que conecta ao WhatsApp Web e envia/recebe mensagens</td></tr>
          <tr><td><strong>Instância</strong></td><td>Uma sessão do WhatsApp conectada (cada número = 1 instância)</td></tr>
          <tr><td><strong>Webhook</strong></td><td>Ponte entre o Multi-Menu e a Evolution API para receber eventos</td></tr>
          <tr><td><strong>QR Code</strong></td><td>Autenticação do WhatsApp — escaneado pelo celular para conectar</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Instâncias -->
  <section class="gc-sec" id="instances">
    <h2><span class="gc-icon">📋</span> Instâncias</h2>
    <div class="gc-card">
      <p>A tela de instâncias mostra todas as sessões WhatsApp configuradas:</p>
      <ul>
        <li><strong>Status</strong> — conectada (verde), desconectada (amarelo) ou erro (vermelho)</li>
        <li><strong>Criar instância</strong> — define um nome e cria a sessão na Evolution API</li>
        <li><strong>Sincronizar</strong> — busca instâncias do servidor e atualiza a lista local</li>
      </ul>
    </div>
  </section>

  <!-- Conexão -->
  <section class="gc-sec" id="connection">
    <h2><span class="gc-icon">🔗</span> Conexão</h2>
    <div class="gc-card">
      <h3>Fluxo de conexão</h3>
      <ol>
        <li><strong>Crie</strong> a instância (ou use uma existente)</li>
        <li><strong>Gere o QR Code</strong> — clique em "Conectar"</li>
        <li><strong>Escaneie</strong> com o WhatsApp do celular (Dispositivos → Vincular)</li>
        <li>Aguarde o status mudar para <strong>"Conectada"</strong></li>
      </ol>
      <div class="gc-warn">
        <strong>Desconexão:</strong> Se o celular perder internet ou você deslogar do WhatsApp Web no aparelho, a instância desconecta. Será necessário reconectar via QR Code.
      </div>
    </div>
    <div class="gc-card">
      <h3>Token da Instância</h3>
      <p>Cada instância tem um <strong>token único</strong> gerado automaticamente. Esse token é usado internamente para autenticar chamadas à API. Não compartilhe.</p>
    </div>
  </section>

  <!-- Configurações -->
  <section class="gc-sec" id="settings">
    <h2><span class="gc-icon">⚙️</span> Configurações da Instância</h2>
    <div class="gc-card">
      <p>Toggles de comportamento do WhatsApp conectado:</p>
      <table class="gc-table" style="margin-top:10px">
        <thead><tr><th>Toggle</th><th>O que faz</th></tr></thead>
        <tbody>
          <tr><td><strong>Rejeitar chamadas</strong></td><td>Recusa automaticamente ligações recebidas. Pode enviar mensagem automática ao rejeitar.</td></tr>
          <tr><td><strong>Ler mensagens</strong></td><td>Marca todas as mensagens recebidas como lidas automaticamente</td></tr>
          <tr><td><strong>Sempre online</strong></td><td>Mantém o status "online" constantemente</td></tr>
          <tr><td><strong>Ignorar grupos</strong></td><td>Não processa mensagens de grupos do WhatsApp</td></tr>
          <tr><td><strong>Visualizar status</strong></td><td>Marca stories/status como visualizados automaticamente</td></tr>
          <tr><td><strong>Sincronizar histórico</strong></td><td>Baixa o histórico completo de conversas do WhatsApp</td></tr>
        </tbody>
      </table>
      <div class="gc-tip">
        <strong>"Rejeitar chamadas"</strong> é recomendado para números comerciais — evita chamadas e envia uma mensagem educada redirecionando para o cardápio.
      </div>
    </div>
  </section>

  <!-- Notificações -->
  <section class="gc-sec" id="notifications">
    <h2><span class="gc-icon">🔔</span> Notificação de Pedido</h2>
    <div class="gc-card">
      <p>Receba uma mensagem no WhatsApp a cada novo pedido:</p>
      <table class="gc-table" style="margin-top:10px">
        <thead><tr><th>Campo</th><th>Descrição</th></tr></thead>
        <tbody>
          <tr><td><strong>Ativar</strong></td><td>Toggle para ligar/desligar notificações de novos pedidos</td></tr>
          <tr><td><strong>Número principal</strong></td><td>Recebe todas as notificações de pedido</td></tr>
          <tr><td><strong>Número secundário</strong></td><td>Backup (opcional) — também recebe as notificações</td></tr>
        </tbody>
      </table>
      <div class="gc-tip">
        <strong>Dica:</strong> Use o número do dono como principal e o do gerente como secundário para redundância.
      </div>
    </div>
  </section>

  <!-- Engajamento -->
  <section class="gc-sec" id="engagement">
    <h2><span class="gc-icon">🎯</span> Engajamento Automático</h2>
    <div class="gc-card">
      <p>Envia mensagens automáticas para <strong>recuperar clientes</strong> em dois cenários:</p>
    </div>
    <div class="gc-card">
      <h3>Cenário 1: Cadastro sem pedido</h3>
      <p>Cliente se cadastrou mas nunca fez um pedido.</p>
      <ul>
        <li><strong>Tempo de espera</strong> — minutos após o cadastro para enviar a mensagem (ex: 30 min)</li>
        <li>Ideal para converter visitantes curiosos em compradores</li>
      </ul>
    </div>
    <div class="gc-card">
      <h3>Cenário 2: Cliente inativo</h3>
      <p>Cliente que já comprou mas parou de pedir.</p>
      <ul>
        <li><strong>Período de inatividade</strong> — dias sem pedido para disparar mensagem (ex: 7 dias)</li>
        <li>Ideal para reativação de clientes antigos</li>
      </ul>
    </div>
    <div class="gc-warn">
      <strong>Moderação:</strong> Enviar mensagens com muita frequência pode incomodar. Recomende no mínimo 30 min para cenário 1 e 7+ dias para cenário 2.
    </div>
  </section>

  <!-- Respostas Automáticas -->
  <section class="gc-sec" id="auto-reply">
    <h2><span class="gc-icon">💬</span> Respostas Automáticas</h2>
    <div class="gc-card">
      <h3>Fora do Expediente</h3>
      <p>Quando a loja está fechada (fora dos horários configurados), envia uma mensagem automática ao cliente.</p>
      <p style="margin-top:6px"><strong>Placeholders disponíveis:</strong></p>
      <div class="gc-code">{saudacao} → Bom dia / Boa tarde / Boa noite
{dia} → Dia atual da semana
{hora} → Horário atual</div>
    </div>
    <div class="gc-card">
      <h3>Pausa Programada</h3>
      <p>Durante uma pausa agendada (ex: feriado, manutenção), envia mensagem informando o motivo e previsão de retorno.</p>
      <p style="margin-top:6px"><strong>Placeholders disponíveis:</strong></p>
      <div class="gc-code">{motivo} → Motivo da pausa programada
{tempo_restante} → Tempo até reabrir</div>
    </div>
    <div class="gc-tip">
      <strong>Personalize!</strong> Use os placeholders para criar mensagens dinâmicas. Ex: "Olá! {saudacao}! Estamos fechados agora ({dia}, {hora}). Volte amanhã!"
    </div>
  </section>

  <!-- Dicas -->
  <section class="gc-sec" id="tips">
    <h2><span class="gc-icon">💡</span> Dicas</h2>
    <div class="gc-card">
      <ul>
        <li><strong>Mantenha o celular conectado à internet</strong> — a sessão depende do aparelho estar online.</li>
        <li><strong>Use "Rejeitar chamadas"</strong> com mensagem personalizada para profissionalismo.</li>
        <li><strong>"Ignorar grupos"</strong> evita que mensagens de grupos sejam processadas como comandos.</li>
        <li><strong>Engajamento moderado</strong> — mensagens muito frequentes podem ser marcadas como spam.</li>
        <li><strong>Teste a mensagem</strong> fora do expediente enviando uma mensagem para o número da instância.</li>
        <li><strong>Webhook configurado automaticamente</strong> — o sistema configura o webhook da Evolution API ao criar a instância.</li>
      </ul>
    </div>
  </section>

</div>
</div>
<div style="height:80px;"></div>
</div>

<script>
(function(){var secs=document.querySelectorAll('.gc-sec'),links=document.querySelectorAll('.gc-nav a[data-section]');function up(){var y=window.scrollY+150,c='';var atBottom=(window.innerHeight+window.scrollY)>=(document.documentElement.scrollHeight-80);if(atBottom&&secs.length){c=secs[secs.length-1].id}else{secs.forEach(function(s){if(s.offsetTop<=y)c=s.id})}links.forEach(function(a){a.classList.toggle('active',a.dataset.section===c)})}window.addEventListener('scroll',up);up();links.forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();var t=document.getElementById(this.dataset.section);if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})})})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
