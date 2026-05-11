<?php
/**
 * Menu de Configurações Mobile
 */
ob_start();
?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Perfil do Usuário -->
<div class="settings-header">
    <div class="user-avatar-lg">
        <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
    </div>
    <div class="user-info">
        <h2 class="user-name"><?= htmlspecialchars($user['name'] ?? 'Administrador') ?></h2>
        <p class="user-role"><?= ucfirst($user['role'] ?? 'admin') ?></p>
    </div>
</div>

<!-- Menu de Configurações -->
<div class="settings-menu">
    <div class="settings-section">
        <h3 class="settings-section-title">Loja</h3>
        
        <a href="/settings/store" class="settings-item">
            <div class="settings-item-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Dados da Loja</span>
                <span class="settings-item-desc">Nome, logo, telefone, endereço</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        
        <a href="/settings/hours" class="settings-item">
            <div class="settings-item-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Horários</span>
                <span class="settings-item-desc">Horário de funcionamento</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        
        <a href="/settings/delivery" class="settings-item">
            <div class="settings-item-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="9" cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Entrega</span>
                <span class="settings-item-desc">Taxas e áreas de entrega</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        
        <a href="/settings/payments" class="settings-item">
            <div class="settings-item-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Pagamentos</span>
                <span class="settings-item-desc">Formas de pagamento aceitas</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        
        <a href="/settings/loyalty" class="settings-item">
            <div class="settings-item-icon" style="background: #fef3c7; color: #d97706;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                    <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                    <line x1="9" y1="9" x2="9.01" y2="9"/>
                    <line x1="15" y1="9" x2="15.01" y2="9"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Fidelidade</span>
                <span class="settings-item-desc">Cupons, taxas e descontos</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>

        <a href="/coupons" class="settings-item">
            <div class="settings-item-icon" style="background: #fef3c7; color: #f59e0b;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6"/>
                    <path d="M2 8h20v4H2z"/>
                    <path d="M12 2v6"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Cupons</span>
                <span class="settings-item-desc">Cupons de desconto</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>

        <a href="/ingredients" class="settings-item">
            <div class="settings-item-icon" style="background: #fef3c7; color: #d97706;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Ingredientes</span>
                <span class="settings-item-desc">Insumos e controle de custo</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
    </div>
    
    <div class="settings-section">
        <h3 class="settings-section-title">Gestão</h3>
        
        <a href="/analytics" class="settings-item analytics-item">
            <div class="settings-item-icon" style="background: #ede9fe; color: #7c3aed;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Analytics</span>
                <span class="settings-item-desc">Métricas e relatórios de vendas</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        
        <a href="/financial" class="settings-item financial-item">
            <div class="settings-item-icon" style="background: #dcfce7; color: #16a34a;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Financeiro</span>
                <span class="settings-item-desc">Receitas, custos e lucratividade</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        
        <a href="/expenses" class="settings-item expenses-item">
            <div class="settings-item-icon" style="background: #fee2e2; color: #dc2626;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="3" width="20" height="18" rx="2"/>
                    <line x1="8" y1="7" x2="16" y2="7"/>
                    <line x1="8" y1="11" x2="16" y2="11"/>
                    <line x1="8" y1="15" x2="12" y2="15"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Despesas</span>
                <span class="settings-item-desc">Controle de gastos e categorias</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        
        <a href="/product-costs" class="settings-item product-costs-item">
            <div class="settings-item-icon" style="background: #fef3c7; color: #d97706;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Custos de Produtos</span>
                <span class="settings-item-desc">Margens, ingredientes e embalagens</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        
        <a href="/packaging" class="settings-item packaging-item">
            <div class="settings-item-icon" style="background: #ede9fe; color: #7c3aed;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Insumos & Embalagens</span>
                <span class="settings-item-desc">Embalagens, estoque e custos</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
    </div>

    <div class="settings-section">
        <h3 class="settings-section-title">Integrações</h3>
        
        <a href="/ifood/config" class="settings-item ifood-item">
            <div class="settings-item-icon" style="background: #fee2e2; color: #dc2626;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A2.704 2.704 0 003 15.546V12a9 9 0 0118 0v3.546z"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">iFood</span>
                <span class="settings-item-desc">Integração e pedidos do iFood</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        
        <a href="/settings/whatsapp" class="settings-item whatsapp-item">
            <div class="settings-item-icon whatsapp-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">WhatsApp</span>
                <span class="settings-item-desc">Instâncias e notificações</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        
        <a href="/settings/api" class="settings-item api-item">
            <div class="settings-item-icon api-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">API</span>
                <span class="settings-item-desc">Tokens, chaves e endpoints</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
    </div>
    
    <div class="settings-section">
        <h3 class="settings-section-title">Conta</h3>
        
        <a href="/settings/profile" class="settings-item">
            <div class="settings-item-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Meu Perfil</span>
                <span class="settings-item-desc">Nome, email e senha</span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="chevron">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
    </div>
    
    <div class="settings-section">
        <a href="/logout" class="settings-item danger">
            <div class="settings-item-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </div>
            <div class="settings-item-content">
                <span class="settings-item-title">Sair</span>
                <span class="settings-item-desc">Encerrar sessão</span>
            </div>
        </a>
    </div>
</div>

<style>
.settings-item.whatsapp-item {
    border-left: 3px solid #25D366;
}
.settings-item-icon.whatsapp-icon {
    background: #dcfce7;
    color: #25D366;
}
.settings-item.api-item {
    border-left: 3px solid #2563eb;
}
.settings-item-icon.api-icon {
    background: #dbeafe;
    color: #2563eb;
}
</style>

<!-- Versão -->
<div class="app-version">
    <p>Multi Menu Mobile v1.0</p>
    <p class="text-muted"><?= htmlspecialchars($company['name'] ?? '') ?></p>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
