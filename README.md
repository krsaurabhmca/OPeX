# OPeX - Generic Application Development Platform v2.0

A powerful, secure, and flexible PHP platform for building any type of web application with built-in CRUD operations, role-based access control, and dynamic form generation.

## 🌟 Features

### Core Features
- ✅ **Complete CRUD Operations** with PDO prepared statements
- ✅ **Role-Based Access Control (RBAC)** - Fine-grained permissions
- ✅ **Dynamic Form & Table Generation** - Auto-generate UI from database schema
- ✅ **Secure Authentication** - Password hashing, session management
- ✅ **File Upload Management** - Images, documents with validation
- ✅ **Database Migration System** - Version control for your database
- ✅ **Comprehensive Logging** - Activity logs, error tracking
- ✅ **API Support** - RESTful API with token authentication
- ✅ **Multi-Channel Communication** - Email, SMS, WhatsApp integration
- ✅ **Data Import/Export** - CSV, Excel support
- ✅ **Backup & Restore** - Automated database backups
- ✅ **Search & Filtering** - Advanced data filtering
- ✅ **Responsive UI** - Bootstrap-based responsive design

### Security Features
- 🔒 SQL Injection Protection (Prepared Statements)
- 🔒 XSS Prevention
- 🔒 CSRF Token Protection
- 🔒 Password Hashing (Argon2ID)
- 🔒 Secure File Uploads
- 🔒 Input Validation & Sanitization
- 🔒 Session Security

## 📋 Requirements

- PHP 7.4 or higher (PHP 8.x recommended)
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx web server
- PDO MySQL extension
- OpenSSL extension
- GD Library (for image processing)
- Composer (for dependencies)

## 🚀 Installation

### 1. Clone or Download

```bash
git clone https://github.com/offerplant/opex.git
cd opex
```

### 2. Configure Environment

Copy the environment example file:

```bash
copy env.example.txt .env
```

Edit `.env` file with your configuration:

```env
# Database Configuration
DB_HOST=localhost
DB_DATABASE=opex
DB_USERNAME=root
DB_PASSWORD=your_password

# Application URL
APP_URL=http://localhost/opex/

# Security Keys (generate random 32-character strings)
SECRET_KEY=your-secret-key-here
ENCRYPTION_KEY=your-encryption-key-here
```

### 3. Create Database

Create a new MySQL database:

```sql
CREATE DATABASE opex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import the base schema:

```bash
mysql -u root -p opex < system/opex_db.sql
```

### 4. Run Migrations

Run database migrations to set up improved schema:

```bash
php system/migrate.php
```

### 5. Set Permissions

Set proper permissions for upload and log directories:

```bash
chmod 755 upload/
chmod 755 system/logs/
```

### 6. Access Application

Open your browser and navigate to:

```
http://localhost/opex/
```

Default login credentials:
- **Username:** admin
- **Password:** (check the database)

## 📖 Usage Guide

### Basic Operations

#### Insert Data

```php
<?php
require_once 'system/op_lib_v2.php';

// Using new V2 functions (secure with PDO)
$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'mobile' => '9876543210'
];

$result = insert_data_v2('users', $data);

if ($result['status'] === 'success') {
    echo "User created with ID: " . $result['id'];
}
```

#### Update Data

```php
<?php
$data = [
    'name' => 'John Updated',
    'email' => 'john.new@example.com'
];

$result = update_data_v2('users', $data, 1); // Update user with ID 1
```

#### Get Data

```php
<?php
// Get single record
$user = get_data_v2('users', 1);
echo $user['data']['name'];

// Get all records
$users = get_all_v2('users', '*', ['status' => 'ACTIVE']);
foreach ($users['data'] as $user) {
    echo $user['name'];
}
```

#### Delete Data

```php
<?php
// Soft delete (mark as DELETED)
remove_data_v2('users', 1);

// Hard delete (permanent - admin only)
delete_data_v2('users', 1);
```

### Security Functions

#### Password Management

```php
<?php
// Hash password
$hashedPassword = hash_password('user_password_123');

// Verify password
if (verify_password('user_password_123', $hashedPassword)) {
    echo "Password is correct!";
}
```

#### CSRF Protection

```php
<!-- In your form -->
<form method="POST" action="process.php">
    <?php echo csrf_field(); ?>
    <input type="text" name="username">
    <button type="submit">Submit</button>
</form>

<?php
// In process.php
if (!verify_csrf()) {
    die('CSRF token validation failed');
}
// Process form...
?>
```

#### Input Validation

```php
<?php
$rules = [
    'email' => 'required|email',
    'name' => 'required|min:3|max:50',
    'mobile' => 'required|mobile',
    'password' => 'required|strong_password|confirmed'
];

$validation = validate_input($_POST, $rules);

if (!$validation['valid']) {
    // Show errors
    print_r($validation['errors']);
} else {
    // Process data
}
```

### Database Migrations

#### Create Migration

```bash
php system/migrate.php create add_columns_to_users create_table
```

#### Run Migrations

```bash
php system/migrate.php migrate
```

#### Rollback Migrations

```bash
php system/migrate.php rollback
```

#### Check Migration Status

```bash
php system/migrate.php status
```

### Dynamic Form Generation

```php
<?php
// Generate form automatically from database schema
$form = create_form('users', $id, 'yes', 'master_update_data');
echo $form;
```

### Dynamic Table Generation

```php
<?php
// Generate data table
$data = get_all_v2('users', '*', ['status' => 'ACTIVE']);
$buttons = [
    'btn_edit' => 'users_add',
    'btn_view' => '',
    'btn_delete' => ''
];
$table = create_data_table('users', $data, $buttons);
echo $table;
```

## 🏗️ Architecture

### Directory Structure

```
opex/
├── system/              # Core system files
│   ├── Config.php      # Configuration management
│   ├── Database.php    # PDO database wrapper
│   ├── Security.php    # Security functions
│   ├── Validator.php   # Input validation
│   ├── Logger.php      # Logging system
│   ├── Response.php    # Response handlers
│   ├── Migration.php   # Migration system
│   ├── bootstrap.php   # Application bootstrap
│   ├── op_lib.php      # Legacy functions
│   └── op_lib_v2.php   # Improved functions
├── database/
│   └── migrations/     # Database migrations
├── upload/             # File uploads
├── backup/             # Database backups
├── public/             # Public assets
├── .env                # Environment config
└── index.php           # Entry point
```

### New OOP Classes

1. **Config** - Environment configuration management
2. **Database** - PDO wrapper with prepared statements
3. **Security** - Encryption, CSRF, XSS protection
4. **Validator** - Input validation
5. **Logger** - Application and error logging
6. **Response** - API response formatting
7. **Migration** - Database schema versioning

## 🔧 Configuration

### Environment Variables

All configuration is done through the `.env` file:

```env
# Application
APP_NAME=OPeX
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com/

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=opex
DB_USERNAME=root
DB_PASSWORD=

# Security
SECRET_KEY=your-32-char-secret-key
ENCRYPTION_KEY=your-32-char-encryption-key

# Email
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password

# SMS (Fast2SMS)
SMS_AUTH_KEY=your-fast2sms-auth-key

# Payment (Razorpay)
RAZORPAY_KEY_ID=your-key-id
RAZORPAY_KEY_SECRET=your-key-secret
```

## 📊 Database Schema

### Core Tables

1. **op_user** - User management
2. **op_role** - Role-based permissions
3. **op_table** - Dynamic table registry
4. **op_master_table** - Column definitions
5. **op_menu** - Dynamic menu system
6. **op_config** - Application settings
7. **op_log** - Activity logs
8. **op_migrations** - Migration tracking
9. **op_api_tokens** - API authentication
10. **op_notifications** - Notification system

### Standard Columns

Every table includes:
- `id` - Auto-increment primary key
- `status` - Record status (ACTIVE, INACTIVE, DELETED)
- `created_at` - Creation timestamp
- `created_by` - Creator user ID
- `updated_at` - Last update timestamp
- `updated_by` - Last updater user ID

## 🔐 Security Best Practices

### 1. Environment Configuration
Never commit `.env` file to version control. Use strong, unique keys.

### 2. Password Security
Always use `hash_password()` for storing passwords.

### 3. SQL Injection Prevention
Use V2 functions that implement PDO prepared statements.

### 4. CSRF Protection
Always include CSRF tokens in forms and verify on submission.

### 5. File Upload Security
Validate file types, rename files, store outside web root when possible.

### 6. Access Control
Always check user permissions before allowing operations.

## 📝 API Documentation

### Authentication

```php
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}

Response:
{
    "status": "success",
    "data": {
        "token": "api_token_here",
        "user": {...}
    }
}
```

### CRUD Operations

```php
// Create
POST /api/users
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com"
}

// Read
GET /api/users
GET /api/users/1

// Update
PUT /api/users/1
{
    "name": "John Updated"
}

// Delete
DELETE /api/users/1
```

## 🛠️ Development

### Running Migrations

```bash
# Create new migration
php system/migrate.php create migration_name

# Run pending migrations
php system/migrate.php migrate

# Rollback last batch
php system/migrate.php rollback

# Check migration status
php system/migrate.php status
```

### Logging

```php
<?php
// Log messages
Logger::debug('Debug message', ['data' => $debug_data]);
Logger::info('Info message');
Logger::warning('Warning message');
Logger::error('Error message', ['error' => $error]);

// Log user activity
log_activity('User logged in', ['user_id' => $userId]);
```

## 📈 Performance Optimization

1. **Database Indexing** - Indexes added on frequently queried columns
2. **Connection Pooling** - PDO persistent connections
3. **Query Optimization** - Prepared statements, efficient queries
4. **Caching** - Built-in cache table for application data
5. **Lazy Loading** - Load resources only when needed

## 🧪 Testing

Run the test suite:

```bash
php system/test.php
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is proprietary software developed by OfferPlant Technologies Private Limited.

## 👥 Support

- **Email:** ask@offerplant.com
- **Phone:** +91-9431426600
- **Website:** http://offerplant.com

## 🔄 Version History

### Version 2.0 (Current)
- ✅ Migrated to PDO with prepared statements
- ✅ Added OOP architecture
- ✅ Implemented comprehensive security
- ✅ Added database migration system
- ✅ Improved logging and error handling
- ✅ Added API support
- ✅ Environment-based configuration

### Version 1.0
- Initial release with mysqli
- Basic CRUD operations
- Role-based access control
- Dynamic form generation

## 🙏 Acknowledgments

- Bootstrap for UI framework
- PHPMailer for email functionality
- DataTables for table management
- All contributors and users of OPeX

---

**Made with ❤️ by OfferPlant Technologies**
