<?php

/**
 * JsonHelper
 * 
 * Helper para operações JSON
 * Centraliza encoding/decoding com tratamento de erros
 * 
 * @package App\Helpers
 */
class JsonHelper 
{
    /**
     * Decodifica JSON com fallback para arrays
     * Aceita tanto string JSON quanto array já decodificado
     * 
     * @param mixed $data String JSON ou array
     * @param bool $assoc Se deve retornar array associativo (true) ou objeto (false)
     * @return array|object Array/objeto decodificado ou vazio em caso de erro
     */
    public static function decode($data, bool $assoc = true) 
    {
        // Se já é array, retorna direto
        if (is_array($data)) {
            return $data;
        }
        
        // Se é string, tenta decodificar
        if (is_string($data)) {
            $decoded = json_decode($data, $assoc);
            
            // Log de erro se houver
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("[JsonHelper] Decode error: " . json_last_error_msg() . " | Data: " . substr($data, 0, 100));
                return $assoc ? [] : (object)[];
            }
            
            return $decoded ?? ($assoc ? [] : (object)[]);
        }
        
        // Para outros tipos, retorna vazio
        return $assoc ? [] : (object)[];
    }
    
    /**
     * Codifica para JSON com configuração padrão
     * Usa flags para UTF-8 e URLs sem escape
     * 
     * @param mixed $data Dados a serem codificados
     * @param int|null $flags Flags adicionais do json_encode (opcional)
     * @return string JSON codificado
     */
    public static function encode($data, ?int $flags = null): string 
    {
        $defaultFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        
        if ($flags !== null) {
            $defaultFlags |= $flags;
        }
        
        $encoded = json_encode($data, $defaultFlags);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[JsonHelper] Encode error: " . json_last_error_msg());
            return '{}';
        }
        
        return $encoded;
    }
    
    /**
     * Verifica se string é JSON válido
     * 
     * @param string $data String a ser verificada
     * @return bool True se é JSON válido
     */
    public static function isValid(string $data): bool 
    {
        if (empty($data)) {
            return false;
        }
        
        json_decode($data);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Decodifica JSON de forma segura, retornando valor padrão em caso de erro
     * 
     * @param string $data String JSON
     * @param mixed $default Valor padrão se falhar
     * @return mixed Dados decodificados ou valor padrão
     */
    public static function decodeSafe(string $data, $default = []) 
    {
        if (empty($data)) {
            return $default;
        }
        
        $decoded = json_decode($data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }
        
        return $decoded ?? $default;
    }
}
