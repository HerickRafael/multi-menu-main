<?php
$pageTitle = $pageTitle ?? 'Guia WhatsApp';
$activeNav = $activeNav ?? 'settings';
$showBackButton = $showBackButton ?? true;
?>

<style>
:root{--primary:var(--admin-primary-color,#4361ee);--primary-light:color-mix(in srgb,var(--primary) 12%,white);--primary-dark:color-mix(in srgb,var(--primary) 60%,black)}
.gi-wrap{padding:16px 16px 100px}
.gi-title{font-size:1.25rem;font-weight:800;color:var(--text-primary,#1e293b);margin-bottom:2px}
.gi-sub{font-size:.82rem;color:#64748b;margin-bottom:18px}
.gi-pills{display:flex;gap:6px;overflow-x:auto;padding-bottom:8px;margin-bottom:18px;-webkit-overflow-scrolling:touch;scrollbar-width:none}
.gi-pills::-webkit-scrollbar{display:none}
.gi-pills a{flex-shrink:0;padding:6px 14px;border-radius:10px;font-size:.78rem;font-weight:600;color:#64748b;background:#f1f5f9;text-decoration:none;white-space:nowrap;transition:all .2s}
.gi-pills a.active{background:var(--primary);color:#fff}
.gi-section{margin-bottom:24px;scroll-margin-top:70px}
.gi-section h2{font-size:1rem;font-weight:700;color:var(--text-primary,#1e293b);margin-bottom:10px;display:flex;align-items:center;gap:6px}
.gi-section h2 .gi-ic{width:24px;height:24px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;background:var(--primary-light);font-size:13px}
.gi-card{background:var(--card-bg,#fff);border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin-bottom:10px}
.gi-card h3{font-size:.88rem;font-weight:700;color:#334155;margin-bottom:6px}
.gi-card p,.gi-card li{font-size:.82rem;color:#475569;line-height:1.55}
.gi-card ul{list-style:disc;padding-left:16px;margin-top:4px}
.gi-card ol{padding-left:16px;margin-top:4px}
.gi-tbl{width:100%;border-collapse:collapse;font-size:.78rem;margin-top:6px}
.gi-tbl th{text-align:left;padding:6px 8px;background:var(--primary-light);color:#334155;font-weight:600}
.gi-tbl td{padding:6px 8px;border-bottom:1px solid #f1f5f9;color:#475569}
.gi-tip{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 12px;font-size:.8rem;color:#92400e;margin-top:8px}
.gi-tip strong{color:#78350f}
.gi-warn{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 12px;font-size:.8rem;color:#991b1b;margin-top:8px}
.gi-warn strong{color:#7f1d1d}
.gi-code{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-family:monospace;font-size:.75rem;color:#334155;margin-top:6px;white-space:pre-wrap}
.gi-fab{position:fixed;bottom:80px;right:16px;width:44px;height:44px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 2px 8px rgba(0,0,0,.18);text-decoration:none;z-index:30}
</style>

<div class="gi-wrap">
  <h1 class="gi-title">WhatsApp / Evolution API</h1>
  <p class="gi-sub">Automação de mensagens via WhatsApp.</p>

  <nav class="gi-pills" id="gi-nav">
    <a href="#overview">Visão Geral</a>
    <a href="#connection">Conexão</a>
    <a href="#settings">Config</a>
    <a href="#notifications">Notificações</a>
    <a href="#engagement">Engajamento</a>
    <a href="#auto-reply">Respostas</a>
    <a href="#tips">Dicas</a>
  </nav>

  <!-- Visão Geral -->
  <section class="gi-section" id="overview">
    <h2><span class="gi-ic">📱</span> Visão Geral</h2>
    <div class="gi-card">
      <p>A <strong>Evolution API</strong> conecta o WhatsApp ao sistema para enviar mensagens automáticas.</p>
      <table class="gi-tbl" style="margin-top:8px">
        <tr><td><strong>Instância</strong></td><td>Sessão do WhatsApp (1 número = 1 instância)</td></tr>
        <tr><td><strong>QR Code</strong></td><td>Autenticação escaneada pelo celular</td></tr>
        <tr><td><strong>Webhook</strong></td><td>Ponte para receber eventos (configurado auto)</td></tr>
      </table>
    </div>
  </section>

  <!-- Conexão -->
  <section class="gi-section" id="connection">
    <h2><span class="gi-ic">🔗</span> Conexão</h2>
    <div class="gi-card">
      <ol>
        <li>Crie ou selecione uma instância</li>
        <li>Clique em <strong>"Conectar"</strong> → QR Code</li>
        <li>Escaneie com WhatsApp do celular</li>
        <li>Aguarde status <strong>"Conectada"</strong></li>
      </ol>
      <div class="gi-warn"><strong>Atenção:</strong> Se o celular perder internet, a sessão desconecta.</div>
    </div>
  </section>

  <!-- Configurações -->
  <section class="gi-section" id="settings">
    <h2><span class="gi-ic">⚙️</span> Configurações</h2>
    <div class="gi-card">
      <table class="gi-tbl">
        <tr><th>Toggle</th><th>O que faz</th></tr>
        <tr><td><strong>Rejeitar chamadas</strong></td><td>Recusa ligações + mensagem automática</td></tr>
        <tr><td><strong>Ler mensagens</strong></td><td>Marca como lidas automaticamente</td></tr>
        <tr><td><strong>Sempre online</strong></td><td>Status "online" constante</td></tr>
        <tr><td><strong>Ignorar grupos</strong></td><td>Não processa mensagens de grupos</td></tr>
        <tr><td><strong>Visualizar status</strong></td><td>Marca stories como vistos</td></tr>
        <tr><td><strong>Sincronizar histórico</strong></td><td>Baixa conversas do WhatsApp</td></tr>
      </table>
    </div>
  </section>

  <!-- Notificações -->
  <section class="gi-section" id="notifications">
    <h2><span class="gi-ic">🔔</span> Notificações de Pedido</h2>
    <div class="gi-card">
      <p>Receba WhatsApp a cada novo pedido:</p>
      <table class="gi-tbl" style="margin-top:6px">
        <tr><td><strong>Número principal</strong></td><td>Recebe todas as notificações</td></tr>
        <tr><td><strong>Número secundário</strong></td><td>Backup (opcional)</td></tr>
      </table>
    </div>
  </section>

  <!-- Engajamento -->
  <section class="gi-section" id="engagement">
    <h2><span class="gi-ic">🎯</span> Engajamento Automático</h2>
    <div class="gi-card">
      <h3>1. Cadastro sem pedido</h3>
      <p>Cliente cadastrou mas não pediu. <strong>Tempo de espera:</strong> minutos após cadastro (ex: 30min).</p>
    </div>
    <div class="gi-card">
      <h3>2. Cliente inativo</h3>
      <p>Cliente parou de pedir. <strong>Período de inatividade:</strong> dias sem pedido (ex: 7 dias).</p>
    </div>
    <div class="gi-warn"><strong>Moderação:</strong> Mínimo 30min (cenário 1) e 7+ dias (cenário 2) para evitar spam.</div>
  </section>

  <!-- Respostas Automáticas -->
  <section class="gi-section" id="auto-reply">
    <h2><span class="gi-ic">💬</span> Respostas Automáticas</h2>
    <div class="gi-card">
      <h3>Fora do Expediente</h3>
      <p>Mensagem quando a loja está fechada.</p>
      <div class="gi-code">{saudacao} = Bom dia/tarde/noite
{dia} = Dia da semana
{hora} = Horário atual</div>
    </div>
    <div class="gi-card">
      <h3>Pausa Programada</h3>
      <p>Mensagem durante pausa (feriado, etc.).</p>
      <div class="gi-code">{motivo} = Motivo da pausa
{tempo_restante} = Tempo até reabrir</div>
    </div>
  </section>

  <!-- Dicas -->
  <section class="gi-section" id="tips">
    <h2><span class="gi-ic">💡</span> Dicas</h2>
    <div class="gi-card">
      <ul>
        <li><strong>Celular online</strong> — a sessão depende do aparelho.</li>
        <li><strong>"Rejeitar chamadas"</strong> é recomendado para números comerciais.</li>
        <li><strong>"Ignorar grupos"</strong> evita processamento desnecessário.</li>
        <li><strong>Engajamento moderado</strong> — evita marcação de spam.</li>
        <li><strong>Teste</strong> mensagens fora do expediente enviando para o número da instância.</li>
      </ul>
    </div>
  </section>
</div>

<a href="#" class="gi-fab" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">↑</a>

<script>
(function(){
  const nav=document.getElementById('gi-nav');if(!nav)return;
  const links=[...nav.querySelectorAll('a[href^="#"]')];
  const secs=links.map(l=>document.getElementById(l.getAttribute('href').slice(1))).filter(Boolean);
  const io=new IntersectionObserver(entries=>{
    entries.forEach(e=>{if(e.isIntersecting){
      const i=secs.indexOf(e.target);if(i>-1)links.forEach((l,j)=>l.classList.toggle('active',j===i));
    }});
  },{rootMargin:'-30% 0px -60% 0px'});
  secs.forEach(s=>io.observe(s));
})();
</script>
