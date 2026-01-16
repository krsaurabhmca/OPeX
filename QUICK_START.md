# OPeX v2.0 - Quick Start Guide

## 🚀 Getting Started in 5 Minutes

This guide will help you quickly start using the improved OPeX platform with all the new security and performance features.

---

## Step 1: Setup Environment (2 minutes)

### 1.1 Create .env File

Rename `env.example.txt` to `.env`:

```bash
copy env.example.txt .env
```

### 1.2 Configure Database

Edit `.env` file:

```env
DB_HOST=localhost
DB_DATABASE=opex
DB_USERNAME=root
DB_PASSWORD=your_password

APP_URL=http://localhost/opex/
```

### 1.3 Generate Security Keys

Generate random 32-character strings for:

```env
SECRET_KEY=your-random-32-char-secret-key
ENCRYPTION_KEY=your-random-32-char-encryption-key
```

**Quick generator (PHP):**
```php
<?php
echo bin2hex(random_bytes(16)); // Run twice for two keys
?>
```

---

## Step 2: Run Database Migration (1 minute)

### 2.1 Check Migration Status

```bash
cd system
php migrate.php status
```

### 2.2 Run Migrations

```bash
php migrate.php migrate
```

This will:
- Create migration tracking table
- Add indexes to existing tables
- Create new system tables (api_tokens, notifications, cache)

---

## Step 3: Start Using New Functions (2 minutes)

### 3.1 Basic CRUD Operations (Secure)

Create a new PHP file: `test_v2.php`

```php
<?php
require_once 'system/op_lib_v2.php';

// INSERT - Secure with PDO
$result = insert_data_v2('users', [
    'user_name' => 'johndoe',
    'email' => 'john@example.com',
    'password' => hash_password('MySecurePass123!'),
    'mobile' => '9876543210',
    'user_type' => 'USER'
]);

if ($result['status'] === 'success') {
    echo "User created! ID: " . $result['id'] . "\n";
}

// GET - Fetch user
$user = get_data_v2('users', $result['id']);
echo "User: " . $user['data']['user_name'] . "\n";

// UPDATE - Update user
update_data_v2('users', [
    'user_name' => 'john_updated'
], $result['id']);

// DELETE - Soft delete
remove_data_v2('users', $result['id']);
```

### 3.2 Input Validation

```php
<?php
// Validate form data
$validation = validate_input($_POST, [
    'email' => 'required|email|unique:op_user',
    'password' => 'required|strong_password|confirmed',
    'mobile' => 'required|mobile',
    'user_name' => 'required|min:3|max:50'
]);

if (!$validation['valid']) {
    // Show errors
    foreach ($validation['errors'] as $field => $errors) {
        foreach ($errors as $error) {
            echo "Error: $error\n";
        }
    }
} else {
    // Process data
    $data = $_POST;
    $data['password'] = hash_password($_POST['password']);
    insert_data_v2('users', $data);
}
```

### 3.3 CSRF Protection in Forms

```php
<!-- In your HTML form -->
<form method="POST" action="process.php">
    <?php echo csrf_field(); ?>
    
    <input type="text" name="username" required>
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Register</button>
</form>

<!-- In process.php -->
<?php
require_once 'system/op_lib_v2.php';

// Verify CSRF token
if (!verify_csrf()) {
    die('Security check failed. Please try again.');
}

// Process form...
?>
```

---

## Step 4: Use the API (Bonus)

### 4.1 Login via API

```bash
curl -X POST http://localhost/opex/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "admin_password"
  }'
```

**Response:**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "token": "your_api_token_here",
    "user": {
      "id": 1,
      "user_name": "admin",
      "email": "admin@example.com"
    }
  }
}
```

### 4.2 Use API Token

```bash
curl -X GET http://localhost/opex/api/users \
  -H "Authorization: Bearer your_api_token_here"
```

---

## Common Tasks Cheat Sheet

### Create a New Table

```bash
php system/migrate.php create create_products_table create_table
```

Edit the migration file in `database/migrations/`, then:

```bash
php system/migrate.php migrate
```

### Add a Record

```php
<?php
$result = insert_data_v2('products', [
    'product_name' => 'Laptop',
    'price' => 45000,
    'category' => 'Electronics',
    'status' => 'ACTIVE'
]);
?>
```

### Fetch Records

```php
<?php
// Get all active products
$products = get_all_v2('products', '*', ['status' => 'ACTIVE']);

foreach ($products['data'] as $product) {
    echo $product['product_name'] . ': ₹' . $product['price'] . "\n";
}

// Get single product
$product = get_data_v2('products', 1);
?>
```

### Update a Record

```php
<?php
update_data_v2('products', [
    'price' => 42000,
    'stock' => 50
], 1); // Update product with ID 1
?>
```

### Delete a Record

```php
<?php
// Soft delete (can be restored)
remove_data_v2('products', 1);

// Hard delete (permanent - admin only)
delete_data_v2('products', 1);
?>
```

### Log Activity

```php
<?php
// Log different levels
Logger::info('User logged in', ['user_id' => 1]);
Logger::error('Payment failed', ['order_id' => 123, 'error' => 'Timeout']);
Logger::warning('Low stock alert', ['product_id' => 5]);

// Log user activity
log_activity('Product added', ['product_id' => 10, 'name' => 'Laptop']);
?>
```

---

## Security Checklist

✅ **Passwords**
```php
// ALWAYS hash passwords
$hashed = hash_password('user_password');

// NEVER store plain text
// ❌ insert_data_v2('users', ['password' => 'plain_password']);
// ✅ insert_data_v2('users', ['password' => hash_password('plain_password')]);
```

✅ **CSRF Protection**
```php
// ALWAYS include in forms
echo csrf_field();

// ALWAYS verify on submission
if (!verify_csrf()) die('Invalid request');
```

✅ **Input Validation**
```php
// ALWAYS validate user input
$validation = validate_input($_POST, $rules);
if (!$validation['valid']) {
    // Handle errors
}
```

✅ **SQL Injection Prevention**
```php
// ALWAYS use V2 functions (they use PDO prepared statements)
// ❌ mysqli_query($con, "SELECT * FROM users WHERE id = $id");
// ✅ get_data_v2('users', $id);
```

✅ **XSS Prevention**
```php
// ALWAYS escape output
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// OR use the helper
echo xss_clean_v2($user_input);
```

---

## Performance Tips

### 1. Use Indexes
```php
// Create migration to add index
$db->execute("ALTER TABLE products ADD INDEX idx_category (category)");
```

### 2. Cache Configuration
```php
// Cache is automatic for op_config table
$value = get_config('app_name'); // Uses cache
```

### 3. Limit Results
```php
// Instead of getting all records
$all = get_all_v2('products'); // Could be thousands

// Use WHERE clause
$active = get_all_v2('products', '*', ['status' => 'ACTIVE']);
```

### 4. Connection Pooling
```php
// Already enabled! PDO uses persistent connections
// No action needed
```

---

## Debugging

### Enable Debug Mode

In `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### View Logs

```bash
# View today's log
type system\logs\app-2026-01-16.log

# Watch logs in real-time (Windows PowerShell)
Get-Content system\logs\app-2026-01-16.log -Wait
```

### Check Database Logs

```php
<?php
// Query recent errors
$logs = get_all_v2('op_log', '*', ['log_level' => 'ERROR'], 'id DESC');
foreach ($logs['data'] as $log) {
    echo $log['log_message'] . "\n";
}
?>
```

---

## Migration Commands Reference

```bash
# Create migration
php migrate.php create migration_name [type]
# Types: create_table, alter_table, custom

# Run migrations
php migrate.php migrate

# Rollback last migration batch
php migrate.php rollback

# Rollback multiple batches
php migrate.php rollback --steps=3

# Check status
php migrate.php status

# Examples
php migrate.php create add_products_table create_table
php migrate.php create add_category_column alter_table
```

---

## API Endpoints Quick Reference

### Authentication
```
POST   /api/auth/login       - Login
POST   /api/auth/logout      - Logout
POST   /api/auth/refresh     - Refresh token
```

### Users
```
GET    /api/users            - List all users
GET    /api/users/{id}       - Get user
POST   /api/users            - Create user
PUT    /api/users/{id}       - Update user
DELETE /api/users/{id}       - Delete user
```

### Generic Data Operations
```
GET    /api/data/{table}           - Get all records
GET    /api/data/{table}/{id}      - Get one record
POST   /api/data/{table}           - Insert record
PUT    /api/data/{table}/{id}      - Update record
DELETE /api/data/{table}/{id}      - Delete record
```

### System
```
GET    /api/tables           - List all tables
GET    /api/config           - Get configuration
```

---

## Helpful Validation Rules

```php
$rules = [
    // Required field
    'field' => 'required',
    
    // Email validation
    'email' => 'required|email|unique:op_user',
    
    // String length
    'name' => 'required|min:3|max:50',
    
    // Numeric
    'age' => 'required|numeric',
    'quantity' => 'required|integer',
    
    // Mobile (Indian)
    'mobile' => 'required|mobile',
    
    // Strong password
    'password' => 'required|strong_password|confirmed',
    
    // Date
    'dob' => 'required|date',
    'start_date' => 'required|date_format:Y-m-d',
    
    // URL
    'website' => 'url',
    
    // In array
    'status' => 'required|in:ACTIVE,INACTIVE',
    
    // Alphanumeric
    'username' => 'required|alphanumeric|min:4',
    
    // Exists in database
    'user_id' => 'required|exists:op_user,id'
];
```

---

## Need Help?

### Documentation
- [README.md](README.md) - Complete documentation
- [IMPROVEMENTS_SUMMARY.md](IMPROVEMENTS_SUMMARY.md) - What changed in v2.0
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) - Database structure

### Support
- **Email:** ask@offerplant.com
- **Phone:** +91-9431426600
- **Website:** http://offerplant.com

### Common Issues

**Issue:** Database connection failed  
**Solution:** Check `.env` database credentials

**Issue:** CSRF validation failed  
**Solution:** Ensure `csrf_field()` is in form and session is started

**Issue:** Migration not running  
**Solution:** Check file permissions on `database/migrations/` folder

**Issue:** API 401 Unauthorized  
**Solution:** Include `Authorization: Bearer {token}` header

---

## Next Steps

1. ✅ Explore the existing code in `system/op_lib_v2.php`
2. ✅ Create your first migration
3. ✅ Build a simple CRUD module
4. ✅ Test the API endpoints
5. ✅ Read the complete README.md

**Happy Coding! 🚀**

---

**OPeX Platform v2.0**  
*By OfferPlant Technologies*
