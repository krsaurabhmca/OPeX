<?php
/**
 * Database Class - PDO Wrapper with Security Enhancements
 * 
 * Provides secure database operations using PDO with prepared statements
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

class Database
{
    private static $instance = null;
    private $pdo;
    private $config;
    private $userId;
    private $currentDateTime;
    
    /**
     * Private constructor
     */
    private function __construct()
    {
        $this->config = Config::getInstance();
        $this->connect();
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->currentDateTime = date('Y-m-d H:i:s');
    }

    /**
     * Get singleton instance
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection using PDO
     * 
     * @throws PDOException
     */
    private function connect(): void
    {
        try {
            $host = Config::get('DB_HOST', 'localhost');
            $port = Config::get('DB_PORT', '3306');
            $database = Config::get('DB_DATABASE', 'opex');
            $username = Config::get('DB_USERNAME', 'root');
            $password = Config::get('DB_PASSWORD', '');
            $charset = Config::get('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true, // Connection pooling
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE utf8mb4_unicode_ci"
            ];

            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            $this->handleError('Connection Failed', $e);
        }
    }

    /**
     * Get PDO instance
     * 
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a SELECT query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Query results
     */
    public function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError('Query Failed', $e, $sql);
            return [];
        }
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return int Affected rows or last insert ID
     */
    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Return last insert ID for INSERT queries
            if (stripos(trim($sql), 'INSERT') === 0) {
                return (int)$this->pdo->lastInsertId();
            }
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->handleError('Execute Failed', $e, $sql);
            return 0;
        }
    }

    /**
     * Insert data into table
     * 
     * @param string $table Table name
     * @param array $data Data to insert
     * @return array Result with id and status
     */
    public function insert(string $table, array $data): array
    {
        try {
            // Add metadata
            $data['created_by'] = $this->userId;
            $data['created_at'] = $this->currentDateTime;
            
            // Build query
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($data), '?');
            
            $sql = sprintf(
                "INSERT INTO `%s` (`%s`) VALUES (%s)",
                $table,
                implode('`, `', $columns),
                implode(', ', $placeholders)
            );
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));
            
            $id = (int)$this->pdo->lastInsertId();
            
            return [
                'status' => 'success',
                'id' => $id,
                'msg' => 'Data inserted successfully',
                'affected_rows' => $stmt->rowCount()
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'id' => 0,
                'msg' => $e->getMessage(),
                'affected_rows' => 0
            ];
        }
    }

    /**
     * Update data in table
     * 
     * @param string $table Table name
     * @param array $data Data to update
     * @param mixed $identifier ID or array of conditions
     * @param string $pkey Primary key column name
     * @return array Result with status
     */
    public function update(string $table, array $data, $identifier, string $pkey = 'id'): array
    {
        try {
            // Add metadata
            $data['updated_by'] = $this->userId;
            $data['updated_at'] = $this->currentDateTime;
            
            // Remove empty values
            $data = array_filter($data, function($value) {
                return $value !== '' && $value !== null;
            });
            
            // Build SET clause
            $setClause = [];
            $values = [];
            foreach ($data as $key => $value) {
                $setClause[] = "`$key` = ?";
                $values[] = is_array($value) ? implode(',', $value) : $value;
            }
            
            // Build WHERE clause
            $whereClause = '';
            if (is_array($identifier)) {
                $conditions = [];
                foreach ($identifier as $key => $value) {
                    $conditions[] = "`$key` = ?";
                    $values[] = $value;
                }
                $whereClause = implode(' AND ', $conditions);
            } else {
                $whereClause = "`$pkey` = ?";
                $values[] = $identifier;
            }
            
            $sql = sprintf(
                "UPDATE `%s` SET %s WHERE %s",
                $table,
                implode(', ', $setClause),
                $whereClause
            );
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            
            $affected = $stmt->rowCount();
            
            return [
                'status' => $affected > 0 ? 'success' : 'error',
                'msg' => $affected > 0 ? "$affected record(s) updated successfully" : 'No changes made',
                'affected_rows' => $affected,
                'sql' => $sql
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'msg' => $e->getMessage(),
                'affected_rows' => 0
            ];
        }
    }

    /**
     * Soft delete - mark as DELETED
     * 
     * @param string $table Table name
     * @param mixed $condition ID or array of conditions
     * @param string $pkey Primary key column
     * @return array Result
     */
    public function softDelete(string $table, $condition, string $pkey = 'id'): array
    {
        return $this->update($table, ['status' => 'DELETED'], $condition, $pkey);
    }

    /**
     * Hard delete - permanently remove from database
     * 
     * @param string $table Table name
     * @param mixed $condition ID or array of conditions
     * @param string $pkey Primary key column
     * @return array Result
     */
    public function delete(string $table, $condition, string $pkey = 'id'): array
    {
        try {
            // Check permissions
            $userType = $_SESSION['user_type'] ?? '';
            if (!in_array($userType, ['ADMIN', 'DEV'])) {
                return [
                    'status' => 'error',
                    'msg' => 'Permission denied: Only ADMIN/DEV can permanently delete records'
                ];
            }
            
            // Build WHERE clause
            $values = [];
            if (is_array($condition)) {
                $conditions = [];
                foreach ($condition as $key => $value) {
                    $conditions[] = "`$key` = ?";
                    $values[] = $value;
                }
                $whereClause = implode(' AND ', $conditions);
            } else {
                $whereClause = "`$pkey` = ?";
                $values[] = $condition;
            }
            
            $sql = "DELETE FROM `$table` WHERE $whereClause";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            
            $affected = $stmt->rowCount();
            
            return [
                'status' => $affected > 0 ? 'success' : 'error',
                'msg' => $affected > 0 ? "$affected record(s) deleted permanently" : 'No records found',
                'affected_rows' => $affected
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'msg' => $e->getMessage(),
                'affected_rows' => 0
            ];
        }
    }

    /**
     * Get single record
     * 
     * @param string $table Table name
     * @param mixed $id Record ID
     * @param string|null $field Specific field to return
     * @param string $pkey Primary key column
     * @return array Result
     */
    public function getOne(string $table, $id, ?string $field = null, string $pkey = 'id'): array
    {
        try {
            $sql = "SELECT * FROM `$table` WHERE `$pkey` = ? LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            
            if ($data) {
                return [
                    'status' => 'success',
                    'count' => 1,
                    'data' => $field ? $data[$field] ?? null : $data
                ];
            }
            
            return [
                'status' => 'error',
                'count' => 0,
                'data' => null
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'count' => 0,
                'data' => null,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all records matching conditions
     * 
     * @param string $table Table name
     * @param string|array $columns Columns to select
     * @param array|null $where WHERE conditions
     * @param string $orderBy ORDER BY clause
     * @param string $operator Comparison operator (=, !=, >, <, etc.)
     * @return array Result
     */
    public function getAll(
        string $table,
        $columns = '*',
        ?array $where = null,
        string $orderBy = 'id DESC',
        string $operator = '='
    ): array {
        try {
            // Build column list
            $columnList = is_array($columns) ? implode(', ', $columns) : $columns;
            
            // Build WHERE clause
            $whereClause = '';
            $values = [];
            if ($where && is_array($where)) {
                $conditions = [];
                foreach ($where as $key => $value) {
                    $conditions[] = "`$key` $operator ?";
                    $values[] = $value;
                }
                $whereClause = ' WHERE ' . implode(' AND ', $conditions);
            }
            
            $sql = "SELECT $columnList FROM `$table`$whereClause ORDER BY $orderBy";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            $data = $stmt->fetchAll();
            
            return [
                'status' => 'success',
                'count' => count($data),
                'data' => $data
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'count' => 0,
                'data' => [],
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Handle database errors
     * 
     * @param string $message Error message
     * @param PDOException $e Exception
     * @param string|null $sql SQL query
     * @throws PDOException
     */
    private function handleError(string $message, PDOException $e, ?string $sql = null): void
    {
        $debugMode = Config::get('APP_DEBUG', false);
        
        // Log error
        $logMessage = sprintf(
            "[%s] %s: %s\nSQL: %s\nStack Trace: %s\n",
            date('Y-m-d H:i:s'),
            $message,
            $e->getMessage(),
            $sql ?? 'N/A',
            $e->getTraceAsString()
        );
        
        error_log($logMessage, 3, __DIR__ . '/logs/database.log');
        
        // Show detailed error in debug mode
        if ($debugMode) {
            throw $e;
        } else {
            throw new PDOException("Database error occurred. Please contact support.");
        }
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
