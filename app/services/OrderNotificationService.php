<?php
// app/services/OrderNotificationService.php
// Serviço para enviar notificações de pedidos para WhatsApp

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../controllers/AdminEvolutionInstanceController.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../helpers/DataValidator.php';
require_once __DIR__ . '/../helpers/JsonHelper.php';
require_once __DIR__ . '/../helpers/MoneyFormatter.php';
require_once __DIR__ . '/../helpers/ReceiptFormatter.php';
require_once __DIR__ . '/../helpers/TextParser.php';
require_once __DIR__ . '/../helpers/OrderMessageBuilder.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/WhatsAppSendService.php';

class OrderNotificationService
{
    /**
     * Envia notificação de pedido via WhatsApp usando Evolution API
     * 
     * @param int $companyId ID da empresa
     * @param array $orderData Dados do pedido
     * @return bool True se enviado com sucesso
     */
    public static function sendOrderNotification($companyId, $orderData)
    {
        try {
            error_log("=== INICIANDO ENVIO DE NOTIFICAÇÃO DE PEDIDO ===");
            error_log("Company ID: {$companyId}");
            error_log("Order ID: " . ($orderData['id'] ?? 'N/A'));
            
            $companyModel = new Company();
            $company = $companyModel->find($companyId);
            
            if (!$company) {
                error_log("ERRO: Empresa não encontrada - ID: {$companyId}");
                return false;
            }

            error_log("Empresa encontrada: " . ($company['name'] ?? 'N/A'));

            // Buscar qual instância tem notificação ATIVADA para esta empresa
            // (Confiamos que a instância está conectada - o status é verificado na UI)
            $activeNotificationConfig = self::getActiveNotificationConfig($companyId);
            
            if (!$activeNotificationConfig) {
                error_log("Nenhuma instância com notificação ativada para esta empresa");
                return false;
            }
            
            $activeInstanceName = $activeNotificationConfig['instance_name'];
            error_log("Instância com notificação ativa: " . $activeInstanceName);
            
            // Obter números para notificação da configuração ativa
            $notificationNumbers = [];
            $notificationConfig = $activeNotificationConfig['config'];
            
            // Adicionar número primário
            if (!empty($notificationConfig['primary_number'])) {
                $primaryNumber = normalizePhone($notificationConfig['primary_number']);
                if ($primaryNumber) {
                    $notificationNumbers[] = $primaryNumber;
                    error_log("Número primário configurado: {$primaryNumber}");
                }
            }
            
            // Adicionar número secundário
            if (!empty($notificationConfig['secondary_number'])) {
                $secondaryNumber = normalizePhone($notificationConfig['secondary_number']);
                if ($secondaryNumber && !in_array($secondaryNumber, $notificationNumbers)) {
                    $notificationNumbers[] = $secondaryNumber;
                    error_log("Número secundário configurado: {$secondaryNumber}");
                }
            }
            
            if (empty($notificationNumbers)) {
                error_log("ERRO: Notificação ativada mas sem números configurados na instância '{$activeInstanceName}'");
                Logger::warning("Notificação ativada sem números configurados", [
                    'company_id' => $companyId,
                    'instance' => $activeInstanceName
                ]);
                return false;
            }
            
            error_log("Total de números para notificar: " . count($notificationNumbers) . " - Números: " . implode(', ', $notificationNumbers));

            // Gerar mensagem
            $message = self::generateStandardOrderMessage($orderData, $company);
            error_log("Tamanho da mensagem: " . strlen($message) . " caracteres");

            $success = false;

            // Enviar apenas pela instância que tem notificação ativa
            $instanceName = $activeInstanceName;
            error_log("Enviando via instância: {$instanceName}");

            // Enviar em PARALELO para todos os números usando curl_multi
            $success = self::sendMessagesInParallel($company, $instanceName, $notificationNumbers, $message);

            error_log("=== FIM DO ENVIO DE NOTIFICAÇÃO - Sucesso: " . ($success ? 'SIM' : 'NÃO') . " ===");
            return $success;

        } catch (Exception $e) {
            error_log("EXCEÇÃO ao enviar notificação: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Envia mensagens em paralelo usando curl_multi
     * Retorna true se pelo menos uma mensagem foi enviada com sucesso
     */
    private static function sendMessagesInParallel($company, $instanceName, array $numbers, $message): bool
    {
        $companyId = (int) ($company['id'] ?? 0);
        if (!$companyId) {
            error_log("ERRO: company_id ausente para sendMessagesInParallel");
            return false;
        }

        $sendService = WhatsAppSendService::getInstance();
        $success = false;

        error_log("Enviando para " . count($numbers) . " número(s) via WhatsAppSendService");

        foreach ($numbers as $number) {
            $result = $sendService->sendOnce(
                $companyId,
                $instanceName,
                $number,
                $message,
                'notification',
                ['timeout' => 10]
            );

            if ($result['success']) {
                $success = true;
                error_log("✓ Mensagem enviada para {$number} (msgId={$result['message_id']})");
            } else {
                error_log("✗ Falha para {$number}: {$result['error']}");
            }
        }

        return $success;
    }

    /**
     * Enviar mensagem via API Evolution
     */
    private static function sendMessageViaAPI($company, $instanceName, $number, $message)
    {
        $companyId = (int) ($company['id'] ?? 0);
        if (!$companyId) {
            error_log("ERRO: company_id ausente para sendMessageViaAPI");
            return false;
        }

        $result = WhatsAppSendService::getInstance()->sendOnce(
            $companyId,
            $instanceName,
            $number,
            $message,
            'notification',
            ['timeout' => 8]
        );

        return $result['success'];
    }

    /**
     * Verifica o estado REAL de uma instância usando /instance/connectionState
     * Retorna o estado normalizado: 'open', 'close', 'connecting' ou null
     */
    private static function getInstanceRealState($company, string $instanceName): ?string
    {
        try {
            $server = rtrim($company['evolution_server_url'] ?? '', '/');
            $apiKey = $company['evolution_api_key'] ?? null;
            
            if (!$server || !$apiKey) {
                return null;
            }
            
            $url = $server . '/instance/connectionState/' . urlencode($instanceName);
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'apikey: ' . $apiKey,
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log("getInstanceRealState: HTTP {$httpCode} para instância {$instanceName}");
                return null;
            }
            
            $data = json_decode($response, true);
            
            // Log para debug
            error_log("getInstanceRealState response for '$instanceName': " . json_encode($data));
            
            // Tentar múltiplos caminhos possíveis na resposta
            // Formato 1: data.instance.state
            if (isset($data['instance']['state'])) {
                return $data['instance']['state'];
            }
            // Formato 2: data.state (resposta direta)
            if (isset($data['state'])) {
                return $data['state'];
            }
            // Formato 3: Apenas verificar se está connected
            if (isset($data['connected']) && $data['connected'] === true) {
                return 'open';
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("EXCEÇÃO em getInstanceRealState: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica se uma instância está realmente conectada (estado = 'open')
     */
    private static function isInstanceConnected($company, string $instanceName): bool
    {
        $state = self::getInstanceRealState($company, $instanceName);
        return $state === 'open';
    }

    /**
     * Busca instâncias diretamente da API Evolution
     * 
     * @param array $company Dados da empresa com evolution_server_url e evolution_api_key
     * @return array|false Array de instâncias ou false em caso de erro
     */
    private static function fetchInstancesFromApi($company)
    {
        try {
            if (empty($company['evolution_server_url']) || empty($company['evolution_api_key'])) {
                error_log("ERRO: Credenciais Evolution não configuradas (server_url ou api_key)");
                return false;
            }

            $server = rtrim($company['evolution_server_url'], '/');
            $apiKey = $company['evolution_api_key'];
            $url = $server . '/instance/fetchInstances';
            
            error_log("Consultando API Evolution: {$url}");

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json',
                'apikey: ' . $apiKey
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                error_log("ERRO cURL ao buscar instâncias: {$curlError}");
                return false;
            }

            error_log("API Evolution respondeu com HTTP {$httpCode}");
            error_log("Resposta da API: " . substr($response, 0, 500));

            if ($httpCode < 200 || $httpCode >= 300) {
                error_log("ERRO: API retornou HTTP {$httpCode}");
                return false;
            }

            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("ERRO: Resposta não é JSON válido - " . json_last_error_msg());
                return false;
            }

            // Normalizar formato da resposta (pode vir como array direto ou dentro de 'data')
            if (isset($data['instances']) && is_array($data['instances'])) {
                return $data['instances'];
            } elseif (isset($data['data']['instances']) && is_array($data['data']['instances'])) {
                return $data['data']['instances'];
            } elseif (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
            } elseif (is_array($data)) {
                return $data;
            }

            error_log("ERRO: Formato de resposta não reconhecido");
            return false;

        } catch (Exception $e) {
            error_log("EXCEÇÃO em fetchInstancesFromApi: " . $e->getMessage());
            return false;
        }
    }

    private static function generateStandardOrderMessage($orderData, $company)
    {
        $companyName = strtoupper(DataValidator::getString($company, 'name') ?: 'RESTAURANTE');
        $orderId = DataValidator::getString($orderData, 'id') ?: 'N/A';
        $orderNumber = DataValidator::getString($orderData, 'order_number') ?: $orderId;
        $clientName = DataValidator::getString($orderData, 'cliente_nome', 'customer_name') ?: 'Cliente não informado';
        $customerPhone = DataValidator::getString($orderData, 'customer_phone');
        $customerAddress = DataValidator::getString($orderData, 'customer_address');
        $total = DataValidator::getFloat($orderData, 'total');
        $subtotal = DataValidator::getFloat($orderData, 'subtotal') ?: $total;
        $deliveryFee = DataValidator::getFloat($orderData, 'delivery_fee');
        $loyaltyDiscount = DataValidator::getFloat($orderData, 'loyalty_discount');
        $discount = DataValidator::getFloat($orderData, 'discount');
        $items = DataValidator::getArray($orderData, 'itens', 'items');
        $paymentMethod = DataValidator::getString($orderData, 'forma_pagamento', 'payment_method', 'pagamento') ?: 'Não informado';
        $notes = DataValidator::getString($orderData, 'notes', 'observacoes');
        
        // Calcular desconto de fidelidade se não vier nos dados
        if ($loyaltyDiscount <= 0) {
            $loyaltyDiscount = OrderMessageBuilder::calculateLoyaltyDiscount($company, $items);
            if ($loyaltyDiscount > 0) {
                $total = $subtotal + $deliveryFee - $loyaltyDiscount - $discount;
            }
        }
        
        $sep = "- - - - - - - - - - - - - - - -";
        
        // Cabeçalho
        $message = "`{$companyName}`\n";
        if (DataValidator::hasValue($company, 'whatsapp')) {
            $message .= "Tel: {$company['whatsapp']}\n";
        }
        $message .= "\n{$sep}\n\n";
        
        // Pedido
        $message .= "`PEDIDO #{$orderNumber}`\n";
        $message .= date('d/m/Y H:i') . "\n";
        $message .= "\n{$sep}\n\n";
        
        // Cliente
        $message .= "`CLIENTE`\n";
        $message .= "{$clientName}\n";
        if (!empty($customerPhone)) {
            $message .= "Tel: {$customerPhone}\n";
        }
        
        // Endereço
        if (!empty($customerAddress)) {
            $addressOneLine = str_replace("\n", ', ', $customerAddress);
            // Para o Maps: remove bairro e referência, mantém rua+número+cidade
            $addressForMaps = $addressOneLine;
            $addressForMaps = preg_replace('/,\s*Referência:.*$/i', '', $addressForMaps);
            // Remove bairro (padrão: "Bairro - Cidade" → "Cidade")
            $addressForMaps = preg_replace('/,\s*[^,]+\s*-\s*([^,]+)$/', ', $1', $addressForMaps);
            $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode(trim($addressForMaps));
            $message .= "\n`ENDEREÇO`\n";
            $message .= $addressOneLine . "\n";
            $message .= $mapsUrl . "\n";
        }
        
        // Pagamento
        $paymentType = DataValidator::getString($orderData, 'payment_type');
        if ($paymentType === 'pix') {
            $message .= "\n`PAGAMENTO`\n{$paymentMethod} - Mandar comprovante após pagamento\n";
        } else {
            $message .= "\n`PAGAMENTO`\n{$paymentMethod}\n";
        }
        
        // Troco (para pagamento em dinheiro)
        $cashAmountNotif = DataValidator::getFloat($orderData, 'cash_amount');
        $cashChangeNotif = DataValidator::getFloat($orderData, 'cash_change');
        if ($cashAmountNotif > 0.009) {
            $message .= "💰 Troco para: " . MoneyFormatter::format($cashAmountNotif);
            if ($cashChangeNotif > 0.009) {
                $message .= " (Troco: " . MoneyFormatter::format($cashChangeNotif) . ")";
            } else {
                $message .= " (pagamento exato)";
            }
            $message .= "\n";
        }
        
        $message .= "\n{$sep}\n\n";
        
        // Itens
        $message .= "`ITENS`\n\n";
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $quantity = DataValidator::getInt($item, 'quantidade', 'quantity') ?: 1;
                $name = DataValidator::getString($item, 'nome', 'name') ?: 'Item';
                $price = DataValidator::getFloat($item, 'preco', 'price');
                
                // Verificar se é combo
                $combo = DataValidator::getString($item, 'combo');
                $comboData = null;
                $componentSwapExtra = 0.0;
                
                if (!empty($combo)) {
                    $comboData = @json_decode($combo, true);
                    if (is_array($comboData) && isset($comboData['component_swap_extra'])) {
                        $componentSwapExtra = (float)$comboData['component_swap_extra'];
                    }
                }
                
                // Preço base do item (sem extras de personalização e troca de componente)
                $customizationDelta = DataValidator::getFloat($item, 'customization_delta');
                $basePrice = $price - $componentSwapExtra - $customizationDelta;
                $itemSubtotal = $basePrice * $quantity;
                
                // Linha principal do item com backticks
                $message .= "`{$quantity}x {$name}` — " . MoneyFormatter::format($itemSubtotal) . "\n";
                
                // Processar combo (se houver)
                if (!empty($combo) && is_array($comboData) && isset($comboData['groups'])) {
                    $componentCustomizations = $item['component_customizations'] ?? [];
                    $comboLines = OrderMessageBuilder::processComboItemsFormatted($comboData, $componentCustomizations);
                    $message .= $comboLines;
                }
                
                // Processar personalizações (se houver)
                $customization = DataValidator::getString($item, 'personalizacao', 'customization');
                if (!empty($customization)) {
                    $customLines = OrderMessageBuilder::processSimpleCustomizationFormatted($customization);
                    $message .= $customLines;
                } elseif (!empty($item['customization_data']['groups'])) {
                    // Formato estruturado (pedidos manuais do admin)
                    $custLines = OrderMessageBuilder::processCustomizationGroups($item['customization_data']['groups']);
                    $message .= OrderMessageBuilder::formatLines($custLines);
                }
                
                // Observações do item
                $itemNotes = DataValidator::getString($item, 'notes');
                if (!empty($itemNotes)) {
                    $message .= "  _Obs: {$itemNotes}_\n";
                }
                
                $message .= "\n";
            }
        }
        
        // Totais
        $message .= "{$sep}\n\n";
        $message .= "Subtotal: " . MoneyFormatter::format($subtotal) . "\n";
        $message .= "Taxa Entrega: " . MoneyFormatter::format($deliveryFee) . "\n";
        
        if ($loyaltyDiscount > 0) {
            $message .= "Desconto Fidelidade: -" . MoneyFormatter::format($loyaltyDiscount) . "\n";
        }
        if ($discount > 0) {
            $message .= "Desconto: -" . MoneyFormatter::format($discount) . "\n";
        }
        
        $message .= "\n`TOTAL: " . MoneyFormatter::format($total) . "`\n";
        
        // Observações gerais (apenas texto digitado pelo cliente, sem linhas de sistema)
        $userNotes = $notes;
        $userNotes = preg_replace('/^Pagamento:[^\n]*/mi', '', $userNotes);
        $userNotes = preg_replace('/^Troco para:[^\n]*/mi', '', $userNotes);
        $userNotes = preg_replace('/^Observa[çc][õo]es:\s*/mi', '', $userNotes);
        $userNotes = trim(preg_replace('/\n{2,}/', "\n\n", $userNotes));
        if ($userNotes !== '') {
            $message .= "\n{$sep}\n\n";
            $message .= "`OBSERVAÇÕES`\n{$userNotes}\n";
        }
        
        // Rodapé
        $message .= "\n{$sep}\n\n";
        $message .= "`Novo pedido recebido!`\nPreparar o quanto antes.";
        
        return $message;
    }

    private static function formatCustomMessage($template, $orderData, $company)
    {
        $variables = [
            '{company_name}' => $company['name'] ?? 'Restaurante',
            '{order_id}' => $orderData['id'] ?? 'N/A',
            '{customer_name}' => $orderData['cliente_nome'] ?? $orderData['customer_name'] ?? 'Cliente',
            '{total}' => MoneyFormatter::format($orderData['total'] ?? 0),
            '{items}' => self::formatOrderItems($orderData['itens'] ?? $orderData['items'] ?? []),
            '{payment_method}' => $orderData['forma_pagamento'] ?? $orderData['payment_method'] ?? 'Não informado',
            '{datetime}' => date('d/m/Y H:i')
        ];
        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    private static function formatOrderItems($items)
    {
        if (empty($items)) {
            return 'Nenhum item';
        }
        $formatted = [];
        foreach ($items as $item) {
            $quantity = DataValidator::getInt($item, 'quantidade', 'quantity') ?: 1;
            $name = DataValidator::getString($item, 'nome', 'name') ?: 'Item';
            $price = DataValidator::getFloat($item, 'preco', 'price');
            $itemTotal = $price * $quantity;
            $formatted[] = "• {$quantity}x {$name} - " . MoneyFormatter::format($itemTotal);
        }
        return implode("\n", $formatted);
    }

    /**
     * Busca a configuração de notificação de pedidos da instância
     * 
     * @param int $companyId ID da empresa
     * @param string $instanceName Nome da instância
     * @return array Configuração de notificação
     */
    private static function getNotificationConfig($companyId, $instanceName)
    {
        try {
            require_once __DIR__ . '/../config/db.php';
            $pdo = db();
            
            $sql = "SELECT config_value FROM instance_configs 
                    WHERE company_id = ? AND instance_name = ? AND config_key = 'order_notification'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$companyId, $instanceName]);
            
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($row) {
                return json_decode($row['config_value'], true) ?: [];
            }
            
            return [];
        } catch (Exception $e) {
            error_log("Erro ao buscar configuração de notificação: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca a instância que tem notificação ATIVADA para a empresa
     * Retorna null se nenhuma instância tiver notificação ativada
     */
    private static function getActiveNotificationConfig($companyId)
    {
        try {
            require_once __DIR__ . '/../config/db.php';
            $pdo = db();
            
            $sql = "SELECT instance_name, config_value FROM instance_configs 
                    WHERE company_id = ? AND config_key = 'order_notification'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$companyId]);
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $config = json_decode($row['config_value'], true);
                
                // Verificar se esta instância tem notificação ATIVADA
                if (!empty($config['enabled'])) {
                    return [
                        'instance_name' => $row['instance_name'],
                        'config' => $config
                    ];
                }
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Erro ao buscar instância com notificação ativa: " . $e->getMessage());
            return null;
        }
    }
}
