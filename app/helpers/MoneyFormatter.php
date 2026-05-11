<?php

/**
 * MoneyFormatter
 * 
 * Helper para formatação de valores monetários
 * Centraliza toda a lógica de formatação de dinheiro do sistema
 * 
 * @package App\Helpers
 */
class MoneyFormatter 
{
    /**
     * Formata valor para exibição monetária brasileira
     * 
     * @param float $value Valor a ser formatado
     * @param bool $withSymbol Se deve incluir o símbolo R$
     * @return string Valor formatado (ex: "R$ 45,90")
     */
    public static function format(float $value, bool $withSymbol = true): string 
    {
        $formatted = number_format($value, 2, ',', '.');
        return $withSymbol ? "R$ {$formatted}" : $formatted;
    }
    
    /**
     * Converte string monetária brasileira para float
     * 
     * @param string $value Valor como string (ex: "R$ 45,90" ou "45,90")
     * @return float Valor numérico
     */
    public static function parse(string $value): float 
    {
        // Remove R$, espaços e separadores de milhares
        $clean = str_replace(['R$', ' ', '.'], '', $value);
        
        // Converte vírgula decimal para ponto
        $clean = str_replace(',', '.', $clean);
        
        return (float)$clean;
    }
    
    /**
     * Formata valor sem o símbolo R$ (apenas números)
     * 
     * @param float $value Valor a ser formatado
     * @return string Valor formatado (ex: "45,90")
     */
    public static function formatWithoutSymbol(float $value): string 
    {
        return self::format($value, false);
    }
}
