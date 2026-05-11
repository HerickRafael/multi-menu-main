<?php
/**
 * OrderMessageBuilder
 * 
 * Helper centralizado para construir mensagens de pedidos (notificações e cliente)
 * Evita duplicação de código entre OrderNotificationService e views
 * 
 * @package App\Helpers
 */

require_once __DIR__ . '/DataValidator.php';
require_once __DIR__ . '/MoneyFormatter.php';
require_once __DIR__ . '/ReceiptFormatter.php';
require_once __DIR__ . '/TextParser.php';

class OrderMessageBuilder
{
    /**
     * Processa itens do combo no formato JSON
     * Retorna array de linhas formatadas
     * 
     * @param array $comboData Dados do combo parseados de JSON
     * @param array $componentCustomizations Customizações dos componentes do combo
     * @return array Array de strings formatadas
     */
    public static function processComboItems($comboData, $componentCustomizations = [])
    {
        $lines = [];
        
        if (empty($comboData['groups'])) {
            return $lines;
        }
        
        foreach ($comboData['groups'] as $group) {
            // Encontrar o item padrão do grupo e seu preço/delta
            $defaultPrice = null;
            $defaultDelta = 0.0;
            if (!empty($group['all_items'])) {
                foreach ($group['all_items'] as $allItem) {
                    if (!empty($allItem['is_default']) || !empty($allItem['default'])) {
                        $defaultPrice = (float)($allItem['base_price'] ?? $allItem['price_override'] ?? $allItem['price'] ?? 0);
                        $defaultDelta = (float)($allItem['delta'] ?? 0);
                        break;
                    }
                }
            }
            
            // Processar itens selecionados
            if (!empty($group['items'])) {
                foreach ($group['items'] as $selectedItem) {
                    $itemName = $selectedItem['name'] ?? 'Item';
                    $itemPrice = (float)($selectedItem['base_price'] ?? $selectedItem['price_override'] ?? $selectedItem['price'] ?? 0);
                    $itemDelta = (float)($selectedItem['delta'] ?? 0);
                    $isDefault = !empty($selectedItem['is_default']) || !empty($selectedItem['default']);
                    
                    // Pegar quantidade do item
                    $itemQty = isset($selectedItem['qty']) ? (int)$selectedItem['qty'] : (isset($selectedItem['default_qty']) ? (int)$selectedItem['default_qty'] : 1);
                    if ($itemQty <= 0) $itemQty = 1;
                    
                    // Processar customizações dos itens do combo (ex: extras adicionados)
                    $simpleId = $selectedItem['simple_id'] ?? 0;
                    
                    // Verificar se há customizações por unidade (unit_customizations)
                    $hasUnitCustomizations = $simpleId > 0 && 
                        !empty($componentCustomizations[$simpleId]['unit_customizations']) &&
                        is_array($componentCustomizations[$simpleId]['unit_customizations']);
                    
                    // Se tem múltiplas unidades com customizações diferentes, mostrar cada uma separadamente
                    if ($hasUnitCustomizations && $itemQty > 1) {
                        foreach ($componentCustomizations[$simpleId]['unit_customizations'] as $unitNum => $unitCust) {
                            // Nome com indicador de unidade: "Woll Smash (1º)"
                            $displayName = "{$itemName} ({$unitNum}º)";
                            
                            // Calcular diferença de preço
                            $priceDiff = 0;
                            if (!$isDefault) {
                                $deltaDiff = $itemDelta - $defaultDelta;
                                if (abs($deltaDiff) > 0.009) {
                                    $priceDiff = $deltaDiff;
                                } elseif ($defaultPrice !== null && $itemPrice > 0) {
                                    $priceDiff = $itemPrice - $defaultPrice;
                                }
                            }
                            
                            // Adicionar item do combo com indicador de unidade
                            if (abs($priceDiff) > 0.009) {
                                $lines[] = [
                                    'text' => ReceiptFormatter::indent($displayName),
                                    'price' => $priceDiff,
                                    'type' => 'combo_item_with_price'
                                ];
                            } else {
                                $lines[] = [
                                    'text' => ReceiptFormatter::indent($displayName),
                                    'price' => 0,
                                    'type' => 'combo_item_included'
                                ];
                            }
                            
                            // Processar customizações desta unidade específica
                            if (!empty($unitCust['groups'])) {
                                $custLines = self::processCustomizationGroups($unitCust['groups']);
                                $lines = array_merge($lines, $custLines);
                            }
                        }
                    } else {
                        // Comportamento original: quantidade única ou sem customizações por unidade
                        $displayName = $itemQty > 1 ? "{$itemQty}x {$itemName}" : $itemName;
                        
                        // Calcular diferença de preço
                        $priceDiff = 0;
                        if (!$isDefault) {
                            $deltaDiff = $itemDelta - $defaultDelta;
                            if (abs($deltaDiff) > 0.009) {
                                $priceDiff = $deltaDiff;
                            } elseif ($defaultPrice !== null && $itemPrice > 0) {
                                $priceDiff = $itemPrice - $defaultPrice;
                            }
                        }
                        
                        // Adicionar item do combo
                        if (abs($priceDiff) > 0.009) {
                            $lines[] = [
                                'text' => ReceiptFormatter::indent($displayName),
                                'price' => $priceDiff,
                                'type' => 'combo_item_with_price'
                            ];
                        } else {
                            $lines[] = [
                                'text' => ReceiptFormatter::indent($displayName),
                                'price' => 0,
                                'type' => 'combo_item_included'
                            ];
                        }
                        
                        // Processar customizações - verificar diferentes estruturas
                        if ($simpleId > 0 && !empty($componentCustomizations[$simpleId])) {
                            $custGroups = null;
                            
                            // Tentar pegar de customization.groups (formato expandido)
                            if (!empty($componentCustomizations[$simpleId]['customization']['groups'])) {
                                $custGroups = $componentCustomizations[$simpleId]['customization']['groups'];
                            }
                            // Tentar pegar de unit_customizations (primeira unidade como fallback)
                            elseif (!empty($componentCustomizations[$simpleId]['unit_customizations'])) {
                                $firstUnit = reset($componentCustomizations[$simpleId]['unit_customizations']);
                                if (!empty($firstUnit['groups'])) {
                                    $custGroups = $firstUnit['groups'];
                                }
                            }
                            
                            if ($custGroups) {
                                $custLines = self::processCustomizationGroups($custGroups);
                                $lines = array_merge($lines, $custLines);
                            }
                        }
                    }
                }
            }
        }
        
        return $lines;
    }
    
    /**
     * Processa grupos de customização (extras, remoções, escolhas, etc)
     * 
     * @param array $custGroups Grupos de customização
     * @return array Array de linhas formatadas
     */
    public static function processCustomizationGroups($custGroups)
    {
        $lines = [];
        
        foreach ($custGroups as $custGroup) {
            if (empty($custGroup['items'])) {
                continue;
            }
            
            // Verificar se é um grupo de seleção/escolha (single, addon, choice)
            $groupType = $custGroup['type'] ?? 'extra';
            $isChoiceGroup = in_array($groupType, ['single', 'addon', 'choice']);
            $groupName = $custGroup['name'] ?? '';
            
            foreach ($custGroup['items'] as $custItem) {
                $custName = $custItem['name'] ?? '';
                // Manter qty como null se não existir para detectar remoções corretamente
                $custQty = isset($custItem['qty']) ? (int)$custItem['qty'] : null;
                $custDeltaQty = isset($custItem['delta_qty']) ? (int)$custItem['delta_qty'] : null;
                $custPrice = (float)($custItem['price'] ?? 0);
                $custDefaultQty = isset($custItem['default_qty']) ? (int)$custItem['default_qty'] : null;
                $custUnitPrice = isset($custItem['unit_price']) ? (float)$custItem['unit_price'] : 0;
                $isSelected = !empty($custItem['selected']) || ($custQty !== null && $custQty > 0);
                // Remoção: item marcado como removido OU tem default_qty > 0 e qty é 0 ou null
                $isRemoved = !empty($custItem['removed']) || ($custDefaultQty !== null && $custDefaultQty > 0 && ($custQty === 0 || $custQty === null));
                
                // Verificar se é remoção (item removido)
                if ($isRemoved && $custName) {
                    $lines[] = [
                        'text' => ReceiptFormatter::indent("Sem {$custName}"),
                        'price' => 0,
                        'type' => 'removal'
                    ];
                    continue;
                }
                
                // Usar qty efetivo: se null, assume 0
                $effectiveQty = $custQty ?? 0;
                
                // Para grupos de escolha: mostrar o item selecionado (qty > 0)
                if ($isChoiceGroup && $isSelected && $effectiveQty > 0 && $custName) {
                    // Mostrar apenas o nome do item selecionado
                    $lines[] = [
                        'text' => ReceiptFormatter::indent($custName),
                        'price' => $custPrice,
                        'type' => 'choice_selection'
                    ];
                    continue; // Não processar novamente abaixo
                }
                
                // Para grupos pool (montagem/açaí): mostrar TODOS os itens com qty > 0
                elseif ($groupType === 'pool') {
                    if ($custName && $effectiveQty > 0) {
                        $paidQty = isset($custItem['paid_qty']) ? (int)$custItem['paid_qty'] : 0;
                        $custUnitPricePool = isset($custItem['unit_price']) ? (float)$custItem['unit_price'] : 0;
                        $displayPrice = ($paidQty > 0 && $custUnitPricePool > 0) ? $paidQty * $custUnitPricePool : $custPrice;
                        $qtyPrefix = $effectiveQty > 1 ? "{$effectiveQty}x " : "";
                        $lines[] = [
                            'text' => ReceiptFormatter::indent($qtyPrefix . $custName),
                            'price' => $displayPrice,
                            'type' => $displayPrice > 0.009 ? 'customization_with_price' : 'customization_included'
                        ];
                    }
                    continue;
                }
                
                // Calcular delta se não existir
                if ($custDeltaQty === null && $custDefaultQty !== null && $custQty !== null && $custQty >= 0) {
                    $custDeltaQty = $custQty - $custDefaultQty;
                }
                
                // Verificar se é remoção por delta negativo
                if ($custDeltaQty !== null && $custDeltaQty < 0 && $custName) {
                    $lines[] = [
                        'text' => ReceiptFormatter::indent("Sem {$custName}"),
                        'price' => 0,
                        'type' => 'removal'
                    ];
                    continue;
                }
                
                // Calcular preço se não existir
                if ($custPrice <= 0.009 && $custDeltaQty !== null && $custUnitPrice > 0) {
                    $custPrice = $custUnitPrice * abs($custDeltaQty);
                }
                
                // Mostrar apenas itens com modificação ou preço
                if ($custName && (($custDeltaQty !== null && $custDeltaQty != 0) || $custPrice > 0.009)) {
                    $displayQty = $custDeltaQty !== null ? abs($custDeltaQty) : $effectiveQty;
                    $prefix = $custDeltaQty !== null && $custDeltaQty > 0 ? '+' : '';
                    $custDisplayName = $prefix . ($displayQty > 1 ? "{$displayQty}x " : "") . $custName;
                    
                    $lines[] = [
                        'text' => ReceiptFormatter::indent($custDisplayName),
                        'price' => $custPrice,
                        'type' => $custPrice > 0.009 ? 'customization_with_price' : 'customization_included'
                    ];
                }
            }
        }
        
        return $lines;
    }
    
    /**
     * Processa personalizações simples (formato texto)
     * 
     * @param string $customization String de customização
     * @return array Array de linhas formatadas
     */
    public static function processSimpleCustomization($customization)
    {
        $lines = [];
        
        if (empty($customization)) {
            return $lines;
        }
        
        $customItems = TextParser::splitItems($customization, true);
        
        foreach ($customItems as $customItem) {
            $parsed = TextParser::extractAll($customItem);
            
            // Verificar se é remoção (Sem algo)
            if (preg_match('/^Sem\s+(.+)$/i', $parsed['text'], $semMatch)) {
                $lines[] = [
                    'text' => ReceiptFormatter::indent("Sem {$semMatch[1]}"),
                    'price' => 0,
                    'type' => 'removal'
                ];
                continue;
            }
            
            // Verificar se é remoção com prefixo -
            if ($parsed['prefix'] === '-') {
                $lines[] = [
                    'text' => ReceiptFormatter::indent("{$parsed['qty']}x {$parsed['text']}"),
                    'price' => 0,
                    'type' => 'removal'
                ];
                continue;
            }
            
            // Item com ou sem preço
            $displayText = ($parsed['qty'] > 1 ? "{$parsed['qty']}x " : "") . $parsed['text'];
            $lines[] = [
                'text' => ReceiptFormatter::indent($displayText),
                'price' => $parsed['price'],
                'type' => $parsed['price'] > 0 ? 'extra_with_price' : 'extra_included'
            ];
        }
        
        return $lines;
    }
    
    /**
     * Processa itens do combo com formatação visual melhorada
     * Retorna string formatada pronta para uso
     * 
     * @param array $comboData Dados do combo parseados de JSON
     * @param array $componentCustomizations Customizações dos componentes do combo
     * @return string Texto formatado
     */
    public static function processComboItemsFormatted($comboData, $componentCustomizations = [])
    {
        $message = '';
        
        if (empty($comboData['groups'])) {
            return $message;
        }
        
        foreach ($comboData['groups'] as $group) {
            if (empty($group['items'])) {
                continue;
            }
            
            foreach ($group['items'] as $selectedItem) {
                $itemName = $selectedItem['name'] ?? 'Item';
                $simpleId = $selectedItem['simple_id'] ?? 0;
                $itemQty = isset($selectedItem['qty']) ? (int)$selectedItem['qty'] : (isset($selectedItem['default_qty']) ? (int)$selectedItem['default_qty'] : 1);
                if ($itemQty <= 0) $itemQty = 1;
                
                // Verificar se há unit_customizations
                $hasUnitCustomizations = $simpleId > 0 && 
                    !empty($componentCustomizations[$simpleId]['unit_customizations']) &&
                    is_array($componentCustomizations[$simpleId]['unit_customizations']);
                
                if ($hasUnitCustomizations && $itemQty > 1) {
                    // Mostrar cada unidade separadamente
                    foreach ($componentCustomizations[$simpleId]['unit_customizations'] as $unitNum => $unitCust) {
                        // Verificar se esta unidade tem customizações
                        $hasCustoms = !empty($unitCust['groups']);
                        $unitExtras = [];
                        
                        if ($hasCustoms) {
                            // Coletar extras desta unidade
                            foreach ($unitCust['groups'] as $custGroup) {
                                if (empty($custGroup['items'])) continue;
                                
                                $custGroupType = $custGroup['type'] ?? 'extra';
                                $isCustChoiceGroup = in_array($custGroupType, ['single', 'addon', 'choice']);
                                
                                foreach ($custGroup['items'] as $custItem) {
                                    $custName = $custItem['name'] ?? '';
                                    $custQty = isset($custItem['qty']) ? (int)$custItem['qty'] : null;
                                    $custDeltaQty = isset($custItem['delta_qty']) ? (int)$custItem['delta_qty'] : null;
                                    $custPrice = (float)($custItem['price'] ?? 0);
                                    $custDefaultQty = isset($custItem['default_qty']) ? (int)$custItem['default_qty'] : null;
                                    $custIsSelected = !empty($custItem['selected']) || ($custQty !== null && $custQty > 0);
                                    $custIsRemoved = !empty($custItem['removed']) || ($custDefaultQty !== null && $custDefaultQty > 0 && ($custQty === 0 || $custQty === null));
                                    
                                    if ($custDeltaQty === null && $custDefaultQty !== null && $custQty !== null) {
                                        $custDeltaQty = $custQty - $custDefaultQty;
                                    }
                                    
                                    if ($custIsRemoved && $custName) {
                                        $unitExtras[] = ['text' => "Sem {$custName}", 'price' => 0, 'isExtra' => false];
                                    } elseif ($isCustChoiceGroup && $custIsSelected && $custQty > 0 && $custName) {
                                        // Item de escolha (ex: queijo)
                                        $unitExtras[] = ['text' => $custName, 'price' => $custPrice, 'isExtra' => false];
                                    } elseif ($custDeltaQty !== null && $custDeltaQty > 0 && $custName) {
                                        // Extra adicionado
                                        $displayQty = abs($custDeltaQty);
                                        $extraText = "+{$displayQty}x {$custName}";
                                        $unitExtras[] = ['text' => $extraText, 'price' => $custPrice, 'isExtra' => true];
                                    } elseif ($custDeltaQty !== null && $custDeltaQty < 0 && $custName) {
                                        $unitExtras[] = ['text' => "Sem {$custName}", 'price' => 0, 'isExtra' => false];
                                    }
                                }
                            }
                        }
                        
                        // Formatar linha do item com unidade
                        if (!empty($unitExtras)) {
                            // Item com customizações - usar negrito
                            $message .= "\n • *{$itemName} ({$unitNum}º)*\n";
                        } else {
                            // Item sem customizações
                            $message .= " • {$itemName} ({$unitNum}º)\n";
                        }
                        
                        // Adicionar extras
                        foreach ($unitExtras as $extra) {
                            if ($extra['isExtra'] && $extra['price'] > 0) {
                                $message .= "- `{$extra['text']} — " . MoneyFormatter::format($extra['price']) . "`\n";
                            } elseif ($extra['isExtra']) {
                                $message .= "- `{$extra['text']}`\n";
                            } else {
                                $message .= "- {$extra['text']}\n";
                            }
                        }
                    }
                } else {
                    // Item sem unit_customizations ou qty = 1
                    $displayName = $itemQty > 1 ? "{$itemQty}x {$itemName}" : $itemName;
                    
                    // Verificar se tem customizações normais
                    $hasCustoms = !empty($componentCustomizations[$simpleId]['customization']['groups']) ||
                                  !empty($componentCustomizations[$simpleId]['unit_customizations']);
                    
                    if ($hasCustoms) {
                        $message .= "\n • *{$displayName}*\n";
                        
                        // Processar customizações
                        $custGroups = null;
                        if (!empty($componentCustomizations[$simpleId]['customization']['groups'])) {
                            $custGroups = $componentCustomizations[$simpleId]['customization']['groups'];
                        } elseif (!empty($componentCustomizations[$simpleId]['unit_customizations'])) {
                            $firstUnit = reset($componentCustomizations[$simpleId]['unit_customizations']);
                            if (!empty($firstUnit['groups'])) {
                                $custGroups = $firstUnit['groups'];
                            }
                        }
                        
                        if ($custGroups) {
                            foreach ($custGroups as $custGroup) {
                                if (empty($custGroup['items'])) continue;
                                
                                $custGroupType = $custGroup['type'] ?? 'extra';
                                $isCustChoiceGroup = in_array($custGroupType, ['single', 'addon', 'choice']);
                                
                                foreach ($custGroup['items'] as $custItem) {
                                    $custName = $custItem['name'] ?? '';
                                    $custQty = isset($custItem['qty']) ? (int)$custItem['qty'] : null;
                                    $custDeltaQty = isset($custItem['delta_qty']) ? (int)$custItem['delta_qty'] : null;
                                    $custPrice = (float)($custItem['price'] ?? 0);
                                    $custDefaultQty = isset($custItem['default_qty']) ? (int)$custItem['default_qty'] : null;
                                    $custIsSelected = !empty($custItem['selected']) || ($custQty !== null && $custQty > 0);
                                    $custIsRemoved = !empty($custItem['removed']) || ($custDefaultQty !== null && $custDefaultQty > 0 && ($custQty === 0 || $custQty === null));
                                    
                                    if ($custDeltaQty === null && $custDefaultQty !== null && $custQty !== null) {
                                        $custDeltaQty = $custQty - $custDefaultQty;
                                    }
                                    
                                    if ($custIsRemoved && $custName) {
                                        $message .= "- Sem {$custName}\n";
                                    } elseif ($isCustChoiceGroup && $custIsSelected && $custQty > 0 && $custName) {
                                        $message .= "- {$custName}\n";
                                    } elseif ($custDeltaQty !== null && $custDeltaQty > 0 && $custName) {
                                        $displayQty = abs($custDeltaQty);
                                        if ($custPrice > 0) {
                                            $message .= "- `+{$displayQty}x {$custName} — " . MoneyFormatter::format($custPrice) . "`\n";
                                        } else {
                                            $message .= "- `+{$displayQty}x {$custName}`\n";
                                        }
                                    } elseif ($custDeltaQty !== null && $custDeltaQty < 0 && $custName) {
                                        $message .= "- Sem {$custName}\n";
                                    }
                                }
                            }
                        }
                    } else {
                        // Item simples sem customizações
                        $message .= " • {$displayName}\n";
                    }
                }
            }
        }
        
        return $message;
    }
    
    /**
     * Processa personalização simples com formatação visual melhorada
     * 
     * @param string $customization String de personalização
     * @return string Texto formatado
     */
    public static function processSimpleCustomizationFormatted($customization)
    {
        $message = '';
        
        if (empty($customization)) {
            return $message;
        }
        
        $items = TextParser::splitItems($customization);
        
        foreach ($items as $item) {
            $parsed = TextParser::extractAll($item);
            $displayText = ($parsed['qty'] > 1 ? "{$parsed['qty']}x " : "") . $parsed['text'];
            
            if ($parsed['price'] > 0) {
                $message .= "- `{$displayText} — " . MoneyFormatter::format($parsed['price']) . "`\n";
            } else {
                $message .= "- {$displayText}\n";
            }
        }
        
        return $message;
    }
    
    /**
     * Formata array de linhas em string de mensagem
     * 
     * @param array $lines Array de linhas com 'text', 'price', 'type'
     * @return string Mensagem formatada
     */
    public static function formatLines($lines)
    {
        $message = '';
        
        foreach ($lines as $line) {
            if ($line['price'] > 0.009) {
                $message .= ReceiptFormatter::formatItemLine($line['text'], MoneyFormatter::format($line['price']));
            } else {
                $message .= $line['text'] . "\n";
            }
        }
        
        return $message;
    }
    
    /**
     * Calcula desconto de fidelidade baseado na empresa e itens
     * 
     * @param array $company Dados da empresa
     * @param array $items Itens do pedido
     * @return float Valor do desconto
     */
    public static function calculateLoyaltyDiscount($company, $items)
    {
        if (empty($company['embedded_delivery_fee']) || $company['embedded_delivery_fee'] <= 0) {
            return 0.0;
        }
        
        $embeddedFee = (float)$company['embedded_delivery_fee'];
        
        // Buscar IDs dos produtos com taxa embutida habilitada
        $companyId = (int)($company['id'] ?? 0);
        $embeddedEnabledIds = [];
        if ($companyId > 0) {
            $db = db();
            $stmt = $db->prepare('SELECT id FROM products WHERE company_id = ? AND embedded_fee_enabled = 1');
            $stmt->execute([$companyId]);
            $embeddedEnabledIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'id');
            $embeddedEnabledIds = array_map('intval', $embeddedEnabledIds);
        }
        
        $totalQty = 0;
        
        foreach ($items as $item) {
            $productId = (int)(DataValidator::getInt($item, 'product_id') ?: ($item['product']['id'] ?? 0));
            $qty = DataValidator::getInt($item, 'quantidade', 'quantity') ?: 1;
            
            if (in_array($productId, $embeddedEnabledIds, true)) {
                $totalQty += $qty;
            }
        }
        
        return $embeddedFee * $totalQty;
    }
    
    /**
     * Formata bloco de totais (subtotal, taxa, descontos, total)
     * 
     * @param float $subtotal Subtotal do pedido
     * @param float $deliveryFee Taxa de entrega
     * @param float $loyaltyDiscount Desconto de fidelidade
     * @param float $discount Desconto adicional
     * @param float $total Total final
     * @return string Bloco formatado
     */
    public static function formatTotalsBlock($subtotal, $deliveryFee, $loyaltyDiscount, $discount, $total)
    {
        $message = ReceiptFormatter::separator() . "\n";
        $message .= ReceiptFormatter::formatMoneyLine('Subtotal:', $subtotal);
        
        if ($deliveryFee > 0) {
            $message .= ReceiptFormatter::formatMoneyLine('Taxa Entrega:', $deliveryFee);
        }
        
        if ($loyaltyDiscount > 0) {
            $message .= ReceiptFormatter::formatMoneyLine('Desconto Fidelidade:', -$loyaltyDiscount);
        }
        
        if ($discount > 0) {
            $message .= ReceiptFormatter::formatMoneyLine('Desconto:', -$discount);
        }
        
        $message .= "\n" . ReceiptFormatter::alignRight('*TOTAL:', MoneyFormatter::format($total) . "*");
        
        return $message;
    }
}
