<?php

/**
 * TextParser
 * 
 * Helper para parsing de strings complexas
 * Extrai informações como preços, quantidades e divide itens
 * 
 * @package App\Helpers
 */
class TextParser 
{
    /**
     * Extrai preço do final de uma string
     * 
     * @param string $text Texto contendo preço (ex: "Queijo (+ R$ 3,50)")
     * @return array ['price' => 3.50, 'text' => 'Queijo']
     */
    public static function extractPrice(string $text): array 
    {
        $price = 0.0;
        $cleanText = $text;
        
        if (preg_match(FormatConstants::REGEX_PRICE_EXTRACTION, $text, $match)) {
            $price = floatval(str_replace(',', '.', $match[1]));
            $cleanText = trim(preg_replace(FormatConstants::REGEX_PRICE_EXTRACTION, '', $text));
        }
        
        return [
            'price' => $price, 
            'text' => $cleanText
        ];
    }
    
    /**
     * Extrai quantidade do início de uma string
     * 
     * @param string $text Texto com quantidade (ex: "+2x Bacon" ou "-1x Cebola")
     * @return array ['qty' => 2, 'text' => 'Bacon', 'prefix' => '+']
     */
    public static function extractQuantity(string $text): array 
    {
        $qty = 1;
        $cleanText = $text;
        $prefix = '';
        
        if (preg_match(FormatConstants::REGEX_QUANTITY_EXTRACTION, $text, $match)) {
            $prefix = $match[1] ?? '';
            $qty = (int)$match[2];
            $cleanText = $match[3];
        }
        
        return [
            'qty' => $qty, 
            'text' => $cleanText, 
            'prefix' => $prefix
        ];
    }
    
    /**
     * Separa itens por vírgula (sem quebrar preços decimais)
     * 
     * Exemplo:
     * "Queijo, Bacon, 2x Cebola" => ['Queijo', 'Bacon', '2x Cebola']
     * "Queijo (+ R$ 3,50), Bacon" => ['Queijo (+ R$ 3,50)', 'Bacon']
     * 
     * @param string $text Texto com múltiplos itens
     * @param bool $includeModifiers Se deve considerar modificadores (Sem, +, -)
     * @return array Lista de itens separados
     */
    public static function splitItems(string $text, bool $includeModifiers = false): array 
    {
        if (empty(trim($text))) {
            return [];
        }
        
        $pattern = $includeModifiers 
            ? FormatConstants::REGEX_SPLIT_ITEMS_WITH_MODIFIERS
            : FormatConstants::REGEX_SPLIT_ITEMS;
            
        $items = preg_split($pattern, $text);
        
        // Remove itens vazios
        return array_filter(array_map('trim', $items));
    }
    
    /**
     * Extrai tanto preço quanto quantidade de uma string
     * 
     * @param string $text Texto completo (ex: "+2x Queijo (+ R$ 3,50)")
     * @return array ['qty' => 2, 'price' => 3.50, 'text' => 'Queijo', 'prefix' => '+']
     */
    public static function extractAll(string $text): array 
    {
        // Primeiro extrai o preço
        $priceData = self::extractPrice($text);
        
        // Depois extrai a quantidade do texto sem preço
        $qtyData = self::extractQuantity($priceData['text']);
        
        return [
            'qty' => $qtyData['qty'],
            'price' => $priceData['price'],
            'text' => $qtyData['text'],
            'prefix' => $qtyData['prefix']
        ];
    }
    
    /**
     * Remove emojis de uma string
     * Útil para mensagens que vão para PDF ou sistemas que não suportam emoji
     * 
     * @param string $text Texto com possíveis emojis
     * @return string Texto sem emojis
     */
    public static function removeEmojis(string $text): string 
    {
        return preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $text);
    }
}
