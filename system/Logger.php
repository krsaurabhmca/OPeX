<?php
/**
 * Logger Class - Application Logging
 * 
 * Provides comprehensive logging functionality
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

class Logger
{
    private static $instance = null;
    private $logPath;
    private $logLevel;
    private $levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'NOTICE' => 2,
        'WARNING' => 3,
        'ERROR' => 4,
        'CRITICAL' => 5,
        'ALERT' => 6,
        'EMERGENCY' => 7
    ];

    private function __construct()
    {
        $this->logPath = Config::get('LOG_PATH', __DIR__ . '/logs/');
        $this->logLevel = strtoupper(Config::get('LOG_LEVEL', 'INFO'));
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Log a message
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        // Check if we should log this level
        $currentLevelValue = $this->levels[$this->logLevel] ?? 1;
        $messageLevelValue = $this->levels[$level] ?? 1;
        
        if ($messageLevelValue < $currentLevelValue) {
            return;
        }

        // Format message
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message$contextString" . PHP_EOL;

        // Determine log file
        $channel = Config::get('LOG_CHANNEL', 'daily');
        $filename = $channel === 'daily' 
            ? 'app-' . date('Y-m-d') . '.log'
            : 'app.log';

        // Write to file
        file_put_contents(
            $this->logPath . $filename,
            $logMessage,
            FILE_APPEND | LOCK_EX
        );

        // Also log errors to database for critical issues
        if (in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'])) {
            $this->logToDatabase($level, $message, $context);
        }
    }

    /**
     * Log to database (op_log table)
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     */
    private function logToDatabase(string $level, string $message, array $context): void
    {
        try {
            $db = Database::getInstance();
            $db->insert('op_log', [
                'log_level' => $level,
                'log_message' => $message,
                'log_context' => json_encode($context),
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'status' => 'ACTIVE'
            ]);
        } catch (Exception $e) {
            // Silently fail - don't want logging to break the app
        }
    }

    /**
     * Debug level log
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->log('DEBUG', $message, $context);
    }

    /**
     * Info level log
     */
    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->log('INFO', $message, $context);
    }

    /**
     * Notice level log
     */
    public static function notice(string $message, array $context = []): void
    {
        self::getInstance()->log('NOTICE', $message, $context);
    }

    /**
     * Warning level log
     */
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->log('WARNING', $message, $context);
    }

    /**
     * Error level log
     */
    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->log('ERROR', $message, $context);
    }

    /**
     * Critical level log
     */
    public static function critical(string $message, array $context = []): void
    {
        self::getInstance()->log('CRITICAL', $message, $context);
    }

    /**
     * Alert level log
     */
    public static function alert(string $message, array $context = []): void
    {
        self::getInstance()->log('ALERT', $message, $context);
    }

    /**
     * Emergency level log
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::getInstance()->log('EMERGENCY', $message, $context);
    }

    /**
     * Log SQL query
     */
    public static function query(string $sql, array $params = []): void
    {
        if (Config::get('APP_DEBUG', false)) {
            self::debug('SQL Query', [
                'sql' => $sql,
                'params' => $params
            ]);
        }
    }

    /**
     * Log user activity
     */
    public static function activity(string $action, array $details = []): void
    {
        self::info("User Activity: $action", array_merge([
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['user_name'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ], $details));
    }

    /**
     * Clear old logs
     * 
     * @param int $days Keep logs for this many days
     */
    public static function clearOldLogs(int $days = 30): void
    {
        $instance = self::getInstance();
        $files = glob($instance->logPath . '*.log');
        $cutoff = time() - ($days * 86400);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}
