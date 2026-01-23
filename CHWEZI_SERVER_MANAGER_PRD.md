# Chwezi Server Manager
## Product Requirements Document & Software Requirements Specification

**Version:** 1.0  
**Date:** January 2025  
**Author:** Peter Bamuhigire  
**Project:** PHP Web Application for Server Management

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Product Overview](#2-product-overview)
3. [System Architecture](#3-system-architecture)
4. [Core Features](#4-core-features)
5. [Database Schema](#5-database-schema)
6. [API Specification](#6-api-specification)
7. [User Interface Design](#7-user-interface-design)
8. [Security Requirements](#8-security-requirements)
9. [Deployment Requirements](#9-deployment-requirements)
10. [Development Phases](#10-development-phases)

---

## 1. Executive Summary

### 1.1 Purpose

Chwezi Server Manager is a web-based PHP application that provides a unified interface for managing Ubuntu VPS servers. It replaces the need to manage multiple bash scripts by providing a GUI for common server administration tasks including Git deployments, WordPress installations, SSL certificate management, and domain configuration.

### 1.2 Problem Statement

Managing servers via SSH and multiple bash scripts is:
- Time-consuming and error-prone
- Difficult to track what's deployed where
- Requires remembering multiple commands
- No centralized view of server status
- Manual SSL renewal tracking

### 1.3 Solution

A single PHP web application that:
- Provides visual dashboard of all managed applications
- One-click deployments from GitHub
- Automated WordPress setup with full SSL
- DNS configuration guidance for Namecheap
- Centralized logging and monitoring
- Mobile-responsive interface

### 1.4 Target Users

- Solo developers managing multiple projects
- Small teams deploying web applications
- Agencies managing client websites

---

## 2. Product Overview

### 2.1 Product Name

**Chwezi Server Manager** (or "Chwezi SM")

### 2.2 Key Features Summary

| Feature | Description |
|---------|-------------|
| **Dashboard** | Overview of all apps, server health, SSL status |
| **App Manager** | Deploy, update, manage GitHub-based applications |
| **WordPress Installer** | One-click WordPress setup with database and SSL |
| **Domain Manager** | Configure Apache virtual hosts, manage DNS |
| **SSL Manager** | Install, renew, monitor Let's Encrypt certificates |
| **Database Manager** | Create, backup, restore MySQL databases |
| **Git Manager** | Configure SSH keys, manage repositories |
| **Submodules Manager** | Share code/skills across multiple apps |
| **Cron Manager** | Schedule automated tasks |
| **Log Viewer** | View Apache, PHP, application logs |
| **Terminal** | Web-based terminal for advanced tasks |

### 2.3 Technology Stack

| Component | Technology |
|-----------|------------|
| **Backend** | PHP 8.x |
| **Frontend** | HTML5, TailwindCSS, Alpine.js |
| **Database** | MySQL 8.x |
| **Web Server** | Apache 2.4 |
| **OS** | Ubuntu 22.04/24.04 LTS |
| **SSL** | Let's Encrypt (Certbot) |
| **Version Control** | Git |

### 2.4 Constraints

- Must run on the same server it manages
- PHP must have shell_exec enabled
- Root/sudo access required for system operations
- Designed for single-server deployments

---

## 3. System Architecture

### 3.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Web Browser                              │
│                    (Admin Interface)                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Apache Web Server                            │
│                   (Port 80/443 + SSL)                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Chwezi Server Manager                         │
│                      (PHP Application)                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │   Auth   │  │   API    │  │  Shell   │  │   Jobs   │        │
│  │  Module  │  │  Routes  │  │ Executor │  │  Queue   │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
└─────────────────────────────────────────────────────────────────┘
         │              │              │              │
         ▼              ▼              ▼              ▼
┌──────────────┐ ┌─────────────┐ ┌───────────┐ ┌────────────┐
│    MySQL     │ │   Apache    │ │   System  │ │   Cron     │
│   Database   │ │   Config    │ │  Commands │ │   Jobs     │
└──────────────┘ └─────────────┘ └───────────┘ └────────────┘
```

### 3.2 Directory Structure

```
/var/www/html/chwezi-sm/
├── index.php                 # Entry point
├── .env                      # Configuration
├── .htaccess                 # Apache rewrite rules
│
├── app/
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
│   │   ├── User.php
│   │   ├── App.php
│   │   ├── Domain.php
│   │   ├── Database.php
│   │   ├── SslCertificate.php
│   │   ├── Submodule.php
│   │   ├── SkillsRepository.php
│   │   ├── Skill.php
│   │   ├── CronJob.php
│   │   └── ActivityLog.php
│   │
│   ├── Services/
│   │   ├── ShellService.php      # Execute system commands
│   │   ├── ApacheService.php     # Manage Apache configs
│   │   ├── GitService.php        # Git operations
│   │   ├── SubmoduleService.php  # Git submodule operations
│   │   ├── SkillsService.php     # Skills repository management
│   │   ├── SslService.php        # Certbot operations
│   │   ├── DatabaseService.php   # MySQL operations
│   │   ├── WordPressService.php  # WordPress setup
│   │   ├── DnsService.php        # DNS verification
│   │   └── BackupService.php     # Backup operations
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   └── CsrfMiddleware.php
│   │
│   └── Helpers/
│       ├── functions.php
│       └── validators.php
│
├── config/
│   ├── app.php
│   ├── database.php
│   └── paths.php
│
├── database/
│   ├── migrations/
│   │   └── 001_create_tables.sql
│   └── seeds/
│       └── admin_user.sql
│
├── public/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   └── app.js
│   └── img/
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   └── main.php
│   │   ├── auth/
│   │   │   └── login.php
│   │   ├── dashboard/
│   │   │   └── index.php
│   │   ├── apps/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   └── show.php
│   │   ├── wordpress/
│   │   │   └── create.php
│   │   ├── domains/
│   │   │   └── index.php
│   │   ├── ssl/
│   │   │   └── index.php
│   │   ├── databases/
│   │   │   └── index.php
│   │   ├── cron/
│   │   │   └── index.php
│   │   ├── logs/
│   │   │   └── index.php
│   │   └── terminal/
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
│   ├── backups/
│   └── cache/
│
├── scripts/
│   ├── install.sh            # Initial setup script
│   ├── sudoers-setup.sh      # Configure sudo permissions
│   └── cron-runner.php       # Cron job executor
│
└── vendor/                   # Composer dependencies (optional)
```

### 3.3 Component Interactions

```
User Action: "Deploy New App"
       │
       ▼
┌─────────────────┐
│ AppController   │──────► Validate Input
└─────────────────┘
       │
       ▼
┌─────────────────┐        ┌─────────────────┐
│ GitService      │───────►│ Clone Repository│
└─────────────────┘        └─────────────────┘
       │
       ▼
┌─────────────────┐        ┌─────────────────┐
│ ApacheService   │───────►│ Create VHost    │
└─────────────────┘        └─────────────────┘
       │
       ▼
┌─────────────────┐        ┌─────────────────┐
│ SslService      │───────►│ Install SSL     │
└─────────────────┘        └─────────────────┘
       │
       ▼
┌─────────────────┐        ┌─────────────────┐
│ App Model       │───────►│ Save to Database│
└─────────────────┘        └─────────────────┘
       │
       ▼
┌─────────────────┐
│ Return Success  │
└─────────────────┘
```

---

## 4. Core Features

### 4.1 Authentication & Authorization

#### 4.1.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| AUTH-001 | Single admin user authentication | High |
| AUTH-002 | Secure password hashing (bcrypt) | High |
| AUTH-003 | Session-based authentication | High |
| AUTH-004 | Remember me functionality | Medium |
| AUTH-005 | Password change capability | High |
| AUTH-006 | Session timeout after 30 minutes | Medium |
| AUTH-007 | CSRF protection on all forms | High |
| AUTH-008 | Two-factor authentication (TOTP) | Low |

#### 4.1.2 User Flow

```
┌─────────┐    ┌─────────────┐    ┌──────────┐    ┌───────────┐
│  Login  │───►│  Validate   │───►│  Create  │───►│ Dashboard │
│  Page   │    │ Credentials │    │ Session  │    │           │
└─────────┘    └─────────────┘    └──────────┘    └───────────┘
```

---

### 4.2 Dashboard

#### 4.2.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| DASH-001 | Display total apps count | High |
| DASH-002 | Display active domains count | High |
| DASH-003 | Display SSL certificates status | High |
| DASH-004 | Show SSL expiring within 14 days | High |
| DASH-005 | Server health metrics (CPU, RAM, Disk) | Medium |
| DASH-006 | Recent activity log | Medium |
| DASH-007 | Quick action buttons | Medium |
| DASH-008 | Apache status indicator | Medium |
| DASH-009 | MySQL status indicator | Medium |

#### 4.2.2 Dashboard Widgets

```
┌─────────────────────────────────────────────────────────────────┐
│                      Chwezi Server Manager                      │
├──────────────┬──────────────┬──────────────┬───────────────────┤
│    Apps      │   Domains    │     SSL      │   Server Health   │
│     12       │      8       │   ✓ 7 OK     │   CPU: 23%        │
│   Active     │   Configured │   ⚠ 1 Expiring│   RAM: 45%        │
│              │              │              │   Disk: 67%       │
├──────────────┴──────────────┴──────────────┴───────────────────┤
│                      Quick Actions                              │
│  [+ New App]  [+ WordPress]  [+ Domain]  [Renew All SSL]       │
├─────────────────────────────────────────────────────────────────┤
│                    Recent Activity                              │
│  • 10:30 - Updated erp.birdc.ug from GitHub                    │
│  • 09:15 - SSL renewed for demo.chwezi.tech                    │
│  • Yesterday - Created new WordPress site blog.example.com     │
└─────────────────────────────────────────────────────────────────┘
```

---

### 4.3 Application Manager

#### 4.3.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| APP-001 | List all deployed applications | High |
| APP-002 | Deploy new app from GitHub | High |
| APP-003 | Choose deployment strategy (force/merge) | High |
| APP-004 | One-click update from GitHub | High |
| APP-005 | View last update timestamp | Medium |
| APP-006 | View current Git commit | Medium |
| APP-007 | Rollback to previous commit | Medium |
| APP-008 | Delete application | High |
| APP-009 | View application logs | Medium |
| APP-010 | Detect app type (Laravel, WordPress, Node, etc.) | Medium |
| APP-011 | Run post-update commands (migrations, cache clear) | High |
| APP-012 | Database backup before update | High |

#### 4.3.2 App Deployment Form

**Fields:**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| App Name | text | Yes | alphanumeric, 3-50 chars |
| Domain | text | Yes | valid domain format |
| GitHub URL | text | Yes | valid GitHub URL |
| Branch | text | Yes | default: main |
| App Directory | text | Yes | auto-generated |
| Document Root | text | Yes | auto-detected or custom |
| Deployment Strategy | select | Yes | force/merge |
| Install SSL | checkbox | No | default: true |
| Database Name | text | No | for Laravel apps |
| Run Migrations | checkbox | No | for Laravel apps |

#### 4.3.3 Deployment Process

```
1. Validate inputs
2. Check domain DNS points to server
3. Create app directory
4. Configure Git SSH
5. Clone repository
6. Detect app type
7. Create Apache virtual host
8. Install SSL certificate
9. Set permissions
10. Run post-install commands
11. Save to database
12. Return success
```

#### 4.3.4 Update Process

```
1. Check for updates (git fetch)
2. Show pending commits
3. Backup database (if Laravel)
4. Enable maintenance mode (if Laravel)
5. Pull changes (force or merge)
6. Install dependencies (composer/npm)
7. Run migrations (if Laravel)
8. Clear caches
9. Disable maintenance mode
10. Update last_updated timestamp
11. Log activity
```

---

### 4.4 WordPress Installer

#### 4.4.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| WP-001 | One-click WordPress installation | High |
| WP-002 | Automatic database creation | High |
| WP-003 | Automatic wp-config.php generation | High |
| WP-004 | SSL certificate installation | High |
| WP-005 | Apache virtual host configuration | High |
| WP-006 | Set proper file permissions | High |
| WP-007 | DNS verification before install | Medium |
| WP-008 | Namecheap DNS configuration guide | High |
| WP-009 | Custom table prefix | Medium |
| WP-010 | Admin user creation | Medium |

#### 4.4.2 WordPress Installation Form

**Fields:**

| Field | Type | Required | Default |
|-------|------|----------|---------|
| Site Title | text | Yes | - |
| Domain | text | Yes | - |
| Admin Username | text | Yes | - |
| Admin Password | password | Yes | (generated) |
| Admin Email | email | Yes | - |
| Database Name | text | Yes | (auto-generated) |
| Table Prefix | text | No | wp_ |
| Install SSL | checkbox | No | true |

#### 4.4.3 WordPress Installation Process

```
Step 1: Validate Inputs
    └── Check domain format
    └── Verify DNS points to server (optional)
    └── Ensure no existing installation

Step 2: Create Database
    └── Generate database name: wp_[sitename]
    └── Generate database user: wp_[sitename]
    └── Generate strong password
    └── Create MySQL database
    └── Create MySQL user with privileges

Step 3: Download WordPress
    └── Download latest WordPress
    └── Extract to /var/www/html/[domain]
    └── Set permissions (www-data:www-data)

Step 4: Configure WordPress
    └── Generate wp-config.php
    └── Set database credentials
    └── Generate security keys
    └── Set table prefix
    └── Enable debug (optional)

Step 5: Configure Apache
    └── Create virtual host file
    └── Enable site
    └── Reload Apache

Step 6: Install SSL
    └── Run certbot
    └── Auto-configure HTTPS redirect
    └── Verify certificate

Step 7: Complete Setup
    └── Save to database
    └── Show admin credentials
    └── Provide login URL
```

#### 4.4.4 Namecheap DNS Configuration Guide

When user enters a domain, show DNS configuration steps:

```
┌─────────────────────────────────────────────────────────────────┐
│           DNS Configuration for example.com                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Your server IP: 209.105.248.233                               │
│                                                                 │
│  Configure these records in Namecheap:                         │
│                                                                 │
│  1. Log into Namecheap: https://ap.www.namecheap.com           │
│  2. Click "Domain List" → "Manage" on your domain              │
│  3. Click "Advanced DNS"                                        │
│  4. Add/Edit these records:                                     │
│                                                                 │
│  ┌──────────┬────────┬─────────────────┬───────┐               │
│  │  Type    │  Host  │     Value       │  TTL  │               │
│  ├──────────┼────────┼─────────────────┼───────┤               │
│  │  A       │   @    │ 209.105.248.233 │ Auto  │               │
│  │  A       │  www   │ 209.105.248.233 │ Auto  │               │
│  │  CNAME   │  www   │ example.com     │ Auto  │               │
│  └──────────┴────────┴─────────────────┴───────┘               │
│                                                                 │
│  5. Wait 5-30 minutes for DNS propagation                      │
│                                                                 │
│  [Check DNS Now]  [I've Configured DNS - Continue]             │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 4.5 Domain Manager

#### 4.5.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| DOM-001 | List all configured domains | High |
| DOM-002 | Add new domain | High |
| DOM-003 | Edit domain configuration | Medium |
| DOM-004 | Delete domain | High |
| DOM-005 | DNS verification | Medium |
| DOM-006 | Apache virtual host management | High |
| DOM-007 | Enable/disable domain | Medium |
| DOM-008 | Redirect domain to another | Low |

#### 4.5.2 Domain Configuration

```php
// Domain data structure
[
    'id' => 1,
    'domain' => 'erp.birdc.ug',
    'document_root' => '/var/www/html/birdcerp/public',
    'app_id' => 5,
    'ssl_enabled' => true,
    'ssl_expiry' => '2025-04-07',
    'status' => 'active',
    'created_at' => '2025-01-01',
]
```

---

### 4.6 SSL Manager

#### 4.6.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| SSL-001 | List all SSL certificates | High |
| SSL-002 | Install new SSL certificate | High |
| SSL-003 | Renew SSL certificate | High |
| SSL-004 | Auto-renew expiring certificates | High |
| SSL-005 | Show expiry dates | High |
| SSL-006 | Alert for certificates expiring in 14 days | High |
| SSL-007 | Delete SSL certificate | Medium |
| SSL-008 | View certificate details | Low |

#### 4.6.2 SSL Dashboard

```
┌─────────────────────────────────────────────────────────────────┐
│                    SSL Certificates                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [Renew All Expiring]                                          │
│                                                                 │
│  ┌─────────────────────┬────────────┬──────────┬─────────────┐ │
│  │  Domain             │  Expiry    │  Status  │  Actions    │ │
│  ├─────────────────────┼────────────┼──────────┼─────────────┤ │
│  │ erp.birdc.ug        │ Apr 7, 25  │ ✓ Valid  │ [Renew]     │ │
│  │ demo.chwezi.tech    │ Jan 20, 25 │ ⚠ Soon   │ [Renew]     │ │
│  │ blog.example.com    │ Mar 15, 25 │ ✓ Valid  │ [Renew]     │ │
│  └─────────────────────┴────────────┴──────────┴─────────────┘ │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 4.7 Database Manager

#### 4.7.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| DB-001 | List all databases | High |
| DB-002 | Create new database | High |
| DB-003 | Create database user | High |
| DB-004 | Backup database | High |
| DB-005 | Restore database | High |
| DB-006 | Delete database | High |
| DB-007 | View database size | Medium |
| DB-008 | Export database (SQL file) | Medium |
| DB-009 | Import database | Medium |
| DB-010 | Scheduled backups | Medium |

#### 4.7.2 Database Backup

```php
// Backup configuration
[
    'database' => 'birdcerp',
    'backup_dir' => '/var/backups/databases/',
    'retention_days' => 30,
    'compression' => 'gzip',
    'schedule' => 'daily',
]
```

---

### 4.8 Git Manager

#### 4.8.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| GIT-001 | Display SSH public key | High |
| GIT-002 | Generate new SSH key | Medium |
| GIT-003 | Test GitHub connection | High |
| GIT-004 | List configured repositories | Medium |
| GIT-005 | Add new repository | Medium |
| GIT-006 | Instructions for adding key to GitHub | High |

#### 4.8.2 Git Setup Interface

```
┌─────────────────────────────────────────────────────────────────┐
│                    Git Configuration                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  SSH Public Key (add this to GitHub):                          │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIMxK8Q7nP2Q...      │   │
│  │                                          [Copy]         │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  GitHub SSH Status: ✓ Connected                                │
│  [Test Connection]  [Generate New Key]                         │
│                                                                 │
│  Instructions:                                                  │
│  1. Copy the SSH key above                                     │
│  2. Go to GitHub → Settings → SSH Keys                         │
│  3. Click "New SSH key"                                        │
│  4. Paste the key and save                                     │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

### 4.9 Cron Manager

#### 4.9.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| CRON-001 | List all cron jobs | High |
| CRON-002 | Create new cron job | High |
| CRON-003 | Edit cron job | Medium |
| CRON-004 | Delete cron job | High |
| CRON-005 | Enable/disable cron job | Medium |
| CRON-006 | View cron job logs | Medium |
| CRON-007 | Manual run cron job | Medium |
| CRON-008 | Common schedule presets | Low |

#### 4.9.2 Cron Job Types

| Type | Description |
|------|-------------|
| App Update | Update specific app from GitHub |
| SSL Renewal | Renew SSL certificates |
| Database Backup | Backup specific database |
| Custom Command | Run any shell command |
| Laravel Schedule | Run Laravel scheduler |

---

### 4.10 Log Viewer

#### 4.10.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| LOG-001 | View Apache error logs | High |
| LOG-002 | View Apache access logs | Medium |
| LOG-003 | View PHP error logs | High |
| LOG-004 | View Laravel logs | High |
| LOG-005 | Filter logs by date | Medium |
| LOG-006 | Filter logs by level | Medium |
| LOG-007 | Real-time log tailing | Low |
| LOG-008 | Download log files | Low |

---

### 4.11 Web Terminal

#### 4.11.1 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| TERM-001 | Execute shell commands | Medium |
| TERM-002 | Command history | Low |
| TERM-003 | Working directory navigation | Low |
| TERM-004 | Command output display | Medium |
| TERM-005 | Dangerous command warnings | High |

#### 4.11.2 Security Restrictions

Commands that should be blocked or warned:
- `rm -rf /`
- `dd if=/dev/zero`
- Anything with `sudo rm` on system directories
- `chmod -R 777`
- `shutdown`, `reboot` (without confirmation)

---

### 4.12 Git Submodules Manager

#### 4.12.1 Overview

Git Submodules allow sharing code/skills across multiple projects. This feature enables:
- Central repository for shared code (e.g., Claude Skills, common libraries)
- One pull brings both project and shared code
- Easy team collaboration

#### 4.12.2 Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| SUB-001 | List all submodules in an app | High |
| SUB-002 | Add new submodule to app | High |
| SUB-003 | Remove submodule from app | Medium |
| SUB-004 | Update submodule to latest | High |
| SUB-005 | Configure auto-recurse for pulls | High |
| SUB-006 | View submodule status (commit, branch) | Medium |
| SUB-007 | Create central skills repository | Medium |
| SUB-008 | Sync submodule across multiple apps | Medium |
| SUB-009 | Clone app with submodules | High |

#### 4.12.3 Submodule Configuration Interface

```
┌─────────────────────────────────────────────────────────────────┐
│              Git Submodules - BIRDC ERP                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Auto-recurse on pull: ✅ Enabled                              │
│  [Configure Auto-Recurse]                                       │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────────┐
│  │  Submodules                                      [+ Add]    │
│  ├─────────────────┬──────────────────┬─────────┬─────────────┤
│  │  Path           │  Repository      │  Branch │  Actions    │
│  ├─────────────────┼──────────────────┼─────────┼─────────────┤
│  │ skills/claude   │ chwezi/skills    │ main    │ [↻] [✎] [×] │
│  │ vendor/common   │ chwezi/common-lib│ main    │ [↻] [✎] [×] │
│  └─────────────────┴──────────────────┴─────────┴─────────────┘
│                                                                 │
│  [Update All Submodules]  [Sync to Other Apps]                 │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### 4.12.4 Add Submodule Form

```
┌─────────────────────────────────────────────────────────────────┐
│                    Add Git Submodule                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Submodule Repository URL *                                     │
│  [git@github.com:chwezi/claude-skills.git____]                 │
│                                                                 │
│  Local Path *                                                   │
│  [skills/claude-skills_________________________]                │
│                                                                 │
│  Branch to Track                                                │
│  [main_________________________________________]                │
│                                                                 │
│  ☑ Configure auto-recurse (recommended)                        │
│  ☐ Add to other apps too:                                       │
│     ☐ Medic8                                                    │
│     ☐ KesiLex                                                   │
│     ☐ BrightSoma                                                │
│                                                                 │
│  [Cancel]                              [Add Submodule]          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### 4.12.5 Submodule Operations

**Add Submodule:**
```bash
cd /var/www/html/app-directory

# Add submodule
git submodule add {REPO_URL} {LOCAL_PATH}

# Configure branch tracking
git config submodule.{LOCAL_PATH}.branch {BRANCH}

# Configure auto-recurse
git config submodule.recurse true

# Commit changes
git add .gitmodules {LOCAL_PATH}
git commit -m "Add {SUBMODULE_NAME} submodule"
```

**Update Submodule:**
```bash
cd /var/www/html/app-directory

# Update to latest
git submodule update --remote {LOCAL_PATH}

# Or update all
git submodule update --remote --merge

# Commit if changed
git add {LOCAL_PATH}
git commit -m "Update {SUBMODULE_NAME} to latest"
```

**Remove Submodule:**
```bash
cd /var/www/html/app-directory

# Remove from .gitmodules
git submodule deinit -f {LOCAL_PATH}

# Remove from .git/modules
rm -rf .git/modules/{LOCAL_PATH}

# Remove directory
git rm -f {LOCAL_PATH}

# Commit
git commit -m "Remove {SUBMODULE_NAME} submodule"
```

**Clone with Submodules:**
```bash
# Clone including all submodules
git clone --recurse-submodules {REPO_URL}

# Or if already cloned
git submodule update --init --recursive
```

#### 4.12.6 Central Skills Repository

Feature to create and manage a central repository for Claude AI skills:

```
┌─────────────────────────────────────────────────────────────────┐
│              Claude Skills Repository                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Repository: git@github.com:chwezi/claude-skills.git           │
│  Status: ✅ Connected                                          │
│                                                                 │
│  Skills Available:                                              │
│  ┌─────────────────────────────────────────────────────────────┐
│  │ • multi-tenant-saas-architecture (3,500 lines)             │
│  │ • laravel-best-practices (2,100 lines)                     │
│  │ • api-design-patterns (1,800 lines)                        │
│  │ • security-hardening (1,200 lines)                         │
│  └─────────────────────────────────────────────────────────────┘
│                                                                 │
│  Deployed to Apps:                                              │
│  ✅ Maduuka    ✅ Medic8    ✅ KesiLex    ✅ BrightSoma        │
│                                                                 │
│  [+ Add New Skill]  [Deploy to All Apps]  [Update All Apps]    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### 4.12.7 Workflow Integration

When updating an app, the system automatically:
1. Checks if submodules exist
2. If auto-recurse enabled: single `git pull` updates everything
3. If not: runs `git submodule update --remote` after pull
4. Reports submodule update status

```
┌─────────────────────────────────────────────────────────────────┐
│              Update Results - BIRDC ERP                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ✅ Main Repository                                            │
│     From: abc1234 → To: def5678                                │
│     3 commits pulled                                            │
│                                                                 │
│  ✅ Submodule: skills/claude-skills                            │
│     From: 111aaa → To: 222bbb                                  │
│     1 commit pulled (new skill added)                          │
│                                                                 │
│  ✅ Submodule: vendor/common-lib                               │
│     Already up to date                                          │
│                                                                 │
│  Total: 4 commits applied                                       │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### 4.12.8 Database Schema Addition

```sql
-- Submodules table
CREATE TABLE submodules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    path VARCHAR(255) NOT NULL,
    repository_url VARCHAR(255) NOT NULL,
    branch VARCHAR(50) DEFAULT 'main',
    current_commit VARCHAR(40) NULL,
    last_updated TIMESTAMP NULL,
    auto_update BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    UNIQUE KEY unique_app_path (app_id, path)
);

-- Skills repository table (central)
CREATE TABLE skills_repositories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    repository_url VARCHAR(255) NOT NULL,
    description TEXT NULL,
    skills_count INT DEFAULT 0,
    last_sync TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Skills tracking
CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repository_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    path VARCHAR(255) NOT NULL,
    description TEXT NULL,
    lines_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repository_id) REFERENCES skills_repositories(id) ON DELETE CASCADE
);
```

---

## 5. Database Schema

### 5.1 Entity Relationship Diagram

```
┌─────────────┐       ┌─────────────┐       ┌─────────────────┐
│   users     │       │    apps     │       │    domains      │
├─────────────┤       ├─────────────┤       ├─────────────────┤
│ id          │       │ id          │◄──────│ id              │
│ username    │       │ name        │       │ domain          │
│ password    │       │ github_url  │       │ app_id          │
│ email       │       │ branch      │       │ document_root   │
│ created_at  │       │ directory   │       │ ssl_enabled     │
│ updated_at  │       │ doc_root    │       │ ssl_expiry      │
└─────────────┘       │ app_type    │       │ status          │
                      │ strategy    │       │ created_at      │
                      │ last_commit │       └─────────────────┘
                      │ last_update │
                      │ status      │       ┌─────────────────┐
                      │ created_at  │       │  ssl_certs      │
                      └─────────────┘       ├─────────────────┤
                                            │ id              │
┌─────────────────┐   ┌─────────────────┐   │ domain_id       │
│   databases     │   │   cron_jobs     │   │ certificate_path│
├─────────────────┤   ├─────────────────┤   │ key_path        │
│ id              │   │ id              │   │ expiry_date     │
│ name            │   │ name            │   │ issued_date     │
│ username        │   │ command         │   │ auto_renew      │
│ app_id          │   │ schedule        │   │ created_at      │
│ size_mb         │   │ app_id          │   └─────────────────┘
│ created_at      │   │ enabled         │
│ last_backup     │   │ last_run        │   ┌─────────────────┐
└─────────────────┘   │ next_run        │   │ activity_logs   │
                      │ created_at      │   ├─────────────────┤
                      └─────────────────┘   │ id              │
                                            │ user_id         │
                                            │ action          │
                                            │ entity_type     │
                                            │ entity_id       │
                                            │ details         │
                                            │ ip_address      │
                                            │ created_at      │
                                            └─────────────────┘
```

### 5.2 Table Definitions

#### 5.2.1 users

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    totp_secret VARCHAR(32) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 5.2.2 apps

```sql
CREATE TABLE apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    github_url VARCHAR(255) NOT NULL,
    branch VARCHAR(50) DEFAULT 'main',
    directory VARCHAR(255) NOT NULL,
    document_root VARCHAR(255) NOT NULL,
    app_type ENUM('laravel', 'wordpress', 'nodejs', 'php', 'static', 'other') DEFAULT 'php',
    deployment_strategy ENUM('force', 'merge') DEFAULT 'merge',
    last_commit VARCHAR(40) NULL,
    last_update TIMESTAMP NULL,
    status ENUM('active', 'maintenance', 'disabled') DEFAULT 'active',
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 5.2.3 domains

```sql
CREATE TABLE domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL UNIQUE,
    app_id INT NULL,
    document_root VARCHAR(255) NOT NULL,
    config_file VARCHAR(255) NULL,
    ssl_enabled BOOLEAN DEFAULT FALSE,
    ssl_expiry DATE NULL,
    redirect_to VARCHAR(255) NULL,
    status ENUM('active', 'disabled', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE SET NULL
);
```

#### 5.2.4 ssl_certificates

```sql
CREATE TABLE ssl_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_id INT NOT NULL,
    certificate_path VARCHAR(255) NOT NULL,
    key_path VARCHAR(255) NOT NULL,
    chain_path VARCHAR(255) NULL,
    issuer VARCHAR(100) DEFAULT 'Let''s Encrypt',
    issued_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    last_renewal_attempt TIMESTAMP NULL,
    renewal_status ENUM('success', 'failed', 'pending') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE
);
```

#### 5.2.5 databases_managed

```sql
CREATE TABLE databases_managed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    username VARCHAR(32) NOT NULL,
    app_id INT NULL,
    size_mb DECIMAL(10,2) DEFAULT 0,
    last_backup TIMESTAMP NULL,
    backup_schedule VARCHAR(50) NULL,
    backup_retention_days INT DEFAULT 30,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE SET NULL
);
```

#### 5.2.6 cron_jobs

```sql
CREATE TABLE cron_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    command TEXT NOT NULL,
    schedule VARCHAR(100) NOT NULL,
    job_type ENUM('app_update', 'ssl_renewal', 'db_backup', 'laravel_schedule', 'custom') DEFAULT 'custom',
    app_id INT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    last_run TIMESTAMP NULL,
    last_status ENUM('success', 'failed') NULL,
    last_output TEXT NULL,
    next_run TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE
);
```

#### 5.2.7 activity_logs

```sql
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
);
```

#### 5.2.8 settings

```sql
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NULL,
    type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default settings
INSERT INTO settings (key_name, value, type, description) VALUES
('server_ip', '209.105.248.233', 'string', 'Server IP address'),
('default_branch', 'main', 'string', 'Default Git branch'),
('ssl_email', 'admin@example.com', 'string', 'Email for SSL certificates'),
('auto_ssl_renew', '1', 'boolean', 'Auto-renew SSL certificates'),
('backup_retention_days', '30', 'integer', 'Days to keep backups'),
('maintenance_secret', 'update-in-progress', 'string', 'Laravel maintenance bypass secret');
```

---

## 6. API Specification

### 6.1 Route Structure

```php
// Authentication
POST   /login                    # Login
POST   /logout                   # Logout
GET    /profile                  # View profile
POST   /profile/password         # Change password

// Dashboard
GET    /                         # Dashboard
GET    /api/stats                # Get dashboard stats (AJAX)

// Apps
GET    /apps                     # List apps
GET    /apps/create              # Create app form
POST   /apps                     # Deploy new app
GET    /apps/{id}                # View app details
POST   /apps/{id}/update         # Update app from GitHub
POST   /apps/{id}/rollback       # Rollback to previous commit
DELETE /apps/{id}                # Delete app
GET    /apps/{id}/logs           # View app logs

// WordPress
GET    /wordpress/create         # WordPress install form
POST   /wordpress                # Install WordPress
GET    /wordpress/dns-check      # Check DNS (AJAX)

// Domains
GET    /domains                  # List domains
POST   /domains                  # Add domain
PUT    /domains/{id}             # Update domain
DELETE /domains/{id}             # Delete domain
POST   /domains/{id}/enable      # Enable domain
POST   /domains/{id}/disable     # Disable domain

// SSL
GET    /ssl                      # List SSL certificates
POST   /ssl/install              # Install SSL for domain
POST   /ssl/{id}/renew           # Renew SSL certificate
POST   /ssl/renew-all            # Renew all expiring
DELETE /ssl/{id}                 # Delete SSL certificate

// Databases
GET    /databases                # List databases
POST   /databases                # Create database
DELETE /databases/{id}           # Delete database
POST   /databases/{id}/backup    # Backup database
POST   /databases/{id}/restore   # Restore database
GET    /databases/{id}/download  # Download backup

// Git
GET    /git                      # Git configuration
POST   /git/generate-key         # Generate new SSH key
GET    /git/test-connection      # Test GitHub connection
GET    /git/public-key           # Get public key

// Cron
GET    /cron                     # List cron jobs
POST   /cron                     # Create cron job
PUT    /cron/{id}                # Update cron job
DELETE /cron/{id}                # Delete cron job
POST   /cron/{id}/run            # Manual run
POST   /cron/{id}/toggle         # Enable/disable

// Logs
GET    /logs                     # Log viewer
GET    /logs/apache/error        # Apache error log
GET    /logs/apache/access       # Apache access log
GET    /logs/php                 # PHP error log
GET    /logs/app/{id}            # App-specific logs

// Terminal
GET    /terminal                 # Terminal interface
POST   /terminal/execute         # Execute command

// Submodules
GET    /submodules               # List all submodules across apps
GET    /apps/{id}/submodules     # List submodules for specific app
POST   /apps/{id}/submodules     # Add submodule to app
PUT    /apps/{id}/submodules/{subId}    # Update submodule config
DELETE /apps/{id}/submodules/{subId}    # Remove submodule
POST   /apps/{id}/submodules/{subId}/update  # Update submodule to latest
POST   /apps/{id}/submodules/update-all      # Update all submodules
POST   /apps/{id}/submodules/configure-recurse # Enable auto-recurse

// Skills Repository (Central)
GET    /skills                   # List skills repositories
POST   /skills/repository        # Add skills repository
GET    /skills/repository/{id}   # View repository details
POST   /skills/deploy            # Deploy skills to selected apps
POST   /skills/sync              # Sync skills across all apps

// Settings
GET    /settings                 # Settings page
POST   /settings                 # Save settings
```

### 6.2 API Response Format

```json
// Success response
{
    "success": true,
    "message": "App deployed successfully",
    "data": {
        "id": 5,
        "name": "BIRDC ERP",
        "domain": "erp.birdc.ug"
    }
}

// Error response
{
    "success": false,
    "message": "Failed to deploy app",
    "errors": {
        "github_url": "Invalid GitHub URL format"
    }
}
```

---

## 7. User Interface Design

### 7.1 Design Principles

- **Clean and minimal** - Focus on functionality
- **Mobile responsive** - Work on tablets and phones
- **Fast and lightweight** - Minimal JavaScript
- **Consistent** - Same patterns throughout
- **Accessible** - Keyboard navigation, screen readers

### 7.2 Color Scheme

| Element | Color | Hex |
|---------|-------|-----|
| Primary | Blue | #3B82F6 |
| Success | Green | #10B981 |
| Warning | Yellow | #F59E0B |
| Danger | Red | #EF4444 |
| Background | Gray | #F3F4F6 |
| Card | White | #FFFFFF |
| Text | Dark Gray | #1F2937 |

### 7.3 Layout Structure

```
┌─────────────────────────────────────────────────────────────────┐
│  [Logo]  Chwezi Server Manager          [Profile] [Logout]      │
├──────────────────┬──────────────────────────────────────────────┤
│                  │                                              │
│  📊 Dashboard    │         Main Content Area                    │
│  📱 Apps         │                                              │
│  🌐 WordPress    │         (Page-specific content)              │
│  🔗 Domains      │                                              │
│  🔒 SSL          │                                              │
│  🗄️ Databases    │                                              │
│  📁 Git          │                                              │
│  🔄 Submodules   │                                              │
│  🧠 Skills       │                                              │
│  ⏰ Cron Jobs    │                                              │
│  📋 Logs         │                                              │
│  💻 Terminal     │                                              │
│  ⚙️ Settings     │                                              │
│                  │                                              │
├──────────────────┴──────────────────────────────────────────────┤
│  Chwezi Server Manager v1.0 | © 2025                           │
└─────────────────────────────────────────────────────────────────┘
```

### 7.4 Key Screens

#### 7.4.1 Login Screen

```
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│                                                                 │
│                    ┌─────────────────────┐                      │
│                    │  Chwezi SM          │                      │
│                    │                     │                      │
│                    │  Username           │                      │
│                    │  [________________] │                      │
│                    │                     │                      │
│                    │  Password           │                      │
│                    │  [________________] │                      │
│                    │                     │                      │
│                    │  ☐ Remember me      │                      │
│                    │                     │                      │
│                    │  [     Login     ]  │                      │
│                    │                     │                      │
│                    └─────────────────────┘                      │
│                                                                 │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### 7.4.2 App Deployment Form

```
┌─────────────────────────────────────────────────────────────────┐
│                    Deploy New Application                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  App Name *                                                     │
│  [BIRDC ERP_______________________________]                     │
│                                                                 │
│  Domain *                                                       │
│  [erp.birdc.ug_______________________________]                  │
│                                                                 │
│  GitHub Repository URL *                                        │
│  [git@github.com:chwezi/birdcerp.git________]                  │
│                                                                 │
│  Branch *                        Deployment Strategy *          │
│  [main___________]               (●) Merge (preserve local)     │
│                                  ( ) Force (overwrite local)    │
│                                                                 │
│  ─────────────── Advanced Options ───────────────               │
│                                                                 │
│  App Directory                                                  │
│  [/var/www/html/birdcerp_____________________]                  │
│                                                                 │
│  Document Root                                                  │
│  [/var/www/html/birdcerp/public______________]                  │
│                                                                 │
│  ☑ Install SSL Certificate                                      │
│  ☑ Create Database (Laravel)                                    │
│  ☑ Run Migrations                                               │
│                                                                 │
│  [Cancel]                              [Deploy Application]     │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### 7.4.3 WordPress Installation

```
┌─────────────────────────────────────────────────────────────────┐
│                    Install WordPress                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Step 1 of 3: Site Information                           │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  Site Title *                                                   │
│  [My Awesome Blog___________________________]                   │
│                                                                 │
│  Domain *                                                       │
│  [blog.example.com__________________________]                   │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ ⚠️ DNS Not Configured                                    │  │
│  │                                                          │  │
│  │ Your domain is not pointing to this server yet.         │  │
│  │ Please configure these DNS records in Namecheap:        │  │
│  │                                                          │  │
│  │ Type: A    Host: @    Value: 209.105.248.233            │  │
│  │ Type: A    Host: www  Value: 209.105.248.233            │  │
│  │                                                          │  │
│  │ [Open Namecheap DNS Settings]  [Check DNS Again]        │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  [Back]                                             [Next →]    │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 8. Security Requirements

### 8.1 Authentication Security

| Requirement | Implementation |
|-------------|----------------|
| Password Hashing | bcrypt with cost 12 |
| Session Security | HTTPOnly, Secure, SameSite cookies |
| CSRF Protection | Token in all forms |
| Brute Force Protection | Rate limiting (5 attempts/minute) |
| Session Timeout | 30 minutes inactivity |

### 8.2 Authorization

| Action | Required Permission |
|--------|---------------------|
| All actions | Authenticated user |
| Delete app | Confirmation required |
| Delete database | Confirmation + password |
| Terminal access | Enabled in settings |

### 8.3 Input Validation

- All inputs sanitized before use
- SQL queries use prepared statements
- Shell commands use escapeshellarg()
- File paths validated against allowed directories

### 8.4 Shell Command Security

```php
// Safe shell execution wrapper
class ShellService {
    private array $allowedCommands = [
        'git', 'certbot', 'apache2ctl', 'systemctl',
        'composer', 'npm', 'php', 'mysql', 'mysqldump'
    ];
    
    private array $blockedPatterns = [
        '/rm\s+-rf\s+\//',
        '/dd\s+if=/',
        '/chmod\s+-R\s+777/',
        '/>\s*\/dev\//',
    ];
    
    public function execute(string $command): array {
        // Validate command is allowed
        // Check for dangerous patterns
        // Execute with timeout
        // Return output and status
    }
}
```

### 8.5 File System Security

- App runs as www-data user
- Sudo permissions via sudoers.d file
- Limited to specific commands
- No direct root access

```bash
# /etc/sudoers.d/chwezi-sm
www-data ALL=(ALL) NOPASSWD: /usr/bin/certbot
www-data ALL=(ALL) NOPASSWD: /usr/sbin/a2ensite
www-data ALL=(ALL) NOPASSWD: /usr/sbin/a2dissite
www-data ALL=(ALL) NOPASSWD: /usr/sbin/apache2ctl
www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload apache2
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart apache2
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart php*-fpm
www-data ALL=(ALL) NOPASSWD: /usr/bin/git
www-data ALL=(ALL) NOPASSWD: /bin/chown
www-data ALL=(ALL) NOPASSWD: /bin/chmod
www-data ALL=(ALL) NOPASSWD: /usr/bin/mysql
www-data ALL=(ALL) NOPASSWD: /usr/bin/mysqldump
```

---

## 9. Deployment Requirements

### 9.1 Server Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| PHP | 8.1 | 8.2+ |
| MySQL | 8.0 | 8.0+ |
| Apache | 2.4 | 2.4+ |
| RAM | 1 GB | 2 GB+ |
| Disk | 20 GB | 50 GB+ |

### 9.2 PHP Extensions Required

- php-curl
- php-mbstring
- php-xml
- php-zip
- php-mysql
- php-json

### 9.3 Installation Script

```bash
#!/bin/bash
# install.sh - Chwezi Server Manager Installation

echo "Installing Chwezi Server Manager..."

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root"
   exit 1
fi

# Install dependencies
apt update
apt install -y php php-curl php-mbstring php-xml php-zip php-mysql \
               apache2 mysql-server certbot python3-certbot-apache git

# Create application directory
mkdir -p /var/www/html/chwezi-sm
cd /var/www/html/chwezi-sm

# Clone repository (or extract release)
# git clone https://github.com/chwezi/server-manager.git .

# Set permissions
chown -R www-data:www-data /var/www/html/chwezi-sm
chmod -R 755 /var/www/html/chwezi-sm
chmod -R 775 /var/www/html/chwezi-sm/storage

# Configure sudoers
cp scripts/sudoers-chwezi-sm /etc/sudoers.d/chwezi-sm
chmod 440 /etc/sudoers.d/chwezi-sm

# Create database
mysql -e "CREATE DATABASE IF NOT EXISTS chwezi_sm;"
mysql -e "CREATE USER IF NOT EXISTS 'chwezi_sm'@'localhost' IDENTIFIED BY 'GENERATED_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON chwezi_sm.* TO 'chwezi_sm'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Run migrations
mysql chwezi_sm < database/migrations/001_create_tables.sql

# Create .env file
cp .env.example .env
# Update .env with database credentials

# Configure Apache virtual host
cp scripts/apache-vhost.conf /etc/apache2/sites-available/chwezi-sm.conf
a2ensite chwezi-sm.conf
a2enmod rewrite
systemctl reload apache2

# Generate SSH key for Git
ssh-keygen -t ed25519 -C "chwezi-sm@server" -f /root/.ssh/id_ed25519 -N ""

echo ""
echo "Installation complete!"
echo ""
echo "Next steps:"
echo "1. Configure domain DNS to point to this server"
echo "2. Install SSL: certbot --apache -d sm.yourdomain.com"
echo "3. Access: https://sm.yourdomain.com"
echo "4. Default login: admin / (check .env for password)"
echo "5. Add SSH key to GitHub: cat /root/.ssh/id_ed25519.pub"
```

---

## 10. Development Phases

### Phase 1: Foundation (Week 1-2)

**Goal:** Basic framework and authentication

- [ ] Project setup and directory structure
- [ ] Database schema and migrations
- [ ] Authentication system (login, logout, sessions)
- [ ] Base layout and navigation
- [ ] Dashboard with placeholder widgets
- [ ] Settings page

### Phase 2: Core Features (Week 3-4)

**Goal:** App deployment and domain management

- [ ] Git service (SSH key management, test connection)
- [ ] App deployment from GitHub
- [ ] Apache virtual host service
- [ ] Domain management CRUD
- [ ] Basic logging

### Phase 3: SSL & WordPress (Week 5-6)

**Goal:** SSL management and WordPress installer

- [ ] SSL certificate installation
- [ ] SSL renewal
- [ ] SSL status monitoring
- [ ] WordPress installation wizard
- [ ] Database service (create, backup, restore)
- [ ] Namecheap DNS guidance

### Phase 4: Advanced Features (Week 7-8)

**Goal:** Automation and monitoring

- [ ] Cron job management
- [ ] Scheduled SSL renewals
- [ ] Scheduled database backups
- [ ] Log viewer
- [ ] Activity logging
- [ ] Dashboard widgets (real data)

### Phase 5: Polish & Security (Week 9-10)

**Goal:** Security hardening and UX improvements

- [ ] Security audit
- [ ] Input validation review
- [ ] Error handling improvements
- [ ] Mobile responsiveness
- [ ] Performance optimization
- [ ] Documentation

### Phase 6: Testing & Launch (Week 11-12)

**Goal:** Production readiness

- [ ] Comprehensive testing
- [ ] Bug fixes
- [ ] Installation documentation
- [ ] User guide
- [ ] Production deployment
- [ ] Monitoring setup

---

## Appendix A: Templates

### A.1 Apache Virtual Host Template

```apache
# /resources/templates/apache/vhost.conf.tpl

<VirtualHost *:80>
    ServerName {{DOMAIN}}
    DocumentRoot {{DOCUMENT_ROOT}}
    
    <Directory {{DOCUMENT_ROOT}}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    <FilesMatch "^\.env">
        Require all denied
    </FilesMatch>
    
    <DirectoryMatch "^/.*/\.git/">
        Require all denied
    </DirectoryMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/{{DOMAIN}}-error.log
    CustomLog ${APACHE_LOG_DIR}/{{DOMAIN}}-access.log combined
</VirtualHost>
```

### A.2 WordPress wp-config.php Template

```php
# /resources/templates/wordpress/wp-config.php.tpl

<?php
define( 'DB_NAME', '{{DB_NAME}}' );
define( 'DB_USER', '{{DB_USER}}' );
define( 'DB_PASSWORD', '{{DB_PASSWORD}}' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

{{AUTH_KEYS}}

$table_prefix = '{{TABLE_PREFIX}}';

define( 'WP_DEBUG', false );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
```

### A.3 Update Script Template

```bash
# /resources/templates/scripts/update-app.sh.tpl

#!/bin/bash
REPO_DIR="{{DIRECTORY}}"
BRANCH="{{BRANCH}}"

echo "Updating {{APP_NAME}} from GitHub..."

cd "$REPO_DIR" || { echo "ERROR: Cannot find $REPO_DIR"; exit 1; }

git config --global --add safe.directory "$REPO_DIR"
git fetch --all

{{#if FORCE_STRATEGY}}
# Force strategy - discard local changes
if git reset --hard origin/$BRANCH; then
    echo "✅ Successfully updated (force)"
else
    echo "❌ Error updating!"
    exit 1
fi
{{else}}
# Merge strategy - preserve local changes
if git pull origin $BRANCH; then
    echo "✅ Successfully updated (merge)"
else
    echo "❌ Error updating - conflicts may exist"
    exit 1
fi
{{/if}}

{{#if IS_LARAVEL}}
# Laravel post-update commands
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
{{/if}}

# Fix permissions
chown -R www-data:www-data "$REPO_DIR"

echo "Done."
```

---

## Appendix B: Glossary

| Term | Definition |
|------|------------|
| VHost | Apache Virtual Host - configuration for serving a specific domain |
| SSL | Secure Sockets Layer - encryption for HTTPS |
| Certbot | Let's Encrypt client for SSL certificates |
| SSH Key | Cryptographic key pair for secure Git access |
| Document Root | Directory where Apache serves files from |
| Migration | Database schema change script |
| Cron | Linux task scheduler |

---

## Appendix C: References

- Apache Documentation: https://httpd.apache.org/docs/
- Let's Encrypt: https://letsencrypt.org/docs/
- WordPress CLI: https://developer.wordpress.org/cli/
- Namecheap DNS: https://www.namecheap.com/support/knowledgebase/
- PHP Documentation: https://www.php.net/docs.php

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Draft - Ready for Development
