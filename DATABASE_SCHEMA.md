# OPeX Database Schema Documentation

## Overview

The OPeX platform uses a well-structured MySQL database schema designed for flexibility and security. All tables follow a consistent pattern with standard metadata columns.

## Standard Columns

Every table in the system includes these standard columns:

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) AUTO_INCREMENT | Primary key |
| `status` | ENUM/VARCHAR | Record status (ACTIVE, INACTIVE, DELETED, AUTO) |
| `created_at` | TIMESTAMP | Record creation time |
| `created_by` | INT(11) | User who created the record |
| `updated_at` | TIMESTAMP | Last update time (auto-updated) |
| `updated_by` | INT(11) | User who last updated the record |

## Core System Tables

### 1. op_user
**Purpose:** User management and authentication

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| user_name | VARCHAR(255) | Username |
| password | VARCHAR(255) | Hashed password (Argon2ID) |
| email | VARCHAR(255) | Email address (indexed) |
| mobile | VARCHAR(20) | Mobile number (indexed) |
| user_type | VARCHAR(50) | Role (ADMIN, STAFF, USER, etc.) |
| photo | VARCHAR(255) | Profile photo path |
| ...standard columns... |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_email (email)
- INDEX idx_mobile (mobile)
- INDEX idx_user_type (user_type)
- INDEX idx_status (status)

---

### 2. op_table
**Purpose:** Registry of all dynamic tables in the system

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| table_id | VARCHAR(255) | Unique table identifier |
| table_name | VARCHAR(255) | Table name (indexed) |
| table_title | VARCHAR(255) | Display title |
| description | TEXT | Table description |
| icon | VARCHAR(100) | Icon class |
| ...standard columns... |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_table_name (table_name)
- INDEX idx_status (status)

---

### 3. op_master_table
**Purpose:** Column definitions for dynamic tables

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| table_name | VARCHAR(255) | Parent table name |
| column_name | VARCHAR(255) | Column name |
| column_title | VARCHAR(255) | Display title |
| input_type | VARCHAR(50) | Input type (Text, Email, Date, etc.) |
| data_source | VARCHAR(255) | Data source for dropdowns |
| is_required | VARCHAR(10) | YES/NO |
| is_unique | VARCHAR(10) | YES/NO |
| is_edit | VARCHAR(10) | Editable? YES/NO |
| show_in_table | VARCHAR(10) | Show in table? YES/NO |
| display_id | INT(11) | Display order |
| default_value | VARCHAR(255) | Default value |
| ...standard columns... |

**Supported Input Types:**
- Text, Multiline, RTF
- Email, Mobile, Whatsapp
- Date, Time, Datetime, Month, Week, Year
- Number, Rs (Currency)
- Photo, Image, Camera, Multi-Photo, Docs
- List-Static, List-Dynamic
- CheckList-Static, CheckList-Dynamic
- State, District, Block
- Status, Youtube, Link, Color

---

### 4. op_menu
**Purpose:** Dynamic menu system

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| type | VARCHAR(20) | MAIN or SUB |
| parent | INT(11) | Parent menu ID |
| menu_title | VARCHAR(255) | Menu text |
| menu_icon | VARCHAR(100) | Icon class |
| menu_link | VARCHAR(255) | Target URL |
| access_role | TEXT | Comma-separated roles |
| display_id | INT(11) | Display order |
| ...standard columns... |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_type (type)
- INDEX idx_parent (parent)
- INDEX idx_status (status)

---

### 5. op_role
**Purpose:** Table-level permissions for user roles

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| table_id | VARCHAR(255) | Table identifier |
| role_name | VARCHAR(50) | Role name |
| can_view | VARCHAR(10) | View permission (YES/NO) |
| can_add | VARCHAR(10) | Add permission |
| can_edit | VARCHAR(10) | Edit permission |
| can_delete | VARCHAR(10) | Delete permission |
| can_print | VARCHAR(10) | Print permission |
| can_import | VARCHAR(10) | Import permission |
| can_export | VARCHAR(10) | Export permission |
| ...standard columns... |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_table_id (table_id)
- INDEX idx_role_name (role_name)
- INDEX idx_status (status)

---

### 6. op_config
**Purpose:** Application configuration settings

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| option_type | ENUM('SINGLE','LIST') | Value type |
| option_name | VARCHAR(255) | Config key (indexed) |
| option_value | VARCHAR(2000) | Config value |
| default_value | VARCHAR(2000) | Default value |
| allow_edit | VARCHAR(10) | Can be edited? YES/NO |
| ...standard columns... |

**Common Configurations:**
- Application settings (name, logo, contact)
- Email settings (SMTP, sender)
- SMS settings (API key, sender ID)
- Payment gateway (Razorpay keys)
- Social media links
- System lists (gender, qualification, status)

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_option_name (option_name)
- INDEX idx_status (status)

---

### 7. op_log
**Purpose:** System activity and error logging

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| log_level | VARCHAR(20) | Level (ERROR, INFO, etc.) |
| log_message | TEXT | Log message |
| log_context | TEXT | Additional context (JSON) |
| user_id | INT(11) | Associated user |
| ip_address | VARCHAR(45) | IP address |
| user_agent | VARCHAR(255) | Browser info |
| url | VARCHAR(255) | Request URL |
| ...standard columns... |

---

### 8. op_migrations (New in v2.0)
**Purpose:** Track database migrations

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| migration | VARCHAR(255) | Migration filename |
| batch | INT(11) | Batch number |
| executed_at | TIMESTAMP | Execution time |

**Unique Constraint:** migration

---

### 9. op_api_tokens (New in v2.0)
**Purpose:** API authentication tokens

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| user_id | INT(11) | Token owner |
| token_name | VARCHAR(255) | Token description |
| token | VARCHAR(255) | Actual token (unique, indexed) |
| abilities | TEXT | Permissions (JSON) |
| last_used_at | TIMESTAMP | Last usage time |
| expires_at | TIMESTAMP | Expiration time |
| status | ENUM | ACTIVE, INACTIVE, REVOKED |
| ...standard columns... |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE KEY unique_token (token)
- INDEX idx_user_id (user_id)
- INDEX idx_status (status)

---

### 10. op_notifications (New in v2.0)
**Purpose:** User notification system

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| user_id | INT(11) | Recipient user |
| type | VARCHAR(50) | Notification type |
| title | VARCHAR(255) | Notification title |
| message | TEXT | Notification message |
| data | JSON | Additional data |
| read_at | TIMESTAMP | When read (NULL if unread) |
| ...standard columns... |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_user_id (user_id)
- INDEX idx_read_at (read_at)
- INDEX idx_created_at (created_at)

---

### 11. op_cache (New in v2.0)
**Purpose:** Application-level caching

| Column | Type | Description |
|--------|------|-------------|
| key | VARCHAR(255) | Cache key (primary) |
| value | LONGTEXT | Cached value |
| expiration | INT(11) | Unix timestamp expiration |

**Indexes:**
- PRIMARY KEY (key)
- INDEX idx_expiration (expiration)

---

### 12. op_performance (New in v2.0)
**Purpose:** Performance monitoring

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| endpoint | VARCHAR(255) | Request endpoint |
| method | VARCHAR(10) | HTTP method |
| execution_time | DECIMAL(10,4) | Execution time (seconds) |
| memory_usage | INT(11) | Memory used (bytes) |
| query_count | INT(11) | Number of queries |
| user_id | INT(11) | User ID |
| ip_address | VARCHAR(45) | IP address |
| created_at | TIMESTAMP | Request time |

**Indexes:**
- PRIMARY KEY (id)
- INDEX idx_endpoint (endpoint)
- INDEX idx_created_at (created_at)

---

### 13. op_msg
**Purpose:** Messaging/communication system

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| from_id | INT(11) | Sender user ID |
| to_id | INT(11) | Recipient user ID |
| subject | VARCHAR(255) | Message subject |
| message | TEXT | Message content |
| is_read | VARCHAR(10) | Read status |
| ...standard columns... |

---

### 14. op_sdb
**Purpose:** State/District/Block data for Indian locations

| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| state | VARCHAR(100) | State name |
| district | VARCHAR(100) | District name |
| block | VARCHAR(100) | Block name |
| ...standard columns... |

---

## Schema Patterns

### 1. Soft Delete Pattern
All tables use `status` column for soft deletes:
- `ACTIVE` - Normal record
- `INACTIVE` - Temporarily disabled
- `DELETED` - Soft deleted (can be restored)
- `AUTO` - Auto-generated placeholder

### 2. Audit Trail Pattern
Every record tracks:
- Who created it (`created_by`)
- When it was created (`created_at`)
- Who last modified it (`updated_by`)
- When it was last modified (`updated_at`)

### 3. Status Enum Pattern (v2.0 Optimization)
Tables with limited status values use ENUM instead of VARCHAR for better performance:
```sql
status ENUM('ACTIVE', 'INACTIVE', 'DELETED') DEFAULT 'ACTIVE'
```

### 4. Indexing Strategy
- All foreign keys are indexed
- Status columns are indexed for filtering
- Frequently searched columns (email, mobile) are indexed
- Compound indexes for common query patterns

---

## Database Performance Optimizations

### Indexes Added in v2.0
1. **op_user:** email, mobile, user_type, status
2. **op_table:** table_name, status
3. **op_menu:** type, parent, status
4. **op_role:** table_id, role_name, status
5. **op_config:** option_name, status

### Data Type Optimizations
- ENUM for limited value sets (reduces storage by 50-75%)
- JSON for flexible data structures (op_notifications)
- Proper VARCHAR lengths (prevents waste)
- UTF8MB4 for full Unicode support (emojis, multilingual)

---

## Migration Best Practices

### Creating New Tables
Always use the standard structure:
```php
php system/migrate.php create add_new_table create_table
```

This creates a table with:
- Auto-increment ID
- Standard status column
- Created/Updated tracking
- Proper charset (utf8mb4)

### Modifying Existing Tables
```php
php system/migrate.php create modify_users_table alter_table
```

Always provide both up() and down() methods for rollback support.

---

## Backup & Restore

### Creating Backup
```php
// Using built-in function
create_backup(); // Creates SQL dump in backup/ folder
```

### Restoring Backup
```bash
mysql -u root -p opex < backup/db-backup-YYMMDD.sql
```

---

## Security Considerations

### Sensitive Data
- Passwords: Always hashed with Argon2ID
- API keys: Stored in environment variables, not database
- Personal data: Encrypted when necessary
- Credit card data: Never stored (use payment gateway)

### Access Control
- Role-based permissions in `op_role` table
- Table-level and record-level security
- Audit trail for compliance

### SQL Injection Prevention
- All queries use PDO prepared statements
- No string concatenation in queries
- Input validation before database operations

---

**Last Updated:** 2026-01-16  
**Schema Version:** 2.0  
**Database Engine:** InnoDB  
**Charset:** utf8mb4_unicode_ci
