<?php
/**
 * Configurações de Horários Mobile
 * Design igual ao desktop, adaptado para toque
 * 
 * Usa a mesma estrutura da tabela company_hours do desktop
 * weekday: 1=Segunda, 2=Terça, 3=Quarta, 4=Quinta, 5=Sexta, 6=Sábado, 7=Domingo
 */
$days = [
    1 => ['key' => 'monday', 'label' => 'Segunda'],
    2 => ['key' => 'tuesday', 'label' => 'Terça'],
    3 => ['key' => 'wednesday', 'label' => 'Quarta'],
    4 => ['key' => 'thursday', 'label' => 'Quinta'],
    5 => ['key' => 'friday', 'label' => 'Sexta'],
    6 => ['key' => 'saturday', 'label' => 'Sábado'],
    7 => ['key' => 'sunday', 'label' => 'Domingo']
];
ob_start();
?>

<style>
/* Estilos específicos para horários */
.hours-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.day-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.day-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
}

.day-header:active {
    background: #f9fafb;
}

.day-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

/* Toggle Switch */
.toggle-track {
    position: relative;
    width: 44px;
    height: 26px;
    background: #d1d5db;
    border-radius: 26px;
    transition: background 0.2s;
    flex-shrink: 0;
}

.toggle-track.active {
    background: var(--primary);
}

.toggle-thumb {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    transition: transform 0.2s;
}

.toggle-track.active .toggle-thumb {
    transform: translateX(18px);
}

.toggle-checkbox {
    display: none;
}

.day-text {
    flex: 1;
}

.day-name {
    font-weight: 600;
    font-size: 16px;
    color: #1f2937;
}

.day-summary {
    font-size: 13px;
    color: #6b7280;
    margin-top: 2px;
}

.day-summary.closed {
    color: #9ca3af;
}

.chevron {
    width: 24px;
    height: 24px;
    color: #9ca3af;
    transition: transform 0.2s;
    flex-shrink: 0;
}

.day-card.expanded .chevron {
    transform: rotate(90deg);
}

/* Detalhes expandidos */
.day-details {
    display: none;
    padding: 0 16px 16px;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
}

.day-card.expanded .day-details {
    display: block;
}

/* Bloco de horário */
.time-block {
    margin-top: 16px;
}

.time-block-label {
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.time-row {
    display: flex;
    gap: 12px;
    align-items: center;
}

.time-field {
    flex: 1;
}

.time-field label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 4px;
}

.time-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 500;
    text-align: center;
    background: white;
    color: #1f2937;
    -webkit-appearance: none;
}

.time-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(91, 33, 182, 0.1);
}

.time-separator {
    color: #9ca3af;
    font-size: 14px;
    padding-top: 20px;
}

/* Segundo horário */
.slot2-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 16px 0 8px;
}

.slot2-divider-line {
    flex: 1;
    height: 1px;
    background: #e5e7eb;
}

.slot2-divider-text {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Botões de ação */
.btn-add-slot2,
.btn-remove-slot2 {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 12px;
}

.btn-add-slot2 {
    background: white;
    border: 1px dashed #d1d5db;
    color: #6b7280;
}

.btn-add-slot2:active {
    background: #f9fafb;
}

.btn-remove-slot2 {
    background: transparent;
    border: none;
    color: #ef4444;
}

.btn-remove-slot2:active {
    background: #fef2f2;
}

.slot2-container {
    display: none;
}

.slot2-container.visible {
    display: block;
}

/* Botão salvar */
.save-container {
    position: fixed;
    bottom: calc(var(--bottom-nav-height, 64px) + env(safe-area-inset-bottom, 0px));
    left: 0;
    right: 0;
    padding: 16px;
    background: linear-gradient(to top, white 80%, transparent);
}

.btn-save {
    width: 100%;
    padding: 16px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 14px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(91, 33, 182, 0.3);
}

.btn-save:active {
    transform: scale(0.98);
}

/* Espaço extra no final para o botão fixo */
.hours-spacer {
    height: 100px;
}
</style>

<form method="POST" action="/settings/hours" class="mobile-form">
    
    <p style="font-size: 14px; color: #6b7280; margin-bottom: 16px;">
        Configure os horários de funcionamento. Toque em um dia para expandir.
    </p>
    
    <div class="hours-container">
        <?php foreach ($days as $weekday => $day): ?>
            <?php 
                $key = $day['key'];
                $label = $day['label'];
                $row = $hours[$weekday] ?? ['is_open' => 0, 'open1' => null, 'close1' => null, 'open2' => null, 'close2' => null];
                $isOpen = !empty($row['is_open']);
                $open1 = $row['open1'] ? substr($row['open1'], 0, 5) : '';
                $close1 = $row['close1'] ? substr($row['close1'], 0, 5) : '';
                $open2 = $row['open2'] ? substr($row['open2'], 0, 5) : '';
                $close2 = $row['close2'] ? substr($row['close2'], 0, 5) : '';
                $hasSlot2 = !empty($open2) && !empty($close2);
                
                // Resumo do horário
                $summary = 'Fechado';
                if ($isOpen) {
                    if ($open1 && $close1) {
                        $summary = $open1 . ' - ' . $close1;
                        if ($hasSlot2) {
                            $summary .= ' / ' . $open2 . ' - ' . $close2;
                        }
                    } else {
                        $summary = 'Horário não definido';
                    }
                }
            ?>
            <div class="day-card" data-day="<?= $key ?>">
                <div class="day-header" onclick="toggleDayCard('<?= $key ?>')">
                    <div class="day-info">
                        <label class="toggle-track <?= $isOpen ? 'active' : '' ?>" onclick="event.stopPropagation()">
                            <input type="checkbox" class="toggle-checkbox" 
                                   name="<?= $key ?>_active" value="1" 
                                   <?= $isOpen ? 'checked' : '' ?>
                                   onchange="toggleDayStatus('<?= $key ?>', this.checked)">
                            <span class="toggle-thumb"></span>
                        </label>
                        <div class="day-text">
                            <div class="day-name"><?= $label ?></div>
                            <div class="day-summary <?= $isOpen ? '' : 'closed' ?>" id="summary-<?= $key ?>">
                                <?= htmlspecialchars($summary) ?>
                            </div>
                        </div>
                    </div>
                    <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 6l6 6-6 6"/>
                    </svg>
                </div>
                
                <div class="day-details">
                    <!-- Primeiro Horário -->
                    <div class="time-block">
                        <div class="time-block-label">Horário principal</div>
                        <div class="time-row">
                            <div class="time-field">
                                <label>Início</label>
                                <input type="time" class="time-input" 
                                       name="<?= $key ?>_open1" 
                                       value="<?= htmlspecialchars($open1) ?>"
                                       onchange="updateSummary('<?= $key ?>')">
                            </div>
                            <span class="time-separator">às</span>
                            <div class="time-field">
                                <label>Término</label>
                                <input type="time" class="time-input" 
                                       name="<?= $key ?>_close1" 
                                       value="<?= htmlspecialchars($close1) ?>"
                                       onchange="updateSummary('<?= $key ?>')">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Segundo Horário -->
                    <div class="slot2-container <?= $hasSlot2 ? 'visible' : '' ?>" id="slot2-<?= $key ?>">
                        <div class="slot2-divider">
                            <div class="slot2-divider-line"></div>
                            <span class="slot2-divider-text">Segundo horário</span>
                            <div class="slot2-divider-line"></div>
                        </div>
                        <div class="time-row">
                            <div class="time-field">
                                <label>Início</label>
                                <input type="time" class="time-input" 
                                       name="<?= $key ?>_open2" 
                                       value="<?= htmlspecialchars($open2) ?>"
                                       onchange="updateSummary('<?= $key ?>')">
                            </div>
                            <span class="time-separator">às</span>
                            <div class="time-field">
                                <label>Término</label>
                                <input type="time" class="time-input" 
                                       name="<?= $key ?>_close2" 
                                       value="<?= htmlspecialchars($close2) ?>"
                                       onchange="updateSummary('<?= $key ?>')">
                            </div>
                        </div>
                        <button type="button" class="btn-remove-slot2" onclick="removeSlot2('<?= $key ?>')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M6 12h12"/>
                            </svg>
                            Remover segundo horário
                        </button>
                    </div>
                    
                    <!-- Botão adicionar segundo horário -->
                    <button type="button" class="btn-add-slot2 <?= $hasSlot2 ? 'hidden' : '' ?>" 
                            id="btn-add-<?= $key ?>" onclick="addSlot2('<?= $key ?>')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 6v12M6 12h12"/>
                        </svg>
                        Adicionar segundo horário
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="hours-spacer"></div>
    
    <div class="save-container">
        <button type="submit" class="btn-save">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M20 7L9 18l-5-5"/>
            </svg>
            Salvar Horários
        </button>
    </div>
</form>

<script>
// Expandir/recolher card
function toggleDayCard(day) {
    const card = document.querySelector('.day-card[data-day="' + day + '"]');
    card.classList.toggle('expanded');
}

// Toggle ativo/inativo
function toggleDayStatus(day, checked) {
    const card = document.querySelector('.day-card[data-day="' + day + '"]');
    const toggle = card.querySelector('.toggle-track');
    const summary = document.getElementById('summary-' + day);
    
    if (checked) {
        toggle.classList.add('active');
        summary.classList.remove('closed');
        updateSummary(day);
    } else {
        toggle.classList.remove('active');
        summary.classList.add('closed');
        summary.textContent = 'Fechado';
    }
}

// Atualizar resumo do horário
function updateSummary(day) {
    const card = document.querySelector('.day-card[data-day="' + day + '"]');
    const checkbox = card.querySelector('.toggle-checkbox');
    const summary = document.getElementById('summary-' + day);
    
    if (!checkbox.checked) {
        summary.textContent = 'Fechado';
        return;
    }
    
    const open1 = card.querySelector('input[name="' + day + '_open1"]').value;
    const close1 = card.querySelector('input[name="' + day + '_close1"]').value;
    const open2 = card.querySelector('input[name="' + day + '_open2"]').value;
    const close2 = card.querySelector('input[name="' + day + '_close2"]').value;
    
    let text = '';
    if (open1 && close1) {
        text = open1 + ' - ' + close1;
    }
    if (open2 && close2) {
        text += (text ? ' / ' : '') + open2 + ' - ' + close2;
    }
    
    summary.textContent = text || 'Horário não definido';
}

// Adicionar segundo horário
function addSlot2(day) {
    const slot2 = document.getElementById('slot2-' + day);
    const btnAdd = document.getElementById('btn-add-' + day);
    
    slot2.classList.add('visible');
    btnAdd.classList.add('hidden');
}

// Remover segundo horário
function removeSlot2(day) {
    const slot2 = document.getElementById('slot2-' + day);
    const btnAdd = document.getElementById('btn-add-' + day);
    const card = document.querySelector('.day-card[data-day="' + day + '"]');
    
    // Limpar valores
    card.querySelector('input[name="' + day + '_open2"]').value = '';
    card.querySelector('input[name="' + day + '_close2"]').value = '';
    
    slot2.classList.remove('visible');
    btnAdd.classList.remove('hidden');
    
    updateSummary(day);
}

// Classe hidden
document.head.insertAdjacentHTML('beforeend', '<style>.hidden{display:none!important}</style>');
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
