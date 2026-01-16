<?php
/**
 * Response Class - Handle API/AJAX Responses
 * 
 * Provides standardized response formatting
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

class Response
{
    /**
     * Send JSON response
     * 
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     */
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): void
    {
        self::json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ], $statusCode);
    }

    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param mixed $errors Error details
     * @param int $statusCode HTTP status code
     */
    public static function error(string $message = 'Error occurred', $errors = null, int $statusCode = 400): void
    {
        self::json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'timestamp' => time()
        ], $statusCode);
    }

    /**
     * Send validation error response
     * 
     * @param array $errors Validation errors
     */
    public static function validationError(array $errors): void
    {
        self::error('Validation failed', $errors, 422);
    }

    /**
     * Send unauthorized response
     * 
     * @param string $message Error message
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, null, 401);
    }

    /**
     * Send forbidden response
     * 
     * @param string $message Error message
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, null, 403);
    }

    /**
     * Send not found response
     * 
     * @param string $message Error message
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, null, 404);
    }

    /**
     * Redirect to URL
     * 
     * @param string $url Redirect URL
     * @param int $statusCode HTTP status code
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit;
    }

    /**
     * Redirect back
     */
    public static function back(): void
    {
        $url = $_SERVER['HTTP_REFERER'] ?? Config::get('APP_URL', '/');
        self::redirect($url);
    }

    /**
     * Redirect with message
     * 
     * @param string $url Redirect URL
     * @param string $message Flash message
     * @param string $type Message type (success, error, warning, info)
     */
    public static function redirectWith(string $url, string $message, string $type = 'success'): void
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        self::redirect($url);
    }

    /**
     * Get flash message
     * 
     * @return array|null Flash message and type
     */
    public static function getFlash(): ?array
    {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'info';
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return ['message' => $message, 'type' => $type];
        }
        return null;
    }

    /**
     * Download file
     * 
     * @param string $filepath File path
     * @param string|null $filename Download filename
     */
    public static function download(string $filepath, ?string $filename = null): void
    {
        if (!file_exists($filepath)) {
            self::notFound('File not found');
        }

        $filename = $filename ?? basename($filepath);
        $mimeType = mime_content_type($filepath);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        readfile($filepath);
        exit;
    }

    /**
     * Stream file (for viewing in browser)
     * 
     * @param string $filepath File path
     */
    public static function stream(string $filepath): void
    {
        if (!file_exists($filepath)) {
            self::notFound('File not found');
        }

        $mimeType = mime_content_type($filepath);
        $filename = basename($filepath);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        
        readfile($filepath);
        exit;
    }
}
