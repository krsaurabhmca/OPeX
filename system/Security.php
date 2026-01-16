<?php
/**
 * Security Class - Handle Security Operations
 * 
 * Provides security functions: encryption, CSRF protection, XSS prevention, etc.
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

class Security
{
    private static $instance = null;
    private $encryptionKey;
    private $cipher = 'AES-256-CBC';

    private function __construct()
    {
        $this->encryptionKey = Config::get('ENCRYPTION_KEY', 'default-key-change-this-please');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Encrypt data using AES-256-CBC
     * 
     * @param mixed $data Data to encrypt
     * @return string Encrypted data
     */
    public function encrypt($data): string
    {
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            hash('sha256', $this->encryptionKey, true),
            0,
            $iv
        );

        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data
     * 
     * @param string $data Encrypted data
     * @return mixed Decrypted data
     */
    public function decrypt(string $data)
    {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            hash('sha256', $this->encryptionKey, true),
            0,
            $iv
        );

        // Try to decode JSON
        $json = json_decode($decrypted, true);
        return $json ?? $decrypted;
    }

    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool True if valid
     */
    public function verifyCSRFToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        $expire = Config::get('CSRF_TOKEN_EXPIRE', 3600);
        $tokenAge = time() - ($_SESSION['csrf_token_time'] ?? 0);

        if ($tokenAge > $expire) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get CSRF token input field
     * 
     * @return string HTML input field
     */
    public function getCSRFField(): string
    {
        $token = $this->generateCSRFToken();
        $tokenName = Config::get('CSRF_TOKEN_NAME', 'csrf_token');
        return "<input type='hidden' name='$tokenName' value='$token'>";
    }

    /**
     * Clean input data (XSS prevention)
     * 
     * @param mixed $data Data to clean
     * @param string $allowedTags Allowed HTML tags
     * @return mixed Cleaned data
     */
    public function xssClean($data, string $allowedTags = '')
    {
        if (is_array($data)) {
            return array_map(function($value) use ($allowedTags) {
                return $this->xssClean($value, $allowedTags);
            }, $data);
        }

        if (!is_string($data)) {
            return $data;
        }

        // Remove null bytes
        $data = str_replace(chr(0), '', $data);
        
        // Strip tags except allowed
        $data = strip_tags($data, $allowedTags);
        
        // Convert special characters to HTML entities
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $data;
    }

    /**
     * Clean POST/GET data
     * 
     * @param array $data Data array to clean
     * @return array Cleaned data
     */
    public function cleanInput(array $data): array
    {
        $cleaned = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $cleaned[$key] = $this->cleanInput($value);
            } else {
                // Remove special characters except alphanumeric, space, and common punctuation
                $cleaned[$key] = preg_replace('/[^A-Za-z0-9\s\-_.@,:+\/\\\]/', '', $value);
            }
        }
        return $cleaned;
    }

    /**
     * Hash password securely
     * 
     * @param string $password Plain password
     * @return string Hashed password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2
        ]);
    }

    /**
     * Verify password against hash
     * 
     * @param string $password Plain password
     * @param string $hash Password hash
     * @return bool True if matches
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate random string
     * 
     * @param int $length String length
     * @param string $type Type: alphanumeric, alpha, numeric, hex
     * @return string Random string
     */
    public function generateRandomString(int $length = 10, string $type = 'alphanumeric'): string
    {
        $charset = [
            'alphanumeric' => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'alpha' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'numeric' => '0123456789',
            'hex' => '0123456789abcdef',
            'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'lowercase' => 'abcdefghijklmnopqrstuvwxyz'
        ];

        $chars = $charset[$type] ?? $charset['alphanumeric'];
        $charLength = strlen($chars);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $chars[random_int(0, $charLength - 1)];
        }

        return $randomString;
    }

    /**
     * Sanitize filename for uploads
     * 
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Get extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        // Remove special characters
        $basename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $basename);
        $basename = preg_replace('/_+/', '_', $basename);
        $basename = trim($basename, '_');

        // Limit length
        $basename = substr($basename, 0, 50);

        // Add timestamp to prevent conflicts
        $basename .= '_' . time();

        return $basename . '.' . strtolower($extension);
    }

    /**
     * Validate file upload
     * 
     * @param array $file $_FILES array element
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Max file size in bytes
     * @return array Validation result
     */
    public function validateFileUpload(array $file, array $allowedTypes = [], int $maxSize = 0): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'status' => 'error',
                'msg' => 'File upload error: ' . $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Check file size
        if ($maxSize > 0 && $file['size'] > $maxSize) {
            return [
                'status' => 'error',
                'msg' => 'File size exceeds maximum allowed (' . $this->formatBytes($maxSize) . ')'
            ];
        }

        // Validate MIME type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                return [
                    'status' => 'error',
                    'msg' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)
                ];
            }
        }

        return [
            'status' => 'success',
            'msg' => 'File is valid'
        ];
    }

    /**
     * Get upload error message
     * 
     * @param int $code Error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $code): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        return $errors[$code] ?? 'Unknown upload error';
    }

    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Bytes
     * @return string Formatted string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}
}
