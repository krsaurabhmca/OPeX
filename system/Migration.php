<?php
/**
 * Migration Class - Database Schema Management
 * 
 * Handles database migrations for version control of database schema
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

class Migration
{
    private $db;
    private $migrationsPath;
    private $migrationsTable = 'op_migrations';

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->migrationsPath = OPEX_ROOT . '/database/migrations/';
        
        // Create migrations directory if doesn't exist
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        
        // Ensure migrations table exists
        $this->createMigrationsTable();
    }

    /**
     * Create migrations tracking table
     */
    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT(11) NOT NULL,
            `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->execute($sql);
    }

    /**
     * Run pending migrations
     * 
     * @return array Results
     */
    public function migrate(): array
    {
        $executed = [];
        $batch = $this->getNextBatch();
        
        // Get pending migrations
        $pending = $this->getPendingMigrations();
        
        if (empty($pending)) {
            return [
                'status' => 'info',
                'message' => 'Nothing to migrate',
                'executed' => []
            ];
        }
        
        foreach ($pending as $migration) {
            try {
                // Run migration
                $this->runMigration($migration);
                
                // Record in database
                $this->db->execute(
                    "INSERT INTO `{$this->migrationsTable}` (migration, batch) VALUES (?, ?)",
                    [$migration, $batch]
                );
                
                $executed[] = $migration;
                Logger::info("Migration executed: $migration");
            } catch (Exception $e) {
                Logger::error("Migration failed: $migration", ['error' => $e->getMessage()]);
                return [
                    'status' => 'error',
                    'message' => "Migration failed: $migration - " . $e->getMessage(),
                    'executed' => $executed
                ];
            }
        }
        
        return [
            'status' => 'success',
            'message' => count($executed) . ' migration(s) executed',
            'executed' => $executed
        ];
    }

    /**
     * Rollback last batch of migrations
     * 
     * @param int $steps Number of batches to rollback
     * @return array Results
     */
    public function rollback(int $steps = 1): array
    {
        $rolledBack = [];
        
        for ($i = 0; $i < $steps; $i++) {
            $batch = $this->getLastBatch();
            
            if (!$batch) {
                break;
            }
            
            // Get migrations from this batch
            $migrations = $this->db->query(
                "SELECT migration FROM `{$this->migrationsTable}` WHERE batch = ? ORDER BY id DESC",
                [$batch]
            );
            
            foreach ($migrations as $migration) {
                try {
                    // Run rollback
                    $this->rollbackMigration($migration['migration']);
                    
                    // Remove from database
                    $this->db->execute(
                        "DELETE FROM `{$this->migrationsTable}` WHERE migration = ?",
                        [$migration['migration']]
                    );
                    
                    $rolledBack[] = $migration['migration'];
                    Logger::info("Migration rolled back: " . $migration['migration']);
                } catch (Exception $e) {
                    Logger::error("Rollback failed: " . $migration['migration'], ['error' => $e->getMessage()]);
                    return [
                        'status' => 'error',
                        'message' => "Rollback failed: " . $migration['migration'] . " - " . $e->getMessage(),
                        'rolled_back' => $rolledBack
                    ];
                }
            }
        }
        
        return [
            'status' => 'success',
            'message' => count($rolledBack) . ' migration(s) rolled back',
            'rolled_back' => $rolledBack
        ];
    }

    /**
     * Create a new migration file
     * 
     * @param string $name Migration name
     * @param string $type Type (create_table, alter_table, custom)
     * @return array Result
     */
    public function create(string $name, string $type = 'custom'): array
    {
        $timestamp = date('Y_m_d_His');
        $className = $this->migrationClassName($name);
        $filename = $timestamp . '_' . $name . '.php';
        $filepath = $this->migrationsPath . $filename;
        
        $template = $this->getTemplate($type, $className, $name);
        
        file_put_contents($filepath, $template);
        
        return [
            'status' => 'success',
            'message' => "Migration created: $filename",
            'file' => $filepath
        ];
    }

    /**
     * Get migration template
     */
    private function getTemplate(string $type, string $className, string $name): string
    {
        $tableName = str_replace('_', '', $name);
        
        $templates = [
            'create_table' => "<?php
/**
 * Migration: $className
 */
class $className
{
    public function up(\$db)
    {
        \$sql = \"CREATE TABLE IF NOT EXISTS `$tableName` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `status` VARCHAR(25) DEFAULT 'ACTIVE',
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT(11) DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_by` INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\";
        
        \$db->execute(\$sql);
    }
    
    public function down(\$db)
    {
        \$db->execute(\"DROP TABLE IF EXISTS `$tableName`\");
    }
}
",
            'alter_table' => "<?php
/**
 * Migration: $className
 */
class $className
{
    public function up(\$db)
    {
        // Add your ALTER TABLE statements here
        // \$db->execute(\"ALTER TABLE `table_name` ADD COLUMN `column_name` VARCHAR(255)\");
    }
    
    public function down(\$db)
    {
        // Reverse the changes made in up()
        // \$db->execute(\"ALTER TABLE `table_name` DROP COLUMN `column_name`\");
    }
}
",
            'custom' => "<?php
/**
 * Migration: $className
 */
class $className
{
    public function up(\$db)
    {
        // Add your migration logic here
    }
    
    public function down(\$db)
    {
        // Add rollback logic here
    }
}
"
        ];
        
        return $templates[$type] ?? $templates['custom'];
    }

    /**
     * Get pending migrations
     */
    private function getPendingMigrations(): array
    {
        // Get all migration files
        $files = glob($this->migrationsPath . '*.php');
        $files = array_map('basename', $files);
        sort($files);
        
        // Get executed migrations
        $executed = $this->db->query("SELECT migration FROM `{$this->migrationsTable}`");
        $executedList = array_column($executed, 'migration');
        
        // Return pending
        return array_diff($files, $executedList);
    }

    /**
     * Run a migration
     */
    private function runMigration(string $migration): void
    {
        require_once $this->migrationsPath . $migration;
        
        $className = $this->getMigrationClass($migration);
        $instance = new $className();
        $instance->up($this->db);
    }

    /**
     * Rollback a migration
     */
    private function rollbackMigration(string $migration): void
    {
        require_once $this->migrationsPath . $migration;
        
        $className = $this->getMigrationClass($migration);
        $instance = new $className();
        $instance->down($this->db);
    }

    /**
     * Get migration class name from filename
     */
    private function getMigrationClass(string $filename): string
    {
        // Remove timestamp and extension
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
        $name = str_replace('.php', '', $name);
        return $this->migrationClassName($name);
    }

    /**
     * Convert migration name to class name
     */
    private function migrationClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    /**
     * Get next batch number
     */
    private function getNextBatch(): int
    {
        $result = $this->db->query("SELECT MAX(batch) as batch FROM `{$this->migrationsTable}`");
        return ($result[0]['batch'] ?? 0) + 1;
    }

    /**
     * Get last batch number
     */
    private function getLastBatch(): ?int
    {
        $result = $this->db->query("SELECT MAX(batch) as batch FROM `{$this->migrationsTable}`");
        return $result[0]['batch'] ?? null;
    }

    /**
     * Get migration status
     */
    public function status(): array
    {
        $all = glob($this->migrationsPath . '*.php');
        $all = array_map('basename', $all);
        sort($all);
        
        $executed = $this->db->query(
            "SELECT migration, batch, executed_at FROM `{$this->migrationsTable}` ORDER BY id"
        );
        $executedMap = [];
        foreach ($executed as $row) {
            $executedMap[$row['migration']] = $row;
        }
        
        $status = [];
        foreach ($all as $migration) {
            $status[] = [
                'migration' => $migration,
                'executed' => isset($executedMap[$migration]),
                'batch' => $executedMap[$migration]['batch'] ?? null,
                'executed_at' => $executedMap[$migration]['executed_at'] ?? null
            ];
        }
        
        return $status;
    }
}
