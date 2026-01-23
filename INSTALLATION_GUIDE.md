# Chwezi Server Manager - Installation & Testing Guide

## Phase 1: Authentication & RBAC - Complete Implementation

This guide will help you install and test the Chwezi Server Manager on your local development environment (Windows/WAMP) or production Ubuntu server.

---

## Prerequisites

### For Local Development (Windows/WAMP)
- WAMP Server installed (PHP 8.2+, MySQL 8.0+, Apache 2.4+)
- Git for Windows
- Text editor (VS Code recommended)

### For Production (Ubuntu)
- Ubuntu 22.04 or 24.04 LTS
- Root or sudo access
- Domain name pointed to server (optional for Phase 1)

---

## Installation Steps

### Option 1: Local Development (Windows/WAMP)

1. **Clone Repository**
   ```bash
   cd C:\wamp64\www
   git clone <repository-url> server-manager
   cd server-manager
   ```

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create database: `chwezi_sm`
   - Or use command line:
   ```bash
   mysql -u root -p
   CREATE DATABASE chwezi_sm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

3. **Run Database Migrations**
   ```bash
   mysql -u root -p chwezi_sm < database/migrations/001_create_auth_tables.sql
   ```

4. **Create .env File**
   ```bash
   copy .env.example .env
   ```

   Edit `.env` with your settings:
   ```env
   APP_NAME="Chwezi Server Manager"
   APP_ENV=dev
   APP_DEBUG=true
   APP_URL=http://localhost/server-manager/public

   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=chwezi_sm
   DB_USERNAME=root
   DB_PASSWORD=

   SESSION_LIFETIME=30
   SESSION_SECURE=false
   ```

5. **Create Root User**
   ```bash
   php scripts/create-root-user.php peter peter@techguypeter.com YourPassword123 chwezi_sm root ""
   ```

6. **Set Permissions** (if on Linux/Mac)
   ```bash
   chmod -R 775 storage
   ```

7. **Access Application**
   - Open browser: http://localhost/server-manager/public
   - Login with credentials: `peter` / `YourPassword123`

---

### Option 2: Production Server (Ubuntu)

1. **Install Dependencies**
   ```bash
   sudo apt update
   sudo apt install -y php8.2 php8.2-{cli,curl,mbstring,xml,zip,mysql} \
                       apache2 mysql-server git
   ```

2. **Clone Repository**
   ```bash
   cd /var/www
   sudo git clone <repository-url> server-manager
   cd server-manager
   ```

3. **Run Installation Script**
   ```bash
   sudo bash scripts/install.sh
   ```

   The script will:
   - Prompt for environment (dev/demo/prod)
   - Create database and user
   - Run migrations
   - Create root user
   - Generate .env file
   - Set permissions
   - Optionally configure Apache and SSL

4. **Access Application**
   - Open browser: https://your-domain.com
   - Login with credentials created during installation

---

## Verification & Testing

### 1. Test Login
- [ ] Navigate to login page
- [ ] Enter username and password
- [ ] Check "Remember me"
- [ ] Click "Sign In"
- [ ] Verify dashboard loads
- [ ] Check welcome message shows your username

### 2. Test Session Management
- [ ] Login successfully
- [ ] Wait 31 minutes (or change SESSION_LIFETIME in .env to 1 minute)
- [ ] Try to navigate to another page
- [ ] Verify you're redirected to login with "session expired" message

### 3. Test User Management (as Root)
- [ ] Navigate to "Users" menu (should be visible only for root)
- [ ] Click "Create User"
- [ ] Fill form:
  - Username: `testuser`
  - Email: `test@example.com`
  - Password: `Test123456`
  - Role: `Developer`
- [ ] Click "Create User"
- [ ] Verify user appears in list

### 4. Test User Edit
- [ ] Click "Edit" on the test user
- [ ] Change email to `newemail@example.com`
- [ ] Change role to `Viewer`
- [ ] Click "Update User"
- [ ] Verify changes saved

### 5. Test User Status Toggle
- [ ] Click "Disable" on test user
- [ ] Verify status changes to "Disabled"
- [ ] Logout
- [ ] Try to login as test user
- [ ] Verify login fails with "account disabled" message
- [ ] Login as root
- [ ] Click "Enable" on test user
- [ ] Verify status changes to "Active"

### 6. Test User Deletion
- [ ] Create another test user
- [ ] Click "Delete" on that user
- [ ] Confirm deletion
- [ ] Verify user removed from list
- [ ] Try to delete yourself (should fail)
- [ ] Try to delete last root user (should fail)

### 7. Test RBAC
- [ ] Logout
- [ ] Login as non-root user (Developer or Viewer)
- [ ] Verify "Users" menu is NOT visible
- [ ] Try to access /users directly (type in URL)
- [ ] Verify 403 Forbidden response

### 8. Test Password Change
- [ ] Navigate to "Profile"
- [ ] Enter current password
- [ ] Enter new password (twice)
- [ ] Click "Update Password"
- [ ] Logout
- [ ] Login with new password
- [ ] Verify login successful

### 9. Test CSRF Protection
- [ ] Open browser DevTools (F12)
- [ ] Go to Console tab
- [ ] Try to submit a form without CSRF token:
   ```javascript
   fetch('/users/delete', {
     method: 'POST',
     body: new FormData(document.createElement('form'))
   })
   ```
- [ ] Verify you get "CSRF validation failed" error

### 10. Test Remember Me
- [ ] Login with "Remember me" checked
- [ ] Close browser completely
- [ ] Open browser again
- [ ] Navigate to application
- [ ] Verify you're automatically logged in

---

## Security Checklist

- [x] Passwords hashed with bcrypt (cost 12)
- [x] CSRF protection on all POST/PUT/DELETE
- [x] Session timeout (30 minutes default)
- [x] HTTPOnly cookies
- [x] SQL injection prevention (prepared statements)
- [x] XSS prevention (htmlspecialchars on output)
- [x] Root user isolation
- [x] Permission checks on every action
- [x] Cannot delete self or last root user
- [x] Input validation and sanitization

---

## Troubleshooting

### Database Connection Error
```
Error: Could not connect to database
```
**Solution:**
- Check .env file has correct DB credentials
- Verify MySQL is running
- Test connection: `mysql -u root -p chwezi_sm`

### 404 Not Found
```
The requested URL was not found on this server
```
**Solution:**
- Verify .htaccess exists in `public/` directory
- Enable mod_rewrite: `sudo a2enmod rewrite`
- Check Apache AllowOverride: should be `All` not `None`

### Permission Denied
```
Warning: file_put_contents(...): failed to open stream: Permission denied
```
**Solution:**
```bash
# Linux
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Windows/WAMP
Right-click storage folder → Properties → Security → Edit → Add → Everyone → Full Control
```

### Session Not Working
**Solution:**
- Check `session.save_path` in `php.ini` is writable
- Clear browser cookies
- Restart Apache

### CSRF Token Mismatch
**Solution:**
- Clear browser cache and cookies
- Check `APP_CSRF_ENABLED=true` in .env
- Verify form has `<?= CsrfMiddleware::field() ?>`

---

## File Structure Summary

```
server-manager/
├── public/
│   ├── index.php           # Front controller (router)
│   ├── .htaccess           # URL rewriting
│   └── css/app.css         # Custom styles
├── app/
│   ├── Controllers/
│   │   ├── Controller.php         # Base controller
│   │   ├── AuthController.php     # Login/logout/profile
│   │   ├── UserController.php     # User CRUD (root only)
│   │   └── DashboardController.php
│   ├── Models/
│   │   ├── Model.php       # Base model with CRUD
│   │   ├── User.php        # User model with RBAC
│   │   ├── Role.php        # Role model
│   │   └── Permission.php  # Permission model
│   ├── Middleware/
│   │   ├── AuthMiddleware.php       # Authentication check
│   │   ├── RootMiddleware.php       # Root-only access
│   │   ├── PermissionMiddleware.php # Permission check
│   │   └── CsrfMiddleware.php       # CSRF protection
│   └── Helpers/
│       ├── functions.php   # Core helper functions
│       └── validators.php  # Validation functions
├── config/
│   ├── app.php            # Application config
│   ├── database.php       # Database config
│   └── paths.php          # Path config
├── database/
│   ├── migrations/
│   │   └── 001_create_auth_tables.sql
│   └── seeds/
│       └── 001_root_user.sql
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── main.php           # Main layout
│       ├── auth/
│       │   ├── login.php          # Login page
│       │   └── profile.php        # Profile page
│       ├── users/
│       │   ├── index.php          # User list
│       │   ├── create.php         # Create user form
│       │   └── edit.php           # Edit user form
│       ├── dashboard/
│       │   └── index.php          # Dashboard
│       └── errors/
│           └── 404.php            # 404 page
├── storage/
│   ├── logs/             # Application logs
│   ├── backups/          # Database backups (Phase 2+)
│   └── cache/            # Cache files (Phase 2+)
├── scripts/
│   ├── install.sh              # Installation script
│   ├── create-root-user.php    # Root user creation
│   └── sudoers-chwezi-sm       # Sudoers config (Phase 2+)
├── .env.example          # Environment template
├── .gitignore           # Git ignore rules
└── README.md            # Documentation
```

---

## What's Implemented (Phase 1)

### ✅ Complete Features
1. **Authentication System**
   - Login with username/password
   - Logout
   - Session management with timeout
   - Remember me (30 days)
   - Auto-login via cookie

2. **User Management** (Root Only)
   - Create users
   - Edit users (username, email, password, role)
   - Disable/Enable users
   - Delete users
   - List all users with status

3. **RBAC (Role-Based Access Control)**
   - 4 default roles: Root, Admin, Developer, Viewer
   - 35+ permissions across 12 categories
   - Root users bypass all permission checks
   - Permission checks on every action

4. **Security**
   - Password hashing (bcrypt, cost 12)
   - CSRF protection on all forms
   - Session security (HTTPOnly, timeout)
   - SQL injection prevention (prepared statements)
   - XSS prevention (output escaping)
   - Input validation and sanitization

5. **Dashboard**
   - Welcome message
   - User info display
   - System information
   - Quick actions
   - Phase progress indicator

6. **Profile Management**
   - View profile information
   - Change password
   - View last login time

---

## What's Coming Next

### Phase 2: Dashboard & Server Monitoring
- Real-time server stats (CPU, RAM, Disk)
- App count and status
- SSL expiry alerts
- Activity log

### Phase 3: Git Integration
- SSH key generation
- GitHub connection test
- Repository management

### Phase 4: App Deployment
- Deploy from GitHub
- Apache virtual host configuration
- Laravel auto-detection
- Post-install commands

### Phase 5-13: Full Server Management
- Domain management
- SSL certificates (Let's Encrypt)
- Database management
- WordPress installer
- Cron jobs
- Log viewer
- Web terminal
- Git submodules

---

## Need Help?

### Common Commands

**View logs:**
```bash
# Application log
tail -f storage/logs/app-2024-01-23.log

# Apache error log (Ubuntu)
tail -f /var/log/apache2/error.log

# PHP error log (WAMP)
C:\wamp64\logs\php_error.log
```

**Reset database:**
```bash
mysql -u root -p
DROP DATABASE chwezi_sm;
CREATE DATABASE chwezi_sm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

mysql -u root -p chwezi_sm < database/migrations/001_create_auth_tables.sql
php scripts/create-root-user.php peter peter@example.com Pass123 chwezi_sm root ""
```

**Check PHP version:**
```bash
php -v
```

**Check MySQL version:**
```bash
mysql --version
```

---

## Success Criteria

Phase 1 is complete and successful if you can:

1. ✅ Install the application (automated or manual)
2. ✅ Create a root user
3. ✅ Login to the dashboard
4. ✅ Create additional users with different roles
5. ✅ Verify root-only access to user management
6. ✅ Test RBAC (non-root users can't access /users)
7. ✅ Change your password
8. ✅ Enable/disable users
9. ✅ Delete users (with safety checks)
10. ✅ Session timeout works correctly
11. ✅ Remember me works correctly
12. ✅ CSRF protection prevents unauthorized requests

---

**Congratulations! You've successfully installed Phase 1 of Chwezi Server Manager.** 🎉

Ready to move to Phase 2? The foundation is solid and ready for expansion!
