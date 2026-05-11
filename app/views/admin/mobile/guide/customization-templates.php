<?php
$pageTitle = $pageTitle ?? 'Guia de Personalização';
$activeNav = $activeNav ?? 'products';
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
.gi-tbl{width:100%;border-collapse:collapse;font-size:.78rem;margin-top:6px}
.gi-tbl th{text-align:left;padding:6px 8px;background:var(--primary-light);color:#334155;font-weight:600}
.gi-tbl td{padding:6px 8px;border-bottom:1px solid #f1f5f9;color:#475569}
.gi-badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:.72rem;font-weight:600}
.gi-badge-green{background:#dcfce7;color:#166534}
.gi-badge-blue{background:#dbeafe;color:#1e40af}
.gi-badge-purple{background:#f3e8ff;color:#6b21a8}
.gi-tip{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 12px;font-size:.8rem;color:#92400e;margin-top:8px}
.gi-tip strong{color:#78350f}
.gi-warn{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 12px;font-size:.8rem;color:#991b1b;margin-top:8px}
.gi-warn strong{color:#7f1d1d}
.gi-fab{position:fixed;bottom:80px;right:16px;width:44px;height:44px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 2px 8px rgba(0,0,0,.18);text-decoration:none;z-index:30}
</style>

<div class="gi-wrap">
  <h1 class="gi-title">Grupos de Personalização</h1>
  <p class="gi-sub">Templates reutilizáveis de ingredientes.</p>

  <nav class="gi-pills" id="gi-nav">
    <a href="#overview">Visão Geral</a>
    <a href="#modes">Modos</a>
    <a href="#form">Formulário</a>
    <a href="#sync">Sincronização</a>
    <a href="#tips">Dicas</a>
  </nav>

  <!-- Visão Geral -->
  <section class="gi-section" id="overview">
    <h2><span class="gi-ic">📋</span> Visão Geral</h2>
    <div class="gi-card">
      <p>Grupos de personalização são <strong>templates reutilizáveis</strong> de ingredientes adicionais. Crie um grupo e aplique em vários produtos de uma vez.</p>
      <table class="gi-tbl" style="margin-top:10px">
        <tr><th>Conceito</th><th>Descrição</th></tr>
        <tr><td><strong>Grupo</strong></td><td>Conjunto de ingredientes (ex: "Adicionais")</td></tr>
        <tr><td><strong>Modo</strong></td><td>Como o cliente seleciona (Extra ou Escolha)</td></tr>
        <tr><td><strong>Sincronização</strong></td><td>Edições replicam nos produtos vinculados</td></tr>
      </table>
    </div>
  </section>

  <!-- Modos -->
  <section class="gi-section" id="modes">
    <h2><span class="gi-ic">🔀</span> Modos de Seleção</h2>

    <div class="gi-card">
      <h3><span class="gi-badge gi-badge-green">Extra</span> Adicionar livremente</h3>
      <p>O cliente adiciona ingredientes sem limite global. Cada item tem Mín e Máx individual.</p>
      <p style="margin-top:4px"><em>Ex: "Adicionais" — bacon, queijo extra, ovo...</em></p>
    </div>

    <div class="gi-card">
      <h3><span class="gi-badge gi-badge-blue">Escolha</span> Escolher ingrediente</h3>
      <p>O cliente escolhe entre opções com limite global de seleções.</p>
      <table class="gi-tbl" style="margin-top:6px">
        <tr><td><strong>Seleções mín</strong></td><td>Quantas deve marcar no mínimo (0 = opcional)</td></tr>
        <tr><td><strong>Seleções máx</strong></td><td>Limite de opções a escolher</td></tr>
      </table>
      <p style="margin-top:6px"><em>Ex: "Escolha o molho" — mín 1, máx 2</em></p>
    </div>
  </section>

  <!-- Formulário -->
  <section class="gi-section" id="form">
    <h2><span class="gi-ic">📝</span> Formulário</h2>
    <div class="gi-card">
      <table class="gi-tbl">
        <tr><th>Campo</th><th>Descrição</th></tr>
        <tr><td><strong>Nome</strong></td><td>Nome visível ao cliente</td></tr>
        <tr><td><strong>Grupo ativo</strong></td><td>Inativo = não aparece para copiar</td></tr>
        <tr><td><strong>Ocultar repetidos</strong></td><td>Se ingrediente já existe em outro grupo, fica invisível aqui</td></tr>
        <tr><td><strong>Modo</strong></td><td>Extra ou Escolha</td></tr>
      </table>
    </div>
    <div class="gi-card">
      <h3>Ingredientes (por item)</h3>
      <table class="gi-tbl">
        <tr><td><strong>Ingrediente</strong></td><td>Busca por nome (typeahead)</td></tr>
        <tr><td><strong>Mín</strong></td><td>Quantidade mínima (0 = opcional)</td></tr>
        <tr><td><strong>Máx</strong></td><td>Quantidade máxima permitida</td></tr>
        <tr><td><strong>Padrão</strong></td><td>Já vem selecionado</td></tr>
        <tr><td><strong>Qty Pad</strong></td><td>Quantidade pré-selecionada</td></tr>
      </table>
    </div>
  </section>

  <!-- Sincronização -->
  <section class="gi-section" id="sync">
    <h2><span class="gi-ic">🔄</span> Sincronização</h2>
    <div class="gi-card">
      <p>Ao editar um grupo vinculado a produtos, com <strong>"Sincronizar"</strong> ativado as alterações são aplicadas automaticamente nos produtos.</p>
      <div class="gi-warn">
        <strong>Atenção:</strong> A sincronização substitui os ingredientes do grupo nos produtos. Edições manuais feitas diretamente no produto serão sobrepostas.
      </div>
    </div>
  </section>

  <!-- Dicas -->
  <section class="gi-section" id="tips">
    <h2><span class="gi-ic">💡</span> Dicas</h2>
    <div class="gi-card">
      <ul>
        <li><strong>Nomeie de forma clara</strong> — o nome aparece para o cliente.</li>
        <li><strong>Modo Escolha mín=1</strong> torna a seleção obrigatória.</li>
        <li><strong>Ingrediente padrão</strong> é útil para itens que "vêm no produto".</li>
        <li><strong>Crie grupos genéricos</strong> e reutilize em vários produtos.</li>
        <li><strong>Use "Ocultar repetidos"</strong> quando um ingrediente aparece em múltiplos grupos.</li>
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
