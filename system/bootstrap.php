<?php
/**
 * Bootstrap File - Initialize Application
 * 
 * Loads configuration, starts session, initializes core classes
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set execution time
ini_set('max_execution_time', 300);
set_time_limit(300);

// Define constants
define('OPEX_VERSION', '2.0');
define('OPEX_START_TIME', microtime(true));
define('OPEX_ROOT', dirname(__DIR__));
define('OPEX_SYSTEM', __DIR__);

// Autoloader
spl_autoload_register(function ($class) {
    $file = OPEX_SYSTEM . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

// Load Config class first
require_once __DIR__ . '/Config.php';

// Initialize configuration
$config = Config::getInstance();

// Set timezone
date_default_timezone_set(Config::get('TIMEZONE', 'Asia/Kolkata'));

// Session configuration
ini_set('session.cookie_httponly', Config::get('SESSION_HTTP_ONLY', true));
ini_set('session.cookie_secure', Config::get('SESSION_SECURE', false));
ini_set('session.cookie_samesite', Config::get('SESSION_SAME_SITE', 'Lax'));
ini_set('session.gc_maxlifetime', Config::get('SESSION_LIFETIME', 7200));

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session token
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = session_id();
}

// Global variables for backward compatibility
global $CONFIG, $con, $db_name, $host_name, $db_user, $db_password, $base_url;
global $user_id, $current_date_time, $today, $user_type, $user_name;

// Populate global CONFIG array
$CONFIG = [
    'token' => session_id(),
    'today' => date('Y-m-d'),
    'current_date_time' => date('Y-m-d H:i:s'),
    'app_name' => Config::get('APP_NAME', 'OPeX'),
    'base_url' => Config::get('APP_URL', 'http://localhost/opex/'),
    'dev_company' => Config::get('DEV_COMPANY', 'OfferPlant Technologies Private Limited'),
    'dev_by' => Config::get('DEV_BY', 'OfferPlant'),
    'dev_url' => Config::get('DEV_URL', 'http://offerplant.com'),
    'dev_email' => Config::get('DEV_EMAIL', 'ask@offerplant.com'),
    'dev_contact' => Config::get('DEV_CONTACT', '9431426600'),
    
    // Database config
    'host_name' => Config::get('DB_HOST', 'localhost'),
    'db_user' => Config::get('DB_USERNAME', 'root'),
    'db_password' => Config::get('DB_PASSWORD', ''),
    'db_name' => Config::get('DB_DATABASE', 'opex'),
];

// Set individual variables for backward compatibility
$host_name = $CONFIG['host_name'];
$db_user = $CONFIG['db_user'];
$db_password = $CONFIG['db_password'];
$db_name = $CONFIG['db_name'];
$base_url = $CONFIG['base_url'];
$today = $CONFIG['today'];
$current_date_time = $CONFIG['current_date_time'];

// User session variables
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $CONFIG['user_id'] = $user_id;
}

if (isset($_SESSION['user_type'])) {
    $user_type = $_SESSION['user_type'];
    $CONFIG['user_type'] = $user_type;
}

if (isset($_SESSION['user_name'])) {
    $user_name = $_SESSION['user_name'];
    $CONFIG['user_name'] = $user_name;
}

// Initialize Database (PDO)
try {
    $db = Database::getInstance();
    
    // Also create mysqli connection for backward compatibility
    $con = mysqli_connect($host_name, $db_user, $db_password, $db_name);
    if (!$con) {
        throw new Exception("Unable to Connect: " . mysqli_connect_error());
    }
    mysqli_set_charset($con, 'utf8mb4');
} catch (Exception $e) {
    Logger::critical('Database Connection Failed', ['error' => $e->getMessage()]);
    
    if (Config::get('APP_DEBUG', false)) {
        die("Database Connection Failed: " . $e->getMessage());
    } else {
        die("System Error. Please contact administrator.");
    }
}

// Initialize other core services
$security = Security::getInstance();
$logger = Logger::getInstance();

// Error and exception handlers
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_NOTICE => 'NOTICE',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_DEPRECATED => 'DEPRECATED'
    ];
    
    $type = $errorTypes[$errno] ?? 'UNKNOWN';
    
    Logger::error("PHP $type", [
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    
    // Don't execute PHP internal error handler
    return true;
});

set_exception_handler(function($exception) {
    Logger::critical('Uncaught Exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    if (Config::get('APP_DEBUG', false)) {
        echo "<pre>";
        echo "Uncaught Exception: " . $exception->getMessage() . "\n";
        echo "File: " . $exception->getFile() . "\n";
        echo "Line: " . $exception->getLine() . "\n";
        echo "\nStack Trace:\n" . $exception->getTraceAsString();
        echo "</pre>";
    } else {
        http_response_code(500);
        echo "An error occurred. Please try again later.";
    }
    exit;
});

// Register shutdown function
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        Logger::emergency('Fatal Error', $error);
        
        if (!Config::get('APP_DEBUG', false)) {
            echo "A fatal error occurred. Please contact administrator.";
        }
    }
    
    // Log execution time in debug mode
    if (Config::get('APP_DEBUG', false)) {
        $executionTime = microtime(true) - OPEX_START_TIME;
        Logger::debug('Request completed', [
            'execution_time' => number_format($executionTime, 4) . 's',
            'memory_peak' => number_format(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB'
        ]);
    }
});

// Helper function to extract CONFIG array
extract($CONFIG);

// Log application start (debug mode only)
if (Config::get('APP_DEBUG', false)) {
    Logger::debug('Application Bootstrap Complete', [
        'uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        'user' => $_SESSION['user_id'] ?? 'guest'
    ]);
}
