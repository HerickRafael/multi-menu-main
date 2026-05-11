<?php
/**
 * XSS Protection Helper Functions
 * 
 * Funções auxiliares globais para proteção contra XSS
 * Nota: função e() já definida em app/core/CommonHelpers.php
 */

use App\Middleware\XssProtection;

/**
 * Função global helper para sanitização
 * 
 * @param string $input Input a sanitizar
 * @return string Input sanitizado
 */
if (!function_exists('sanitize')) {
    function sanitize(string $input): string
    {
        return XssProtection::sanitize($input);
    }
}
