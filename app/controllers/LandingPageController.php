<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

class LandingPageController extends Controller
{
    public function index(): void
    {
        $this->view('public/landing', [
            'pageTitle'       => 'MultiMenu — Plataforma Completa para Restaurantes Digitais',
            'pageDescription' => 'Cardápio digital PWA, integração WhatsApp e iFood, KDS, fidelidade, analytics, controle financeiro e IA. A plataforma #1 para restaurantes no Brasil com 47+ módulos profissionais.',
            'whatsappNumber'  => '5551920017687',

            /* ───── Números do sistema real ───── */
            'totalFeatures'    => '80+',
            'totalModules'     => 47,
            'totalControllers' => 47,
            'totalMiddlewares' => 15,

            /* ───── URLs ───── */
            'demoUrl'      => '#demo',
            'videoEmbedId' => '',

            /* ───── Estatísticas (social proof) ───── */
            'stats' => [
                ['value' => 500,  'suffix' => '+',  'label' => 'Restaurantes Ativos'],
                ['value' => 100,  'suffix' => 'K+', 'label' => 'Pedidos Processados'],
                ['value' => 99.9, 'suffix' => '%',  'label' => 'Uptime Garantido'],
                ['value' => 4.9,  'suffix' => '★',  'label' => 'Avaliação Média'],
            ],

            /* ───── ROI Calculator defaults ───── */
            'roiDefaults' => [
                'faturamentoMensal' => 30000,
                'taxaMarketplace'   => 27,
                'planoMultiMenu'    => 197,
            ],

            /* ───── Tabela Comparativa ───── */
            'competitors' => [
                'headers' => ['Funcionalidade', 'MultiMenu', 'iFood', 'Rappi', 'Cardápio PDF', 'Planilha'],
                'rows' => [
                    ['Taxa por pedido',          'full',    'partial', 'partial', 'full',    'full'],
                    ['Cardápio digital PWA',     'full',    'none',    'none',    'none',    'none'],
                    ['Gestão de pedidos',        'full',    'partial', 'partial', 'none',    'none'],
                    ['Dashboard analytics',      'full',    'partial', 'partial', 'none',    'none'],
                    ['Controle financeiro',      'full',    'none',    'none',    'none',    'partial'],
                    ['WhatsApp automático',      'full',    'none',    'none',    'none',    'none'],
                    ['KDS (Cozinha)',            'full',    'none',    'none',    'none',    'none'],
                    ['Programa de fidelidade',   'full',    'none',    'none',    'none',    'none'],
                    ['Personalização de marca',  'full',    'none',    'none',    'partial', 'none'],
                    ['Motor IA / Recomendações', 'full',    'partial', 'partial', 'none',    'none'],
                    ['API REST + Webhooks',      'full',    'partial', 'none',    'none',    'none'],
                    ['Impressão térmica',        'full',    'none',    'none',    'none',    'none'],
                ],
                'labels' => [
                    'full'    => 'Incluso',
                    'partial' => 'Parcial',
                    'none'    => 'Não tem',
                ],
                'costs' => [
                    'MultiMenu'    => 'R$ 97–397/mês fixo',
                    'iFood'        => '12–27% por pedido',
                    'Rappi'        => '15–30% por pedido',
                    'Cardápio PDF' => 'Grátis (limitado)',
                    'Planilha'     => 'Grátis (manual)',
                ],
            ],

            /* ───── Cases de Sucesso ───── */
            'cases' => [
                [
                    'name'       => 'Burger House',
                    'city'       => 'Florianópolis, SC',
                    'owner'      => 'Carlos Silva',
                    'plan'       => 'Professional',
                    'metrics'    => [
                        ['value' => '70%',       'label' => 'Pedidos pelo canal próprio'],
                        ['value' => 'R$ 5.200',  'label' => 'Economia mensal em taxas'],
                        ['value' => '3x',        'label' => 'Mais pedidos recorrentes'],
                    ],
                    'quote'      => 'Saí do iFood como canal principal e hoje 70% dos meus pedidos vêm pelo MultiMenu.',
                ],
                [
                    'name'       => 'Açaí Mania',
                    'city'       => 'Joinville, SC',
                    'owner'      => 'Ana Rodrigues',
                    'plan'       => 'Professional',
                    'metrics'    => [
                        ['value' => '+40%',      'label' => 'Taxa de retorno de clientes'],
                        ['value' => '0',         'label' => 'Pedidos perdidos na cozinha'],
                        ['value' => '+25%',      'label' => 'Aumento no ticket médio'],
                    ],
                    'quote'      => 'O programa de fidelidade aumentou nossa taxa de retorno em 40%.',
                ],
                [
                    'name'       => 'Pizza Express',
                    'city'       => 'Blumenau, SC',
                    'owner'      => 'Roberto Santos',
                    'plan'       => 'Enterprise',
                    'metrics'    => [
                        ['value' => '+65%',      'label' => 'Pedidos pelo canal direto'],
                        ['value' => '15 min',    'label' => 'Tempo médio de preparo'],
                        ['value' => '4.9★',      'label' => 'Avaliação dos clientes'],
                    ],
                    'quote'      => 'O dashboard financeiro me mostrou que eu tinha produtos com margem negativa!',
                ],
            ],

            /* ───── Timeline de Evolução ───── */
            'timeline' => [
                ['quarter' => 'T1 2025', 'title' => 'Fundação',         'items' => ['Cardápio Digital PWA', 'Gestão de Pedidos', 'Checkout inteligente', 'Impressão Térmica 58mm'],       'done' => true],
                ['quarter' => 'T2 2025', 'title' => 'Integrações',      'items' => ['WhatsApp (Evolution API)', 'Integração iFood', 'KDS na Cozinha', 'Web Push Notifications'],        'done' => true],
                ['quarter' => 'T3 2025', 'title' => 'Gestão Financeira','items' => ['Dashboard Financeiro', 'Análise de Custos', 'Gestão de Despesas', 'Endereço Inteligente'],          'done' => true],
                ['quarter' => 'T4 2025', 'title' => 'Inteligência',     'items' => ['Motor IA', 'Cross-Sell', 'Recomendações', 'Promoções com Countdown'],                               'done' => true],
                ['quarter' => 'T1 2026', 'title' => 'Analytics & Marketing', 'items' => ['Analytics BI Avançado', 'Programa de Fidelidade', 'Campanhas de Re-engajamento', 'Admin Mobile PWA'], 'done' => true],
                ['quarter' => 'T2 2026', 'title' => 'Próximos Passos',  'items' => ['Multi-loja unificado', 'App nativo iOS/Android', 'Marketplace próprio', 'Integrações de pagamento'], 'done' => false],
            ],

            /* ───── Screenshots do sistema ───── */
            'screenshots' => [
                ['id' => 'dashboard',  'title' => 'Dashboard Admin',       'desc' => 'Painel completo com KPIs, gráficos de faturamento e pedidos recentes em tempo real.'],
                ['id' => 'cardapio',   'title' => 'Cardápio Digital',      'desc' => 'Menu PWA responsivo com categorias, busca inteligente e imagens otimizadas.'],
                ['id' => 'kds',        'title' => 'KDS — Cozinha',        'desc' => 'Kanban de pedidos com 3 colunas, timer por pedido e alertas sonoros.'],
                ['id' => 'financeiro', 'title' => 'Dashboard Financeiro',  'desc' => 'Lucro líquido, margem, composição de custos e sugestão automática de preço.'],
                ['id' => 'analytics',  'title' => 'Analytics & BI',       'desc' => 'Funil de conversão, ranking de produtos e heatmap de horários de pico.'],
                ['id' => 'whatsapp',   'title' => 'WhatsApp Bot',         'desc' => 'Notificações automáticas de status, campanhas e mensagens programadas.'],
                ['id' => 'checkout',   'title' => 'Checkout Inteligente',  'desc' => 'Endereço com autocomplete, fidelidade aplicada e PIX/Cartão/Dinheiro.'],
                ['id' => 'fidelidade', 'title' => 'Programa de Fidelidade','desc' => 'Tiers progressivos com recompensas automáticas e tracking visual.'],
            ],

            /* ───── Feature Grid (9 destaques) ───── */
            'features' => [
                [
                    'icon'        => '<svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
                    'iconBg'      => 'bg-indigo-100',
                    'title'       => 'Cardápio Digital PWA',
                    'description' => 'App nativo no celular do cliente, sem download. Funciona offline, busca inteligente e imagens otimizadas.',
                    'highlights'  => ['Add to Home Screen', 'Service Worker', 'Imagens WebP/AVIF'],
                ],
                [
                    'icon'        => '<svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
                    'iconBg'      => 'bg-amber-100',
                    'title'       => 'Gestão de Pedidos',
                    'description' => '6 status em tempo real: pendente, confirmado, preparando, pronto, enviado e entregue.',
                    'highlights'  => ['Timeline visual', 'Notificações push', 'Impressão térmica'],
                ],
                [
                    'icon'        => '<svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
                    'iconBg'      => 'bg-green-100',
                    'title'       => 'WhatsApp Bot',
                    'description' => 'Evolution API v2.3 com múltiplas instâncias. Notificações automáticas e re-engajamento.',
                    'highlights'  => ['QR code setup', 'Templates dinâmicos', 'Multi-instância'],
                ],
                [
                    'icon'        => '<svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>',
                    'iconBg'      => 'bg-red-100',
                    'title'       => 'KDS — Kitchen Display',
                    'description' => 'Telão na cozinha com kanban 3 colunas, timer por pedido e alerta sonoro.',
                    'highlights'  => ['Kanban 3 colunas', 'Timer automático', 'Alerta sonoro'],
                ],
                [
                    'icon'        => '<svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    'iconBg'      => 'bg-emerald-100',
                    'title'       => 'Dashboard Financeiro',
                    'description' => 'Lucro líquido, margem visual, composição de custos e sugestão automática de preço.',
                    'highlights'  => ['DRE simplificado', 'Custo por produto', 'Sugestão de markup'],
                ],
                [
                    'icon'        => '<svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
                    'iconBg'      => 'bg-purple-100',
                    'title'       => 'Analytics & BI',
                    'description' => 'Faturamento por período, ranking de produtos, funil de conversão e heatmap de horários.',
                    'highlights'  => ['Funil de conversão', 'Heatmap de pico', 'Ranking de produtos'],
                ],
                [
                    'icon'        => '<svg class="w-6 h-6 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
                    'iconBg'      => 'bg-pink-100',
                    'title'       => 'Programa de Fidelidade',
                    'description' => 'Tiers progressivos com recompensas automáticas. Desconto a cada X pedidos.',
                    'highlights'  => ['Tiers progressivos', 'Cupons automáticos', 'Tracking visual'],
                ],
                [
                    'icon'        => '<svg class="w-6 h-6 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
                    'iconBg'      => 'bg-cyan-100',
                    'title'       => 'Motor IA Híbrido',
                    'description' => '4 algoritmos combinados: preferências, colaborativo, popularidade e temporal.',
                    'highlights'  => ['4 algoritmos', 'Cross-sell', 'Auto-aprendizado'],
                ],
                [
                    'icon'        => '<svg class="w-6 h-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>',
                    'iconBg'      => 'bg-orange-100',
                    'title'       => 'Integração iFood',
                    'description' => 'OAuth2 nativo com pedidos em tempo real, sync de cardápio e gestão unificada.',
                    'highlights'  => ['OAuth2 auto-refresh', 'Webhooks', 'Sync de catálogo'],
                ],
            ],

            /* ───── Features por categoria (tabs) ───── */
            'featureTabs' => [
                'vendas' => [
                    'label' => 'Vendas',
                    'icon'  => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
                    'items' => [
                        [
                            'title'       => 'Cardápio Digital PWA',
                            'description' => 'App nativo no celular do cliente sem baixar nada. Service Worker offline, busca inteligente, imagens WebP/AVIF com lazy loading e skeleton screens.',
                            'highlights'  => ['Add to home screen', 'Funciona offline', 'Imagens otimizadas', 'Busca instantânea'],
                            'mockup'      => 'phone',
                        ],
                        [
                            'title'       => 'Gestão de Pedidos',
                            'description' => 'Fluxo completo com 6 status: pendente → confirmado → preparando → pronto → enviado → entregue. Notificações sonoras e push em cada etapa.',
                            'highlights'  => ['Timeline visual', 'Impressão 58mm', 'Múltiplas origens', 'Cancelamento com justificativa'],
                            'mockup'      => 'browser',
                        ],
                        [
                            'title'       => 'Checkout Inteligente',
                            'description' => 'Endereço com autocomplete de 5 camadas, cupons, fidelidade aplicada automaticamente, PIX/Cartão/Dinheiro e cálculo de taxa de entrega em tempo real.',
                            'highlights'  => ['Endereço inteligente', 'Cupons automáticos', 'Fidelidade integrada', 'Taxa por zona'],
                            'mockup'      => 'phone',
                        ],
                    ],
                ],
                'gestao' => [
                    'label' => 'Gestão',
                    'icon'  => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>',
                    'items' => [
                        [
                            'title'       => 'KDS — Kitchen Display',
                            'description' => 'Telão na cozinha com pedidos em kanban de 3 colunas. Timer desde recebimento, filtro por categoria, priorização automática e alerta sonoro para novos pedidos.',
                            'highlights'  => ['Kanban 3 colunas', 'Timer por pedido', 'Alerta sonoro', 'Priorização automática'],
                            'mockup'      => 'browser',
                        ],
                        [
                            'title'       => 'Controle Financeiro',
                            'description' => 'Dashboard com lucro líquido, margem de lucro visual, composição de custos (ingredientes + embalagem), despesas fixas/variáveis e sugestão automática de preço.',
                            'highlights'  => ['DRE simplificado', 'Custo por produto', 'Margem visual', 'Sugestão de markup'],
                            'mockup'      => 'browser',
                        ],
                        [
                            'title'       => 'Admin Mobile PWA',
                            'description' => 'Painel admin completo no celular via subdomain m.dominio.com. Bottom nav touch-optimized, funciona offline e tem todas as funcionalidades do desktop.',
                            'highlights'  => ['Subdomain dedicado', 'Touch-optimized', 'Funciona offline', 'Painel completo'],
                            'mockup'      => 'phone',
                        ],
                    ],
                ],
                'marketing' => [
                    'label' => 'Marketing',
                    'icon'  => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>',
                    'items' => [
                        [
                            'title'       => 'WhatsApp Bot Nativo',
                            'description' => 'Evolution API v2.3 com múltiplas instâncias. Notificações automáticas de pedidos, campanhas de re-engajamento para clientes inativos e mensagens fora do expediente.',
                            'highlights'  => ['QR code para conectar', 'Templates dinâmicos', 'Campanhas automáticas', 'Multi-instância'],
                            'mockup'      => 'phone',
                        ],
                        [
                            'title'       => 'Fidelidade & Promoções',
                            'description' => 'Programa de fidelidade progressivo com tiers. A cada X pedidos, ganha desconto automático. Cupons com countdown, promoções temporárias e desconto % ou R$.',
                            'highlights'  => ['Tiers progressivos', 'Cupons automáticos', 'Countdown em promos', 'Desconto na taxa'],
                            'mockup'      => 'browser',
                        ],
                        [
                            'title'       => 'Web Push Notifications',
                            'description' => 'Notificações push nativas com VAPID keys. Alerte clientes sobre promoções, novos produtos e status de entrega direto no navegador — sem app.',
                            'highlights'  => ['Sem instalar app', 'Desktop e mobile', 'Real-time', 'VAPID keys'],
                            'mockup'      => 'browser',
                        ],
                    ],
                ],
                'inteligencia' => [
                    'label' => 'Inteligência',
                    'icon'  => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
                    'items' => [
                        [
                            'title'       => 'Motor IA Híbrido',
                            'description' => '4 algoritmos combinados: 35% preferências pessoais + 35% colaborativo + 15% popularidade + 15% temporal. Cross-sell inteligente por categoria.',
                            'highlights'  => ['4 algoritmos', 'Tracking completo', 'Cross-sell', 'Auto-aprendizado'],
                            'mockup'      => 'browser',
                        ],
                        [
                            'title'       => 'Analytics & BI',
                            'description' => 'Faturamento por período, ranking de produtos, funil de conversão, ticket médio, heatmap de horários de pico e métricas detalhadas de pagamento.',
                            'highlights'  => ['Funil de conversão', 'Heatmap de horários', 'Ranking de produtos', 'Comparação de períodos'],
                            'mockup'      => 'browser',
                        ],
                        [
                            'title'       => 'iFood Integrado',
                            'description' => 'OAuth2 com refresh automático, polling de pedidos, webhooks, sincronização de catálogo e gestão de status diretamente pelo MultiMenu.',
                            'highlights'  => ['Pedidos em tempo real', 'Sync de cardápio', 'Gestão de status', 'Webhooks'],
                            'mockup'      => 'browser',
                        ],
                    ],
                ],
            ],

            /* ───── Módulos do sistema (24 módulos) ───── */
            'modules' => [
                ['emoji' => '📋', 'name' => 'Cardápio Digital',       'desc' => 'PWA responsivo com busca, categorias e imagens otimizadas'],
                ['emoji' => '🛒', 'name' => 'Carrinho & Checkout',    'desc' => 'Carrinho persistente, cupons e checkout inteligente'],
                ['emoji' => '📦', 'name' => 'Gestão de Pedidos',      'desc' => '6 status, timeline visual, múltiplas origens'],
                ['emoji' => '🍳', 'name' => 'KDS (Cozinha)',          'desc' => 'Display em tempo real com timer e alertas'],
                ['emoji' => '📊', 'name' => 'Analytics & BI',         'desc' => 'Faturamento, ranking, funil e heatmap de horários'],
                ['emoji' => '💰', 'name' => 'Dashboard Financeiro',   'desc' => 'Lucro, custos, margem e despesas detalhadas'],
                ['emoji' => '🧾', 'name' => 'Análise de Custos',      'desc' => 'Ingredientes + embalagem + taxa por produto'],
                ['emoji' => '💸', 'name' => 'Gestão de Despesas',     'desc' => 'Fixas e variáveis com categorias e relatórios'],
                ['emoji' => '📱', 'name' => 'WhatsApp Bot',           'desc' => 'Evolution API v2.3, múltiplas instâncias'],
                ['emoji' => '🔴', 'name' => 'Integração iFood',      'desc' => 'OAuth2, polling, webhooks, sync de catálogo'],
                ['emoji' => '🎁', 'name' => 'Programa de Fidelidade', 'desc' => 'Progressivo com tiers e recompensas'],
                ['emoji' => '🏷️', 'name' => 'Cupons & Promoções',    'desc' => 'Temporárias com countdown e validação'],
                ['emoji' => '🚚', 'name' => 'Taxa de Entrega',        'desc' => 'Cidades, zonas, raio e frete grátis'],
                ['emoji' => '📍', 'name' => 'Endereço Inteligente',   'desc' => '5 camadas de busca com auto-aprendizado'],
                ['emoji' => '🤖', 'name' => 'Motor IA',              'desc' => 'Recomendação híbrida 4 algoritmos'],
                ['emoji' => '🔄', 'name' => 'Cross-Sell',            'desc' => 'Sugestões inteligentes por categoria'],
                ['emoji' => '🖨️', 'name' => 'Impressão Térmica',     'desc' => 'PDF 58mm para impressoras profissionais'],
                ['emoji' => '🔔', 'name' => 'Web Push',              'desc' => 'Notificações VAPID em tempo real'],
                ['emoji' => '💳', 'name' => 'Pagamentos',            'desc' => 'PIX, Cartão, Dinheiro, métodos customizados'],
                ['emoji' => '🎨', 'name' => 'Personalização Visual',  'desc' => 'Cores, logo, templates de customização'],
                ['emoji' => '👤', 'name' => 'Gestão de Clientes',     'desc' => 'Perfil, endereços, LGPD, histórico'],
                ['emoji' => '🔑', 'name' => 'API REST & JWT',        'desc' => 'API pública com tokens e webhooks'],
                ['emoji' => '📱', 'name' => 'Admin Mobile PWA',      'desc' => 'Painel completo no celular via subdomain'],
                ['emoji' => '⏸️', 'name' => 'Pausa Programada',      'desc' => 'Pause/retake automático por horário'],
            ],

            /* ───── Stack tecnológico ───── */
            'techStack' => [
                'PHP 8.4', 'MySQL 8', 'Redis 7', 'Tailwind CSS 3.4',
                'PWA', 'Evolution API v2.3', 'iFood API', 'Docker',
                'Traefik', 'Web Push (VAPID)', 'FPDF', 'AES-256-GCM',
            ],

            /* ───── Integrações ───── */
            'integrations' => [
                [
                    'emoji'       => '💬',
                    'name'        => 'WhatsApp',
                    'subtitle'    => 'Evolution API v2.3',
                    'description' => 'Conexão nativa com múltiplas instâncias, QR code para conectar, templates dinâmicos e campanhas de re-engajamento automáticas.',
                    'badges'      => ['Notificações Automáticas', 'Campanhas', 'Multi-Instância', 'Fora do Horário'],
                    'color'       => 'green',
                ],
                [
                    'emoji'       => '🔴',
                    'name'        => 'iFood',
                    'subtitle'    => 'Integração Oficial',
                    'description' => 'OAuth2 com refresh automático. Pedidos do iFood direto no seu painel, sincronize cardápio e gerencie status em um só lugar.',
                    'badges'      => ['OAuth2', 'Pedidos em Tempo Real', 'Sync Catálogo', 'Webhooks'],
                    'color'       => 'red',
                ],
                [
                    'emoji'       => '🔔',
                    'name'        => 'Web Push',
                    'subtitle'    => 'VAPID Notifications',
                    'description' => 'Notificações push nativas no navegador. Alerte clientes sobre pedidos, promoções e entregas sem precisar de app.',
                    'badges'      => ['VAPID Keys', 'Real-Time', 'Desktop & Mobile', 'Sem App'],
                    'color'       => 'indigo',
                ],
                [
                    'emoji'       => '🖨️',
                    'name'        => 'Impressora',
                    'subtitle'    => 'Térmica 58mm',
                    'description' => 'Geração automática de comprovantes PDF 58mm via FPDF. Formatação profissional para qualquer impressora térmica.',
                    'badges'      => ['PDF 58mm', 'Auto-geração', 'Formato Padrão', 'FPDF'],
                    'color'       => 'amber',
                ],
                [
                    'emoji'       => '🔑',
                    'name'        => 'API REST',
                    'subtitle'    => 'JWT + Webhooks',
                    'description' => 'API RESTful completa com autenticação JWT. Endpoints para produtos, pedidos, categorias e webhooks para integrações externas.',
                    'badges'      => ['JWT Auth', 'Webhooks', 'CRUD Completo', 'Docs API'],
                    'color'       => 'purple',
                ],
                [
                    'emoji'       => '🐳',
                    'name'        => 'Docker',
                    'subtitle'    => 'Traefik + SSL',
                    'description' => 'Deploy completo com Docker Compose. Traefik como reverse proxy com HTTPS automático, Portainer e monitoramento.',
                    'badges'      => ['Docker Compose', 'HTTPS Auto', 'Portainer', 'Zero Downtime'],
                    'color'       => 'cyan',
                ],
            ],

            /* ───── Steps ───── */
            'steps' => [
                ['title' => 'Cadastre seu restaurante',  'description' => 'Em 5 minutos, configure nome, logo, cores, endereço e métodos de pagamento. O assistente de onboarding guia cada passo.', 'icon' => 'store'],
                ['title' => 'Monte seu cardápio',        'description' => 'Adicione categorias e produtos com fotos, preços, ingredientes e personalizações. Imagens são otimizadas automaticamente.', 'icon' => 'menu'],
                ['title' => 'Comece a vender!',           'description' => 'Compartilhe o link do seu cardápio digital no WhatsApp, Instagram e redes sociais. Pedidos chegam em tempo real.', 'icon' => 'rocket'],
            ],

            /* ───── Admin highlights ───── */
            'adminHighlights' => [
                ['emoji' => '📊', 'title' => 'Dashboard com 8+ widgets',      'desc' => 'Faturamento, pedidos, ticket médio, clientes novos, gráficos de tendência — tudo em tempo real.'],
                ['emoji' => '📱', 'title' => 'Admin Mobile (PWA)',             'desc' => 'Acesse tudo pelo celular via subdomain m.dominio.com. Bottom nav, touch-optimized, funciona offline.'],
                ['emoji' => '🍳', 'title' => 'KDS na cozinha',                'desc' => 'Telão com pedidos em fila, timer por pedido, alerta sonoro quando novo pedido chega.'],
                ['emoji' => '💰', 'title' => 'Financeiro detalhado',          'desc' => 'Custo por produto, margem de lucro, despesas categorizadas, sugestão automática de preço.'],
                ['emoji' => '🎨', 'title' => 'Personalização total',          'desc' => 'Cores do header, botões, gradientes, logo, grupos de personalização e templates reutilizáveis.'],
            ],

            /* ───── Segurança (15 middlewares OWASP) ───── */
            'securityFeatures' => [
                ['emoji' => '🛡️', 'title' => 'CSRF Protection',      'desc' => 'Token único por sessão em todos os forms'],
                ['emoji' => '🔒', 'title' => 'XSS Prevention',       'desc' => 'Escape context-aware em todas as saídas'],
                ['emoji' => '🗄️', 'title' => 'SQL Injection',        'desc' => 'Prepared statements em 100% das queries'],
                ['emoji' => '⏱️', 'title' => 'Rate Limiting',        'desc' => 'Proteção contra brute force e DDoS'],
                ['emoji' => '🔐', 'title' => 'AES-256-GCM',         'desc' => 'Criptografia de dados sensíveis'],
                ['emoji' => '📋', 'title' => 'Audit Logging',        'desc' => 'Registro completo de ações admin'],
                ['emoji' => '🍪', 'title' => 'Session Security',     'desc' => 'SameSite cookies, rotação de sessão'],
                ['emoji' => '📜', 'title' => 'LGPD Compliance',      'desc' => 'Consent tracking, export e delete de dados'],
            ],

            /* ───── Depoimentos ───── */
            'testimonials' => [
                [
                    'name' => 'Carlos Silva',
                    'role' => 'Dono — Burger House, Florianópolis',
                    'text' => 'Saí do iFood como canal principal e hoje 70% dos meus pedidos vêm pelo MultiMenu. Economizo mais de R$ 5.000/mês em taxas e tenho controle total do meu negócio.',
                ],
                [
                    'name' => 'Ana Rodrigues',
                    'role' => 'Gerente — Açaí Mania, Joinville',
                    'text' => 'O programa de fidelidade aumentou nossa taxa de retorno em 40%. Os clientes adoram acumular pedidos e ganhar desconto. E o KDS na cozinha acabou com os pedidos perdidos.',
                ],
                [
                    'name' => 'Roberto Santos',
                    'role' => 'Proprietário — Pizza Express, Blumenau',
                    'text' => 'A integração WhatsApp mudou tudo. Os clientes recebem notificação automática de cada status. O dashboard financeiro me mostrou que eu tinha produtos com margem negativa!',
                ],
            ],

            /* ───── FAQ ───── */
            'faq' => [
                [
                    'question' => 'Preciso instalar algum aplicativo?',
                    'answer'   => 'Não! O MultiMenu é um PWA (Progressive Web App). Seus clientes acessam pelo navegador e podem "instalar" no celular sem baixar nada da loja. O painel admin também funciona como PWA no celular.',
                ],
                [
                    'question' => 'Vocês cobram taxa por pedido?',
                    'answer'   => 'Nunca. O valor do plano é fixo mensal, independente de quantos pedidos você receba. Diferente de marketplaces que cobram 12% a 27% por pedido.',
                ],
                [
                    'question' => 'Consigo usar junto com o iFood?',
                    'answer'   => 'Sim! A integração iFood é nativa. Pedidos do iFood chegam no mesmo painel, no mesmo KDS. Você gerencia tudo em um lugar só, sem alternar entre sistemas.',
                ],
                [
                    'question' => 'Meus dados estão seguros?',
                    'answer'   => 'Temos 15 camadas de segurança OWASP: CSRF, XSS, SQL injection prevention, rate limiting, criptografia AES-256-GCM, audit logging e compliance total com LGPD.',
                ],
                [
                    'question' => 'Quanto tempo leva para configurar?',
                    'answer'   => 'Em média 24 horas. O assistente de onboarding guia você por cada etapa: dados do restaurante, cardápio, personalização, pagamentos e integrações.',
                ],
                [
                    'question' => 'Funciona para múltiplas unidades?',
                    'answer'   => 'Sim! O plano Enterprise suporta multi-tenant completo — cada unidade com seu slug, cardápio, equipe e configurações independentes, tudo gerenciado em um único painel.',
                ],
                [
                    'question' => 'Posso personalizar as cores e logo?',
                    'answer'   => 'Totalmente. Cores do header, botões, gradientes, logo, favicon — tudo personalizável pelo painel admin sem precisar de desenvolvedor.',
                ],
                [
                    'question' => 'Tem API para integrações externas?',
                    'answer'   => 'Sim! API REST completa com autenticação JWT, endpoints para produtos, pedidos, categorias e webhooks para notificar sistemas externos em tempo real.',
                ],
                [
                    'question' => 'Quanto vou economizar comparado ao iFood?',
                    'answer'   => 'Depende do seu faturamento. Um restaurante que fatura R$ 30K/mês no iFood paga até R$ 8.100 de taxas. Com o MultiMenu, o custo fixo é de R$ 97 a R$ 397/mês — uma economia de até R$ 7.903/mês.',
                ],
                [
                    'question' => 'Como funciona a migração do meu restaurante?',
                    'answer'   => 'Nossa equipe de onboarding cuida de tudo: importamos seu cardápio, configuramos integrações, personalizamos cores e logo, e treinamos sua equipe. O processo leva em média 24 horas.',
                ],
                [
                    'question' => 'Posso testar antes de contratar?',
                    'answer'   => 'Sim! Oferecemos 7 dias de teste grátis com acesso completo ao plano Professional. Se não gostar, cancelamos sem custo nenhum.',
                ],
                [
                    'question' => 'O suporte é humanizado?',
                    'answer'   => 'Sim. Nosso suporte é feito por humanos, via WhatsApp, com tempo médio de resposta de 15 minutos em horário comercial. No plano Enterprise, você tem um gerente de conta dedicado.',
                ],
            ],

            /* ───── Pricing ───── */
            'pricing' => [
                'starter' => [
                    'name'     => 'Starter',
                    'price'    => 97,
                    'priceAnnual' => 77,
                    'period'   => '/mês',
                    'features' => [
                        'Cardápio Digital PWA Responsivo',
                        'Até 50 Produtos',
                        'Gestão de Pedidos em Tempo Real',
                        'Painel Admin Desktop & Mobile',
                        'Personalização de Cores e Logo',
                        'PIX, Cartão e Dinheiro',
                        'Categorias com Ícones',
                        'Impressão Térmica 58mm',
                        'Web Push Notifications',
                        'Suporte por Email',
                    ],
                ],
                'professional' => [
                    'name'       => 'Professional',
                    'price'      => 197,
                    'priceAnnual' => 157,
                    'period'     => '/mês',
                    'popular'    => true,
                    'features'   => [
                        'Tudo do Starter +',
                        'Produtos Ilimitados',
                        'WhatsApp Bot (Evolution API)',
                        'Integração iFood Nativa',
                        'KDS — Kitchen Display System',
                        'Programa de Fidelidade Progressivo',
                        'Cupons & Promoções com Countdown',
                        'Analytics & Relatórios Financeiros',
                        'Endereço Autocomplete 5 Camadas',
                        'Análise de Custos por Produto',
                        'Gestão de Despesas Completa',
                        'Cross-Sell Inteligente',
                        'Suporte Prioritário via WhatsApp',
                    ],
                ],
                'enterprise' => [
                    'name'     => 'Enterprise',
                    'price'    => 397,
                    'priceAnnual' => 317,
                    'period'   => '/mês',
                    'features' => [
                        'Tudo do Professional +',
                        'Multi-Unidades (Multi-Tenant)',
                        'API REST Completa + JWT + Webhooks',
                        'Campanhas de Re-engajamento WhatsApp',
                        'Motor de Recomendação (IA Híbrida)',
                        'Custo & Margem com Sugestão de Preço',
                        'ICMS / PIS / COFINS configurável',
                        'Gerente de Conta Dedicado',
                        'SLA 99.9% Uptime',
                        'Onboarding Premium Guiado',
                    ],
                ],
            ],
        ]);
    }
}
