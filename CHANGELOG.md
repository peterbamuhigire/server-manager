# Changelog

All notable changes to the Chwezi Server Manager project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Planned
- Multi-server support
- 2FA authentication
- API access

---

## [0.14.0] - 2026-01-25

### ✅ Phase 14: Discovery Enhancements & UX Improvements - COMPLETE

This release adds comprehensive discovery page enhancements including SSL certificate management, Git integration, Apache controls, and global SweetAlert2 implementation.

### Added

#### SweetAlert2 Global Implementation
- ✅ Replaced all native `alert()` and `confirm()` with SweetAlert2
- ✅ Created `SwalUtil` global utility object with methods:
  - `success()`, `error()`, `warning()`, `info()` - Toast notifications
  - `confirm()`, `confirmDelete()` - Confirmation dialogs
  - `loading()`, `closeLoading()` - Loading spinners
  - `alert()`, `errorDetail()` - Full modals for detailed errors
- ✅ Updated 13+ view files for consistent UX
- ✅ Session flash messages now use SweetAlert2

#### SSL Certificate Management (Discovery Page)
- ✅ Register existing SSL certificates into database
- ✅ Unregister SSL certificates (files remain intact)
- ✅ SSL registration status display (green/yellow badges)
- ✅ View registered SSL certificates in SSL management
- ✅ Support for Let's Encrypt, self-signed, and custom certificates

#### Git Clone/Pull Integration (Discovery Page)
- ✅ Clone repositories from GitHub (HTTPS and SSH URLs)
- ✅ Clone into `/var/www/html/` with custom app name
- ✅ Branch selection for cloning
- ✅ Pull latest changes for existing git repositories
- ✅ SSH key integration for private repositories
- ✅ "Clone Repository" button in Applications section
- ✅ "Pull" button for each git-enabled application

#### Apache Controls (Discovery Page)
- ✅ Apache service status indicator (running/stopped)
- ✅ Reload Apache (graceful - no connection drops)
- ✅ Restart Apache (full restart)
- ✅ Configuration test before restart/reload
- ✅ Real-time status updates

#### Database Cleanup Feature
- ✅ "Cleanup DB" button to remove orphaned entries
- ✅ Detects apps, domains, databases, SSL certs that no longer exist
- ✅ Useful after server migration
- ✅ Detailed report of removed entries

#### System Setup Commands Panel
- ✅ Collapsible panel with required system commands
- ✅ Apache control permissions (sudoers configuration)
- ✅ Git SSH key setup for www-data user
- ✅ Web directory permissions commands
- ✅ Copy single command button (appears on hover)
- ✅ "Copy All" button for each section
- ✅ Terminal-style dark theme display

### Fixed

#### Database Credentials Security
- ✅ Removed hardcoded fallback credentials in ServerDiscoveryService
- ✅ Fixed DatabaseService using wrong config key (`db.xxx` → `database.xxx`)
- ✅ All database connections now strictly use config/database.php values

#### Backup Functionality
- ✅ Fixed mysqldump command escaping issue (Exit Code 2)
- ✅ Don't escapeshellarg() the command name on Linux
- ✅ Improved error messages with detailed diagnostics
- ✅ AJAX support for backup operations with detailed error display

#### Apps Controller
- ✅ Fixed 500 error caused by PHP namespaces
- ✅ Rewrote to use require_once pattern (matching other controllers)
- ✅ Fixed method name mismatches (`all()` → `getAll()`)

### Changed
- Dashboard stat cards now show "Manage" links instead of "Phase X"
- Error displays now use full modals instead of toasts for better readability
- Discovery page layout improved with action buttons and status indicators

### Technical Details
- **New Routes:** 8 (git-clone, git-pull, git-status, register-ssl, unregister-ssl, cleanup, restart-apache, reload-apache, apache-status)
- **Updated Files:** 15+
- **New Methods in GitService:** cloneRepository(), pullRepository(), getRepoStatus()
- **New Methods in ServerDiscoveryService:** registerSSLCertificate(), unregisterSSLCertificate(), cleanupOrphanedEntries(), restartApache(), reloadApache(), getApacheStatus()

### System Requirements
For Apache controls to work, add to sudoers:
```
www-data ALL=(ALL) NOPASSWD: /usr/sbin/apache2ctl configtest
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart apache2
www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload apache2
www-data ALL=(ALL) NOPASSWD: /bin/systemctl is-active apache2
www-data ALL=(ALL) NOPASSWD: /bin/systemctl status apache2
```

---

## [0.3.0] - 2026-01-24

### ✅ Phase 3: Git Integration - COMPLETE

This release adds SSH key management and GitHub integration for repository deployments.

### Added
- **SSH Key Management**
  - Generate RSA 4096-bit SSH key pairs
  - Display SSH public key with one-click copy to clipboard
  - View SSH key fingerprint for verification
  - Delete and regenerate SSH keys
  - Secure storage in storage/ssh directory (excluded from git)

- **GitHub Integration**
  - Test GitHub SSH connection with real-time feedback
  - Step-by-step instructions for adding SSH key to GitHub
  - Success/failure status display with detailed output
  - AJAX-based connection testing (no page reload)

- **Git Configuration**
  - View current Git global configuration (user.name, user.email)
  - Update Git configuration through web interface
  - Git version detection and display
  - Git installation status check

- **User Interface**
  - New "Git Integration" menu item in sidebar
  - Dedicated Git settings page with clean layout
  - Status indicators for Git installation and SSH key
  - Copy to clipboard button for public key
  - Responsive design for mobile devices
  - "New" badge on Git menu item

- **Activity Logging**
  - Log SSH key generation events
  - Log SSH key deletion events
  - Log GitHub connection test attempts
  - Log Git configuration updates
  - All activities tracked in audit log

- **Services & Controllers**
  - GitService class for all Git operations
  - GitController for HTTP request handling
  - Cross-platform support (Windows & Linux)
  - Comprehensive error handling

### Changed
- Updated sidebar navigation with Git Integration link
- Updated dashboard version to 1.0.0 (Phase 3)
- Updated roadmap on dashboard
- Enhanced STATUS.md with Phase 3 completion (31% progress)
- Updated README.md progress badges

### Technical Details
- **Lines of Code:** ~1,500 new lines
- **Files Created:** 5 new files
- **Files Updated:** 3 existing files
- **SSH Key Type:** RSA 4096-bit
- **Storage:** Secure directory with proper permissions
- **Security:** Keys excluded from version control

---

## [0.2.0] - 2026-01-24

### ✅ Phase 2: Dashboard & Server Monitoring - COMPLETE

This release adds real-time server monitoring and comprehensive activity logging.

### Added
- **Server Monitoring**
  - Real-time CPU usage monitoring with color-coded progress bars
  - Real-time RAM usage monitoring
  - Real-time Disk usage monitoring with space breakdown
  - Apache service status indicator (running/stopped)
  - MySQL service status indicator (running/stopped)
  - System uptime display
  - Load average display (Linux only)
  - Windows and Linux platform compatibility

- **Activity Logging System**
  - Comprehensive user activity tracking
  - Login/logout event logging
  - User CRUD operations logging (create, update, delete, toggle status)
  - IP address and user agent capture
  - Recent activity feed on dashboard (last 10 activities)
  - Activity search and filtering capabilities
  - Historical activity logs with pagination

- **Dashboard Enhancements**
  - Server health widget with real-time metrics
  - Color-coded health indicators (green: good, yellow: warning, red: critical)
  - Service status grid (Apache, MySQL)
  - Enhanced system information panel
  - Recent activity feed with timestamps
  - Updated application version to 1.0.0 (Phase 2)

- **Database Tables**
  - `system_metrics` table for storing performance data
  - `activity_logs` table for audit trail
  - Proper indexes for query performance

- **Models**
  - `SystemMetric` model with methods for recording, retrieving, and analyzing metrics
  - `ActivityLog` model with search, filter, and statistics methods
  - Support for metric history and statistics (min, max, avg)
  - Old data cleanup methods for maintenance

- **Services**
  - `ServerMonitoringService` for collecting system metrics
  - Cross-platform support (Windows & Linux)
  - CPU usage calculation
  - RAM usage calculation
  - Disk space monitoring
  - Service status checking
  - Metrics collection and storage

- **Helper Functions**
  - `log_activity()` function for easy activity logging
  - Automatic user ID, IP, and user agent capture
  - Integration with all user management operations

- **Scripts**
  - `collect-metrics.php` CLI script for cron-based metric collection
  - Detailed logging with timestamps
  - Error handling and recovery
  - Executable permissions set

### Changed
- Updated `DashboardController` to fetch and display real metrics
- Updated dashboard view with server health widgets
- Enhanced `AuthController` to log login/logout activities
- Enhanced `UserController` to log all user management actions
- Updated STATUS.md with Phase 2 completion
- Updated progress metrics (23% complete, 2 of 13 phases)

### Technical Details
- **Lines of Code:** ~2,000 new lines
- **Files Created:** 5 new files
- **Files Updated:** 5 existing files
- **Platform Support:** Windows (WAMP) and Linux (Ubuntu)
- **Performance:** Minimal overhead, metrics collected every 5 minutes via cron
- **Security:** All activities logged with user context for audit

---

## [0.1.0] - 2026-01-23

### ✅ Phase 1: Authentication & RBAC - COMPLETE

This is the initial release focusing on authentication and role-based access control.

### Added
- **Authentication System**
  - Login/logout functionality with secure session management
  - Password hashing using bcrypt (cost 12)
  - "Remember me" functionality with 30-day persistent login
  - Session timeout (configurable, default 30 minutes)
  - Auto-login via remember token
  - Session fixation protection

- **User Management (Root Only)**
  - Create new users with username, email, password, and role
  - Edit existing users (all fields including password)
  - Delete users with safety checks (can't delete self or last root user)
  - Enable/disable user accounts
  - List all users with role, status, and last login
  - Cannot modify own root status

- **Role-Based Access Control (RBAC)**
  - 4 default roles: Root, Admin, Developer, Viewer
  - 35+ permissions across 12 categories
  - Flexible permission system for future features
  - Root users bypass all permission checks
  - Permission checks on every controller action

- **Security Features**
  - CSRF protection on all POST/PUT/DELETE requests
  - SQL injection prevention (PDO prepared statements)
  - XSS prevention (htmlspecialchars output escaping)
  - Input validation and sanitization
  - Password strength validation
  - Email and username validation
  - Unique constraint checks

- **Dashboard**
  - Welcome message with username and role
  - System information display
  - Quick action buttons
  - Stats placeholders for future phases
  - Roadmap preview

- **Database Schema**
  - `users` table with authentication fields
  - `roles` table with 4 default roles
  - `permissions` table with 35+ permissions
  - `role_permissions` pivot table
  - `sessions` table for active session tracking
  - Proper indexes and foreign keys

- **Installation**
  - Automated installation script (`install.sh`)
  - CLI script to create root user (`create-root-user.php`)
  - Environment configuration (`.env` support)
  - Support for 3 environments: dev, demo, prod
  - Apache virtual host configuration (optional)
  - SSL certificate installation (optional)

- **UI/UX**
  - Clean, modern interface using TailwindCSS
  - Responsive design (mobile-friendly)
  - Sidebar navigation with role-based menu items
  - Flash messages for success/error feedback
  - Form validation with inline error display
  - Confirmation modals for destructive actions
  - Environment badge (dev/demo/prod)

- **Routing System**
  - Front controller pattern in `public/index.php`
  - Clean URLs via `.htaccess` rewriting
  - Middleware support (auth, root, csrf)
  - Route parameter extraction
  - 404 error handling
  - Exception handling with debug mode

- **Helper Functions**
  - Database connection (`db()`)
  - Configuration loader (`config()`)
  - Authentication helpers (`auth_check()`, `auth_user()`, `is_root()`)
  - Session management (`session()`, `session_set()`)
  - HTTP helpers (`redirect()`, `back()`, `url()`)
  - Response helpers (`json()`, `json_error()`, `json_success()`)
  - View rendering (`view()`, `partial()`)
  - Security helpers (`hash_password()`, `verify_password()`, `e()`)
  - Validation helpers (email, username, password, etc.)
  - Flash messages (`flash()`, `get_flash()`, `has_flash()`)
  - Logging (`log_message()`)
  - Utility functions (`format_date()`, `get_client_ip()`, etc.)

- **Documentation**
  - Comprehensive README.md
  - INSTALLATION_GUIDE.md with testing procedures
  - PHASE_1_SUMMARY.md with complete file inventory
  - Updated PROJECT_BRIEF.md with progress
  - Updated ARCHITECTURE.md with status
  - Updated TECH_STACK.md with implementation notes
  - CHANGELOG.md (this file)

### File Structure
```
35 files created across:
- 4 config files
- 2 database files
- 2 helper files
- 8 model & controller files
- 4 middleware files
- 8 view files
- 3 public files
- 3 installation scripts
- Documentation files
```

### Database
- 5 tables created: users, roles, permissions, role_permissions, sessions
- 4 default roles seeded
- 35+ permissions seeded
- Role-permission relationships seeded

### Security
- All passwords hashed with bcrypt (cost 12)
- CSRF tokens on all forms
- SQL injection prevention via prepared statements
- XSS prevention via output escaping
- Session security (HTTPOnly, SameSite, timeout)
- Input validation and sanitization
- Root user isolation

### Routes Implemented
- `GET /login` - Login form
- `POST /login` - Process login
- `GET /logout` - Logout
- `GET /` - Dashboard (authenticated)
- `GET /profile` - User profile (authenticated)
- `POST /profile/password` - Change password (authenticated)
- `GET /users` - User list (root only)
- `GET /users/create` - Create user form (root only)
- `POST /users/create` - Create user (root only)
- `GET /users/edit` - Edit user form (root only)
- `POST /users/update` - Update user (root only)
- `POST /users/delete` - Delete user (root only)
- `POST /users/toggle-status` - Enable/disable user (root only)

### Technical Details
- **Language:** PHP 8.2+
- **Database:** MySQL 8.0+
- **Frontend:** TailwindCSS + Alpine.js (CDN)
- **Web Server:** Apache 2.4+
- **Lines of Code:** ~4,500+
- **No Composer Dependencies:** Keeping it simple and portable

### Known Limitations
- No email functionality (password reset requires manual intervention)
- No 2FA (planned for v1.1)
- No API (planned for v1.1)
- No activity logging (planned for Phase 2)
- Single server only (multi-server in v2.0)

---

## Project Information

**Author:** Peter Bamuhigire
**License:** MIT
**Repository:** https://github.com/chwezi/server-manager
**Started:** January 2025
**Phase 1 Complete:** January 23, 2026

---

## Next Release (Phase 2)

Planned features for next release:
- Dashboard statistics (app count, domain count, SSL status)
- Server monitoring (CPU, RAM, Disk usage)
- Activity logging (user actions, system events)
- System service management
