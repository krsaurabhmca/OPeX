<?php
/**
 * Configuration Class
 * Handles environment variables and application configuration
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

class Config
{
    private static $instance = null;
    private static $config = [];
    private static $loaded = false;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->loadEnv();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnv(): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            // Try .env.example as fallback
            $envFile = __DIR__ . '/../.env.example';
        }

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse line
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    // Remove quotes if present
                    if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                        (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                        $value = substr($value, 1, -1);
                    }

                    // Set in environment and config array
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                    self::$config[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Get configuration value
     * 
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get(string $key, $default = null)
    {
        // Initialize if not loaded
        if (!self::$loaded) {
            self::getInstance();
        }

        // Check environment variable first
        $envValue = getenv($key);
        if ($envValue !== false) {
            return self::parseValue($envValue);
        }

        // Check $_ENV
        if (isset($_ENV[$key])) {
            return self::parseValue($_ENV[$key]);
        }

        // Check config array
        if (isset(self::$config[$key])) {
            return self::parseValue(self::$config[$key]);
        }

        return $default;
    }

    /**
     * Set configuration value at runtime
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     */
    public static function set(string $key, $value): void
    {
        self::$config[$key] = $value;
        $_ENV[$key] = $value;
        putenv("$key=" . (is_string($value) ? $value : json_encode($value)));
    }

    /**
     * Parse configuration value to appropriate type
     * 
     * @param string $value Raw value
     * @return mixed Parsed value
     */
    private static function parseValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $lower = strtolower($value);

        // Boolean values
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        
        // Null value
        if ($lower === 'null') return null;

        // Numeric values
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        return $value;
    }

    /**
     * Get all configuration
     * 
     * @return array All configuration values
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::getInstance();
        }
        return self::$config;
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if exists
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }
}
