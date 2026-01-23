# Changelog

All notable changes to the Chwezi Server Manager project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Planned for Phase 2
- Server metrics dashboard (CPU, RAM, Disk)
- Activity logging system
- System health monitoring

### Planned for Phase 3
- Git SSH key management
- GitHub connection testing
- Repository management

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
