<?php
/**
 * API Controller - Handle API Requests
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

class ApiController
{
    private $db;
    private $security;
    private $method;
    private $endpoint;
    private $params;
    private $user;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->security = Security::getInstance();
        $this->method = $_SERVER['REQUEST_METHOD'];
        
        // Parse endpoint
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = str_replace('/api/', '', $uri);
        $this->endpoint = explode('/', trim($uri, '/'));
        
        // Get request body
        $input = file_get_contents('php://input');
        $this->params = json_decode($input, true) ?? [];
        
        // Merge with GET params
        $this->params = array_merge($_GET, $this->params);
    }

    /**
     * Handle incoming request
     */
    public function handleRequest(): void
    {
        try {
            // Check authentication for protected routes
            $publicRoutes = ['auth/login', 'auth/register', 'auth/forgot-password'];
            $route = implode('/', $this->endpoint);
            
            if (!in_array($route, $publicRoutes)) {
                $this->authenticate();
            }
            
            // Route to appropriate handler
            $resource = $this->endpoint[0] ?? '';
            
            switch ($resource) {
                case 'auth':
                    $this->handleAuth();
                    break;
                
                case 'users':
                    $this->handleUsers();
                    break;
                
                case 'tables':
                    $this->handleTables();
                    break;
                
                case 'data':
                    $this->handleData();
                    break;
                
                case 'config':
                    $this->handleConfig();
                    break;
                
                default:
                    Response::notFound('Endpoint not found');
            }
        } catch (Exception $e) {
            Logger::error('API Error', [
                'endpoint' => $this->endpoint,
                'error' => $e->getMessage()
            ]);
            
            Response::error($e->getMessage());
        }
    }

    /**
     * Authenticate API request
     */
    private function authenticate(): void
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!$authHeader || !preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            Response::unauthorized('Missing or invalid authorization header');
        }
        
        $token = $matches[1];
        
        // Verify token from database
        $result = $this->db->query(
            "SELECT t.*, u.* FROM op_api_tokens t 
             JOIN op_user u ON t.user_id = u.id 
             WHERE t.token = ? AND t.status = 'ACTIVE' 
             AND (t.expires_at IS NULL OR t.expires_at > NOW())",
            [$token]
        );
        
        if (empty($result)) {
            Response::unauthorized('Invalid or expired token');
        }
        
        $this->user = $result[0];
        
        // Update last used timestamp
        $this->db->execute(
            "UPDATE op_api_tokens SET last_used_at = NOW() WHERE token = ?",
            [$token]
        );
        
        // Set session variables for backward compatibility
        $_SESSION['user_id'] = $this->user['user_id'];
        $_SESSION['user_type'] = $this->user['user_type'];
        $_SESSION['user_name'] = $this->user['user_name'];
    }

    /**
     * Handle authentication endpoints
     */
    private function handleAuth(): void
    {
        $action = $this->endpoint[1] ?? '';
        
        switch ($action) {
            case 'login':
                $this->login();
                break;
            
            case 'logout':
                $this->logout();
                break;
            
            case 'refresh':
                $this->refreshToken();
                break;
            
            default:
                Response::notFound();
        }
    }

    /**
     * User login
     */
    private function login(): void
    {
        $email = $this->params['email'] ?? '';
        $password = $this->params['password'] ?? '';
        
        if (!$email || !$password) {
            Response::error('Email and password are required', null, 400);
        }
        
        // Get user
        $user = $this->db->query(
            "SELECT * FROM op_user WHERE email = ? AND status = 'ACTIVE' LIMIT 1",
            [$email]
        );
        
        if (empty($user)) {
            Response::error('Invalid credentials', null, 401);
        }
        
        $user = $user[0];
        
        // Verify password
        if (!$this->security->verifyPassword($password, $user['password'])) {
            Response::error('Invalid credentials', null, 401);
        }
        
        // Generate API token
        $token = bin2hex(random_bytes(32));
        $tokenName = $this->params['device'] ?? 'Web Client';
        
        $this->db->insert('op_api_tokens', [
            'user_id' => $user['id'],
            'token_name' => $tokenName,
            'token' => $token,
            'status' => 'ACTIVE'
        ]);
        
        // Remove sensitive data
        unset($user['password']);
        
        Logger::activity('API Login', ['user_id' => $user['id']]);
        
        Response::success([
            'token' => $token,
            'user' => $user
        ], 'Login successful');
    }

    /**
     * Logout - revoke token
     */
    private function logout(): void
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $this->db->update('op_api_tokens', ['status' => 'REVOKED'], ['token' => $token]);
        }
        
        Response::success(null, 'Logged out successfully');
    }

    /**
     * Refresh token
     */
    private function refreshToken(): void
    {
        // Generate new token
        $newToken = bin2hex(random_bytes(32));
        
        // Get current token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        preg_match('/Bearer\s+(.+)/', $authHeader, $matches);
        $oldToken = $matches[1] ?? '';
        
        // Update token
        $this->db->update(
            'op_api_tokens',
            ['token' => $newToken],
            ['token' => $oldToken]
        );
        
        Response::success(['token' => $newToken], 'Token refreshed');
    }

    /**
     * Handle user endpoints
     */
    private function handleUsers(): void
    {
        $id = $this->endpoint[1] ?? null;
        
        switch ($this->method) {
            case 'GET':
                if ($id) {
                    $this->getUser($id);
                } else {
                    $this->getUsers();
                }
                break;
            
            case 'POST':
                $this->createUser();
                break;
            
            case 'PUT':
                if ($id) {
                    $this->updateUser($id);
                } else {
                    Response::error('User ID required', null, 400);
                }
                break;
            
            case 'DELETE':
                if ($id) {
                    $this->deleteUser($id);
                } else {
                    Response::error('User ID required', null, 400);
                }
                break;
            
            default:
                Response::error('Method not allowed', null, 405);
        }
    }

    /**
     * Get all users
     */
    private function getUsers(): void
    {
        $page = (int)($this->params['page'] ?? 1);
        $perPage = (int)($this->params['per_page'] ?? 20);
        $offset = ($page - 1) * $perPage;
        
        $where = ["status != 'DELETED'"];
        $params = [];
        
        if (!empty($this->params['search'])) {
            $where[] = "(user_name LIKE ? OR email LIKE ?)";
            $search = '%' . $this->params['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        // Get total count
        $total = $this->db->query("SELECT COUNT(*) as count FROM op_user $whereClause", $params);
        $totalCount = $total[0]['count'];
        
        // Get users
        $users = $this->db->query(
            "SELECT id, user_name, email, mobile, user_type, status, created_at 
             FROM op_user $whereClause 
             ORDER BY id DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        
        Response::success([
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $perPage)
            ]
        ]);
    }

    /**
     * Get single user
     */
    private function getUser($id): void
    {
        $user = $this->db->getOne('op_user', $id);
        
        if ($user['count'] == 0) {
            Response::notFound('User not found');
        }
        
        unset($user['data']['password']);
        Response::success($user['data']);
    }

    /**
     * Create user
     */
    private function createUser(): void
    {
        // Validate input
        $validator = new Validator($this->params);
        $isValid = $validator->validate([
            'user_name' => 'required|min:3',
            'email' => 'required|email|unique:op_user,email',
            'password' => 'required|strong_password',
            'mobile' => 'mobile'
        ]);
        
        if (!$isValid) {
            Response::validationError($validator->getErrors());
        }
        
        // Hash password
        $this->params['password'] = $this->security->hashPassword($this->params['password']);
        
        // Create user
        $result = $this->db->insert('op_user', $this->params);
        
        Response::success($result, 'User created successfully', 201);
    }

    /**
     * Update user
     */
    private function updateUser($id): void
    {
        // Check if user exists
        $existing = $this->db->getOne('op_user', $id);
        if ($existing['count'] == 0) {
            Response::notFound('User not found');
        }
        
        // Hash password if provided
        if (!empty($this->params['password'])) {
            $this->params['password'] = $this->security->hashPassword($this->params['password']);
        }
        
        // Update user
        $result = $this->db->update('op_user', $this->params, $id);
        
        Response::success($result, 'User updated successfully');
    }

    /**
     * Delete user
     */
    private function deleteUser($id): void
    {
        $result = $this->db->softDelete('op_user', $id);
        Response::success($result, 'User deleted successfully');
    }

    /**
     * Handle generic data operations
     */
    private function handleData(): void
    {
        $table = $this->endpoint[1] ?? null;
        $id = $this->endpoint[2] ?? null;
        
        if (!$table) {
            Response::error('Table name required', null, 400);
        }
        
        // Verify table exists
        $tables = table_list();
        if (!in_array($table, $tables['data'] ?? [])) {
            Response::notFound('Table not found');
        }
        
        switch ($this->method) {
            case 'GET':
                if ($id) {
                    $result = get_data_v2($table, $id);
                    Response::success($result['data']);
                } else {
                    $result = get_all_v2($table);
                    Response::success($result['data']);
                }
                break;
            
            case 'POST':
                $result = insert_data_v2($table, $this->params);
                Response::success($result, 'Data created', 201);
                break;
            
            case 'PUT':
                if (!$id) {
                    Response::error('ID required', null, 400);
                }
                $result = update_data_v2($table, $this->params, $id);
                Response::success($result, 'Data updated');
                break;
            
            case 'DELETE':
                if (!$id) {
                    Response::error('ID required', null, 400);
                }
                $result = remove_data_v2($table, $id);
                Response::success($result, 'Data deleted');
                break;
            
            default:
                Response::error('Method not allowed', null, 405);
        }
    }

    /**
     * Handle table management endpoints
     */
    private function handleTables(): void
    {
        if ($this->method === 'GET') {
            $tables = table_list();
            Response::success($tables['data']);
        } else {
            Response::error('Method not allowed', null, 405);
        }
    }

    /**
     * Handle configuration endpoints
     */
    private function handleConfig(): void
    {
        if ($this->method === 'GET') {
            $config = all_config();
            Response::success($config);
        } else {
            Response::error('Method not allowed', null, 405);
        }
    }
}
