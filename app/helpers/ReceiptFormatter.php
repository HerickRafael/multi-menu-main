<?php

/**
 * ReceiptFormatter
 * 
 * Helper para formatação de mensagens de recibo (WhatsApp/SMS)
 * Lida com alinhamento, truncamento e formatação de linhas
 * 
 * @package App\Helpers
 */
class ReceiptFormatter 
{
    /**
     * Alinha texto à direita com valor
     * Garante que o valor fique sempre ao final da linha de 32 caracteres
     * 
     * @param string $label Texto/label do lado esquerdo
     * @param string $value Valor a ser alinhado à direita
     * @return string Linha formatada com quebra de linha
     */
    public static function alignRight(string $label, string $value): string 
    {
        $availableSpace = FormatConstants::MESSAGE_WIDTH - strlen($value);
        
        // Se o label for muito longo, trunca
        if (strlen($label) >= $availableSpace) {
            $label = substr($label, 0, $availableSpace - 1);
        }
        
        return str_pad($label, $availableSpace, ' ') . $value . "\n";
    }
    
    /**
     * Formata linha com valor monetário
     * Combina alinhamento com formatação de dinheiro
     * 
     * @param string $label Texto do lado esquerdo
     * @param float $amount Valor monetário
     * @return string Linha formatada (ex: "Subtotal:              R$ 45,90\n")
     */
    public static function formatMoneyLine(string $label, float $amount): string 
    {
        return self::alignRight($label, MoneyFormatter::format($amount));
    }
    
    /**
     * Retorna linha separadora padrão
     * 
     * @return string Linha separadora com quebra de linha
     */
    public static function separator(): string 
    {
        return FormatConstants::MESSAGE_SEPARATOR . "\n";
    }
    
    /**
     * Indenta texto com espaços
     * 
     * @param string $text Texto a ser indentado
     * @param int $level Nível de indentação (1 = 2 espaços, 2 = 4 espaços...)
     * @return string Texto indentado
     */
    public static function indent(string $text, int $level = 1): string 
    {
        $indent = str_repeat(FormatConstants::MESSAGE_INDENT, $level);
        return $indent . $text;
    }
    
    /**
     * Trunca texto para caber na largura especificada
     * Adiciona "..." se necessário truncar
     * 
     * @param string $text Texto a ser truncado
     * @param int|null $maxLength Largura máxima (padrão: MESSAGE_WIDTH)
     * @return string Texto truncado se necessário
     */
    public static function truncate(string $text, ?int $maxLength = null): string 
    {
        $maxLength = $maxLength ?? FormatConstants::MESSAGE_WIDTH;
        
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        
        return substr($text, 0, $maxLength - 3) . '...';
    }
    
    /**
     * Trunca texto reservando espaço para um valor à direita
     * Usado quando precisa garantir espaço para preço/quantidade
     * 
     * @param string $text Texto a ser truncado
     * @param string $value Valor que ficará à direita
     * @return string Texto truncado considerando o valor
     */
    public static function truncateWithValue(string $text, string $value): string 
    {
        $valueLength = strlen($value);
        $maxTextLength = FormatConstants::MESSAGE_WIDTH - $valueLength - 1; // -1 para espaço
        
        if (strlen($text) <= $maxTextLength) {
            return $text;
        }
        
        return substr($text, 0, $maxTextLength - 3) . '...';
    }
    
    /**
     * Formata linha de item com nome e valor
     * Trunca o nome se necessário para caber o valor
     * 
     * @param string $itemName Nome do item
     * @param string $value Valor formatado (ex: "R$ 10,00" ou "+2x")
     * @return string Linha formatada
     */
    public static function formatItemLine(string $itemName, string $value): string 
    {
        $truncatedName = self::truncateWithValue($itemName, $value);
        return self::alignRight($truncatedName, $value);
    }
}
