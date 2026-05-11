<?php

/**
 * FormatConstants
 * 
 * Centraliza todas as constantes de formatação do sistema
 * Elimina "números mágicos" espalhados pelo código
 * 
 * @package App\Config
 */
class FormatConstants 
{
    // ========================================
    // MENSAGEM WHATSAPP / NOTIFICAÇÕES
    // ========================================
    
    /**
     * Largura padrão de linhas em mensagens de texto (WhatsApp)
     * 58 caracteres para melhor alinhamento de valores
     */
    public const MESSAGE_WIDTH = 58;
    
    /**
     * Linha separadora para mensagens de texto
     */
    public const MESSAGE_SEPARATOR = "- - - - - - - - - - - - - - - -";
    
    /**
     * Indentação padrão para subitens
     */
    public const MESSAGE_INDENT = "  ";
    
    // ========================================
    // FORMATAÇÃO MONETÁRIA
    // ========================================
    
    /**
     * Símbolo da moeda brasileira
     */
    public const CURRENCY_SYMBOL = "R$ ";
    
    /**
     * Separador decimal brasileiro
     */
    public const DECIMAL_SEPARATOR = ",";
    
    /**
     * Separador de milhares brasileiro
     */
    public const THOUSANDS_SEPARATOR = ".";
    
    /**
     * Casas decimais para valores monetários
     */
    public const DECIMAL_PLACES = 2;
    
    // ========================================
    // PDF TÉRMICO 58mm
    // ========================================
    
    /**
     * Largura do papel térmico em mm
     */
    public const THERMAL_WIDTH = 58;
    
    /**
     * Margem lateral do PDF térmico
     */
    public const THERMAL_MARGIN = 2;
    
    /**
     * Fonte padrão para PDF térmico
     */
    public const THERMAL_FONT = 'Arial';
    
    /**
     * Tamanho de fonte padrão
     */
    public const THERMAL_FONT_SIZE = 8;
    
    /**
     * Tamanho de fonte para títulos
     */
    public const THERMAL_FONT_SIZE_TITLE = 10;
    
    /**
     * Tamanho de fonte para totais
     */
    public const THERMAL_FONT_SIZE_TOTAL = 9;
    
    // ========================================
    // STATUS DE PEDIDOS
    // ========================================
    
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_CONFIRMADO = 'confirmado';
    public const STATUS_PREPARANDO = 'preparando';
    public const STATUS_PRONTO = 'pronto';
    public const STATUS_ENVIADO = 'enviado';
    public const STATUS_ENTREGUE = 'entregue';
    public const STATUS_CANCELADO = 'cancelado';
    
    // ========================================
    // MÉTODOS DE PAGAMENTO
    // ========================================
    
    public const PAYMENT_DINHEIRO = 'dinheiro';
    public const PAYMENT_CARTAO_CREDITO = 'cartao_credito';
    public const PAYMENT_CARTAO_DEBITO = 'cartao_debito';
    public const PAYMENT_PIX = 'pix';
    
    // ========================================
    // REGEX PATTERNS
    // ========================================
    
    /**
     * Pattern para extrair preço do final de string
     * Ex: "Queijo (+ R$ 3,50)" => captura "3,50"
     */
    public const REGEX_PRICE_EXTRACTION = '/\(\+\s*R\$\s*([\d,\.]+)\)\s*$/';
    
    /**
     * Pattern para extrair quantidade do início
     * Ex: "+2x Bacon" => captura "+", "2", "Bacon"
     */
    public const REGEX_QUANTITY_EXTRACTION = '/^([+\-])?(\d+)x\s+(.+)$/';
    
    /**
     * Pattern para dividir itens por vírgula (sem quebrar decimais)
     */
    public const REGEX_SPLIT_ITEMS = '/\n|,\s+(?=\d|[A-Z])/i';
    
    /**
     * Pattern para dividir itens incluindo modificadores (Sem, +, -)
     */
    public const REGEX_SPLIT_ITEMS_WITH_MODIFIERS = '/\n|,\s+(?=\d|[A-Z]|Sem|[\+\-])/i';
}
