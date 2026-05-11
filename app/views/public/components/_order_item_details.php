<?php
/**
 * Partial: detalhes de um item do pedido (combo + personalizações)
 *
 * Variáveis esperadas no escopo (passadas pela closure em order.php):
 *   array|null $comboData              — dados decodificados de combo_data
 *   array      $componentCustomizations — component_customizations do combo_data
 *   array|null $customData             — dados decodificados de customization_data
 */

// Mostrar itens do combo
if ($comboData && is_array($comboData)):
    if (!empty($comboData['groups'])):
        // Formato novo: groups com items
        foreach ($comboData['groups'] as $group):
            if (!empty($group['items'])):
                foreach ($group['items'] as $selectedItem):
                    $itemName = $selectedItem['name'] ?? $selectedItem['simple_name'] ?? 'Item';
                    $simpleId = $selectedItem['simple_id'] ?? 0;
                    $itemQty = isset($selectedItem['qty'])
                        ? (int)$selectedItem['qty']
                        : (isset($selectedItem['default_qty']) ? (int)$selectedItem['default_qty'] : 1);
                    if ($itemQty <= 0) $itemQty = 1;

                    $hasUnitCustomizations = $simpleId > 0
                        && !empty($componentCustomizations[$simpleId]['unit_customizations'])
                        && is_array($componentCustomizations[$simpleId]['unit_customizations']);

                    if ($hasUnitCustomizations && $itemQty > 1):
                        // Mostrar cada unidade separadamente
                        foreach ($componentCustomizations[$simpleId]['unit_customizations'] as $unitNum => $unitCust):
?>
                        <div class="summary-item-detail">
                          <span><?= e($itemName) ?> (<?= $unitNum ?>º)</span>
                        </div>
<?php
                            if (!empty($unitCust['groups'])):
                                foreach ($unitCust['groups'] as $custGroup):
                                    $custGroupType = $custGroup['type'] ?? 'extra';
                                    $isCustChoiceGroup = in_array($custGroupType, ['single', 'addon', 'choice', 'pool']);

                                    if (!empty($custGroup['items'])):
                                        foreach ($custGroup['items'] as $custItem):
                                            $custName       = $custItem['name'] ?? '';
                                            $custQty        = isset($custItem['qty'])         ? (int)$custItem['qty']         : null;
                                            $custDeltaQty   = isset($custItem['delta_qty'])   ? (int)$custItem['delta_qty']   : null;
                                            $custPrice      = (float)($custItem['price']      ?? 0);
                                            $custDefaultQty = isset($custItem['default_qty']) ? (int)$custItem['default_qty'] : null;
                                            $custIsSelected = !empty($custItem['selected']) || ($custQty !== null && $custQty > 0);
                                            $custIsRemoved  = !empty($custItem['removed'])
                                                || ($custDefaultQty !== null && $custDefaultQty > 0
                                                    && ($custQty === 0 || $custQty === null));

                                            if ($custIsRemoved && $custName):
?>
                                            <div class="summary-item-detail" style="padding-left: 1rem;">
                                              <span>Sem <?= e($custName) ?></span>
                                            </div>
<?php
                                            elseif ($isCustChoiceGroup):
                                                if ($custIsSelected || ($custQty !== null && $custQty > 0)):
                                                    $actualQty       = ($custQty !== null && $custQty > 0) ? $custQty : 1;
                                                    $custDisplayName = ($actualQty > 1 ? "{$actualQty}x " : '') . $custName;
?>
                                            <div class="summary-item-detail" style="display:flex;justify-content:space-between;width:100%;padding-left:1rem;">
                                              <span style="color:#4b5563;font-size:0.9rem;">&bull; <?= e($custDisplayName) ?></span>
                                              <?php if ($custPrice > 0): ?>
                                                <span style="color:#4b5563;font-size:0.9rem;">+ R$ <?= number_format($custPrice, 2, ',', '.') ?></span>
                                              <?php endif; ?>
                                            </div>
<?php
                                                endif;
                                            else:
                                                if ($custDeltaQty === null && $custDefaultQty !== null && $custQty !== null) {
                                                    $custDeltaQty = $custQty - $custDefaultQty;
                                                }
                                                $shouldShowCust  = false;
                                                $custDisplayName = '';
                                                if ($custDeltaQty !== null && $custDeltaQty > 0) {
                                                    $shouldShowCust  = true;
                                                    $displayCustQty  = abs($custDeltaQty);
                                                    $custDisplayName = '+' . ($displayCustQty > 1 ? "{$displayCustQty}x " : '') . $custName;
                                                } elseif ($custDeltaQty !== null && $custDeltaQty < 0 && $custName) {
                                                    $shouldShowCust  = true;
                                                    $custDisplayName = 'Sem ' . $custName;
                                                    $custPrice       = 0;
                                                } elseif ($custPrice > 0 && ($custQty ?? 0) > 0) {
                                                    $shouldShowCust  = true;
                                                    $effectiveCustQty = $custQty ?? 0;
                                                    $custDisplayName = ($effectiveCustQty > 1 ? "{$effectiveCustQty}x " : '') . $custName;
                                                }
                                                if ($shouldShowCust && $custDisplayName):
?>
                                            <div class="summary-item-detail" style="display:flex;justify-content:space-between;width:100%;padding-left:1rem;">
                                              <span style="color:#4b5563;font-size:0.9rem;">&bull; <?= e($custDisplayName) ?></span>
                                              <?php if ($custPrice > 0): ?>
                                                <span style="color:#4b5563;font-size:0.9rem;">+ R$ <?= number_format($custPrice, 2, ',', '.') ?></span>
                                              <?php endif; ?>
                                            </div>
<?php
                                                endif;
                                            endif;
                                        endforeach;
                                    endif;
                                endforeach;
                            endif;
                        endforeach;
                    else:
                        // Comportamento original: item com quantidade
                        $displayQty = $itemQty > 1 ? $itemQty . 'x ' : '';
?>
                        <div class="summary-item-detail">
                          <span><?= e($displayQty . $itemName) ?></span>
                        </div>
<?php
                    endif;
                endforeach;
            endif;
        endforeach;
    else:
        // Formato antigo (array simples com item_name)
        foreach ($comboData as $group):
            if (!empty($group['item_name'])):
                $comboQty = !empty($group['qty']) && (int)$group['qty'] > 1 ? (int)$group['qty'] . 'x ' : '';
?>
                <div class="summary-item-detail">
                  <span><?= e($comboQty . $group['item_name']) ?></span>
                </div>
<?php
            endif;
        endforeach;
    endif;
endif;

// Mostrar personalizações
$customGroups = is_array($customData) && isset($customData['groups']) ? $customData['groups'] : $customData;
if ($customGroups && is_array($customGroups)):
    foreach ($customGroups as $groupData):
        $groupType    = $groupData['type'] ?? 'extra';
        $isChoiceGroup = in_array($groupType, ['single', 'addon', 'choice', 'pool']);

        if (!empty($groupData['items']) && is_array($groupData['items'])):
            foreach ($groupData['items'] as $customItem):
                if (!empty($customItem['name'])):
                    $customQty        = isset($customItem['qty'])         ? (int)$customItem['qty']         : null;
                    $customPrice      = !empty($customItem['price'])      ? (float)$customItem['price']      : 0;
                    $customDeltaQty   = isset($customItem['delta_qty'])   ? (int)$customItem['delta_qty']   : null;
                    $customDefaultQty = isset($customItem['default_qty']) ? (int)$customItem['default_qty'] : null;
                    $isSelected       = !empty($customItem['selected']) || ($customQty !== null && $customQty > 0);
                    $isRemoved        = !empty($customItem['removed'])
                        || ($customDefaultQty !== null && $customDefaultQty > 0
                            && ($customQty === 0 || $customQty === null));

                    if ($isRemoved):
?>
                    <div class="summary-item-detail">
                      <span>Sem <?= e($customItem['name']) ?></span>
                    </div>
<?php
                    elseif ($isChoiceGroup):
                        if ($isSelected || ($customQty !== null && $customQty > 0)):
                            $actualQty   = ($customQty !== null && $customQty > 0) ? $customQty : 1;
                            $displayName = ($actualQty > 1 ? "{$actualQty}x " : '') . $customItem['name'];
?>
                    <div class="summary-item-detail" style="display:flex;justify-content:space-between;width:100%;">
                      <span style="color:#4b5563;font-size:0.9rem;">&bull; <?= e($displayName) ?></span>
                      <?php if ($customPrice > 0): ?>
                        <span style="color:#4b5563;font-size:0.9rem;">+ R$ <?= number_format($customPrice, 2, ',', '.') ?></span>
                      <?php endif; ?>
                    </div>
<?php
                        endif;
                    else:
                        $effectiveQty = $customQty ?? 0;
                        if ($isRemoved) {
                            $displayName = 'Sem ' . $customItem['name'];
                        } else {
                            $prefix      = ($customDeltaQty !== null && $customDeltaQty > 0) ? '+ ' : '';
                            $displayQty  = $effectiveQty > 1 ? "{$effectiveQty}x " : '';
                            $displayName = $prefix . $displayQty . $customItem['name'];
                        }
?>
                    <div class="summary-item-detail" style="display:flex;justify-content:space-between;width:100%;">
                      <span style="color:#4b5563;font-size:0.9rem;">&bull; <?= e($displayName) ?></span>
                      <?php if ($customPrice > 0): ?>
                        <span style="color:#4b5563;font-size:0.9rem;">+ R$ <?= number_format($customPrice, 2, ',', '.') ?></span>
                      <?php endif; ?>
                    </div>
<?php
                    endif;
                endif;
            endforeach;
        endif;
    endforeach;
endif;
