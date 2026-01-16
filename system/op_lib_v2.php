<?php
/**
 * OPeX Library V2 - Improved Functions with OOP Approach
 * 
 * This file provides wrapper functions that use the new OOP classes
 * while maintaining backward compatibility with the old API
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

require_once __DIR__ . '/bootstrap.php';

// ====================
// DATABASE OPERATIONS
// ====================

/**
 * Insert data into table (V2 - Using PDO)
 * 
 * @param string $table_name Table name
 * @param array $data Data to insert
 * @return array Result array
 */
function insert_data_v2(string $table_name, array $data): array
{
    try {
        $db = Database::getInstance();
        $result = $db->insert($table_name, $data);
        
        Logger::activity("Data inserted into $table_name", ['id' => $result['id']]);
        
        return $result;
    } catch (Exception $e) {
        Logger::error("Insert failed: $table_name", ['error' => $e->getMessage()]);
        return [
            'status' => 'error',
            'id' => 0,
            'msg' => $e->getMessage()
        ];
    }
}

/**
 * Update data in table (V2 - Using PDO)
 * 
 * @param string $table_name Table name
 * @param array $data Data to update
 * @param mixed $identifier ID or conditions array
 * @param string $pkey Primary key column
 * @return array Result array
 */
function update_data_v2(string $table_name, array $data, $identifier, string $pkey = 'id'): array
{
    try {
        $db = Database::getInstance();
        $result = $db->update($table_name, $data, $identifier, $pkey);
        
        Logger::activity("Data updated in $table_name", ['identifier' => $identifier]);
        
        return $result;
    } catch (Exception $e) {
        Logger::error("Update failed: $table_name", ['error' => $e->getMessage()]);
        return [
            'status' => 'error',
            'msg' => $e->getMessage()
        ];
    }
}

/**
 * Get single record (V2 - Using PDO)
 * 
 * @param string $table_name Table name
 * @param mixed $id Record ID
 * @param string|null $field Specific field to return
 * @param string $pkey Primary key column
 * @return array Result array
 */
function get_data_v2(string $table_name, $id, ?string $field = null, string $pkey = 'id'): array
{
    try {
        $db = Database::getInstance();
        return $db->getOne($table_name, $id, $field, $pkey);
    } catch (Exception $e) {
        Logger::error("Get data failed: $table_name", ['error' => $e->getMessage()]);
        return [
            'status' => 'error',
            'count' => 0,
            'data' => null
        ];
    }
}

/**
 * Get all records (V2 - Using PDO)
 * 
 * @param string $table_name Table name
 * @param string|array $columns Columns to select
 * @param array|null $where WHERE conditions
 * @param string $orderBy ORDER BY clause
 * @param string $operator Comparison operator
 * @return array Result array
 */
function get_all_v2(
    string $table_name,
    $columns = '*',
    ?array $where = null,
    string $orderBy = 'id DESC',
    string $operator = '='
): array {
    try {
        $db = Database::getInstance();
        return $db->getAll($table_name, $columns, $where, $orderBy, $operator);
    } catch (Exception $e) {
        Logger::error("Get all failed: $table_name", ['error' => $e->getMessage()]);
        return [
            'status' => 'error',
            'count' => 0,
            'data' => []
        ];
    }
}

/**
 * Soft delete (mark as DELETED)
 * 
 * @param string $table_name Table name
 * @param mixed $condition ID or conditions array
 * @param string $pkey Primary key column
 * @return array Result array
 */
function remove_data_v2(string $table_name, $condition, string $pkey = 'id'): array
{
    try {
        $db = Database::getInstance();
        $result = $db->softDelete($table_name, $condition, $pkey);
        
        Logger::activity("Soft deleted from $table_name", ['condition' => $condition]);
        
        return $result;
    } catch (Exception $e) {
        Logger::error("Soft delete failed: $table_name", ['error' => $e->getMessage()]);
        return [
            'status' => 'error',
            'msg' => $e->getMessage()
        ];
    }
}

/**
 * Hard delete (permanently remove)
 * 
 * @param string $table_name Table name
 * @param mixed $condition ID or conditions array
 * @param string $pkey Primary key column
 * @return array Result array
 */
function delete_data_v2(string $table_name, $condition, string $pkey = 'id'): array
{
    try {
        $db = Database::getInstance();
        $result = $db->delete($table_name, $condition, $pkey);
        
        Logger::activity("Hard deleted from $table_name", ['condition' => $condition]);
        
        return $result;
    } catch (Exception $e) {
        Logger::error("Hard delete failed: $table_name", ['error' => $e->getMessage()]);
        return [
            'status' => 'error',
            'msg' => $e->getMessage()
        ];
    }
}

// ====================
// SECURITY FUNCTIONS
// ====================

/**
 * Encrypt data (V2 - Using OpenSSL)
 * 
 * @param mixed $data Data to encrypt
 * @return string Encrypted data
 */
function encrypt_v2($data): string
{
    $security = Security::getInstance();
    return $security->encrypt($data);
}

/**
 * Decrypt data (V2 - Using OpenSSL)
 * 
 * @param string $data Encrypted data
 * @return mixed Decrypted data
 */
function decrypt_v2(string $data)
{
    $security = Security::getInstance();
    return $security->decrypt($data);
}

/**
 * Clean input data (XSS prevention)
 * 
 * @param mixed $data Data to clean
 * @param string $allowedTags Allowed HTML tags
 * @return mixed Cleaned data
 */
function xss_clean_v2($data, string $allowedTags = '')
{
    $security = Security::getInstance();
    return $security->xssClean($data, $allowedTags);
}

/**
 * Clean POST/GET data
 * 
 * @param array $data Data to clean
 * @return array Cleaned data
 */
function clean_input_v2(array $data): array
{
    $security = Security::getInstance();
    return $security->cleanInput($data);
}

/**
 * Generate CSRF token field
 * 
 * @return string HTML input field
 */
function csrf_field(): string
{
    $security = Security::getInstance();
    return $security->getCSRFField();
}

/**
 * Verify CSRF token
 * 
 * @param string|null $token Token to verify (or get from POST)
 * @return bool True if valid
 */
function verify_csrf(?string $token = null): bool
{
    $security = Security::getInstance();
    $token = $token ?? ($_POST[Config::get('CSRF_TOKEN_NAME', 'csrf_token')] ?? '');
    return $security->verifyCSRFToken($token);
}

/**
 * Hash password securely
 * 
 * @param string $password Plain password
 * @return string Hashed password
 */
function hash_password(string $password): string
{
    $security = Security::getInstance();
    return $security->hashPassword($password);
}

/**
 * Verify password
 * 
 * @param string $password Plain password
 * @param string $hash Password hash
 * @return bool True if matches
 */
function verify_password(string $password, string $hash): bool
{
    $security = Security::getInstance();
    return $security->verifyPassword($password, $hash);
}

/**
 * Generate random string
 * 
 * @param int $length String length
 * @param string $type Type of string
 * @return string Random string
 */
function random_string(int $length = 10, string $type = 'alphanumeric'): string
{
    $security = Security::getInstance();
    return $security->generateRandomString($length, $type);
}

// ====================
// VALIDATION
// ====================

/**
 * Validate input data
 * 
 * @param array $data Data to validate
 * @param array $rules Validation rules
 * @return array Result with errors if any
 */
function validate_input(array $data, array $rules): array
{
    $validator = new Validator($data);
    $isValid = $validator->validate($rules);
    
    return [
        'valid' => $isValid,
        'errors' => $validator->getErrors(),
        'first_error' => $validator->getFirstError()
    ];
}

// ====================
// LOGGING
// ====================

/**
 * Log activity
 * 
 * @param string $level Log level (debug, info, warning, error, etc.)
 * @param string $message Log message
 * @param array $context Additional context
 */
function log_message(string $level, string $message, array $context = []): void
{
    $method = strtolower($level);
    if (method_exists('Logger', $method)) {
        Logger::$method($message, $context);
    } else {
        Logger::info($message, $context);
    }
}

/**
 * Log user activity
 * 
 * @param string $action Action description
 * @param array $details Additional details
 */
function log_activity(string $action, array $details = []): void
{
    Logger::activity($action, $details);
}

// ====================
// RESPONSE HELPERS
// ====================

/**
 * Send JSON response
 * 
 * @param mixed $data Response data
 * @param int $statusCode HTTP status code
 */
function json_response($data, int $statusCode = 200): void
{
    Response::json($data, $statusCode);
}

/**
 * Send success response
 * 
 * @param mixed $data Response data
 * @param string $message Success message
 */
function success_response($data = null, string $message = 'Success'): void
{
    Response::success($data, $message);
}

/**
 * Send error response
 * 
 * @param string $message Error message
 * @param mixed $errors Error details
 */
function error_response(string $message = 'Error occurred', $errors = null): void
{
    Response::error($message, $errors);
}

/**
 * Redirect to URL
 * 
 * @param string $url Target URL
 */
function redirect_to(string $url): void
{
    Response::redirect($url);
}

/**
 * Redirect back
 */
function redirect_back(): void
{
    Response::back();
}

// ====================
// UTILITY FUNCTIONS
// ====================

/**
 * Get configuration value
 * 
 * @param string $key Configuration key
 * @param mixed $default Default value
 * @return mixed Configuration value
 */
function config(string $key, $default = null)
{
    return Config::get($key, $default);
}

/**
 * Check if user is authenticated
 * 
 * @return bool True if authenticated
 */
function is_authenticated(): bool
{
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Check if user has specific role
 * 
 * @param string|array $roles Role name(s)
 * @return bool True if user has role
 */
function has_role($roles): bool
{
    if (!is_authenticated()) {
        return false;
    }
    
    $userRole = $_SESSION['user_type'] ?? '';
    $roles = is_array($roles) ? $roles : [$roles];
    
    return in_array($userRole, $roles);
}

/**
 * Require authentication
 * 
 * @param string|null $redirectUrl Redirect URL if not authenticated
 */
function require_auth(?string $redirectUrl = null): void
{
    if (!is_authenticated()) {
        $redirectUrl = $redirectUrl ?? Config::get('APP_URL') . 'system/op_login.php';
        Response::redirect($redirectUrl);
    }
}

/**
 * Require specific role
 * 
 * @param string|array $roles Required role(s)
 * @param string|null $redirectUrl Redirect URL if access denied
 */
function require_role($roles, ?string $redirectUrl = null): void
{
    require_auth();
    
    if (!has_role($roles)) {
        if ($redirectUrl) {
            Response::redirect($redirectUrl);
        } else {
            Response::forbidden('Access Denied');
        }
    }
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not authenticated
 */
function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 * 
 * @return array|null User data or null
 */
function current_user(): ?array
{
    if (!is_authenticated()) {
        return null;
    }
    
    $result = get_data_v2('op_user', $_SESSION['user_id']);
    return $result['data'] ?? null;
}

// ====================
// BACKWARD COMPATIBILITY WRAPPERS
// ====================
// These functions call the V2 versions but maintain the old function names

if (!function_exists('insert_data_secure')) {
    function insert_data_secure(string $table_name, array $data): array {
        return insert_data_v2($table_name, $data);
    }
}

if (!function_exists('update_data_secure')) {
    function update_data_secure(string $table_name, array $data, $identifier, string $pkey = 'id'): array {
        return update_data_v2($table_name, $data, $identifier, $pkey);
    }
}

if (!function_exists('get_data_secure')) {
    function get_data_secure(string $table_name, $id, ?string $field = null, string $pkey = 'id'): array {
        return get_data_v2($table_name, $id, $field, $pkey);
    }
}

// Include the original op_lib.php for other functions
require_once __DIR__ . '/op_lib.php';
