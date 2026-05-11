<?php

/**
 * DataValidator
 * 
 * Helper para validação e extração segura de dados
 * Previne erros de undefined index e type errors
 * 
 * @package App\Helpers
 */
class DataValidator 
{
    /**
     * Verifica se chave existe e tem valor não vazio
     * 
     * @param array|object $data Array ou objeto com dados
     * @param string $key Nome da chave
     * @return bool True se existe e não está vazio
     */
    public static function hasValue($data, string $key): bool 
    {
        if (is_array($data)) {
            return isset($data[$key]) && !empty($data[$key]);
        }
        
        if (is_object($data)) {
            return isset($data->$key) && !empty($data->$key);
        }
        
        return false;
    }
    
    /**
     * Obtém valor float com fallback para múltiplas chaves
     * Útil quando o campo pode ter nomes diferentes (preco/price)
     * 
     * @param array|object $data Dados fonte
     * @param string ...$keys Chaves a tentar (em ordem de prioridade)
     * @return float Valor encontrado ou 0.0
     */
    public static function getFloat($data, string ...$keys): float 
    {
        foreach ($keys as $key) {
            if (is_array($data) && isset($data[$key])) {
                return (float)$data[$key];
            }
            if (is_object($data) && isset($data->$key)) {
                return (float)$data->$key;
            }
        }
        return 0.0;
    }
    
    /**
     * Obtém valor string com fallback
     * 
     * @param array|object $data Dados fonte
     * @param string ...$keys Chaves a tentar
     * @return string Valor encontrado ou string vazia
     */
    public static function getString($data, string ...$keys): string 
    {
        foreach ($keys as $key) {
            if (is_array($data) && isset($data[$key]) && !empty($data[$key])) {
                return (string)$data[$key];
            }
            if (is_object($data) && isset($data->$key) && !empty($data->$key)) {
                return (string)$data->$key;
            }
        }
        return '';
    }
    
    /**
     * Obtém valor int com fallback
     * 
     * @param array|object $data Dados fonte
     * @param string ...$keys Chaves a tentar
     * @return int Valor encontrado ou 0
     */
    public static function getInt($data, string ...$keys): int 
    {
        foreach ($keys as $key) {
            if (is_array($data) && isset($data[$key])) {
                return (int)$data[$key];
            }
            if (is_object($data) && isset($data->$key)) {
                return (int)$data->$key;
            }
        }
        return 0;
    }
    
    /**
     * Obtém array com fallback
     * 
     * @param array|object $data Dados fonte
     * @param string ...$keys Chaves a tentar
     * @return array Valor encontrado ou array vazio
     */
    public static function getArray($data, string ...$keys): array 
    {
        foreach ($keys as $key) {
            if (is_array($data) && isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
            if (is_object($data) && isset($data->$key) && is_array($data->$key)) {
                return $data->$key;
            }
        }
        return [];
    }
    
    /**
     * Obtém valor bool com fallback
     * 
     * @param array|object $data Dados fonte
     * @param string ...$keys Chaves a tentar
     * @return bool Valor encontrado ou false
     */
    public static function getBool($data, string ...$keys): bool 
    {
        foreach ($keys as $key) {
            if (is_array($data) && isset($data[$key])) {
                return (bool)$data[$key];
            }
            if (is_object($data) && isset($data->$key)) {
                return (bool)$data->$key;
            }
        }
        return false;
    }
}
