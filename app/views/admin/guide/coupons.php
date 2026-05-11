<?php
$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle = 'Guia de Cupons de Desconto';
$pageDescription = 'Aprenda a criar cupons genéricos e individuais, configurar limites e estratégias';
$pageIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58s1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41s-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>';
$breadcrumbs = [
    ['label' => 'Fidelidade & Descontos', 'url' => base_url('admin/' . $slug . '/loyalty-discount')],
    ['label' => 'Guia de Cupons']
];
$actions = [
    ['label' => 'Criar Cupom', 'url' => base_url('admin/' . $slug . '/coupons/create'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true]
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
.gc-input::placeholder{color:#94a3b8}
.gc-field{margin-bottom:12px}
.gc-field:last-child{margin-bottom:0}
.gc-label{display:block;font-size:14px;color:#475569;margin-bottom:4px}
.gc-help{font-size:12px;color:#94a3b8;margin-top:4px}
.gc-row{display:grid;gap:12px}
.gc-row-2{grid-template-columns:1fr 1fr}

.gc-tag{display:inline-block;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:600;margin-left:6px}
.gc-tag-r{background:#fee2e2;color:#dc2626}
.gc-tag-o{background:#f0fdf4;color:#16a34a}

.gc-toggle-row{display:flex;align-items:center;gap:12px;padding:8px 0}
.gc-toggle-track{width:44px;height:24px;border-radius:12px;position:relative;flex-shrink:0;background:#cbd5e1}
.gc-toggle-track.on{background:var(--admin-primary-color)}
.gc-toggle-thumb{position:absolute;left:2px;top:2px;width:20px;height:20px;background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.gc-toggle-track.on .gc-toggle-thumb{transform:translateX(20px)}

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
    <a href="#form" data-section="form">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
        Formulário
    </a>
    <div class="nav-group">Detalhes</div>
    <a href="#types" data-section="types">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
        Genérico vs Individual
    </a>
    <a href="#limits" data-section="limits">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Limites de Uso
    </a>
    <a href="#strategies" data-section="strategies">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        Estratégias
    </a>
    <a href="#tips" data-section="tips">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Dicas
    </a>
    <a href="<?= e(base_url('admin/' . $slug . '/coupons/create')) ?>" class="gc-cta">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        Criar Cupom
    </a>
</nav>

<!-- Main -->
<div>

<!-- ═══ VISÃO GERAL ═══ -->
<section id="overview" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            O que são Cupons?
        </h2>
        <p>Cupons são códigos de desconto que seus clientes digitam no momento do pedido para obter um <b>desconto em porcentagem</b> sobre o valor total.</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:20px 0;">
            <div style="background:color-mix(in srgb,var(--admin-primary-color) 10%,white);border-radius:14px;padding:20px;text-align:center;">
                <div style="font-size:32px;margin-bottom:8px;">🎫</div>
                <div style="font-size:14px;font-weight:700;color:#1e293b;">Genérico</div>
                <div style="font-size:12px;color:#475569;margin-top:4px;">Qualquer cliente pode usar</div>
                <div style="font-size:11px;color:#64748b;margin-top:6px;">Ex: PROMO10, BLACKFRIDAY</div>
            </div>
            <div style="background:linear-gradient(135deg,#d1fae5,#a7f3d044);border-radius:14px;padding:20px;text-align:center;">
                <div style="font-size:32px;margin-bottom:8px;">👤</div>
                <div style="font-size:14px;font-weight:700;color:#1e293b;">Individual</div>
                <div style="font-size:12px;color:#475569;margin-top:4px;">Só um cliente específico</div>
                <div style="font-size:11px;color:#64748b;margin-top:6px;">Ex: cupom via WhatsApp</div>
            </div>
        </div>

        <ol class="gc-steps">
            <li><div>Crie o cupom com código, desconto % e limite de usos</div></li>
            <li><div>Divulgue o código (redes sociais, WhatsApp, impressos)</div></li>
            <li><div>Cliente digita o código no carrinho antes de finalizar</div></li>
            <li><div>Desconto aplicado automaticamente no total do pedido</div></li>
        </ol>

        <div class="gc-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Código gerado automaticamente!</b> Se deixar o campo de código em branco, o sistema gera um código aleatório único.</span>
        </div>
    </div>
</section>

<!-- ═══ FORMULÁRIO ═══ -->
<section id="form" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
            Formulário — Bloco a Bloco
        </h2>
        <p>Veja cada campo do formulário de criação de cupom:</p>

        <!-- Campo 1: Código -->
        <h3>① Código do Cupom</h3>
        <div class="gc-fieldset">
            <div class="gc-field">
                <span class="gc-label">Código do Cupom<span class="gc-tag gc-tag-r">Obrigatório</span></span>
                <input type="text" class="gc-input" value="PRIMEIRACOMPRA10" disabled style="text-transform:uppercase;font-weight:600;letter-spacing:1px;">
                <p class="gc-help">Use letras, números e hífen. Ex: PROMO10, BF2024, JOAO-VIP</p>
            </div>
        </div>
        <div class="gc-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>O código é <b>convertido para maiúsculas</b> automaticamente. O cliente digita no carrinho exatamente como aparece aqui. Deixe em branco para gerar automaticamente.</span>
        </div>

        <!-- Campo 2: Telefone -->
        <h3>② Telefone do Cliente</h3>
        <div class="gc-fieldset">
            <div class="gc-field">
                <span class="gc-label">Telefone do Cliente<span class="gc-tag gc-tag-o">Opcional</span></span>
                <input type="text" class="gc-input" placeholder="Ex: 11999999999" disabled>
                <p class="gc-help">Deixe em branco para cupom genérico ou informe para cupom individual.</p>
            </div>
        </div>
        <div class="gc-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Sem telefone</b> = qualquer cliente usa. <b>Com telefone</b> = só o cliente com esse WhatsApp pode usar.</span>
        </div>

        <!-- Campo 3/4: Desconto + Limite -->
        <h3>③ Desconto & Limite</h3>
        <div class="gc-fieldset">
            <div class="gc-row gc-row-2">
                <div class="gc-field">
                    <span class="gc-label">Desconto (%)<span class="gc-tag gc-tag-r">Obrigatório</span></span>
                    <input type="number" class="gc-input" value="10" disabled>
                    <p class="gc-help">Entre 1% e 100%</p>
                </div>
                <div class="gc-field">
                    <span class="gc-label">Limite de Usos<span class="gc-tag gc-tag-r">Obrigatório</span></span>
                    <input type="number" class="gc-input" value="50" disabled>
                    <p class="gc-help">Total de vezes que pode ser usado</p>
                </div>
            </div>
        </div>
        <div class="gc-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Desconto</b> = porcentagem sobre o total do pedido. <b>Limite</b> = quando atingido, o cupom é automaticamente desativado.</span>
        </div>

        <!-- Campo 5: Toggle múltiplos usos -->
        <h3>④ Uso por Cliente</h3>
        <div class="gc-fieldset">
            <div class="gc-toggle-row">
                <div class="gc-toggle-track on"><div class="gc-toggle-thumb"></div></div>
                <span style="font-size:14px;color:#334155;">Permitir que o mesmo cliente use múltiplas vezes</span>
            </div>
            <p class="gc-help">Se desativado, cada cliente só poderá usar o cupom uma vez.</p>
        </div>
        <div class="gc-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span><b>Ativado:</b> mesmo cliente pode usar várias vezes (até o limite total). <b>Desativado:</b> 1 uso por cliente, mesmo que sobre limite.</span>
        </div>
    </div>
</section>

<!-- ═══ GENÉRICO VS INDIVIDUAL ═══ -->
<section id="types" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
            Genérico vs Individual
        </h2>
        <p>A diferença está no campo <b>Telefone do Cliente</b>:</p>

        <table class="gc-cmp">
            <thead><tr><th>Característica</th><th>🎫 Genérico</th><th>👤 Individual</th></tr></thead>
            <tbody>
                <tr><td><b>Telefone</b></td><td>Em branco</td><td>Preenchido</td></tr>
                <tr><td><b>Quem usa</b></td><td>Qualquer cliente</td><td>Só o cliente do telefone</td></tr>
                <tr><td><b>Caso de uso</b></td><td>Promoção geral, redes sociais</td><td>Compensação, fidelização</td></tr>
                <tr><td><b>Segurança</b></td><td>Pode vazar</td><td>Totalmente seguro</td></tr>
                <tr><td><b>Exemplo</b></td><td>PROMO10, BLACKFRIDAY</td><td>Cupom enviado via WhatsApp</td></tr>
            </tbody>
        </table>

        <div class="gc-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Quando usar qual?</b> Genérico para campanhas de marketing em massa. Individual para resolver problemas com um cliente (pedido atrasado, compensação, agrado).</span>
        </div>
    </div>
</section>

<!-- ═══ LIMITES DE USO ═══ -->
<section id="limits" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Limites de Uso
        </h2>
        <p>Dois controles trabalham juntos para limitar o uso:</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:16px 0;">
            <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:14px;padding:20px;">
                <div style="font-size:15px;font-weight:700;color:#1e40af;margin-bottom:8px;">🔢 Limite Total</div>
                <div style="font-size:13px;color:#1e3a5f;line-height:1.6;">
                    Quantas vezes <b>no total</b> o cupom pode ser usado, somando todos os clientes.<br><br>
                    <b>Exemplo:</b> Limite = 50<br>
                    Após 50 usos → cupom esgotado
                </div>
            </div>
            <div style="background:#faf5ff;border:1.5px solid #e9d5ff;border-radius:14px;padding:20px;">
                <div style="font-size:15px;font-weight:700;color:#7c3aed;margin-bottom:8px;">👤 Uso por Cliente</div>
                <div style="font-size:13px;color:#4c1d95;line-height:1.6;">
                    Se o <b>mesmo cliente</b> pode reutilizar.<br><br>
                    <b>Toggle ON:</b> Cliente A pode usar 5x<br>
                    <b>Toggle OFF:</b> 1 uso por cliente
                </div>
            </div>
        </div>

        <h3>📊 Combinações na prática</h3>
        <table class="gc-cmp">
            <thead><tr><th>Cenário</th><th>Limite</th><th>Multi-uso</th><th>Resultado</th></tr></thead>
            <tbody>
                <tr><td>Promoção lançamento</td><td>100</td><td>OFF</td><td>100 clientes diferentes, 1x cada</td></tr>
                <tr><td>Cliente VIP</td><td>10</td><td>ON</td><td>1 cliente usa até 10x</td></tr>
                <tr><td>Flash sale</td><td>20</td><td>OFF</td><td>Primeiros 20 clientes</td></tr>
                <tr><td>Compensação</td><td>1</td><td>OFF</td><td>1 uso único para 1 cliente</td></tr>
            </tbody>
        </table>

        <div class="gc-warn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span class="t"><b>Atenção:</b> Quando o limite total é atingido, o cupom fica como <b>"Esgotado"</b> automaticamente. Você pode editá-lo para aumentar o limite.</span>
        </div>
    </div>
</section>

<!-- ═══ ESTRATÉGIAS ═══ -->
<section id="strategies" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            Estratégias de Cupom
        </h2>

        <div style="display:grid;gap:14px;">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:16px;">
                <div style="font-size:15px;font-weight:700;color:#166534;margin-bottom:6px;">🚀 Primeira Compra</div>
                <div style="font-size:13px;color:#15803d;line-height:1.6;">
                    Código: <code style="background:#dcfce7;padding:2px 6px;border-radius:4px;">PRIMEIRACOMPRA10</code><br>
                    Desconto: 10% · Limite: 500 · Multi-uso: OFF · Telefone: em branco<br>
                    <b>Objetivo:</b> Incentivar novos clientes a fazer o primeiro pedido.
                </div>
            </div>
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:14px;padding:16px;">
                <div style="font-size:15px;font-weight:700;color:#0369a1;margin-bottom:6px;">🔥 Black Friday / Datas Especiais</div>
                <div style="font-size:13px;color:#075985;line-height:1.6;">
                    Código: <code style="background:#dbeafe;padding:2px 6px;border-radius:4px;">BF2024</code><br>
                    Desconto: 15-20% · Limite: 100 · Multi-uso: OFF · Telefone: em branco<br>
                    <b>Objetivo:</b> Volume alto em data específica. Limite baixo cria urgência.
                </div>
            </div>
            <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:14px;padding:16px;">
                <div style="font-size:15px;font-weight:700;color:#7c3aed;margin-bottom:6px;">💜 VIP / Fidelidade</div>
                <div style="font-size:13px;color:#5b21b6;line-height:1.6;">
                    Código: auto-gerado · Desconto: 10% · Limite: 5 · Multi-uso: ON · Telefone: do cliente<br>
                    <b>Objetivo:</b> Recompensar clientes fiéis com desconto recorrente.
                </div>
            </div>
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;padding:16px;">
                <div style="font-size:15px;font-weight:700;color:#9a3412;margin-bottom:6px;">🔧 Compensação</div>
                <div style="font-size:13px;color:#c2410c;line-height:1.6;">
                    Código: auto-gerado · Desconto: 15-30% · Limite: 1 · Multi-uso: OFF · Telefone: do cliente<br>
                    <b>Objetivo:</b> Resolver problema (atraso, erro). Uso único, individual.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ DICAS ═══ -->
<section id="tips" class="gc-sec">
    <div class="gc-card">
        <h2>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Dicas & Boas Práticas
        </h2>
        <div style="display:grid;gap:12px;">
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Códigos memoráveis</div><div style="font-size:13px;color:#15803d;">Use códigos curtos e fáceis de lembrar: PROMO10, FRETE, VIP20. Evite códigos aleatórios em promoções públicas.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Limite com sentido</div><div style="font-size:13px;color:#15803d;">Limite alto (500+) para campanhas longas. Limite baixo (10-50) para criar urgência ("só os 50 primeiros!").</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Compensação = Individual</div><div style="font-size:13px;color:#15803d;">Para resolver problemas, sempre use cupom individual (com telefone). Nunca divulgue esse código publicamente.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">⚠️</span>
                <div><div style="font-size:14px;font-weight:600;color:#9a3412;margin-bottom:2px;">Cuidado com 100%</div><div style="font-size:13px;color:#c2410c;">Desconto de 100% = pedido grátis. Use com extrema cautela e sempre com limite = 1 e telefone preenchido.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">⚠️</span>
                <div><div style="font-size:14px;font-weight:600;color:#9a3412;margin-bottom:2px;">Cupom genérico pode vazar</div><div style="font-size:13px;color:#c2410c;">Se postar em rede social, qualquer pessoa pode usar. Defina limite baixo para controlar o impacto.</div></div>
            </div>
        </div>
    </div>
</section>

</div>
</div>
<div style="height:80px;"></div>
</div>

<script>
(function(){
    var secs=document.querySelectorAll('.gc-sec'),links=document.querySelectorAll('.gc-nav a[data-section]');
    function up(){
        var y=window.scrollY+150,c='';
        var atBottom=(window.innerHeight+window.scrollY)>=(document.documentElement.scrollHeight-80);
        if(atBottom&&secs.length){c=secs[secs.length-1].id}else{secs.forEach(function(s){if(s.offsetTop<=y)c=s.id})}
        links.forEach(function(a){a.classList.toggle('active',a.dataset.section===c)});
    }
    window.addEventListener('scroll',up);up();
    links.forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();var t=document.getElementById(this.dataset.section);if(t)t.scrollIntoView({behavior:'smooth',block:'start'})})});
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
