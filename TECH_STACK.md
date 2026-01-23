# Chwezi Server Manager - Tech Stack

## Overview

This document defines the technology choices for the Chwezi Server Manager application, including frameworks, libraries, tools, and the reasoning behind each selection.

**Project Status:** Phase 1 ✅ COMPLETE (Authentication & RBAC)
**Last Updated:** January 23, 2026

### Current Implementation
All technologies listed below have been successfully implemented and tested in Phase 1:
- ✅ PHP 8.2+ (vanilla, no frameworks)
- ✅ MySQL 8.0+ with PDO
- ✅ TailwindCSS 3.x (via CDN)
- ✅ Alpine.js 3.x (for interactive components)
- ✅ Apache 2.4+ with mod_rewrite
- ✅ No Composer dependencies (keeping it simple)

---

## Stack Summary

| Layer | Technology | Version |
|-------|------------|---------|
| **Backend** | PHP (Vanilla) | 8.2+ |
| **Frontend** | HTML5, TailwindCSS, Alpine.js | Latest |
| **Database** | MySQL | 8.0+ |
| **Web Server** | Apache | 2.4+ |
| **OS** | Ubuntu | 22.04/24.04 LTS |
| **SSL** | Let's Encrypt (Certbot) | Latest |
| **Version Control** | Git | Latest |

---

## Backend

### PHP 8.2+ (Vanilla - No Framework)

**Choice:** Pure PHP without frameworks like Laravel or Symfony

**Reasoning:**
- **Lightweight** - No framework overhead, faster load times
- **Simple deployment** - Just copy files, no composer dependencies required in production
- **Full control** - Direct access to shell commands without framework abstractions
- **Learning** - Good opportunity to build MVC patterns from scratch
- **Maintenance** - Fewer dependencies to update and maintain

**PHP Extensions Required:**
```
php-curl      # HTTP requests
php-mbstring  # String handling
php-xml       # XML parsing
php-zip       # Archive handling
php-mysql     # MySQL connection
php-json      # JSON encoding/decoding
```

**Key PHP Features Used:**
- PDO for database access (prepared statements)
- Native sessions for authentication
- shell_exec() for system commands
- File handling for logs and configs

---

## Frontend

### HTML5

Standard semantic HTML for structure and accessibility.

### TailwindCSS 3.x

**Choice:** Utility-first CSS framework via CDN

**Reasoning:**
- **Rapid development** - No context switching to CSS files
- **Consistent design** - Built-in design system
- **Small footprint** - Only includes used classes when purged
- **No build step** - Can use CDN for development
- **Responsive** - Mobile-first utilities built-in

**Implementation:**
```html
<!-- Development (CDN) -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Production (compiled CSS) -->
<link href="/css/tailwind.min.css" rel="stylesheet">
```

**Color Scheme:**
```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: '#3B82F6',    // Blue
        success: '#10B981',    // Green
        warning: '#F59E0B',    // Yellow
        danger: '#EF4444',     // Red
      }
    }
  }
}
```

### Alpine.js 3.x

**Choice:** Lightweight JavaScript framework

**Reasoning:**
- **Minimal footprint** - ~15KB minified
- **Declarative** - HTML-centric, no build step
- **Vue-like syntax** - Easy to learn
- **Perfect for enhancement** - Add interactivity without SPA complexity

**Use Cases:**
- Modal dialogs
- Dropdown menus
- Form validation
- Tab interfaces
- Real-time updates (polling)

**Implementation:**
```html
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- Example: Delete confirmation -->
<div x-data="{ showConfirm: false }">
  <button @click="showConfirm = true">Delete</button>
  <div x-show="showConfirm" class="modal">
    <p>Are you sure?</p>
    <button @click="deleteApp()">Yes</button>
    <button @click="showConfirm = false">Cancel</button>
  </div>
</div>
```

---

## Database

### MySQL 8.0+

**Choice:** MySQL over PostgreSQL or SQLite

**Reasoning:**
- **Ubiquitous** - Standard on most hosting environments
- **WordPress compatibility** - Same DB engine for WordPress sites
- **Familiar** - Most PHP developers know MySQL
- **Tooling** - Excellent GUI tools (phpMyAdmin, MySQL Workbench)
- **Performance** - Fast for our use case

**Configuration:**
```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
max_connections = 100
innodb_buffer_pool_size = 256M
```

**Connection (PDO):**
```php
$pdo = new PDO(
    "mysql:host=localhost;dbname=chwezi_sm;charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
```

---

## Web Server

### Apache 2.4+

**Choice:** Apache over Nginx

**Reasoning:**
- **Already in use** - Current server stack
- **.htaccess support** - Easy URL rewriting per-directory
- **mod_rewrite** - Powerful URL manipulation
- **Familiarity** - Team already knows Apache
- **WordPress standard** - Most WordPress tutorials assume Apache

**Required Modules:**
```bash
a2enmod rewrite    # URL rewriting
a2enmod headers    # Security headers
a2enmod ssl        # HTTPS support
a2enmod proxy      # Reverse proxy (if needed)
```

**Virtual Host Template:**
```apache
<VirtualHost *:80>
    ServerName sm.example.com
    DocumentRoot /var/www/html/chwezi-sm/public
    
    <Directory /var/www/html/chwezi-sm/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/chwezi-sm-error.log
    CustomLog ${APACHE_LOG_DIR}/chwezi-sm-access.log combined
</VirtualHost>
```

---

## Operating System

### Ubuntu 22.04/24.04 LTS

**Choice:** Ubuntu LTS over other distributions

**Reasoning:**
- **Long-term support** - 5 years of security updates
- **Wide adoption** - Most tutorials and documentation
- **apt package manager** - Easy software installation
- **Current server OS** - Already running Ubuntu

**System Requirements:**
| Resource | Minimum | Recommended |
|----------|---------|-------------|
| RAM | 1 GB | 2 GB |
| Disk | 20 GB | 50 GB |
| CPU | 1 core | 2 cores |

---

## SSL/TLS

### Let's Encrypt + Certbot

**Choice:** Free SSL certificates from Let's Encrypt

**Reasoning:**
- **Free** - No certificate costs
- **Automated** - Certbot handles renewal
- **Trusted** - Recognized by all browsers
- **Easy** - Apache plugin for automatic configuration

**Installation:**
```bash
apt install certbot python3-certbot-apache
certbot --apache -d sm.example.com
```

**Auto-renewal:**
```bash
# Certbot installs a systemd timer automatically
systemctl status certbot.timer
```

---

## Version Control

### Git

**Features Used:**
- Repository cloning (SSH)
- Branch management
- Git submodules
- Fetch/pull operations
- Commit history

**SSH Key Management:**
```bash
# Generate key
ssh-keygen -t ed25519 -C "chwezi-sm@server"

# Test connection
ssh -T git@github.com
```

---

## Development Tools

### Code Editor

**Visual Studio Code** with extensions:
- PHP Intelephense
- Tailwind CSS IntelliSense
- Alpine.js IntelliSense
- GitLens

### Claude Code

For AI-assisted development in VS Code terminal.

### Local Testing

```bash
# PHP built-in server for quick testing
php -S localhost:8000 -t public/
```

---

## External Services

### GitHub

- Repository hosting
- SSH authentication
- Webhook support (future)

### Namecheap

- Domain registration
- DNS management
- Integration guidance (manual)

---

## Package Management

### Composer (Development Only)

**Optional** - Only if adding PHP libraries

```json
{
    "require": {
        "php": ">=8.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    }
}
```

### NPM (Build Only)

**Optional** - Only for TailwindCSS production build

```json
{
    "devDependencies": {
        "tailwindcss": "^3.0",
        "autoprefixer": "^10.0"
    }
}
```

---

## Security Tools

### Password Hashing

```php
// Hash password
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verify password
password_verify($password, $hash);
```

### CSRF Protection

```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Verify token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF validation failed');
}
```

### Input Sanitization

```php
// Shell commands
$safe = escapeshellarg($userInput);

// SQL (use prepared statements)
$stmt = $pdo->prepare("SELECT * FROM apps WHERE id = ?");
$stmt->execute([$id]);

// HTML output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

---

## Monitoring & Logging

### Application Logs

```php
// Custom logger
function logActivity($action, $details = []) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'],
    ];
    file_put_contents(
        '/var/www/html/chwezi-sm/storage/logs/app.log',
        json_encode($log) . "\n",
        FILE_APPEND
    );
}
```

### System Logs

```bash
# Apache error log
tail -f /var/log/apache2/error.log

# PHP error log
tail -f /var/log/php8.2-fpm.log

# Application log
tail -f /var/www/html/chwezi-sm/storage/logs/app.log
```

---

## Browser Support

| Browser | Minimum Version |
|---------|-----------------|
| Chrome | 90+ |
| Firefox | 88+ |
| Safari | 14+ |
| Edge | 90+ |

**Note:** No IE11 support. Modern browsers only.

---

## Performance Considerations

### PHP

- **OPcache enabled** - Bytecode caching
- **No unnecessary includes** - Lazy loading
- **Prepared statements** - Query caching

### Frontend

- **TailwindCSS purged** - Remove unused styles
- **Alpine.js deferred** - Non-blocking load
- **Minimal JS** - No heavy frameworks

### Database

- **Indexed columns** - Foreign keys, frequently queried
- **Connection pooling** - Persistent connections
- **Query optimization** - Explain plans for slow queries

---

## Deployment Checklist

```bash
# Server preparation
apt update && apt upgrade -y
apt install -y php8.2 php8.2-{curl,mbstring,xml,zip,mysql} \
               apache2 mysql-server certbot python3-certbot-apache git

# Application setup
git clone https://github.com/chwezi/server-manager.git /var/www/html/chwezi-sm
cd /var/www/html/chwezi-sm
cp .env.example .env
nano .env  # Configure database, etc.

# Permissions
chown -R www-data:www-data /var/www/html/chwezi-sm
chmod -R 755 /var/www/html/chwezi-sm
chmod -R 775 /var/www/html/chwezi-sm/storage

# Apache
cp apache/chwezi-sm.conf /etc/apache2/sites-available/
a2ensite chwezi-sm.conf
a2enmod rewrite
systemctl reload apache2

# SSL
certbot --apache -d sm.example.com

# Database
mysql -e "CREATE DATABASE chwezi_sm;"
mysql chwezi_sm < database/schema.sql
```

---

## Tech Stack Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         Browser                                 │
│                   (Chrome, Firefox, etc.)                       │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ HTTPS
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Let's Encrypt SSL                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Apache 2.4 + mod_rewrite                     │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         PHP 8.2+                                │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                  Chwezi Server Manager                   │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐ │  │
│  │  │Controllers│  │ Services │  │  Models  │  │  Views   │ │  │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘ │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
         │              │              │              │
         │              │              │              │
         ▼              ▼              ▼              ▼
┌──────────────┐ ┌─────────────┐ ┌───────────┐ ┌────────────┐
│   MySQL 8    │ │   Apache    │ │   Shell   │ │  Certbot   │
│   Database   │ │   Configs   │ │  Commands │ │    SSL     │
└──────────────┘ └─────────────┘ └───────────┘ └────────────┘
```

---

## Summary

| Category | Choice | Reasoning |
|----------|--------|-----------|
| Backend | PHP 8.2 (Vanilla) | Lightweight, full control |
| Frontend | TailwindCSS + Alpine.js | Rapid development, minimal JS |
| Database | MySQL 8.0 | Standard, familiar, WordPress-compatible |
| Web Server | Apache 2.4 | .htaccess support, already in use |
| OS | Ubuntu LTS | Long-term support, wide adoption |
| SSL | Let's Encrypt | Free, automated |
| VCS | Git | Industry standard |

---

**Document Version:** 1.0  
**Last Updated:** January 2025
