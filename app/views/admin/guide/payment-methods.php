<?php
require_once __DIR__ . '/Guide.php';

$slug = rawurlencode((string)($company['slug'] ?? ''));

$pageTitle       = 'Guia de Métodos de Pagamento';
$pageDescription = 'Aprenda a configurar formas de pagamento, Pix, cartões e dinheiro';
$pageIcon        = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>';
$breadcrumbs = [
    ['label' => 'Métodos de Pagamento', 'url' => base_url('admin/' . $slug . '/payment-methods')],
    ['label' => 'Guia'],
];
$actions = [
    ['label' => 'Gerenciar Métodos', 'url' => base_url('admin/' . $slug . '/payment-methods'), 'icon' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>', 'primary' => true],
];

$guide = Guide::make([
    'Guia' => [
        ['id' => 'overview', 'label' => 'Visão Geral',       'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>'],
        ['id' => 'types',    'label' => 'Tipos de Pagamento', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>'],
        ['id' => 'form',     'label' => 'Formulário',         'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>'],
    ],
    'Detalhes' => [
        ['id' => 'pix',  'label' => 'Pix',   'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>'],
        ['id' => 'tips', 'label' => 'Dicas', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'],
    ],
])->cta([
    'label' => 'Gerenciar Métodos',
    'url'   => base_url('admin/' . $slug . '/payment-methods'),
    'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>',
])->context(get_defined_vars());

ob_start();
?>

<!-- VISÃO GERAL -->
<?= GuideUI::sectionOpen('overview', 'Métodos de Pagamento', ['icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>']) ?>
        <p>Configure quais <b>formas de pagamento</b> seus clientes podem usar no checkout. Cada método aparece como opção na finalização do pedido.</p>

        <div style="background:#f8fafc;border:2px dashed #e2e8f0;border-radius:14px;padding:20px;margin:16px 0;">
            <div style="font-size:13px;font-weight:700;color:#475569;margin-bottom:14px;text-align:center;">Como o cliente vê no checkout:</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;max-width:400px;margin:0 auto;">
                <div style="background:#fff;border:2px solid var(--admin-primary-color);border-radius:10px;padding:10px;text-align:center;">
                    <div style="font-size:20px;">💳</div>
                    <div style="font-size:11px;font-weight:600;color:#1e293b;">Visa Crédito</div>
                </div>
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px;text-align:center;">
                    <div style="font-size:20px;">💳</div>
                    <div style="font-size:11px;font-weight:600;color:#1e293b;">Mastercard</div>
                </div>
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px;text-align:center;">
                    <div style="font-size:20px;">📱</div>
                    <div style="font-size:11px;font-weight:600;color:#1e293b;">Pix</div>
                </div>
            </div>
        </div>

        <ol class="gc-steps">
            <li><div>Escolha o <b>tipo</b> do método (Crédito, Débito, Pix, etc.)</div></li>
            <li><div>Defina <b>nome</b> e opcionalmente envie um <b>ícone/bandeira</b></div></li>
            <li><div>Ative/desative métodos conforme necessário</div></li>
        </ol>
<?= GuideUI::sectionClose() ?>

<!-- TIPOS DE PAGAMENTO -->
<?= GuideUI::sectionOpen('types', 'Tipos de Pagamento', ['icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>']) ?>
        <p>O sistema suporta 6 tipos de método de pagamento:</p>

        <div class="gc-type-grid">
            <div class="gc-type-card" style="border-color:#3b82f6;background:#eff6ff;">
                <div class="tc-icon">💳</div>
                <div class="tc-name" style="color:#1d4ed8;">Crédito</div>
                <div class="tc-desc">Visa, Master, Elo, Amex</div>
            </div>
            <div class="gc-type-card" style="border-color:#10b981;background:#ecfdf5;">
                <div class="tc-icon">💳</div>
                <div class="tc-name" style="color:#059669;">Débito</div>
                <div class="tc-desc">Visa Débito, Master Débito</div>
            </div>
            <div class="gc-type-card" style="border-color:#8b5cf6;background:#faf5ff;">
                <div class="tc-icon">📱</div>
                <div class="tc-name" style="color:#7c3aed;">Pix</div>
                <div class="tc-desc">Pagamento instantâneo</div>
            </div>
            <div class="gc-type-card" style="border-color:#f59e0b;background:#fffbeb;">
                <div class="tc-icon">💵</div>
                <div class="tc-name" style="color:#d97706;">Dinheiro</div>
                <div class="tc-desc">Pagamento em espécie</div>
            </div>
            <div class="gc-type-card" style="border-color:#ec4899;background:#fdf2f8;">
                <div class="tc-icon">🎫</div>
                <div class="tc-name" style="color:#db2777;">Vale-refeição</div>
                <div class="tc-desc">VR, Sodexo, Alelo</div>
            </div>
            <div class="gc-type-card" style="border-color:#6b7280;background:#f9fafb;">
                <div class="tc-icon">📋</div>
                <div class="tc-name" style="color:#4b5563;">Outros</div>
                <div class="tc-desc">Qualquer outro meio</div>
            </div>
        </div>

        <div class="gc-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Pix e Dinheiro</b> têm nomes e ícones automáticos — basta selecionar o tipo e salvar. Para cartões, defina o nome da bandeira.</span>
        </div>
<?= GuideUI::sectionClose() ?>

<!-- FORMULÁRIO -->
<?= GuideUI::sectionOpen('form', 'Formulário — Bloco a Bloco', ['icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>']) ?>

        <!-- Tipo -->
        <h3>① Tipo</h3>
        <?= GuideUI::fieldset(
            GuideUI::field(
                'Tipo de pagamento',
                GuideUI::select([
                    ['value' => 'credit', 'label' => 'Crédito'],
                    ['value' => 'debit',  'label' => 'Débito'],
                    ['value' => 'pix',    'label' => 'Pix'],
                    ['value' => 'cash',   'label' => 'Dinheiro'],
                    ['value' => 'food',   'label' => 'Vale-refeição'],
                    ['value' => 'other',  'label' => 'Outros'],
                ], 'credit'),
                GuideUI::tag('Obrigatório', 'r')
            )
        ) ?>
        <div class="gc-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>O tipo determina quais campos aparecem. <b>Pix</b> mostra campos de chave. <b>Dinheiro</b> oculta o campo nome (é automático).</span>
        </div>

        <!-- Nome -->
        <h3>② Nome da Bandeira</h3>
        <?= GuideUI::fieldset(
            GuideUI::field(
                'Nome',
                GuideUI::input('Visa'),
                GuideUI::tag('Obrigatório*', 'r'),
                'Nome exibido ao cliente no checkout. *Oculto para Pix e Dinheiro.'
            )
        ) ?>

        <!-- Ícone/Bandeira -->
        <h3>③ Ícone / Bandeira</h3>
        <?= GuideUI::fieldset(
            '<div style="border:2px dashed #cbd5e1;border-radius:12px;padding:20px;text-align:center;background:#f8fafc;">'
            . '<div style="font-size:14px;color:#64748b;margin-bottom:8px;">Arraste um arquivo SVG/PNG ou selecione da biblioteca</div>'
            . '<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">'
            . '<div style="width:40px;height:40px;border:2px solid var(--admin-primary-color);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;background:#fff;">💳</div>'
            . '<div style="width:40px;height:40px;border:1px solid #e2e8f0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;background:#fff;">💳</div>'
            . '<div style="width:40px;height:40px;border:1px solid #e2e8f0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;background:#fff;">📱</div>'
            . '</div></div>'
        ) ?>
        <div class="gc-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Duas opções: selecionar da <b>biblioteca de bandeiras</b> pré-cadastradas ou <b>enviar seu próprio arquivo</b> (SVG, PNG, JPG).</span>
        </div>

        <!-- Instruções -->
        <h3>④ Instruções</h3>
        <?= GuideUI::fieldset(
            GuideUI::field(
                'Instruções',
                GuideUI::textarea('Recados exibidos após a escolha do cliente'),
                GuideUI::tag('Opcional', 'o'),
                'Texto mostrado quando o cliente seleciona este método.'
            )
        ) ?>
        <div class="gc-annot">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span>Use para dar instruções como "Tenha o troco pronto" ou "Envie o comprovante Pix pelo WhatsApp".</span>
        </div>
<?= GuideUI::sectionClose() ?>

<!-- PIX -->
<?= GuideUI::sectionOpen('pix', 'Configuração Pix', ['icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>']) ?>
        <p>Ao selecionar tipo <b>Pix</b>, campos adicionais aparecem:</p>

        <?= GuideUI::fieldset(
            GuideUI::field('Chave Pix', GuideUI::input('11999999999')),
            GuideUI::field('Nome do Titular', GuideUI::input('João da Silva'))
        ) ?>

        <h3>Tipos de Chave Pix</h3>
        <table class="gc-cmp">
            <thead><tr><th>Tipo</th><th>Formato</th><th>Exemplo</th></tr></thead>
            <tbody>
                <tr><td><b>CPF</b></td><td>11 dígitos</td><td>12345678901</td></tr>
                <tr><td><b>CNPJ</b></td><td>14 dígitos</td><td>12345678000199</td></tr>
                <tr><td><b>E-mail</b></td><td>email@provedor</td><td>loja@email.com</td></tr>
                <tr><td><b>Telefone</b></td><td>+55 + DDD + número</td><td>+5511999999999</td></tr>
                <tr><td><b>Aleatória</b></td><td>UUID/string</td><td>a1b2c3d4-e5f6...</td></tr>
            </tbody>
        </table>

        <div class="gc-tip">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.663 17h4.673M12 3v1"/></svg>
            <span class="t"><b>Auto-detecção:</b> o sistema identifica automaticamente o tipo da chave ao digitar (CPF, CNPJ, e-mail, telefone ou aleatória).</span>
        </div>

        <div class="gc-warn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
            <span class="t"><b>Atenção:</b> a chave Pix e o nome do titular são exibidos ao cliente para pagamento. Confira se estão corretos!</span>
        </div>
<?= GuideUI::sectionClose() ?>

<!-- DICAS -->
<?= GuideUI::sectionOpen('tips', 'Dicas & Boas Práticas', ['icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>']) ?>
        <div style="display:grid;gap:12px;">
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Mínimo 3 métodos</div><div style="font-size:13px;color:#15803d;">Ofereça pelo menos Pix, cartão de crédito e dinheiro. Quanto mais opções, menos desistência.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Ícones ajudam</div><div style="font-size:13px;color:#15803d;">Use as bandeiras da biblioteca ou envie SVG/PNG. Métodos com ícone são mais confiáveis visualmente.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">✅</span>
                <div><div style="font-size:14px;font-weight:600;color:#166534;margin-bottom:2px;">Instruções claras</div><div style="font-size:13px;color:#15803d;">Para Pix: "Faça o Pix e envie o comprovante". Para troco: "Informe o valor para troco no campo de observação".</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">⚠️</span>
                <div><div style="font-size:14px;font-weight:600;color:#9a3412;margin-bottom:2px;">Desative, não exclua</div><div style="font-size:13px;color:#c2410c;">Use o toggle para desativar métodos temporariamente. Excluir perde o histórico de pedidos associado.</div></div>
            </div>
            <div style="display:flex;gap:12px;padding:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;">
                <span style="font-size:20px;flex-shrink:0;">⚠️</span>
                <div><div style="font-size:14px;font-weight:600;color:#9a3412;margin-bottom:2px;">Verifique a chave Pix</div><div style="font-size:13px;color:#c2410c;">Se a chave Pix estiver errada, o cliente não consegue pagar. Teste antes de ativar.</div></div>
            </div>
        </div>
<?= GuideUI::sectionClose() ?>

<?php $guide->render(ob_get_clean()); ?>
