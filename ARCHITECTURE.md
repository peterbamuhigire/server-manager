# Chwezi Server Manager - Architecture Document

## Overview

This document describes the software architecture of Chwezi Server Manager, including system design, component interactions, data flow, and deployment architecture.

**Project Status:** Phase 1 ✅ COMPLETE (Authentication & RBAC)
**Last Updated:** January 23, 2026
**Implementation Progress:** 15% (Phase 1 of 13)

### Phase 1 Implementation Status
- ✅ **Authentication System** - Login, logout, session management, remember me
- ✅ **RBAC System** - 4 roles (Root, Admin, Developer, Viewer), 35+ permissions
- ✅ **User Management** - Create, edit, delete, disable users (root only)
- ✅ **Security Features** - CSRF protection, password hashing, input validation
- ✅ **Dashboard** - Basic dashboard with welcome and system info
- ✅ **Installation Scripts** - Automated setup for dev/demo/prod environments
- ⏳ **Phase 2-13** - Server monitoring, app deployment, domains, SSL, databases, WordPress, etc.

---

## Table of Contents

1. [System Architecture](#1-system-architecture)
2. [Application Architecture](#2-application-architecture)
3. [Directory Structure](#3-directory-structure)
4. [Component Design](#4-component-design)
5. [Data Architecture](#5-data-architecture)
6. [Security Architecture](#6-security-architecture)
7. [Integration Architecture](#7-integration-architecture)
8. [Deployment Architecture](#8-deployment-architecture)

---

## 1. System Architecture

### 1.1 High-Level Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                           INTERNET                                  │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                                 │ HTTPS (443)
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        UBUNTU VPS SERVER                            │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │                     Apache Web Server                         │  │
│  │                   (Reverse Proxy + SSL)                       │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                 │                                   │
│           ┌─────────────────────┼─────────────────────┐             │
│           │                     │                     │             │
│           ▼                     ▼                     ▼             │
│  ┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐   │
│  │  Chwezi Server  │   │   Managed App   │   │   Managed App   │   │
│  │    Manager      │   │  (erp.birdc.ug) │   │  (medic8.com)   │   │
│  │  (sm.domain)    │   │                 │   │                 │   │
│  └─────────────────┘   └─────────────────┘   └─────────────────┘   │
│           │                                                         │
│           ▼                                                         │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │                        MySQL Server                           │  │
│  │              (chwezi_sm + managed databases)                  │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │                      File System                              │  │
│  │    /var/www/html/         (Web applications)                  │  │
│  │    /etc/apache2/          (Apache configs)                    │  │
│  │    /etc/letsencrypt/      (SSL certificates)                  │  │
│  │    /var/backups/          (Database backups)                  │  │
│  └───────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                                 │ SSH (Git)
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                           GITHUB                                    │
│                    (Source code repositories)                       │
└─────────────────────────────────────────────────────────────────────┘
```

### 1.2 Component Interactions

```
┌─────────────────────────────────────────────────────────────────────┐
│                      Chwezi Server Manager                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   ┌─────────────┐     ┌─────────────┐     ┌─────────────┐          │
│   │   Browser   │────▶│   Apache    │────▶│    PHP      │          │
│   │   (User)    │     │  (Router)   │     │   (App)     │          │
│   └─────────────┘     └─────────────┘     └─────────────┘          │
│                                                  │                  │
│                           ┌──────────────────────┼──────────────┐   │
│                           │                      │              │   │
│                           ▼                      ▼              ▼   │
│                    ┌─────────────┐      ┌─────────────┐  ┌────────┐│
│                    │   MySQL     │      │   Shell     │  │  File  ││
│                    │  Database   │      │   Exec      │  │ System ││
│                    └─────────────┘      └─────────────┘  └────────┘│
│                                                │                    │
│                    ┌───────────────────────────┼────────────────┐   │
│                    │                           │                │   │
│                    ▼                           ▼                ▼   │
│             ┌─────────────┐           ┌─────────────┐    ┌────────┐│
│             │    Git      │           │   Apache    │    │Certbot ││
│             │  Commands   │           │   Config    │    │  SSL   ││
│             └─────────────┘           └─────────────┘    └────────┘│
│                    │                                                │
│                    ▼                                                │
│             ┌─────────────┐                                         │
│             │   GitHub    │                                         │
│             │   (SSH)     │                                         │
│             └─────────────┘                                         │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 2. Application Architecture

### 2.1 MVC Pattern

The application follows a Model-View-Controller pattern:

```
┌─────────────────────────────────────────────────────────────────────┐
│                          REQUEST FLOW                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   HTTP Request                                                      │
│        │                                                            │
│        ▼                                                            │
│   ┌─────────────┐                                                   │
│   │  index.php  │  Entry point                                      │
│   │  (Router)   │                                                   │
│   └─────────────┘                                                   │
│        │                                                            │
│        ▼                                                            │
│   ┌─────────────┐                                                   │
│   │ Middleware  │  Auth check, CSRF validation                      │
│   └─────────────┘                                                   │
│        │                                                            │
│        ▼                                                            │
│   ┌─────────────┐     ┌─────────────┐     ┌─────────────┐          │
│   │ Controller  │────▶│   Service   │────▶│    Model    │          │
│   │             │     │             │     │             │          │
│   │ - Validate  │     │ - Business  │     │ - Database  │          │
│   │ - Delegate  │     │   Logic     │     │   Access    │          │
│   │ - Respond   │     │ - Shell Ops │     │             │          │
│   └─────────────┘     └─────────────┘     └─────────────┘          │
│        │                                                            │
│        ▼                                                            │
│   ┌─────────────┐                                                   │
│   │    View     │  HTML + TailwindCSS + Alpine.js                   │
│   │  (Template) │                                                   │
│   └─────────────┘                                                   │
│        │                                                            │
│        ▼                                                            │
│   HTTP Response                                                     │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.2 Layer Responsibilities

| Layer | Responsibility | Example |
|-------|----------------|---------|
| **Router** | URL to Controller mapping | `/apps/5/update` → `AppController::update(5)` |
| **Middleware** | Cross-cutting concerns | Authentication, CSRF, Logging |
| **Controller** | Request handling | Validate input, call services, return response |
| **Service** | Business logic | Clone repo, configure Apache, install SSL |
| **Model** | Data access | CRUD operations on database |
| **View** | Presentation | HTML templates with data |

---

## 3. Directory Structure

```
/var/www/html/chwezi-sm/
│
├── public/                      # Web root (DocumentRoot)
│   ├── index.php               # Entry point / Router
│   ├── .htaccess               # URL rewriting
│   ├── css/
│   │   └── app.css             # Custom styles
│   ├── js/
│   │   └── app.js              # Custom JavaScript
│   └── img/
│       └── logo.png
│
├── app/                         # Application code
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── AppController.php
│   │   ├── WordPressController.php
│   │   ├── DomainController.php
│   │   ├── SslController.php
│   │   ├── DatabaseController.php
│   │   ├── GitController.php
│   │   ├── SubmodulesController.php
│   │   ├── SkillsController.php
│   │   ├── CronController.php
│   │   ├── LogController.php
│   │   └── TerminalController.php
│   │
│   ├── Models/
│   │   ├── Model.php           # Base model class
│   │   ├── User.php
│   │   ├── App.php
│   │   ├── Domain.php
│   │   ├── Database.php
│   │   ├── SslCertificate.php
│   │   ├── Submodule.php
│   │   ├── CronJob.php
│   │   └── ActivityLog.php
│   │
│   ├── Services/
│   │   ├── ShellService.php
│   │   ├── ApacheService.php
│   │   ├── GitService.php
│   │   ├── SubmoduleService.php
│   │   ├── SslService.php
│   │   ├── DatabaseService.php
│   │   ├── WordPressService.php
│   │   ├── DnsService.php
│   │   └── BackupService.php
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   └── CsrfMiddleware.php
│   │
│   └── Helpers/
│       ├── functions.php       # Global helper functions
│       └── validators.php      # Input validation
│
├── config/
│   ├── app.php                 # Application config
│   ├── database.php            # Database config
│   └── paths.php               # File system paths
│
├── database/
│   ├── migrations/
│   │   ├── 001_create_users.sql
│   │   ├── 002_create_apps.sql
│   │   ├── 003_create_domains.sql
│   │   └── ...
│   └── seeds/
│       └── admin_user.sql
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── main.php        # Main layout
│   │   │   └── auth.php        # Auth layout (login)
│   │   ├── auth/
│   │   │   └── login.php
│   │   ├── dashboard/
│   │   │   └── index.php
│   │   ├── apps/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   ├── show.php
│   │   │   └── edit.php
│   │   ├── wordpress/
│   │   │   └── create.php
│   │   ├── domains/
│   │   │   └── index.php
│   │   ├── ssl/
│   │   │   └── index.php
│   │   ├── databases/
│   │   │   └── index.php
│   │   ├── git/
│   │   │   └── index.php
│   │   ├── submodules/
│   │   │   └── index.php
│   │   ├── cron/
│   │   │   └── index.php
│   │   ├── logs/
│   │   │   └── index.php
│   │   ├── terminal/
│   │   │   └── index.php
│   │   └── settings/
│   │       └── index.php
│   │
│   └── templates/
│       ├── apache/
│       │   ├── vhost.conf.tpl
│       │   └── vhost-ssl.conf.tpl
│       ├── wordpress/
│       │   └── wp-config.php.tpl
│       └── scripts/
│           └── update-app.sh.tpl
│
├── storage/
│   ├── logs/
│   │   ├── app.log
│   │   └── shell.log
│   ├── backups/
│   └── cache/
│
├── scripts/
│   ├── install.sh              # Installation script
│   ├── sudoers-setup.sh        # Sudo permissions setup
│   └── cron-runner.php         # Cron job executor
│
├── .env                        # Environment variables
├── .env.example                # Environment template
├── .gitignore
├── composer.json               # Optional dependencies
└── README.md
```

---

## 4. Component Design

### 4.1 Router (index.php)

```php
<?php
// public/index.php

// Load configuration
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

// Start session
session_start();

// Parse URL
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Define routes
$routes = [
    'GET'  => [
        '/'                    => ['DashboardController', 'index'],
        '/login'               => ['AuthController', 'showLogin'],
        '/apps'                => ['AppController', 'index'],
        '/apps/create'         => ['AppController', 'create'],
        '/apps/{id}'           => ['AppController', 'show'],
        // ... more routes
    ],
    'POST' => [
        '/login'               => ['AuthController', 'login'],
        '/logout'              => ['AuthController', 'logout'],
        '/apps'                => ['AppController', 'store'],
        '/apps/{id}/update'    => ['AppController', 'update'],
        // ... more routes
    ],
];

// Match route and dispatch
$handler = matchRoute($uri, $method, $routes);
if ($handler) {
    // Apply middleware
    if ($handler[0] !== 'AuthController') {
        require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
        AuthMiddleware::handle();
    }
    
    // Load controller and call method
    require_once __DIR__ . "/../app/Controllers/{$handler[0]}.php";
    $controller = new $handler[0]();
    call_user_func_array([$controller, $handler[1]], $handler[2] ?? []);
} else {
    http_response_code(404);
    include __DIR__ . '/../resources/views/errors/404.php';
}
```

### 4.2 Base Controller

```php
<?php
// app/Controllers/Controller.php

abstract class Controller
{
    protected function view(string $name, array $data = []): void
    {
        extract($data);
        include __DIR__ . "/../../resources/views/{$name}.php";
    }
    
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
    
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function validate(array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            if (!validateField($_POST[$field] ?? '', $rule)) {
                $errors[$field] = "Invalid {$field}";
            }
        }
        return $errors;
    }
}
```

### 4.3 Base Model

```php
<?php
// app/Models/Model.php

abstract class Model
{
    protected static string $table;
    protected static PDO $pdo;
    
    public static function init(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
    
    public static function all(): array
    {
        $stmt = self::$pdo->query("SELECT * FROM " . static::$table);
        return $stmt->fetchAll();
    }
    
    public static function find(int $id): ?array
    {
        $stmt = self::$pdo->prepare("SELECT * FROM " . static::$table . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    public static function create(array $data): int
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $stmt = self::$pdo->prepare(
            "INSERT INTO " . static::$table . " ({$fields}) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        
        return (int) self::$pdo->lastInsertId();
    }
    
    public static function update(int $id, array $data): bool
    {
        $sets = implode(' = ?, ', array_keys($data)) . ' = ?';
        
        $stmt = self::$pdo->prepare(
            "UPDATE " . static::$table . " SET {$sets} WHERE id = ?"
        );
        return $stmt->execute([...array_values($data), $id]);
    }
    
    public static function delete(int $id): bool
    {
        $stmt = self::$pdo->prepare("DELETE FROM " . static::$table . " WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
```

### 4.4 Shell Service

```php
<?php
// app/Services/ShellService.php

class ShellService
{
    private array $allowedCommands = [
        'git', 'certbot', 'apache2ctl', 'systemctl', 'a2ensite', 'a2dissite',
        'composer', 'npm', 'php', 'mysql', 'mysqldump', 'chown', 'chmod',
        'cp', 'mv', 'rm', 'mkdir', 'cat', 'grep', 'tail', 'head', 'wget', 'curl'
    ];
    
    private array $dangerousPatterns = [
        '/rm\s+-rf\s+\/[^\/]/',      // rm -rf on root directories
        '/dd\s+if=/',                 // disk destroyer
        '/mkfs/',                     // format disk
        '/>\s*\/dev\//',              // write to devices
        '/chmod\s+-R\s+777\s+\//',    // 777 on root
    ];
    
    public function execute(string $command, ?string $workingDir = null): array
    {
        // Log command
        $this->logCommand($command);
        
        // Validate command
        $this->validateCommand($command);
        
        // Prepare execution
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];
        
        // Execute
        $process = proc_open(
            $command,
            $descriptors,
            $pipes,
            $workingDir
        );
        
        if (!is_resource($process)) {
            throw new Exception('Failed to execute command');
        }
        
        // Read output
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        
        // Close pipes
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        // Get exit code
        $exitCode = proc_close($process);
        
        return [
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }
    
    private function validateCommand(string $command): void
    {
        // Check for dangerous patterns
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $command)) {
                throw new Exception('Dangerous command detected');
            }
        }
    }
    
    private function logCommand(string $command): void
    {
        $log = sprintf(
            "[%s] %s: %s\n",
            date('Y-m-d H:i:s'),
            $_SESSION['user']['username'] ?? 'system',
            $command
        );
        file_put_contents(
            __DIR__ . '/../../storage/logs/shell.log',
            $log,
            FILE_APPEND
        );
    }
}
```

### 4.5 Git Service

```php
<?php
// app/Services/GitService.php

class GitService
{
    private ShellService $shell;
    
    public function __construct()
    {
        $this->shell = new ShellService();
    }
    
    public function clone(string $repoUrl, string $directory, string $branch = 'main'): array
    {
        $safeUrl = escapeshellarg($repoUrl);
        $safeDir = escapeshellarg($directory);
        $safeBranch = escapeshellarg($branch);
        
        return $this->shell->execute(
            "git clone --branch {$safeBranch} {$safeUrl} {$safeDir}"
        );
    }
    
    public function pull(string $directory, string $branch = 'main'): array
    {
        $safeBranch = escapeshellarg($branch);
        
        return $this->shell->execute(
            "git pull origin {$safeBranch}",
            $directory
        );
    }
    
    public function fetch(string $directory): array
    {
        return $this->shell->execute("git fetch --all", $directory);
    }
    
    public function getCurrentCommit(string $directory): string
    {
        $result = $this->shell->execute(
            "git rev-parse --short HEAD",
            $directory
        );
        return trim($result['stdout']);
    }
    
    public function getPendingCommits(string $directory, string $branch = 'main'): array
    {
        $result = $this->shell->execute(
            "git log --oneline HEAD..origin/{$branch}",
            $directory
        );
        return array_filter(explode("\n", trim($result['stdout'])));
    }
    
    public function getPublicKey(): string
    {
        $keyPath = '/root/.ssh/id_ed25519.pub';
        if (!file_exists($keyPath)) {
            $keyPath = '/root/.ssh/id_rsa.pub';
        }
        return file_exists($keyPath) ? file_get_contents($keyPath) : '';
    }
    
    public function testConnection(): bool
    {
        $result = $this->shell->execute("ssh -T git@github.com 2>&1");
        return strpos($result['stdout'] . $result['stderr'], 'successfully authenticated') !== false;
    }
}
```

---

## 5. Data Architecture

### 5.1 Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│     users       │       │      apps       │       │    domains      │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ PK id           │       │ PK id           │◄──────│ PK id           │
│    username     │       │    name         │       │ FK app_id       │
│    email        │       │    slug         │       │    domain       │
│    password     │       │    github_url   │       │    document_root│
│    created_at   │       │    branch       │       │    ssl_enabled  │
│    updated_at   │       │    directory    │       │    status       │
└─────────────────┘       │    app_type     │       │    created_at   │
                          │    last_commit  │       └─────────────────┘
                          │    status       │                │
                          │    created_at   │                │
                          └─────────────────┘                │
                                  │                          │
                                  │                          ▼
                                  │               ┌─────────────────┐
                                  │               │  ssl_certificates│
                                  │               ├─────────────────┤
                                  │               │ PK id           │
                                  │               │ FK domain_id    │
                                  │               │    expiry_date  │
                                  │               │    auto_renew   │
                                  │               │    created_at   │
                                  │               └─────────────────┘
                                  │
          ┌───────────────────────┼───────────────────────┐
          │                       │                       │
          ▼                       ▼                       ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   databases     │     │   submodules    │     │   cron_jobs     │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ PK id           │     │ PK id           │     │ PK id           │
│ FK app_id       │     │ FK app_id       │     │ FK app_id       │
│    name         │     │    name         │     │    name         │
│    username     │     │    path         │     │    command      │
│    last_backup  │     │    repo_url     │     │    schedule     │
│    created_at   │     │    branch       │     │    enabled      │
└─────────────────┘     │    created_at   │     │    last_run     │
                        └─────────────────┘     │    created_at   │
                                                └─────────────────┘

┌─────────────────┐
│  activity_logs  │
├─────────────────┤
│ PK id           │
│ FK user_id      │
│    action       │
│    entity_type  │
│    entity_id    │
│    details      │
│    ip_address   │
│    created_at   │
└─────────────────┘
```

### 5.2 Data Flow

```
User Action: "Deploy App"
       │
       ▼
┌─────────────────┐
│  AppController  │
│    ::store()    │
└─────────────────┘
       │
       ├──────────────────────────────────────────┐
       │                                          │
       ▼                                          ▼
┌─────────────────┐                     ┌─────────────────┐
│  Validate Input │                     │   DNS Check     │
│                 │                     │   (optional)    │
└─────────────────┘                     └─────────────────┘
       │
       ▼
┌─────────────────┐
│  GitService     │
│  ::clone()      │────────────────────────▶ GitHub
└─────────────────┘                              │
       │                                         │
       ▼                                         │
┌─────────────────┐                              │
│ ApacheService   │                              │
│::createVhost()  │                              │
└─────────────────┘                              │
       │                                         │
       ▼                                         │
┌─────────────────┐                              │
│  SslService     │                              │
│ ::install()     │────────────────────────▶ Let's Encrypt
└─────────────────┘
       │
       ▼
┌─────────────────┐
│   App::create() │────────────────────────▶ MySQL
└─────────────────┘
       │
       ▼
┌─────────────────┐
│ ActivityLog     │
│ ::create()      │────────────────────────▶ MySQL
└─────────────────┘
       │
       ▼
┌─────────────────┐
│ Return Success  │
│ Response        │
└─────────────────┘
```

---

## 6. Security Architecture

### 6.1 Authentication Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                      AUTHENTICATION FLOW                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐        │
│   │  Login  │───▶│ Verify  │───▶│ Create  │───▶│Redirect │        │
│   │  Form   │    │Password │    │ Session │    │Dashboard│        │
│   └─────────┘    └─────────┘    └─────────┘    └─────────┘        │
│        │              │                                            │
│        │              │ Failed                                     │
│        │              ▼                                            │
│        │        ┌─────────┐                                        │
│        │◀───────│  Error  │                                        │
│                 │ Message │                                        │
│                 └─────────┘                                        │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│                      AUTHORIZATION CHECK                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐        │
│   │ Request │───▶│  Auth   │───▶│ Session │───▶│ Process │        │
│   │         │    │Middleware│    │  Valid? │    │ Request │        │
│   └─────────┘    └─────────┘    └─────────┘    └─────────┘        │
│                       │              │                              │
│                       │              │ No                           │
│                       │              ▼                              │
│                       │        ┌─────────┐                          │
│                       │        │Redirect │                          │
│                       │        │ Login   │                          │
│                       │        └─────────┘                          │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 6.2 Security Layers

```
┌─────────────────────────────────────────────────────────────────────┐
│                        SECURITY LAYERS                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Layer 1: HTTPS (SSL/TLS)                                          │
│  ├── All traffic encrypted                                          │
│  └── Managed by Certbot/Let's Encrypt                              │
│                                                                     │
│  Layer 2: Apache                                                    │
│  ├── .htaccess protects sensitive files                            │
│  ├── Security headers (X-Frame-Options, etc.)                      │
│  └── Rate limiting (mod_evasive optional)                          │
│                                                                     │
│  Layer 3: Application                                               │
│  ├── Session-based authentication                                   │
│  ├── CSRF tokens on all forms                                       │
│  ├── Input validation/sanitization                                  │
│  ├── Prepared statements for SQL                                    │
│  └── escapeshellarg() for shell commands                           │
│                                                                     │
│  Layer 4: Operating System                                          │
│  ├── Sudoers whitelist for specific commands                       │
│  ├── File permissions (www-data user)                              │
│  └── UFW firewall (ports 22, 80, 443 only)                         │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 6.3 Sudoers Configuration

```bash
# /etc/sudoers.d/chwezi-sm

# Allow www-data to run specific commands without password
www-data ALL=(ALL) NOPASSWD: /usr/bin/certbot
www-data ALL=(ALL) NOPASSWD: /usr/sbin/a2ensite *
www-data ALL=(ALL) NOPASSWD: /usr/sbin/a2dissite *
www-data ALL=(ALL) NOPASSWD: /usr/sbin/apache2ctl configtest
www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload apache2
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart apache2
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart php*-fpm
www-data ALL=(ALL) NOPASSWD: /usr/bin/git *
www-data ALL=(ALL) NOPASSWD: /bin/chown -R www-data\:www-data /var/www/html/*
www-data ALL=(ALL) NOPASSWD: /bin/chmod -R * /var/www/html/*
www-data ALL=(ALL) NOPASSWD: /usr/bin/mysql *
www-data ALL=(ALL) NOPASSWD: /usr/bin/mysqldump *
```

---

## 7. Integration Architecture

### 7.1 External Systems

```
┌─────────────────────────────────────────────────────────────────────┐
│                    EXTERNAL INTEGRATIONS                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌─────────────────┐                                                │
│  │ Chwezi Server   │                                                │
│  │    Manager      │                                                │
│  └─────────────────┘                                                │
│         │                                                           │
│         ├──────────────────────────────────────────────────┐        │
│         │                                                  │        │
│         ▼                                                  ▼        │
│  ┌─────────────────┐                              ┌─────────────┐  │
│  │     GitHub      │                              │Let's Encrypt│  │
│  │                 │                              │             │  │
│  │ • Clone repos   │                              │ • SSL certs │  │
│  │ • Pull updates  │                              │ • Auto-renew│  │
│  │ • Submodules    │                              │             │  │
│  │                 │                              │             │  │
│  │ Protocol: SSH   │                              │Protocol:ACME│  │
│  │ Port: 22        │                              │ Port: 80/443│  │
│  └─────────────────┘                              └─────────────┘  │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    Namecheap (Manual)                        │   │
│  │                                                              │   │
│  │  • DNS configuration guidance displayed in UI                │   │
│  │  • User manually configures A records                        │   │
│  │  • DNS propagation check via dig command                     │   │
│  │                                                              │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 7.2 WordPress Integration

```
┌─────────────────────────────────────────────────────────────────────┐
│                  WORDPRESS INSTALLATION FLOW                        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. User Input                                                      │
│     └── Site name, domain, admin credentials                       │
│                                                                     │
│  2. DNS Check                                                       │
│     └── dig +short {domain} → Compare with server IP               │
│                                                                     │
│  3. Database Setup                                                  │
│     ├── CREATE DATABASE wp_{sitename}                              │
│     ├── CREATE USER wp_{sitename}@localhost                        │
│     └── GRANT ALL PRIVILEGES                                        │
│                                                                     │
│  4. WordPress Download                                              │
│     ├── wget https://wordpress.org/latest.tar.gz                   │
│     └── tar -xzf latest.tar.gz -C /var/www/html/{domain}           │
│                                                                     │
│  5. Configuration                                                   │
│     ├── Generate wp-config.php from template                       │
│     ├── Set database credentials                                    │
│     └── Generate security keys (wp_generate_password)              │
│                                                                     │
│  6. Apache Setup                                                    │
│     ├── Create virtual host config                                  │
│     ├── a2ensite {domain}.conf                                     │
│     └── systemctl reload apache2                                    │
│                                                                     │
│  7. SSL Installation                                                │
│     └── certbot --apache -d {domain}                               │
│                                                                     │
│  8. Permissions                                                     │
│     └── chown -R www-data:www-data /var/www/html/{domain}          │
│                                                                     │
│  9. Complete                                                        │
│     └── Return admin URL and credentials                           │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 8. Deployment Architecture

### 8.1 Server Layout

```
Ubuntu VPS (209.105.248.233)
│
├── /var/www/html/
│   ├── chwezi-sm/              # Server Manager Application
│   │   └── public/             # Document Root
│   │
│   ├── birdcerp/               # Managed App 1
│   │   └── public/
│   │
│   ├── medic8/                 # Managed App 2
│   │   └── public/
│   │
│   └── wordpress-sites/
│       ├── blog.example.com/   # WordPress Site 1
│       └── shop.example.com/   # WordPress Site 2
│
├── /etc/apache2/
│   ├── sites-available/
│   │   ├── chwezi-sm.conf
│   │   ├── erp.birdc.ug.conf
│   │   ├── medic8.com.conf
│   │   └── ...
│   └── sites-enabled/
│       └── (symlinks to available)
│
├── /etc/letsencrypt/
│   └── live/
│       ├── sm.example.com/
│       ├── erp.birdc.ug/
│       └── ...
│
├── /var/backups/
│   ├── databases/
│   │   ├── chwezi_sm/
│   │   ├── birdcerp/
│   │   └── ...
│   └── apps/
│       └── ...
│
└── /root/.ssh/
    ├── id_ed25519
    ├── id_ed25519.pub
    └── known_hosts
```

### 8.2 Network Ports

| Port | Service | Access |
|------|---------|--------|
| 22 | SSH | Admin only (key-based) |
| 80 | HTTP | Public (redirects to 443) |
| 443 | HTTPS | Public |
| 3306 | MySQL | Localhost only |

### 8.3 Backup Strategy

```
┌─────────────────────────────────────────────────────────────────────┐
│                       BACKUP STRATEGY                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Database Backups                                                   │
│  ├── Frequency: Daily (configurable per database)                  │
│  ├── Retention: 30 days (configurable)                             │
│  ├── Format: .sql.gz (compressed)                                  │
│  └── Location: /var/backups/databases/{db_name}/                   │
│                                                                     │
│  Pre-Update Backups                                                 │
│  ├── Trigger: Before every app update                              │
│  ├── Contents: Database only (code from Git)                       │
│  └── Location: /var/backups/apps/{app_name}/                       │
│                                                                     │
│  Configuration Backups                                              │
│  ├── Apache configs: Version controlled in Git                     │
│  ├── .env files: Manual backup recommended                         │
│  └── SSL certs: Managed by Certbot                                 │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Summary

The Chwezi Server Manager follows a clean MVC architecture with:

- **Simple routing** via index.php
- **Controller layer** for request handling
- **Service layer** for business logic and shell operations
- **Model layer** for database access
- **View layer** with PHP templates and TailwindCSS

Key architectural decisions:
- No framework dependencies for simplicity
- Secure shell execution via whitelist and validation
- Sudoers-based permission elevation
- MySQL for persistent storage
- File-based logging

---

**Document Version:** 1.0  
**Last Updated:** January 2025
