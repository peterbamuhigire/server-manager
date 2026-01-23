# Chwezi Server Manager - Project Brief

## Project Overview

**Project Name:** Chwezi Server Manager (Chwezi SM)  
**Project Type:** Web-based Server Administration Tool  
**Version:** 1.0  
**Start Date:** January 2025  
**Target Completion:** March 2025 (12 weeks)

---

## Executive Summary

Chwezi Server Manager is a PHP web application that provides a unified graphical interface for managing Ubuntu VPS servers. It consolidates multiple bash scripts and manual SSH operations into a single, intuitive dashboard accessible via web browser.

The application enables one-click deployments from GitHub, automated WordPress installations with full SSL, domain management with Namecheap DNS guidance, and centralized monitoring of all hosted applications.

---

## Problem Statement

### Current Pain Points

1. **Script Sprawl** - Multiple bash scripts scattered across the server (`update-birdc`, `update-dms`, `fix-apache`, etc.) that are hard to track and maintain

2. **Manual Operations** - Every deployment, SSL renewal, and configuration change requires SSH access and command-line expertise

3. **No Central View** - No single dashboard showing all deployed applications, their status, SSL expiry dates, or server health

4. **Repetitive Tasks** - Setting up a new WordPress site requires 15+ manual steps every time

5. **Knowledge Gap** - Team members need deep Linux knowledge to perform routine tasks

6. **Error-Prone** - Manual operations lead to typos, missed steps, and configuration inconsistencies

---

## Solution

A web-based control panel that:

- **Centralizes** all server management tasks in one interface
- **Automates** repetitive workflows (deployment, SSL, WordPress setup)
- **Visualizes** server status, application health, and upcoming SSL renewals
- **Guides** users through complex tasks (DNS configuration steps for Namecheap)
- **Logs** all activities for audit and troubleshooting
- **Shares** code/skills across projects via Git submodules

---

## Target Users

### Primary User: Solo Developer / Small Team Lead

- Manages 5-20 web applications on a single VPS
- Comfortable with web interfaces, prefers GUI over CLI
- Needs to delegate some tasks to junior team members
- Values time savings over granular control

### Secondary User: Junior Developer / Virtual Assistant

- Can follow guided workflows
- Should not need SSH access for routine tasks
- Needs clear feedback on success/failure

---

## Key Features

### Must Have (MVP)

| Feature | Description |
|---------|-------------|
| Authentication | Secure admin login with session management |
| Dashboard | Overview widgets showing apps, domains, SSL status, server health |
| App Deployment | Deploy PHP/Laravel apps from GitHub with one click |
| App Updates | Pull latest changes from GitHub with database backup |
| Domain Management | Create Apache virtual hosts, enable sites |
| SSL Management | Install and renew Let's Encrypt certificates |
| Database Management | Create MySQL databases, backup/restore |
| Git SSH Setup | Display/generate SSH keys for GitHub |

### Should Have

| Feature | Description |
|---------|-------------|
| WordPress Installer | Complete WordPress setup with database and SSL |
| DNS Guidance | Show Namecheap configuration steps for domains |
| Cron Jobs | Schedule automated updates and backups |
| Activity Logs | Track all operations with timestamps |
| Git Submodules | Share code/skills across multiple projects |

### Nice to Have

| Feature | Description |
|---------|-------------|
| Web Terminal | Execute shell commands from browser |
| Log Viewer | View Apache, PHP, Laravel logs |
| Two-Factor Auth | TOTP-based 2FA for admin login |
| Email Notifications | Alert on SSL expiry, failed updates |
| API Access | REST API for external integrations |

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Time to deploy new app | < 5 minutes (currently 30+ minutes) |
| Time to install WordPress | < 3 minutes (currently 20+ minutes) |
| SSL renewal success rate | 100% automated |
| Deployment errors | < 5% (currently ~20% due to manual errors) |
| Team adoption | All team members using within 2 weeks |

---

## Constraints

### Technical Constraints

- Must run on same server it manages (localhost access to shell)
- PHP must have shell_exec enabled
- Root/sudo access required for system commands
- Single-server deployment only (not multi-server)

### Business Constraints

- Development by single developer
- No budget for external services (use free tools only)
- Must be self-hosted (no SaaS dependencies)

### Security Constraints

- Admin-only access (no multi-user roles in v1)
- All shell commands must be sanitized
- Credentials stored securely (hashed passwords, encrypted tokens)

---

## Project Status

**Current Phase:** Phase 1 ✅ COMPLETE
**Last Updated:** January 23, 2026
**Overall Progress:** 15% (Phase 1 of 13 phases complete)

### Completed Milestones
- ✅ **Phase 1: Authentication & RBAC** (January 23, 2026)
  - Full authentication system with login/logout
  - Role-based access control (4 roles, 35+ permissions)
  - User management (root only)
  - CSRF protection and security hardening
  - Session management with remember me
  - Responsive dashboard with TailwindCSS
  - Installation scripts for automated setup
  - Comprehensive documentation

---

## Timeline

### ✅ Phase 1: Authentication & RBAC (COMPLETE - Jan 23, 2026)
- ✅ Project setup, directory structure
- ✅ Database schema, migrations
- ✅ Authentication system (login, logout, session management)
- ✅ Role-based access control (Root, Admin, Developer, Viewer)
- ✅ User management (create, edit, delete, disable)
- ✅ Base layout, navigation
- ✅ Dashboard (with Phase 1 features)
- ✅ CSRF protection
- ✅ Password hashing and security
- ✅ Installation scripts

### Phase 2: Dashboard & Server Monitoring (PENDING)
- Server metrics (CPU, RAM, Disk usage)
- Application statistics
- Activity logging
- System health monitoring

### Phase 3: Git Integration (PENDING)
- Git service (SSH keys, test connection)
- Repository management
- GitHub connection testing

### Phase 4: App Deployment (PENDING)
- Deploy apps from GitHub
- Apache virtual host service
- Laravel auto-detection
- Post-install commands

### Phase 5: Domain Management (PENDING)
- Domain CRUD operations
- DNS verification
- Namecheap DNS guidance

### Phase 6: SSL Management (PENDING)
- SSL certificate installation (Let's Encrypt)
- SSL renewal and monitoring
- Certificate expiry alerts

### Phase 7: Database Management (PENDING)
- Database service (create, backup, restore)
- MySQL user management
- Database size monitoring

### Phase 8: WordPress Installer (PENDING)
- WordPress installation wizard
- Automated database setup
- wp-config.php generation

### Phase 9: Cron Jobs (PENDING)
- Cron job management
- Scheduled app updates
- Automated backups
- SSL renewal scheduling

### Phase 10: Logs & Monitoring (PENDING)
- Log viewer (Apache, PHP, application logs)
- Real-time log tailing
- Log search and filtering

### Phase 11: Web Terminal (Optional) (PENDING)
- Execute shell commands from browser
- Command history
- Dangerous command warnings

### Phase 12: Git Submodules (Future) (PENDING)
- Git submodules manager
- Skills repository integration
- Central code sharing

### Phase 13: Polish & Production Launch (PENDING)
- Security hardening
- Error handling improvements
- Mobile responsiveness
- Performance optimization
- Testing and bug fixes
- Production deployment
- Monitoring setup

---

## Risks & Mitigations

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| Shell command security vulnerabilities | High | Medium | Strict input validation, command whitelisting |
| Breaking existing applications | High | Low | Backup before operations, rollback capability |
| Scope creep | Medium | High | Strict MVP definition, defer nice-to-haves |
| Single point of failure | Medium | Medium | Regular backups, documented recovery |

---

## Stakeholders

| Role | Name | Responsibility |
|------|------|----------------|
| Product Owner | Peter Bamuhigire | Requirements, priorities, acceptance |
| Developer | Peter Bamuhigire | Design, development, testing |
| End User | Peter + Team | Feedback, testing, adoption |

---

## Approval

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Product Owner | Peter Bamuhigire | | |

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Jan 2025 | Peter Bamuhigire | Initial version |
| 1.1 | Jan 23, 2026 | Peter Bamuhigire | Updated with Phase 1 completion status and detailed timeline |
