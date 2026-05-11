<?php

namespace App\Middleware;

use Exception;
use finfo;

/**
 * File Upload Security Middleware
 * 
 * Provides comprehensive file upload security:
 * - File type validation (MIME + extension)
 * - File size limits
 * - Malicious content detection
 * - Secure filename generation
 * - Path traversal prevention
 * - Image validation and sanitization
 * - Antivirus scanning integration
 * - Quarantine system
 * 
 * OWASP Coverage:
 * - A01: Broken Access Control
 * - A03: Injection
 * - A04: Insecure Design
 * - A05: Security Misconfiguration
 * 
 * CWE Coverage:
 * - CWE-22: Path Traversal
 * - CWE-434: Unrestricted Upload
 * - CWE-616: Incomplete Identification
 * - CWE-829: Local File Inclusion
 * 
 * @package App\Middleware
 * @author Multi-Menu Security Team
 * @version 1.0.0
 */
class FileUploadSecurity
{
    /**
     * Configuration options
     */
    private array $config;

    /**
     * Allowed MIME types by category
     */
    private const ALLOWED_MIMES = [
        'image' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv'
        ],
        'archive' => [
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/gzip'
        ],
        'video' => [
            'video/mp4',
            'video/mpeg',
            'video/quicktime',
            'video/x-msvideo',
            'video/webm'
        ],
        'audio' => [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/ogg',
            'audio/webm'
        ]
    ];

    /**
     * Dangerous file extensions
     */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
        'exe', 'bat', 'cmd', 'com', 'msi',
        'sh', 'bash', 'zsh',
        'js', 'vbs', 'vbe', 'wsf',
        'jar', 'app', 'deb', 'rpm',
        'dll', 'so', 'dylib',
        'asp', 'aspx', 'jsp', 'jspx',
        'cgi', 'pl', 'py', 'rb',
        'htaccess', 'htpasswd'
    ];

    /**
     * Image bomb detection thresholds
     */
    private const IMAGE_BOMB_LIMITS = [
        'max_pixels' => 25000000,      // 25 megapixels
        'max_width' => 10000,
        'max_height' => 10000,
        'max_filesize_ratio' => 200    // filesize vs pixel count ratio
    ];

    /**
     * Malicious patterns in file content
     */
    private const MALICIOUS_PATTERNS = [
        '/<\?php/i',
        '/<script/i',
        '/eval\s*\(/i',
        '/system\s*\(/i',
        '/exec\s*\(/i',
        '/passthru\s*\(/i',
        '/shell_exec\s*\(/i',
        '/base64_decode\s*\(/i',
        '/gzinflate\s*\(/i',
        '/str_rot13\s*\(/i',
        '/\.\.\//',                     // Path traversal
        '/\x00/',                       // Null byte
    ];

    /**
     * Statistics counters
     */
    private array $stats = [
        'uploads_validated' => 0,
        'uploads_rejected' => 0,
        'malware_detected' => 0,
        'type_mismatches' => 0,
        'size_violations' => 0,
        'image_bombs_detected' => 0,
        'path_traversal_attempts' => 0
    ];

    /**
     * Upload result
     */
    private array $uploadResult = [];

    /**
     * Constructor
     * 
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            // General
            'upload_dir' => 'uploads/',
            'quarantine_dir' => 'uploads/quarantine/',
            'allowed_categories' => ['image', 'document'],
            
            // Size limits (bytes)
            'max_file_size' => 5 * 1024 * 1024,      // 5MB
            'max_total_size' => 20 * 1024 * 1024,    // 20MB
            'max_files_per_upload' => 10,
            
            // Security
            'scan_content' => true,                   // Scan for malicious content
            'validate_image' => true,                 // Validate image integrity
            'detect_image_bombs' => true,             // Detect decompression bombs
            'sanitize_filename' => true,              // Generate safe filenames
            'randomize_filename' => true,             // Use random filenames
            'preserve_extension' => true,             // Keep original extension
            
            // Antivirus
            'antivirus_enabled' => false,             // Enable ClamAV scanning
            'antivirus_command' => 'clamscan',        // ClamAV command
            'quarantine_on_detection' => true,        // Move suspicious files
            
            // Storage
            'create_subdirs' => true,                 // Create date-based subdirs
            'subdir_format' => 'Y/m/d',              // Directory structure
            'overwrite_existing' => false,            // Allow overwrite
            
            // Permissions
            'file_permissions' => 0644,
            'dir_permissions' => 0755,
            
            // Logging
            'log_uploads' => true,
            'log_rejections' => true,
            'log_path' => 'storage/logs/uploads.log'
        ], $config);

        // Create directories if needed
        $this->ensureDirectories();
    }

    /**
     * Validate and process file upload
     * 
     * @param array $file File from $_FILES
     * @param array $options Override options
     * @return array Upload result
     * @throws Exception On validation failure
     */
    public function validateUpload(array $file, array $options = []): array
    {
        try {
            // Merge options
            $options = array_merge($this->config, $options);

            // Basic validation
            $this->validateBasics($file);
            
            // Size validation
            $this->validateSize($file);
            
            // Type validation
            $this->validateType($file, $options['allowed_categories']);
            
            // Extension validation
            $this->validateExtension($file);
            
            // Content validation
            if ($options['scan_content']) {
                $this->scanContent($file);
            }
            
            // Image-specific validation
            if ($this->isImage($file) && $options['validate_image']) {
                $this->validateImage($file);
            }
            
            // Antivirus scan
            if ($options['antivirus_enabled']) {
                $this->antivirusScan($file, $options);
            }

            $this->stats['uploads_validated']++;

            // Generate result
            $this->uploadResult = [
                'valid' => true,
                'original_name' => $file['name'],
                'size' => $file['size'],
                'mime_type' => $this->getMimeType($file['tmp_name']),
                'extension' => $this->getExtension($file['name']),
                'tmp_path' => $file['tmp_name']
            ];

            if ($this->config['log_uploads']) {
                $this->logUpload($this->uploadResult, 'validated');
            }

            return $this->uploadResult;

        } catch (Exception $e) {
            $this->stats['uploads_rejected']++;
            
            if ($this->config['log_rejections']) {
                $this->logUpload([
                    'original_name' => $file['name'] ?? 'unknown',
                    'error' => $e->getMessage()
                ], 'rejected');
            }

            throw $e;
        }
    }

    /**
     * Save validated file
     * 
     * @param array $file File from $_FILES
     * @param string|null $customName Custom filename
     * @return array Save result with path
     * @throws Exception On save failure
     */
    public function saveFile(array $file, ?string $customName = null): array
    {
        // Validate first
        $result = $this->validateUpload($file);

        // Generate filename
        $filename = $this->generateFilename(
            $customName ?? $file['name'],
            $this->config['randomize_filename']
        );

        // Generate path
        $relativePath = $this->generatePath($filename);
        $fullPath = $this->config['upload_dir'] . $relativePath;

        // Create directory
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, $this->config['dir_permissions'], true);
        }

        // Check if file exists
        if (file_exists($fullPath) && !$this->config['overwrite_existing']) {
            throw new Exception('File already exists', 409);
        }

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new Exception('Failed to save file', 500);
        }

        // Set permissions
        chmod($fullPath, $this->config['file_permissions']);

        $result['saved'] = true;
        $result['filename'] = $filename;
        $result['path'] = $relativePath;
        $result['full_path'] = $fullPath;
        $result['url'] = $this->generateUrl($relativePath);

        if ($this->config['log_uploads']) {
            $this->logUpload($result, 'saved');
        }

        return $result;
    }

    /**
     * Validate multiple files
     * 
     * @param array $files Files from $_FILES
     * @return array Results for each file
     */
    public function validateMultiple(array $files): array
    {
        $results = [];
        $totalSize = 0;

        // Check file count
        $fileCount = count($files['name'] ?? []);
        if ($fileCount > $this->config['max_files_per_upload']) {
            throw new Exception(
                "Too many files: max {$this->config['max_files_per_upload']} allowed",
                400
            );
        }

        // Process each file
        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            try {
                $result = $this->validateUpload($file);
                $totalSize += $file['size'];
                $results[] = $result;
            } catch (Exception $e) {
                $results[] = [
                    'valid' => false,
                    'original_name' => $file['name'],
                    'error' => $e->getMessage()
                ];
            }
        }

        // Check total size
        if ($totalSize > $this->config['max_total_size']) {
            throw new Exception(
                "Total size exceeds limit: " . 
                $this->formatBytes($this->config['max_total_size']),
                400
            );
        }

        return $results;
    }

    /**
     * Validate basic file properties
     * 
     * @param array $file File array
     * @throws Exception On validation failure
     */
    private function validateBasics(array $file): void
    {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('No file uploaded or invalid upload', 400);
        }

        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadError($file['error']), 400);
        }

        // Check if file exists
        if (!file_exists($file['tmp_name'])) {
            throw new Exception('Uploaded file not found', 500);
        }

        // Check if file is readable
        if (!is_readable($file['tmp_name'])) {
            throw new Exception('Uploaded file is not readable', 500);
        }
    }

    /**
     * Validate file size
     * 
     * @param array $file File array
     * @throws Exception On validation failure
     */
    private function validateSize(array $file): void
    {
        if ($file['size'] > $this->config['max_file_size']) {
            $this->stats['size_violations']++;
            throw new Exception(
                'File too large: max ' . 
                $this->formatBytes($this->config['max_file_size']) . ' allowed',
                413
            );
        }

        if ($file['size'] === 0) {
            throw new Exception('File is empty', 400);
        }
    }

    /**
     * Validate file type (MIME)
     * 
     * @param array $file File array
     * @param array $allowedCategories Allowed categories
     * @throws Exception On validation failure
     */
    private function validateType(array $file, array $allowedCategories): void
    {
        $mimeType = $this->getMimeType($file['tmp_name']);
        
        // Build allowed list
        $allowedMimes = [];
        foreach ($allowedCategories as $category) {
            if (isset(self::ALLOWED_MIMES[$category])) {
                $allowedMimes = array_merge($allowedMimes, self::ALLOWED_MIMES[$category]);
            }
        }

        if (!in_array($mimeType, $allowedMimes)) {
            $this->stats['type_mismatches']++;
            throw new Exception("File type not allowed: $mimeType", 415);
        }
    }

    /**
     * Validate file extension
     * 
     * @param array $file File array
     * @throws Exception On validation failure
     */
    private function validateExtension(array $file): void
    {
        $extension = strtolower($this->getExtension($file['name']));

        // Check dangerous extensions
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            $this->stats['uploads_rejected']++;
            throw new Exception("Dangerous file extension: .$extension", 415);
        }

        // Check double extensions (file.php.jpg)
        $parts = explode('.', $file['name']);
        if (count($parts) > 2) {
            foreach (array_slice($parts, 0, -1) as $part) {
                if (in_array(strtolower($part), self::DANGEROUS_EXTENSIONS)) {
                    $this->stats['uploads_rejected']++;
                    throw new Exception('Double extension detected', 415);
                }
            }
        }
    }

    /**
     * Scan file content for malicious patterns
     * 
     * @param array $file File array
     * @throws Exception If malicious content found
     */
    private function scanContent(array $file): void
    {
        $content = file_get_contents($file['tmp_name'], false, null, 0, 1024 * 100); // First 100KB

        foreach (self::MALICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->stats['malware_detected']++;
                $this->quarantineFile($file, "Malicious pattern: $pattern");
                throw new Exception('Malicious content detected', 403);
            }
        }

        // Check for null bytes in filename (path traversal)
        if (strpos($file['name'], "\0") !== false) {
            $this->stats['path_traversal_attempts']++;
            throw new Exception('Null byte in filename', 400);
        }

        // Check for path traversal in filename
        if (preg_match('/\.\.\/|\.\.\\\\/', $file['name'])) {
            $this->stats['path_traversal_attempts']++;
            throw new Exception('Path traversal attempt detected', 400);
        }
    }

    /**
     * Validate image file
     * 
     * @param array $file File array
     * @throws Exception On validation failure
     */
    private function validateImage(array $file): void
    {
        $imageInfo = @getimagesize($file['tmp_name']);

        if ($imageInfo === false) {
            throw new Exception('Invalid image file', 415);
        }

        [$width, $height, $type] = $imageInfo;

        // Validate image type
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($type, $allowedTypes)) {
            throw new Exception('Image type not allowed', 415);
        }

        // Detect image bombs
        if ($this->config['detect_image_bombs']) {
            $this->detectImageBomb($file, $width, $height);
        }

        // Try to re-encode image (sanitization)
        if ($this->config['validate_image']) {
            $this->sanitizeImage($file, $type);
        }
    }

    /**
     * Detect image decompression bombs
     * 
     * @param array $file File array
     * @param int $width Image width
     * @param int $height Image height
     * @throws Exception If image bomb detected
     */
    private function detectImageBomb(array $file, int $width, int $height): void
    {
        $pixels = $width * $height;
        $filesize = $file['size'];

        // Check total pixels
        if ($pixels > self::IMAGE_BOMB_LIMITS['max_pixels']) {
            $this->stats['image_bombs_detected']++;
            throw new Exception('Image too large (decompression bomb?)', 413);
        }

        // Check dimensions
        if ($width > self::IMAGE_BOMB_LIMITS['max_width'] || 
            $height > self::IMAGE_BOMB_LIMITS['max_height']) {
            $this->stats['image_bombs_detected']++;
            throw new Exception('Image dimensions exceed limits', 413);
        }

        // Check compression ratio (pixels vs filesize)
        if ($filesize > 0) {
            $ratio = $pixels / $filesize;
            if ($ratio > self::IMAGE_BOMB_LIMITS['max_filesize_ratio']) {
                $this->stats['image_bombs_detected']++;
                throw new Exception('Suspicious compression ratio', 413);
            }
        }
    }

    /**
     * Sanitize image by re-encoding
     * 
     * @param array $file File array
     * @param int $type Image type
     * @throws Exception On sanitization failure
     */
    private function sanitizeImage(array &$file, int $type): void
    {
        $image = match($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
            IMAGETYPE_PNG => @imagecreatefrompng($file['tmp_name']),
            IMAGETYPE_GIF => @imagecreatefromgif($file['tmp_name']),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file['tmp_name']),
            default => false
        };

        if ($image === false) {
            throw new Exception('Failed to process image', 415);
        }

        // Re-encode to temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'img_');
        
        $success = match($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $tempPath, 90),
            IMAGETYPE_PNG => imagepng($image, $tempPath, 9),
            IMAGETYPE_GIF => imagegif($image, $tempPath),
            IMAGETYPE_WEBP => imagewebp($image, $tempPath, 90),
            default => false
        };

        imagedestroy($image);

        if (!$success) {
            unlink($tempPath);
            throw new Exception('Failed to sanitize image', 500);
        }

        // Replace original with sanitized version
        unlink($file['tmp_name']);
        rename($tempPath, $file['tmp_name']);
    }

    /**
     * Perform antivirus scan using ClamAV
     * 
     * @param array $file File array
     * @param array $options Options
     * @throws Exception If malware detected
     */
    private function antivirusScan(array $file, array $options): void
    {
        $command = $options['antivirus_command'];
        
        // Check if ClamAV is available
        exec("which $command", $output, $returnCode);
        if ($returnCode !== 0) {
            error_log("ClamAV not available, skipping scan");
            return;
        }

        // Scan file
        $escapedPath = escapeshellarg($file['tmp_name']);
        exec("$command --no-summary $escapedPath", $output, $returnCode);

        // returnCode: 0 = clean, 1 = infected, 2 = error
        if ($returnCode === 1) {
            $this->stats['malware_detected']++;
            
            if ($options['quarantine_on_detection']) {
                $this->quarantineFile($file, 'Malware detected by ClamAV');
            }
            
            throw new Exception('Malware detected', 403);
        }

        if ($returnCode === 2) {
            error_log("ClamAV scan error for: {$file['name']}");
        }
    }

    /**
     * Move file to quarantine
     * 
     * @param array $file File array
     * @param string $reason Quarantine reason
     */
    private function quarantineFile(array $file, string $reason): void
    {
        $quarantineDir = $this->config['quarantine_dir'];
        
        if (!is_dir($quarantineDir)) {
            mkdir($quarantineDir, $this->config['dir_permissions'], true);
        }

        $timestamp = date('YmdHis');
        $safeName = $this->sanitizeFilename($file['name']);
        $quarantinePath = $quarantineDir . $timestamp . '_' . $safeName;

        copy($file['tmp_name'], $quarantinePath);
        
        // Create metadata file
        file_put_contents($quarantinePath . '.meta', json_encode([
            'original_name' => $file['name'],
            'reason' => $reason,
            'timestamp' => $timestamp,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]));

        $this->logUpload([
            'original_name' => $file['name'],
            'quarantine_path' => $quarantinePath,
            'reason' => $reason
        ], 'quarantined');
    }

    /**
     * Generate safe filename
     * 
     * @param string $originalName Original filename
     * @param bool $randomize Use random name
     * @return string Safe filename
     */
    private function generateFilename(string $originalName, bool $randomize = true): string
    {
        $extension = $this->getExtension($originalName);
        
        if ($randomize) {
            return bin2hex(random_bytes(16)) . '.' . $extension;
        }

        return $this->sanitizeFilename($originalName);
    }

    /**
     * Sanitize filename
     * 
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove path components
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove multiple dots
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $extension = $this->getExtension($filename);
            $filename = substr($filename, 0, 255 - strlen($extension) - 1) . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Generate storage path
     * 
     * @param string $filename Filename
     * @return string Relative path
     */
    private function generatePath(string $filename): string
    {
        if ($this->config['create_subdirs']) {
            $subdir = date($this->config['subdir_format']);
            return $subdir . '/' . $filename;
        }

        return $filename;
    }

    /**
     * Generate public URL
     * 
     * @param string $relativePath Relative path
     * @return string Public URL
     */
    private function generateUrl(string $relativePath): string
    {
        $baseUrl = rtrim($this->config['upload_dir'], '/');
        return '/' . $baseUrl . '/' . ltrim($relativePath, '/');
    }

    /**
     * Get MIME type
     * 
     * @param string $filepath File path
     * @return string MIME type
     */
    private function getMimeType(string $filepath): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($filepath);
    }

    /**
     * Get file extension
     * 
     * @param string $filename Filename
     * @return string Extension
     */
    private function getExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Check if file is an image
     * 
     * @param array $file File array
     * @return bool True if image
     */
    private function isImage(array $file): bool
    {
        $mimeType = $this->getMimeType($file['tmp_name']);
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Get upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadError(int $errorCode): string
    {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
            default => 'Unknown upload error'
        };
    }

    /**
     * Format bytes to human readable
     * 
     * @param int $bytes Bytes
     * @return string Formatted string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Ensure required directories exist
     */
    private function ensureDirectories(): void
    {
        $dirs = [
            $this->config['upload_dir'],
            $this->config['quarantine_dir'],
            dirname($this->config['log_path'])
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, $this->config['dir_permissions'], true);
            }
        }
    }

    /**
     * Log upload event
     * 
     * @param array $data Event data
     * @param string $event Event type
     */
    private function logUpload(array $data, string $event): void
    {
        $logEntry = sprintf(
            "[%s] %s: %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($event),
            json_encode($data)
        );

        file_put_contents(
            $this->config['log_path'],
            $logEntry,
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Get statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Get allowed MIME types
     * 
     * @param array|null $categories Categories (null = all)
     * @return array MIME types
     */
    public static function getAllowedMimes(?array $categories = null): array
    {
        if ($categories === null) {
            $mimes = [];
            foreach (self::ALLOWED_MIMES as $categoryMimes) {
                $mimes = array_merge($mimes, $categoryMimes);
            }
            return array_unique($mimes);
        }

        $mimes = [];
        foreach ($categories as $category) {
            if (isset(self::ALLOWED_MIMES[$category])) {
                $mimes = array_merge($mimes, self::ALLOWED_MIMES[$category]);
            }
        }
        return array_unique($mimes);
    }
}
