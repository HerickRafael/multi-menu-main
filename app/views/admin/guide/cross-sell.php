<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia de Cross-Sell';
$pageDescription = 'Aprenda a configurar recomendações automáticas entre categorias';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M19 7h-8v6h8V7zm-2 4h-4V9h4v2zm4-8H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14z"/></svg>';
$breadcrumbs = [
    ['label' => 'Cross-Sell', 'url' => base_url('admin/' . $slug . '/cross-sell-groups')],
    ['label' => 'Guia']
];
$actions = [
    ['label' => 'Gerenciar Regras', 'url' => base_url('admin/' . $slug . '/cross-sell-groups'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
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
.gc-card h3{font-size:16px;font-weight:600;color:#1e293b;margin:20px 0 8px;display:flex;align-items:center;gap:8px}
.gc-card p{font-size:14px;color:#475569;line-height:1.7;margin-bottom:12px}
.gc-steps{list-style:none;padding:0;margin:16px 0;counter-reset:st}
.gc-steps li{counter-increment:st;display:flex;gap:14px;padding:12px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#334155;line-height:1.6}
.gc-steps li:last-child{border-bottom:none}
.gc-steps li::before{content:counter(st);flex-shrink:0;display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--admin-primary-gradient,var(--admin-primary-color));color:#fff;font-size:13px;font-weight:700;margin-top:1px}
.gc-tip{background:color-mix(in srgb,var(--admin-primary-color) 10%,white);border:1px solid color-mix(in srgb,var(--admin-primary-color) 30%,white);border-radius:12px;padding:14px 16px;margin:14px 0;display:flex;gap:10px;align-items:flex-start}
.gc-tip svg{flex-shrink:0;color:var(--admin-primary-color);margin-top:2px}
.gc-tip .t{font-size:13px;color:#334155;line-height:1.6}
.gc-warn{background:linear-gradient(135deg,#fef3c7,#fde68a33);border:1px solid #fcd34d;border-radius:12px;padding:14px 16px;margin:14px 0;display:flex;gap:10px;align-items:flex-start}
.gc-warn svg{flex-shrink:0;color:#d97706;margin-top:2px}
.gc-warn .t{font-size:13px;color:#92400e;line-height:1.6}
.gc-annot{display:flex;gap:8px;margin-top:10px;padding:10px 14px;background:color-mix(in srgb,var(--admin-primary-color) 8%,white);border:1px solid color-mix(in srgb,var(--admin-primary-color) 25%,white);border-radius:10px;font-size:12px;color:#334155;line-height:1.6}
.gc-annot svg{flex-shrink:0;color:var(--admin-primary-color);margin-top:1px}
.gc-fieldset{border:1px solid #e2e8f0;border-radius:16px;padding:20px;box-shadow:0 1px 2px 0 rgb(0 0 0/.05);margin:16px 0}
.gc-input{width:100%;border-radius:12px;border:1px solid #cbd5e1;background:#fff;padding:8px 12px;font-size:14px;color:#0f172a;box-sizing:border-box}
.gc-input:disabled{background:#f8fafc;color:#94a3b8}
.gc-select{width:100%;border-radius:12px;border:1px solid #cbd5e1;background:#fff;padding:8px 12px;font-size:14px;color:#0f172a;box-sizing:border-box;appearance:auto}
.gc-field{margin-bottom:12px}
.gc-field:last-child{margin-bottom:0}
.gc-label{display:block;font-size:14px;color:#475569;margin-bottom:4px}
.gc-help{font-size:12px;color:#94a3b8;margin-top:4px}
.gc-tag{display:inline-block;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:600;margin-left:6px}
.gc-tag-r{background:#fee2e2;color:#dc2626}
.gc-tag-o{background:#f0fdf4;color:#16a34a}
.gc-chk-row{display:flex;align-items:center;gap:10px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:8px}
.gc-chk-row input[type=checkbox]{width:18px;height:18px;accent-color:var(--admin-primary-color)}
.gc-cmp{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;margin:16px 0;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0}
.gc-cmp th{background:#f8fafc;padding:10px 14px;text-align:left;font-weight:600;color:#475569;border-bottom:2px solid #e2e8f0}
.gc-cmp td{padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#334155}
.gc-cmp tbody tr:last-child td{border-bottom:none}
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
    <a href="#how" data-section="how">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
        Como Funciona
    </a>
    <a href="#form" data-section="form">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
        Formulário
    </a>
    <div class="nav-group">Detalhes</div>
    <a href="#examples" data-section="examples">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707"/></svg>
        Exemplos
    </a>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/cross-sell-groups')) ?>" class="gc-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Gerenciar Regras
    </a>
</nav>

<!-- Main -->
<div>

<!-- VISÃO GERAL -->
<section id="overview" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            O que é Cross-Sell?
        </h2>
        <p>Cross-Sell (venda cruzada) é uma técnica para <b>sugerir categorias complementares</b> quando o cliente está navegando pelo seu cardápio. O sistema mostra automaticamente uma seção de recomendação.</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:20px 0;">
            <div style="background:color-mix(in srgb,var(--admin-primary-color) 10%,white);border-radius:14px;padding:20px;text-align:center;">
                <div style="font-size:32px;margin-bottom:8px;">🍔</div>
                <div style="font-size:14px;font-weight:700;color:#1e293b;">Cliente vê Hambúrgueres</div>
                <div style="font-size:12px;color:#475569;margin-top:4px;">Categoria disparadora</div>
            </div>
            <div style="background:linear-gradient(135deg,#d1fae5,#a7f3d044);border-radius:14px;padding:20px;text-align:center;">
                <div style="font-size:32px;margin-bottom:8px;">🥤</div>
                <div style="font-size:14px;font-weight:700;color:#1e293b;">"Que tal uma Bebida?"</div>
                <div style="font-size:12px;color:#475569;margin-top:4px;">Categoria recomendada</div>
            </div>
        </div>

        <div class="gc-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Aumenta o ticket médio!</b> Clientes que veem recomendações tendem a adicionar mais itens ao pedido.</span>
        </div>
    </div>
</section>

<!-- COMO FUNCIONA -->
<section id="how" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
            Como Funciona
        </h2>

        <ol class="gc-steps">
            <li><div>Você escolhe uma <b>categoria disparadora</b> — a que o cliente está vendo</div></li>
            <li><div>Marca quais <b>categorias recomendar</b> quando a disparadora for acessada</div></li>
            <li><div>Define um <b>título personalizado</b> para cada sugestão (ex: "Que tal uma bebida?")</div></li>
            <li><div>A recomendação aparece automaticamente na página da categoria disparadora</div></li>
        </ol>

        <h3>📐 Estrutura da Regra</h3>
        <div style="background:#f8fafc;border:2px dashed #e2e8f0;border-radius:14px;padding:20px;margin:12px 0;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="background:var(--admin-primary-color);color:#fff;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;">🍔 Hambúrgueres</div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                <span style="font-size:13px;color:#64748b;">dispara</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
                    <div style="font-size:12px;color:#64748b;margin-bottom:4px;">Recomendação 1</div>
                    <div style="font-size:14px;font-weight:600;color:#1e293b;">🥤 Bebidas</div>
                    <div style="font-size:12px;color:var(--admin-primary-color);margin-top:2px;">"Que tal uma bebida?"</div>
                </div>
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
                    <div style="font-size:12px;color:#64748b;margin-bottom:4px;">Recomendação 2</div>
                    <div style="font-size:14px;font-weight:600;color:#1e293b;">🍟 Acompanhamentos</div>
                    <div style="font-size:12px;color:var(--admin-primary-color);margin-top:2px;">"Adicione um acompanhamento!"</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FORMULÁRIO -->
<section id="form" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
            Formulário — Bloco a Bloco
        </h2>

        <!-- Campo 1: Categoria Disparadora -->
        <h3>① Categoria Disparadora</h3>
        <div class="gc-fieldset">
            <div class="gc-field">
                <span class="gc-label">Quando o cliente ver<span class="gc-tag gc-tag-r">Obrigatório</span></span>
                <select class="gc-select" disabled>
                    <option>Selecione a categoria</option>
                    <option selected>🍔 Hambúrgueres</option>
                    <option>🍕 Pizzas</option>
                    <option>🥤 Bebidas</option>
                </select>
            </div>
        </div>
        <div class="gc-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>A categoria que <b>ativa</b> a recomendação. Quando o cliente acessar esta categoria, as sugestões aparecerão.</span>
        </div>

        <!-- Campo 2: Categorias Recomendadas -->
        <h3>② Categorias Recomendadas</h3>
        <div class="gc-fieldset">
            <p style="font-size:13px;color:#64748b;margin-bottom:12px;">Marque as categorias para recomendar:</p>
            <div class="gc-chk-row">
                <input type="checkbox" disabled>
                <span style="font-size:14px;color:#334155;">🍕 Pizzas</span>
            </div>
            <div class="gc-chk-row" style="border-color:var(--admin-primary-color);background:color-mix(in srgb,var(--admin-primary-color) 5%,white);">
                <input type="checkbox" checked disabled>
                <span style="font-size:14px;color:#334155;font-weight:600;">🥤 Bebidas</span>
            </div>
            <div style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:8px;background:#f8fafc;">
                <span class="gc-label" style="margin-bottom:6px;">Título da seção para Bebidas</span>
                <input type="text" class="gc-input" value="Que tal uma bebida?" disabled>
            </div>
            <div class="gc-chk-row" style="border-color:var(--admin-primary-color);background:color-mix(in srgb,var(--admin-primary-color) 5%,white);">
                <input type="checkbox" checked disabled>
                <span style="font-size:14px;color:#334155;font-weight:600;">🍟 Acompanhamentos</span>
            </div>
            <div style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;">
                <span class="gc-label" style="margin-bottom:6px;">Título da seção para Acompanhamentos</span>
                <input type="text" class="gc-input" value="Adicione um acompanhamento!" disabled>
            </div>
        </div>
        <div class="gc-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Ao marcar uma categoria, aparece o campo de <b>título personalizado</b> — é o texto que o cliente verá como título da seção de sugestão.</span>
        </div>
    </div>
</section>

<!-- EXEMPLOS -->
<section id="examples" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707"/></svg>
            Exemplos Práticos
        </h2>

        <div style="display:grid;gap:14px;">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:16px;">
                <div style="font-size:15px;font-weight:700;color:#166534;margin-bottom:6px;">🍔 Hamburguerias</div>
                <div style="font-size:13px;color:#15803d;line-height:1.6;">
                    Hambúrgueres → <b>Bebidas</b> ("Peça sua bebida!") + <b>Acompanhamentos</b> ("Batata frita?")<br>
                    Combos → <b>Sobremesas</b> ("Feche com chave de ouro!")
                </div>
            </div>
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:14px;padding:16px;">
                <div style="font-size:15px;font-weight:700;color:#0369a1;margin-bottom:6px;">🍕 Pizzarias</div>
                <div style="font-size:13px;color:#075985;line-height:1.6;">
                    Pizzas Salgadas → <b>Bebidas</b> ("Escolha sua bebida!") + <b>Bordas</b> ("Troque a borda!")<br>
                    Pizzas Doces → <b>Bebidas quentes</b> ("Um café para acompanhar?")
                </div>
            </div>
            <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:14px;padding:16px;">
                <div style="font-size:15px;font-weight:700;color:#7c3aed;margin-bottom:6px;">🍣 Japonesa</div>
                <div style="font-size:13px;color:#5b21b6;line-height:1.6;">
                    Sushis → <b>Bebidas</b> ("Que tal um suco?") + <b>Entradas</b> ("Comece com um edamame!")<br>
                    Temakis → <b>Acompanhamentos</b> ("Adicione missoshiru!")
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DICAS -->
<section id="tips" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Dicas & Boas Práticas
        </h2>
        <div style="display:grid;gap:12px;">
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Títulos persuasivos</div><div style="font-size:13px;color:#15803d;">Use perguntas ou sugestões: "Que tal uma bebida?" funciona melhor que "Bebidas".</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Combinações naturais</div><div style="font-size:13px;color:#15803d;">Recomende categorias que fazem sentido juntas. Hambúrguer → Bebida é natural. Hambúrguer → Salada pode não funcionar.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Desative sem excluir</div><div style="font-size:13px;color:#15803d;">Use o toggle de status para pausar regras em vez de excluí-las. Assim pode reativar facilmente.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">⚠️</span>
                <div><div style="font-size:14px;font-weight:600;color:#9a3412;margin-bottom:2px;">Não exagere</div><div style="font-size:13px;color:#c2410c;">2-3 recomendações por categoria é ideal. Muitas sugestões podem confundir o cliente.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">⚠️</span>
                <div><div style="font-size:14px;font-weight:600;color:#9a3412;margin-bottom:2px;">Evite referência circular</div><div style="font-size:13px;color:#c2410c;">Se Hambúrgueres recomenda Bebidas, Bebidas não precisa recomendar Hambúrgueres de volta.</div></div>
            </div>
        </div>
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
