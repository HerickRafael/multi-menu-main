<?php
declare(strict_types=1);
// app/services/ThermalReceipt.php
// Otimizado para Mini Impressora Bluetooth Térmica 58mm

require_once __DIR__ . '/../../vendor/autoload.php';

class ThermalReceipt
{
    // Usar constantes centralizadas
    private const WIDTH = FormatConstants::THERMAL_WIDTH;
    private const MARGIN = FormatConstants::THERMAL_MARGIN;
    
    /**
     * Desenha uma linha tracejada
     */
    private static function drawDashedLine($pdf, $x1, $y, $x2, $y2): void
    {
        $pdf->SetLineWidth(0.1);
        $pdf->SetDrawColor(100, 100, 100);
        
        $dashLength = 2;
        $gapLength = 1;
        $currentX = $x1;
        
        while ($currentX < $x2) {
            $endX = min($currentX + $dashLength, $x2);
            $pdf->Line($currentX, $y, $endX, $y2);
            $currentX += $dashLength + $gapLength;
        }
        
        $pdf->SetDrawColor(0, 0, 0);
    }
    
    /**
     * Gera PDF otimizado para impressora térmica 58mm
     */
    public static function generatePdf(array $company, array $orderRow, array $items, string $rawMessage = ''): string
    {
        $pdf = new \FPDF('P', 'mm', array(self::WIDTH, 297));
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->SetMargins(self::MARGIN, self::MARGIN, self::MARGIN);
        $pdf->AddPage();
        
        // Helper: convert UTF-8 to ISO-8859-1 for FPDF
        $pdfText = static function ($s) {
            if ($s === null) return '';
            if (!is_string($s)) $s = (string)$s;
            // Remove emojis e caracteres especiais
            $s = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $s);
            $s = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $s);
            $s = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $s);
            
            if (function_exists('iconv')) {
                $c = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
                if ($c !== false) return $c;
            }
            return @utf8_decode($s);
        };

        // Logo
        $logoPath = null;
        $companyLogo = DataValidator::getString($company, 'logo');
        if (!empty($companyLogo)) {
            $candidate = __DIR__ . '/../../public/' . ltrim($companyLogo, '/');
            if (file_exists($candidate)) $logoPath = $candidate;
        }

        // CABEÇALHO
        $pdf->Ln(2);
        
        if ($logoPath && is_readable($logoPath)) {
            try {
                $info = @getimagesize($logoPath);
                if ($info) {
                    $imgW = 20;
                    $centerX = (self::WIDTH - $imgW) / 2;
                    $pdf->Image($logoPath, $centerX, $pdf->GetY(), $imgW);
                    $pdf->Ln(16);
                }
            } catch (Throwable $e) {}
        }

        $companyName = strtoupper(DataValidator::getString($company, 'name'));
        $companyAddress = DataValidator::getString($company, 'address');
        $companyWhatsapp = DataValidator::getString($company, 'whatsapp');
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, $pdfText($companyName), 0, 1, 'C');
        
        if (!empty($companyAddress)) {
            $pdf->SetFont('Arial', '', 7);
            $pdf->MultiCell(0, 3, $pdfText($companyAddress), 0, 'C');
        }
        
        if (!empty($companyWhatsapp)) {
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell(0, 3, $pdfText('Tel: ' . $companyWhatsapp), 0, 1, 'C');
        }
        
        $pdf->Ln(2);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(3);

        // DADOS DO PEDIDO
        $orderId = DataValidator::getString($orderRow, 'id') ?: 'N/A';
        $orderNumber = DataValidator::getString($orderRow, 'order_number') ?: $orderId; // Usa order_number ou fallback para id
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, $pdfText('PEDIDO #' . $orderNumber), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 8);
        $createdAt = DataValidator::getString($orderRow, 'created_at');
        $dateTime = !empty($createdAt)
            ? date('d/m/Y H:i', strtotime($createdAt)) 
            : date('d/m/Y H:i');
        $pdf->Cell(0, 4, $pdfText($dateTime), 0, 1, 'C');
        
        $pdf->Ln(2);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(3);

        // Cliente
        $customerName = DataValidator::getString($orderRow, 'customer_name') ?: 'Nao informado';
        $customerPhone = DataValidator::getString($orderRow, 'customer_phone');
        $customerAddress = DataValidator::getString($orderRow, 'customer_address');
        $paymentMethod = DataValidator::getString($orderRow, 'payment_method');
        
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 4, $pdfText('CLIENTE'), 0, 1);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 4, $pdfText($customerName), 0, 1);
        
        if (!empty($customerPhone)) {
            $pdf->Cell(0, 4, $pdfText('Tel: ' . $customerPhone), 0, 1);
        }
        
        if (!empty($customerAddress)) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 4, $pdfText('ENDERECO'), 0, 1);
            $pdf->SetFont('Arial', '', 7);
            $address = str_replace("\n", ', ', $customerAddress);
            $pdf->MultiCell(0, 3, $pdfText($address), 0);
        }
        
        if (!empty($paymentMethod)) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 4, $pdfText('PAGAMENTO'), 0, 1);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(0, 4, $pdfText($paymentMethod), 0, 1);
        }
        
        $pdf->Ln(2);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(3);

        // ITENS DO PEDIDO
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 4, $pdfText('ITENS'), 0, 1);
        $pdf->Ln(1);

        foreach ($items as $idx => $it) {
            $qty = DataValidator::getInt($it, 'quantity', 'qty') ?: 1;
            $name = DataValidator::getString($it, 'product_name', 'name') ?: 'Produto';
            $price = DataValidator::getFloat($it, 'price');
            $lineTotal = DataValidator::getFloat($it, 'line_total') ?: ($price * $qty);

            // Nome do produto
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(7, 4, $pdfText($qty . 'x'), 0, 0);
            $pdf->MultiCell(0, 4, $pdfText($name), 0);
            
            // Combo
            $comboData = JsonHelper::decode($it['combo_data'] ?? null);
            
            if (!empty($comboData) && is_array($comboData)) {
                $pdf->SetFont('Arial', '', 7);
                foreach ($comboData as $group) {
                    $groupName = DataValidator::getString($group, 'name');
                    if (!empty($groupName)) {
                        $pdf->Cell(4, 3, '', 0, 0);
                        $pdf->Cell(0, 3, $pdfText('> ' . $groupName . ':'), 0, 1);
                    }                    if (!empty($group['items']) && is_array($group['items'])) {
                        foreach ($group['items'] as $option) {
                            $optionName = DataValidator::getString($option, 'name');
                            $optionQty = DataValidator::getInt($option, 'quantity') ?: 1;
                            $optionPrice = DataValidator::getFloat($option, 'price');
                            
                            $pdf->Cell(6, 3, '', 0, 0);
                            $optionText = '  ' . ($optionQty > 1 ? $optionQty . 'x ' : '') . $optionName;
                            
                            if ($optionPrice > 0) {
                                $optionText .= ' (+' . MoneyFormatter::format($optionPrice) . ')';
                            }
                            
                            $pdf->MultiCell(0, 3, $pdfText($optionText), 0);
                        }
                    }
                }
                $pdf->Ln(1);
            }
            
            // Personalização
            $customData = JsonHelper::decode($it['customization_data'] ?? null);

            if (!empty($customData) && is_array($customData)) {
                $customItemsToShow = [];

                // Formato novo: {groups:[{type, items:[]}]}
                $groups = $customData['groups'] ?? null;
                if (is_array($groups)) {
                    foreach ($groups as $group) {
                        $gType = $group['type'] ?? 'extra';
                        if (empty($group['items'])) continue;
                        foreach ($group['items'] as $ci) {
                            $ciName = $ci['name'] ?? '';
                            if (!$ciName) continue;
                            $ciQty = isset($ci['qty']) ? (int)$ci['qty'] : null;
                            $ciDelta = isset($ci['delta_qty']) ? (int)$ci['delta_qty'] : null;
                            $ciDefaultQty = isset($ci['default_qty']) ? (int)$ci['default_qty'] : null;
                            $ciPrice = (float)($ci['price'] ?? 0);
                            $ciRemoved = !empty($ci['removed']) || ($ciDefaultQty > 0 && $ciQty === 0);

                            if ($gType === 'pool') {
                                // Pool (açaí): mostrar todos com qty > 0
                                if ($ciQty > 0) {
                                    $paidQty = (int)($ci['paid_qty'] ?? 0);
                                    $unitPrice = (float)($ci['unit_price'] ?? $ciPrice);
                                    $paidPrice = $paidQty > 0 ? $paidQty * $unitPrice : 0;
                                    $customItemsToShow[] = [
                                        'text' => ($ciQty > 1 ? "{$ciQty}x " : '') . $ciName,
                                        'action' => 'add',
                                        'price' => $paidPrice
                                    ];
                                }
                            } elseif ($ciRemoved) {
                                $customItemsToShow[] = ['text' => "Sem {$ciName}", 'action' => 'remove', 'price' => 0];
                            } elseif ($ciDelta > 0) {
                                $customItemsToShow[] = ['text' => "+{$ciDelta}x {$ciName}", 'action' => 'add', 'price' => $ciPrice];
                            } elseif ($ciDelta < 0) {
                                $customItemsToShow[] = ['text' => "Sem {$ciName}", 'action' => 'remove', 'price' => 0];
                            } elseif (in_array($gType, ['single', 'addon', 'choice']) && $ciQty > 0) {
                                $customItemsToShow[] = ['text' => $ciName, 'action' => 'add', 'price' => $ciPrice];
                            } elseif ($ciPrice > 0 && $ciQty > 0) {
                                $customItemsToShow[] = ['text' => ($ciQty > 1 ? "{$ciQty}x " : '') . $ciName, 'action' => 'add', 'price' => $ciPrice];
                            }
                        }
                    }
                } else {
                    // Formato legado: array flat de [{name, action, quantity, price}]
                    foreach ($customData as $custom) {
                        if (!is_array($custom)) continue;
                        $customName = DataValidator::getString($custom, 'name');
                        $customAction = DataValidator::getString($custom, 'action') ?: 'add';
                        $customQty = DataValidator::getInt($custom, 'quantity') ?: 1;
                        $customPrice = DataValidator::getFloat($custom, 'price');
                        if ($customPrice > 0 || $customAction === 'remove') {
                            $customItemsToShow[] = [
                                'text' => ($customQty > 1 ? "{$customQty}x " : '') . $customName,
                                'action' => $customAction,
                                'price' => $customPrice
                            ];
                        }
                    }
                }

                if (!empty($customItemsToShow)) {
                    $pdf->SetFont('Arial', 'I', 7);
                    $pdf->Cell(4, 3, '', 0, 0);
                    $pdf->Cell(0, 3, $pdfText('> Personalizacao:'), 0, 1);
                    $pdf->SetFont('Arial', '', 7);

                    foreach ($customItemsToShow as $custom) {
                        $prefix = $custom['action'] === 'remove' ? '- ' : '+ ';
                        $customText = '  ' . $prefix . $custom['text'];
                        if ($custom['price'] > 0) {
                            $customText .= ' (+' . MoneyFormatter::format($custom['price']) . ')';
                        }
                        $pdf->Cell(6, 3, '', 0, 0);
                        $pdf->MultiCell(0, 3, $pdfText($customText), 0);
                    }
                    $pdf->Ln(1);
                }
            }
            
            // Observações do item
            $itemNotes = DataValidator::getString($it, 'notes');
            if (!empty($itemNotes)) {
                $pdf->SetFont('Arial', 'I', 7);
                $pdf->Cell(4, 3, '', 0, 0);
                $pdf->MultiCell(0, 3, $pdfText('> Obs: ' . $itemNotes), 0);
                $pdf->Ln(1);
            }
            
            // Valor do item
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 4, $pdfText(MoneyFormatter::format($lineTotal)), 0, 1, 'R');
            
            if ($idx < count($items) - 1) {
                $pdf->Ln(2);
            }
        }

        // TOTAIS
        $pdf->Ln(3);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(3);

        $subtotal = DataValidator::getFloat($orderRow, 'subtotal');
        $deliveryFee = DataValidator::getFloat($orderRow, 'delivery_fee');
        $discount = DataValidator::getFloat($orderRow, 'discount');
        $total = DataValidator::getFloat($orderRow, 'total');
        
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 4, $pdfText('Subtotal:'), 0, 0);
        $pdf->Cell(0, 4, $pdfText(MoneyFormatter::format($subtotal)), 0, 1, 'R');
        
        if ($deliveryFee > 0) {
            $pdf->Cell(0, 4, $pdfText('Taxa de Entrega:'), 0, 0);
            $pdf->Cell(0, 4, $pdfText(MoneyFormatter::format($deliveryFee)), 0, 1, 'R');
        }
        
        if ($discount > 0) {
            $pdf->Cell(0, 4, $pdfText('Desconto:'), 0, 0);
            $pdf->Cell(0, 4, $pdfText('- ' . MoneyFormatter::format($discount)), 0, 1, 'R');
        }
        
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, $pdfText('TOTAL:'), 0, 0);
        $pdf->Cell(0, 5, $pdfText(MoneyFormatter::format($total)), 0, 1, 'R');

        // Observações gerais
        $orderNotes = DataValidator::getString($orderRow, 'notes');
        if (!empty($orderNotes)) {
            $pdf->Ln(3);
            self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
            $pdf->Ln(3);
            
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 4, $pdfText('OBSERVACOES'), 0, 1);
            $pdf->SetFont('Arial', '', 7);
            $pdf->MultiCell(0, 3, $pdfText($orderNotes), 0);
        }

        // RODAPÉ
        $pdf->Ln(4);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(3);
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 4, $pdfText('Obrigado pela preferencia!'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(0, 3, $pdfText('Volte sempre!'), 0, 1, 'C');
        
        $pdf->Ln(3);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(8);

        // Export
        $tmp = sys_get_temp_dir() . '/receipt_' . uniqid() . '.pdf';
        $pdf->Output('F', $tmp);

        return $tmp;
    }
}
