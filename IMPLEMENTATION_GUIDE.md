# OPeX v2.0 - Implementation Guide

## 📦 What Has Been Created

### Core System Files (system/)

| File | Lines | Purpose |
|------|-------|---------|
| **Config.php** | 260 | Environment configuration management (.env support) |
| **Database.php** | 420 | PDO wrapper with prepared statements & security |
| **Security.php** | 320 | Encryption, CSRF, XSS protection, password hashing |
| **Validator.php** | 340 | Comprehensive input validation (20+ rules) |
| **Logger.php** | 180 | Application logging (file & database) |
| **Response.php** | 160 | API response formatting & redirects |
| **Migration.php** | 350 | Database migration system |
| **bootstrap.php** | 200 | Application initialization |
| **op_lib_v2.php** | 450 | Improved functions using new classes |
| **migrate.php** | 110 | CLI migration runner |

### API Layer (api/)

| File | Lines | Purpose |
|------|-------|---------|
| **index.php** | 30 | API entry point |
| **ApiController.php** | 550 | RESTful API controller |
| **.htaccess** | 10 | API routing configuration |

### Database Migrations (database/migrations/)

| File | Purpose |
|------|---------|
| **2024_01_01_000001_create_improved_schema.php** | Database improvements migration |

### Documentation

| File | Purpose |
|------|---------|
| **README.md** | Complete platform documentation |
| **QUICK_START.md** | 5-minute getting started guide |
| **IMPROVEMENTS_SUMMARY.md** | Detailed improvements list |
| **DATABASE_SCHEMA.md** | Database schema documentation |
| **IMPLEMENTATION_GUIDE.md** | This file |
| **env.example.txt** | Environment configuration template |

### Configuration Files

| File | Purpose |
|------|---------|
| **.gitignore** | Git ignore rules (protects sensitive files) |
| **env.example.txt** | Environment variables template |

---

## 🎯 Implementation Steps

### Phase 1: Initial Setup (10 minutes)

#### 1. Create Environment File
```bash
# Copy environment template
copy env.example.txt .env
```

#### 2. Configure .env
Edit `.env` with your settings:
```env
# Database
DB_HOST=localhost
DB_DATABASE=opex
DB_USERNAME=root
DB_PASSWORD=your_password

# Application
APP_URL=http://localhost/opex/
APP_DEBUG=true

# Generate these (32 characters each)
SECRET_KEY=generate-random-32-char-string
ENCRYPTION_KEY=generate-random-32-char-string
```

**Generate secure keys:**
```php
<?php
// Run this twice to get two different keys
echo bin2hex(random_bytes(16)) . "\n";
?>
```

#### 3. Run Database Migrations
```bash
cd system
php migrate.php migrate
```

This creates:
- Migration tracking table
- API tokens table
- Notifications table
- Cache table
- Performance monitoring table
- Adds indexes to existing tables

---

### Phase 2: Test New Features (15 minutes)

#### 1. Test Secure Database Operations

Create `test_new_features.php`:

```php
<?php
require_once 'system/op_lib_v2.php';

echo "Testing OPeX v2.0 Features\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Secure Insert
echo "1. Testing Secure Insert...\n";
$result = insert_data_v2('op_user', [
    'user_name' => 'testuser',
    'email' => 'test@example.com',
    'password' => hash_password('TestPass123!'),
    'mobile' => '9876543210',
    'user_type' => 'USER'
]);

if ($result['status'] === 'success') {
    echo "   ✓ User created with ID: " . $result['id'] . "\n";
    $testUserId = $result['id'];
} else {
    echo "   ✗ Failed: " . $result['msg'] . "\n";
}

// Test 2: Secure Fetch
echo "\n2. Testing Secure Fetch...\n";
$user = get_data_v2('op_user', $testUserId);
if ($user['count'] > 0) {
    echo "   ✓ User fetched: " . $user['data']['user_name'] . "\n";
}

// Test 3: Input Validation
echo "\n3. Testing Input Validation...\n";
$testData = [
    'email' => 'invalid-email',
    'password' => 'weak',
    'mobile' => '123'
];

$validation = validate_input($testData, [
    'email' => 'required|email',
    'password' => 'required|strong_password',
    'mobile' => 'required|mobile'
]);

if (!$validation['valid']) {
    echo "   ✓ Validation working! Errors found:\n";
    foreach ($validation['errors'] as $field => $errors) {
        echo "     - $field: " . implode(', ', $errors) . "\n";
    }
}

// Test 4: Encryption
echo "\n4. Testing Encryption...\n";
$secret = "My secret data";
$encrypted = encrypt_v2($secret);
$decrypted = decrypt_v2($encrypted);
echo "   Original: $secret\n";
echo "   Encrypted: " . substr($encrypted, 0, 30) . "...\n";
echo "   Decrypted: $decrypted\n";
echo "   ✓ Encryption " . ($secret === $decrypted ? "working!" : "failed!") . "\n";

// Test 5: Logging
echo "\n5. Testing Logging...\n";
Logger::info('Test log entry', ['test' => 'data']);
echo "   ✓ Log written to system/logs/\n";

// Test 6: Clean up
echo "\n6. Cleaning up...\n";
remove_data_v2('op_user', $testUserId);
echo "   ✓ Test user removed\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "All tests completed!\n";
?>
```

Run it:
```bash
php test_new_features.php
```

#### 2. Test API

**Login:**
```bash
curl -X POST http://localhost/opex/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@example.com\",\"password\":\"your_password\"}"
```

**Use API (replace TOKEN):**
```bash
curl -X GET http://localhost/opex/api/users ^
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

### Phase 3: Gradual Migration (Ongoing)

#### Strategy: Side-by-Side Approach

Both old and new functions work together:

**Option 1: Use new functions for new code**
```php
<?php
// New code - use V2 functions
$result = insert_data_v2('products', $data);
$products = get_all_v2('products');
```

**Option 2: Gradually replace old code**
```php
<?php
// Old code (still works)
// $result = insert_data('products', $data);

// New code (better)
$result = insert_data_v2('products', $data);
```

**Both methods work!** No rush to change everything.

---

## 🔧 Configuration Options

### Environment Variables

#### Application Settings
```env
APP_NAME=OPeX
APP_ENV=production          # or development
APP_DEBUG=false             # true in development
APP_URL=https://yourdomain.com/
```

#### Database
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=opex
DB_USERNAME=root
DB_PASSWORD=secure_password
```

#### Security
```env
SECRET_KEY=your-32-char-key
ENCRYPTION_KEY=your-32-char-key
CSRF_TOKEN_EXPIRE=3600      # 1 hour
```

#### Email (SMTP)
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

#### SMS (Fast2SMS)
```env
SMS_AUTH_KEY=your-fast2sms-key
SMS_SENDER_ID=OPEXIN
```

#### Payment (Razorpay)
```env
RAZORPAY_KEY_ID=rzp_live_xxx
RAZORPAY_KEY_SECRET=your_secret
```

---

## 📊 New Capabilities

### 1. Secure Database Operations
- ✅ SQL injection protection (PDO prepared statements)
- ✅ Automatic created_by/updated_by tracking
- ✅ Transaction support
- ✅ Connection pooling

### 2. Enhanced Security
- ✅ Argon2ID password hashing
- ✅ CSRF token protection
- ✅ XSS prevention
- ✅ AES-256 encryption
- ✅ Secure file uploads

### 3. Input Validation
- ✅ 20+ validation rules
- ✅ Custom error messages
- ✅ Database validation (unique, exists)
- ✅ Mobile/email validation

### 4. Logging System
- ✅ File logging (daily rotation)
- ✅ Database logging
- ✅ 8 log levels
- ✅ Activity tracking

### 5. RESTful API
- ✅ Token authentication
- ✅ CRUD endpoints
- ✅ Pagination
- ✅ JSON responses

### 6. Database Migrations
- ✅ Version control for schema
- ✅ Up/down migrations
- ✅ Batch rollbacks
- ✅ CLI tool

---

## 🎓 Learning Path

### Day 1: Basics
1. Read QUICK_START.md
2. Run test_new_features.php
3. Create your first migration
4. Test API with Postman/curl

### Day 2: Security
1. Implement CSRF in your forms
2. Add password hashing to login
3. Validate all user inputs
4. Enable logging

### Day 3: Advanced
1. Create custom validation rules
2. Build API endpoints for your tables
3. Implement role-based API access
4. Set up automated backups

### Week 2: Production
1. Disable debug mode
2. Set up HTTPS
3. Configure email/SMS
4. Monitor logs
5. Optimize database

---

## 🚨 Important Security Reminders

### 1. Never Commit .env
The `.gitignore` file protects you, but verify:
```bash
git status
# .env should NOT appear
```

### 2. Always Hash Passwords
```php
// ❌ WRONG
insert_data_v2('users', ['password' => $_POST['password']]);

// ✅ CORRECT
insert_data_v2('users', ['password' => hash_password($_POST['password'])]);
```

### 3. Always Use CSRF Protection
```php
// In form
<?php echo csrf_field(); ?>

// In processor
if (!verify_csrf()) die('Invalid request');
```

### 4. Always Validate Input
```php
$validation = validate_input($_POST, $rules);
if (!$validation['valid']) {
    // Handle errors
}
```

### 5. Use V2 Functions for New Code
```php
// These use PDO prepared statements (secure)
insert_data_v2()
update_data_v2()
get_data_v2()
get_all_v2()
remove_data_v2()
delete_data_v2()
```

---

## 📈 Performance Checklist

- ✅ Enable connection pooling (already enabled)
- ✅ Add indexes on frequently queried columns
- ✅ Use WHERE clauses to limit results
- ✅ Cache configuration data (automatic)
- ✅ Monitor slow queries in logs
- ✅ Use ENUM for limited value sets
- ✅ Regular database optimization

---

## 🔍 Troubleshooting

### Issue: "Database connection failed"
**Solution:** Check .env database credentials

### Issue: "CSRF validation failed"
**Solution:** 
1. Ensure session_start() is called
2. Add csrf_field() to form
3. Verify form method is POST

### Issue: "Class 'Database' not found"
**Solution:** Include bootstrap.php or op_lib_v2.php

### Issue: "Migration failed"
**Solution:**
1. Check database permissions
2. Verify migration syntax
3. Check logs in system/logs/

### Issue: "API returns 401"
**Solution:** 
1. Login to get token
2. Add Authorization header: `Bearer {token}`

---

## 📞 Getting Help

### Documentation
- **Quick Start:** QUICK_START.md
- **Full Docs:** README.md
- **Database:** DATABASE_SCHEMA.md
- **Changes:** IMPROVEMENTS_SUMMARY.md

### Support
- **Email:** ask@offerplant.com
- **Phone:** +91-9431426600
- **Website:** http://offerplant.com

### Logs
Check system/logs/ for errors:
```bash
type system\logs\app-2026-01-16.log
```

---

## ✅ Pre-Production Checklist

Before going live:

- [ ] Set `APP_ENV=production` in .env
- [ ] Set `APP_DEBUG=false` in .env
- [ ] Generate strong SECRET_KEY and ENCRYPTION_KEY
- [ ] Configure HTTPS
- [ ] Set up automated backups
- [ ] Configure email/SMS services
- [ ] Test all API endpoints
- [ ] Verify CSRF protection on all forms
- [ ] Check all passwords are hashed
- [ ] Review user permissions
- [ ] Set up monitoring/logging
- [ ] Optimize database (indexes, cleanup)
- [ ] Test payment gateway (if used)
- [ ] Create admin user
- [ ] Document custom configurations

---

## 🎉 Success Indicators

You'll know the implementation is successful when:

1. ✅ All migrations run without errors
2. ✅ test_new_features.php passes all tests
3. ✅ API login returns a token
4. ✅ Forms submit with CSRF protection
5. ✅ Logs appear in system/logs/
6. ✅ Passwords are hashed in database
7. ✅ No SQL injection vulnerabilities
8. ✅ Input validation catches bad data

---

## 🚀 Next Steps

1. **Immediate:**
   - Run migrations
   - Test new features
   - Read QUICK_START.md

2. **This Week:**
   - Migrate one module to V2 functions
   - Implement CSRF on forms
   - Set up API authentication

3. **This Month:**
   - Complete migration to V2
   - Build new features using API
   - Optimize database
   - Set up monitoring

---

**Congratulations on upgrading to OPeX v2.0!** 🎊

Your platform is now:
- ✅ Secure (SQL injection, XSS, CSRF protected)
- ✅ Modern (PDO, OOP, PSR standards)
- ✅ Scalable (API-ready, cached, indexed)
- ✅ Maintainable (migrations, logging, validation)

**Happy Building!**

---

*OPeX Platform v2.0 - By OfferPlant Technologies*
