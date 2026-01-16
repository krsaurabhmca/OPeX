<?php
/**
 * Migration: Improve Database Schema
 * 
 * This migration improves the existing database schema with:
 * - Better data types
 * - Proper indexes
 * - Foreign key relationships
 * - Optimized columns
 */
class CreateImprovedSchema
{
    public function up($db)
    {
        // Optimize op_config table
        $db->execute("ALTER TABLE `op_config` 
            MODIFY COLUMN `option_type` ENUM('SINGLE', 'LIST') DEFAULT 'SINGLE',
            MODIFY COLUMN `status` ENUM('ACTIVE', 'INACTIVE', 'DELETED') DEFAULT 'ACTIVE',
            ADD INDEX `idx_option_name` (`option_name`),
            ADD INDEX `idx_status` (`status`)
        ");

        // Optimize op_user table if exists
        $tables = $db->query("SHOW TABLES LIKE 'op_user'");
        if (count($tables) > 0) {
            $db->execute("ALTER TABLE `op_user` 
                ADD INDEX `idx_user_type` (`user_type`),
                ADD INDEX `idx_status` (`status`),
                ADD INDEX `idx_email` (`email`),
                ADD INDEX `idx_mobile` (`mobile`)
            ");
        }

        // Optimize op_table table
        $tables = $db->query("SHOW TABLES LIKE 'op_table'");
        if (count($tables) > 0) {
            $db->execute("ALTER TABLE `op_table` 
                ADD INDEX `idx_table_name` (`table_name`),
                ADD INDEX `idx_status` (`status`)
            ");
        }

        // Optimize op_menu table
        $tables = $db->query("SHOW TABLES LIKE 'op_menu'");
        if (count($tables) > 0) {
            $db->execute("ALTER TABLE `op_menu` 
                ADD INDEX `idx_type` (`type`),
                ADD INDEX `idx_parent` (`parent`),
                ADD INDEX `idx_status` (`status`)
            ");
        }

        // Optimize op_role table
        $tables = $db->query("SHOW TABLES LIKE 'op_role'");
        if (count($tables) > 0) {
            $db->execute("ALTER TABLE `op_role` 
                ADD INDEX `idx_table_id` (`table_id`),
                ADD INDEX `idx_role_name` (`role_name`),
                ADD INDEX `idx_status` (`status`)
            ");
        }

        // Add performance monitoring table
        $db->execute("CREATE TABLE IF NOT EXISTS `op_performance` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `endpoint` VARCHAR(255) NOT NULL,
            `method` VARCHAR(10) NOT NULL,
            `execution_time` DECIMAL(10,4) NOT NULL,
            `memory_usage` INT(11) NOT NULL,
            `query_count` INT(11) DEFAULT 0,
            `user_id` INT(11) DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_endpoint` (`endpoint`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Add API tokens table for API authentication
        $db->execute("CREATE TABLE IF NOT EXISTS `op_api_tokens` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `token_name` VARCHAR(255) NOT NULL,
            `token` VARCHAR(255) NOT NULL,
            `abilities` TEXT DEFAULT NULL,
            `last_used_at` TIMESTAMP NULL DEFAULT NULL,
            `expires_at` TIMESTAMP NULL DEFAULT NULL,
            `status` ENUM('ACTIVE', 'INACTIVE', 'REVOKED') DEFAULT 'ACTIVE',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT(11) DEFAULT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_by` INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_token` (`token`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Add notification system table
        $db->execute("CREATE TABLE IF NOT EXISTS `op_notifications` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `type` VARCHAR(50) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `data` JSON DEFAULT NULL,
            `read_at` TIMESTAMP NULL DEFAULT NULL,
            `status` ENUM('ACTIVE', 'DELETED') DEFAULT 'ACTIVE',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_read_at` (`read_at`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Add cache table for application caching
        $db->execute("CREATE TABLE IF NOT EXISTS `op_cache` (
            `key` VARCHAR(255) NOT NULL,
            `value` LONGTEXT NOT NULL,
            `expiration` INT(11) NOT NULL,
            PRIMARY KEY (`key`),
            INDEX `idx_expiration` (`expiration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        Logger::info('Database schema improved successfully');
    }

    public function down($db)
    {
        // Remove added tables
        $db->execute("DROP TABLE IF EXISTS `op_performance`");
        $db->execute("DROP TABLE IF EXISTS `op_api_tokens`");
        $db->execute("DROP TABLE IF EXISTS `op_notifications`");
        $db->execute("DROP TABLE IF EXISTS `op_cache`");

        // Note: We don't reverse the index additions as they don't harm the existing system
        Logger::info('Database schema improvements rolled back');
    }
}
