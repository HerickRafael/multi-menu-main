<?php
/**
 * Gerador de chaves VAPID para Web Push
 */

// Gerar par de chaves EC P-256 para VAPID
$config = [
    'private_key_type' => OPENSSL_KEYTYPE_EC,
    'curve_name' => 'prime256v1'
];

$key = openssl_pkey_new($config);
if (!$key) {
    echo 'Erro ao gerar chave: ' . openssl_error_string() . PHP_EOL;
    exit(1);
}

$details = openssl_pkey_get_details($key);

// Extrair chave privada (32 bytes)
$privateKey = str_pad($details['ec']['d'], 32, chr(0), STR_PAD_LEFT);

// Extrair chave pública (65 bytes: 0x04 + x + y)
$x = str_pad($details['ec']['x'], 32, chr(0), STR_PAD_LEFT);
$y = str_pad($details['ec']['y'], 32, chr(0), STR_PAD_LEFT);
$publicKey = chr(4) . $x . $y;

// Base64 URL encode
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

echo "# Chaves VAPID para Web Push Notifications\n";
echo "# Adicione ao seu arquivo .env ou config/app.php\n\n";
echo "VAPID_PUBLIC_KEY=" . base64url_encode($publicKey) . "\n";
echo "VAPID_PRIVATE_KEY=" . base64url_encode($privateKey) . "\n";
echo "VAPID_SUBJECT=mailto:admin@menuzap.orfrfranco.com.br\n";
