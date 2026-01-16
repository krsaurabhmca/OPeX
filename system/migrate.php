<?php
/**
 * Migration Runner - CLI tool for database migrations
 * 
 * Usage:
 *   php migrate.php migrate              - Run pending migrations
 *   php migrate.php rollback             - Rollback last batch
 *   php migrate.php rollback --steps=2   - Rollback 2 batches
 *   php migrate.php status               - Show migration status
 *   php migrate.php create migration_name - Create new migration
 *   php migrate.php create migration_name create_table - Create table migration
 * 
 * @package OPeX
 * @author OfferPlant Technologies
 * @version 2.0
 */

// Set CLI mode
define('CLI_MODE', php_sapi_name() === 'cli');

if (!CLI_MODE) {
    die('This script can only be run from command line');
}

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Migration.php';

// Initialize migration class
$migration = new Migration();

// Get command from arguments
$command = $argv[1] ?? 'help';

// Parse options
$options = [];
foreach (array_slice($argv, 2) as $arg) {
    if (strpos($arg, '--') === 0) {
        $parts = explode('=', substr($arg, 2), 2);
        $options[$parts[0]] = $parts[1] ?? true;
    }
}

// Execute command
switch ($command) {
    case 'migrate':
        echo "Running migrations...\n";
        $result = $migration->migrate();
        
        if ($result['status'] === 'success' || $result['status'] === 'info') {
            echo "✓ " . $result['message'] . "\n";
            if (!empty($result['executed'])) {
                foreach ($result['executed'] as $executed) {
                    echo "  - $executed\n";
                }
            }
        } else {
            echo "✗ " . $result['message'] . "\n";
            exit(1);
        }
        break;

    case 'rollback':
        $steps = (int)($options['steps'] ?? 1);
        echo "Rolling back $steps batch(es)...\n";
        
        $result = $migration->rollback($steps);
        
        if ($result['status'] === 'success') {
            echo "✓ " . $result['message'] . "\n";
            if (!empty($result['rolled_back'])) {
                foreach ($result['rolled_back'] as $rolledBack) {
                    echo "  - $rolledBack\n";
                }
            }
        } else {
            echo "✗ " . $result['message'] . "\n";
            exit(1);
        }
        break;

    case 'status':
        echo "Migration Status:\n";
        echo str_repeat('-', 80) . "\n";
        printf("%-50s %-10s %-20s\n", 'Migration', 'Status', 'Batch');
        echo str_repeat('-', 80) . "\n";
        
        $status = $migration->status();
        foreach ($status as $item) {
            printf(
                "%-50s %-10s %-20s\n",
                substr($item['migration'], 0, 47) . (strlen($item['migration']) > 47 ? '...' : ''),
                $item['executed'] ? '✓ Run' : '✗ Pending',
                $item['batch'] ?? '-'
            );
        }
        echo str_repeat('-', 80) . "\n";
        break;

    case 'create':
        $name = $argv[2] ?? null;
        if (!$name) {
            echo "✗ Error: Migration name is required\n";
            echo "Usage: php migrate.php create migration_name [type]\n";
            exit(1);
        }
        
        $type = $argv[3] ?? 'custom';
        $result = $migration->create($name, $type);
        
        echo "✓ " . $result['message'] . "\n";
        echo "File: " . $result['file'] . "\n";
        break;

    case 'help':
    default:
        echo <<<HELP
OPeX Migration System

Usage:
  php migrate.php <command> [options]

Commands:
  migrate                     Run all pending migrations
  rollback [--steps=N]        Rollback last N batches (default: 1)
  status                      Show migration status
  create <name> [type]        Create new migration file
                              Types: create_table, alter_table, custom (default)
  help                        Show this help message

Examples:
  php migrate.php migrate
  php migrate.php rollback --steps=2
  php migrate.php status
  php migrate.php create add_users_table create_table
  php migrate.php create update_users_columns alter_table

HELP;
        break;
}

echo "\nDone.\n";
