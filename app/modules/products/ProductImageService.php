<?php

declare(strict_types=1);

class ProductImageService
{
    /**
     * Faz upload de imagem de produto e retorna o caminho relativo.
     *
     * @return array{path: string|null, error: string|null}
     */
    public static function upload(?array $file): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $error = 'Erro no upload (codigo ' . ($file['error'] ?? 'desconhecido') . ')';
            error_log($error . ' para ' . ($file['tmp_name'] ?? 'temp'));
            return ['path' => null, 'error' => $error];
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return ['path' => null, 'error' => 'Formato de arquivo invalido. Use JPG, PNG ou WEBP.'];
        }

        $name = 'p_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $dest = __DIR__ . '/../../../public/uploads/' . $name;
        $dir = dirname($dest);

        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            $error = 'Falha ao criar diretorio de upload';
            error_log($error . ': ' . $dir);
            return ['path' => null, 'error' => $error];
        }

        if (!is_writable($dir)) {
            $error = 'Diretorio de upload nao gravavel';
            error_log($error . ': ' . $dir);
            return ['path' => null, 'error' => $error];
        }

        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            $error = 'Arquivo temporario inexistente';
            error_log($error . ': ' . ($file['tmp_name'] ?? ''));
            return ['path' => null, 'error' => $error];
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $error = 'Falha ao mover arquivo para uploads';
            error_log($error . ': ' . $dest);
            return ['path' => null, 'error' => $error];
        }

        return ['path' => 'uploads/' . $name, 'error' => null];
    }
}
