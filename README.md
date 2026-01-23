# Chwezi Server Manager

A web-based PHP application for managing Ubuntu VPS servers with a beautiful dashboard, one-click deployments, and automated WordPress installations.

![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue)
![License](https://img.shields.io/badge/License-MIT-green)
![Status](https://img.shields.io/badge/Status-In%20Development-yellow)
![Phase](https://img.shields.io/badge/Phase%201-Complete%20%E2%9C%85-brightgreen)
![Progress](https://img.shields.io/badge/Progress-15%25%20(1%2F13%20Phases)-orange)

---

## 📊 Project Status

**Current Phase:** Phase 1 ✅ COMPLETE (Authentication & RBAC)
**Last Updated:** January 23, 2026
**Overall Progress:** 15% (1 of 13 phases complete)

### What's Working Now
- ✅ **User Authentication** - Secure login/logout with session management
- ✅ **User Management** - Create, edit, delete, disable users (root only)
- ✅ **Role-Based Access Control** - 4 roles, 35+ permissions
- ✅ **Security** - CSRF protection, password hashing, input validation
- ✅ **Dashboard** - Basic dashboard with user info and quick actions
- ✅ **Installation** - Automated setup scripts for dev/demo/prod

### Coming Next (Phase 2+)
- ⏳ Server monitoring (CPU, RAM, Disk)
- ⏳ App deployment from GitHub
- ⏳ Domain & SSL management
- ⏳ Database management
- ⏳ WordPress installer
- ⏳ Cron jobs & log viewer

---

## 🚀 Features

### Core Features
- **📊 Dashboard** - Server health, apps overview, SSL expiry alerts
- **📱 App Deployment** - One-click deploy from GitHub (Laravel, PHP, Node.js)
- **🌐 WordPress Installer** - Complete WordPress setup with database and SSL
- **🔗 Domain Management** - Apache virtual hosts configuration
- **🔒 SSL Management** - Let's Encrypt certificates with auto-renewal
- **🗄️ Database Management** - Create, backup, restore MySQL databases
- **📁 Git Integration** - SSH keys, test connections, manage repos
- **🔄 Submodules** - Share code/skills across multiple projects
- **⏰ Cron Jobs** - Schedule automated updates and backups
- **📋 Log Viewer** - Apache, PHP, and application logs
- **💻 Web Terminal** - Execute shell commands from browser

### Highlights
- 🎯 **One-click deployments** - Deploy apps from GitHub in under 5 minutes
- 🔧 **No SSH required** - Perform server tasks from web browser
- 📱 **Mobile responsive** - Manage your server from phone or tablet
- 🔐 **Secure by design** - Input validation, CSRF protection, secure shell execution
- 📖 **DNS guidance** - Step-by-step Namecheap DNS configuration

---

## 📋 Requirements

- **OS:** Ubuntu 22.04 or 24.04 LTS
- **PHP:** 8.2 or higher
- **MySQL:** 8.0 or higher
- **Apache:** 2.4 or higher
- **RAM:** 1GB minimum (2GB recommended)
- **Disk:** 20GB minimum

---

## 🛠️ Installation

### Automated Installation (Recommended)

```bash
# Clone the repository
git clone https://github.com/chwezi/server-manager.git /var/www/chwezi-sm
cd /var/www/chwezi-sm

# Run installation script
sudo bash scripts/install.sh
```

The installer will:
- Create MySQL database and user
- Run database migrations
- Create root user account
- Generate .env configuration
- Set proper permissions
- Optionally configure Apache and SSL

### Manual Installation

1. **Install dependencies:**

```bash
sudo apt update
sudo apt install -y php8.2 php8.2-{curl,mbstring,xml,zip,mysql} \
                    apache2 mysql-server certbot python3-certbot-apache git
```

2. **Clone the repository:**

```bash
cd /var/www/html
git clone https://github.com/chwezi/server-manager.git chwezi-sm
cd chwezi-sm
```

3. **Configure environment:**

```bash
cp .env.example .env
nano .env
```

Update the following:
```env
APP_URL=https://sm.yourdomain.com
DB_DATABASE=chwezi_sm
DB_USERNAME=chwezi_sm
DB_PASSWORD=your_secure_password
```

4. **Set up database:**

```bash
# Create database and user
sudo mysql -e "CREATE DATABASE chwezi_sm;"
sudo mysql -e "CREATE USER 'chwezi_sm'@'localhost' IDENTIFIED BY 'your_secure_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON chwezi_sm.* TO 'chwezi_sm'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Run migrations
mysql -u chwezi_sm -p chwezi_sm < database/migrations/001_create_auth_tables.sql
```

5. **Set permissions:**

```bash
sudo chown -R www-data:www-data /var/www/html/chwezi-sm
sudo chmod -R 755 /var/www/html/chwezi-sm
sudo chmod -R 775 /var/www/html/chwezi-sm/storage
```

6. **Configure sudoers:**

```bash
sudo cp scripts/sudoers-chwezi-sm /etc/sudoers.d/chwezi-sm
sudo chmod 440 /etc/sudoers.d/chwezi-sm
```

7. **Configure Apache:**

```bash
sudo cp apache/chwezi-sm.conf /etc/apache2/sites-available/
sudo a2ensite chwezi-sm.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

8. **Install SSL:**

```bash
sudo certbot --apache -d sm.yourdomain.com
```

9. **Create root user:**

```bash
php scripts/create-root-user.php your_username your@email.com your_password chwezi_sm chwezi_sm your_db_password
```

10. **Access the application:**

Navigate to `https://sm.yourdomain.com/login`

Use the credentials you created in step 9.

---

## 📁 Project Structure

```
chwezi-sm/
├── public/              # Web root
│   ├── index.php       # Entry point
│   └── .htaccess       # URL rewriting
├── app/
│   ├── Controllers/    # Request handlers
│   ├── Models/         # Database models
│   ├── Services/       # Business logic
│   ├── Middleware/     # Auth, CSRF
│   └── Helpers/        # Utility functions
├── config/             # Configuration files
├── database/
│   ├── migrations/     # SQL schema
│   └── seeds/          # Initial data
├── resources/
│   ├── views/          # PHP templates
│   └── templates/      # Config templates
├── storage/
│   ├── logs/           # Application logs
│   └── backups/        # Database backups
├── scripts/            # Shell scripts
├── .env                # Environment config
└── README.md
```

---

## 🖥️ Usage (Phase 1)

### Login

1. Navigate to your application URL (e.g., `http://localhost` or `https://server.yourdomain.com`)
2. Enter your username and password
3. Optionally check "Remember me" for 30-day persistent login

### User Management (Root Only)

**Create a New User:**
1. Navigate to **Users** → **Create User**
2. Enter:
   - Username (3-50 alphanumeric characters)
   - Email address
   - Password (min 8 chars, uppercase, lowercase, numbers)
   - Role (Admin, Developer, or Viewer)
3. Click **Create User**

**Edit User:**
1. Navigate to **Users**
2. Click **Edit** on any user
3. Modify username, email, password, or role
4. Click **Update User**

**Disable/Enable User:**
1. Navigate to **Users**
2. Click **Disable** or **Enable** on any user
3. Disabled users cannot login

**Delete User:**
1. Navigate to **Users**
2. Click **Delete** on any user
3. Confirm deletion
- Note: Cannot delete yourself or the last root user

### Change Password

1. Navigate to **Profile**
2. Enter current password
3. Enter new password (twice)
4. Click **Update Password**

### Role Permissions

**Root** (is_root = true):
- Full system access
- User management
- Cannot be assigned via UI

**Admin**:
- All permissions except user management

**Developer**:
- Apps, domains, SSL, databases, git, submodules, logs

**Viewer**:
- Read-only access to all features

### Development Server

For local development:

```bash
# Windows (WAMP)
http://localhost/server-manager/public

# Linux (with PHP built-in server)
cd /var/www/chwezi-sm
php -S localhost:8000 -t public
```

### Coming in Phase 2+

App deployment, domain management, SSL certificates, databases, WordPress installer, and more!

---

## ⚙️ Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_URL` | Application URL | `http://localhost` |
| `APP_DEBUG` | Enable debug mode | `false` |
| `DB_HOST` | MySQL host | `localhost` |
| `DB_DATABASE` | Database name | `chwezi_sm` |
| `DB_USERNAME` | Database user | `chwezi_sm` |
| `DB_PASSWORD` | Database password | - |
| `SSL_EMAIL` | Email for SSL certs | - |
| `SESSION_LIFETIME` | Session timeout (minutes) | `30` |

### Customization

**Apache virtual host template:**
```
resources/templates/apache/vhost.conf.tpl
```

**WordPress wp-config template:**
```
resources/templates/wordpress/wp-config.php.tpl
```

**Update script template:**
```
resources/templates/scripts/update-app.sh.tpl
```

---

## 🔒 Security

### Implemented Security Measures

- ✅ HTTPS enforced (Let's Encrypt)
- ✅ Password hashing (bcrypt)
- ✅ Session-based authentication
- ✅ CSRF protection on all forms
- ✅ Prepared statements for SQL
- ✅ Input sanitization
- ✅ Shell command validation
- ✅ Sudoers whitelist for elevated commands
- ✅ Secure file permissions

### Recommendations

- Use strong admin password
- Keep PHP and MySQL updated
- Regular security audits
- Monitor access logs
- Enable UFW firewall

```bash
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

---

## 🔧 Troubleshooting

### Common Issues

**Permission denied errors:**
```bash
sudo chown -R www-data:www-data /var/www/html/chwezi-sm
sudo chmod -R 775 /var/www/html/chwezi-sm/storage
```

**Apache not starting:**
```bash
sudo apache2ctl configtest
sudo tail -f /var/log/apache2/error.log
```

**SSL certificate issues:**
```bash
sudo certbot certificates
sudo certbot renew --dry-run
```

**Database connection errors:**
```bash
mysql -u chwezi_sm -p chwezi_sm
# Check credentials in .env
```

**Git clone fails:**
```bash
# Test SSH connection
ssh -T git@github.com

# Display SSH key
cat /root/.ssh/id_ed25519.pub
```

### Logs

```bash
# Application log
tail -f /var/www/html/chwezi-sm/storage/logs/app.log

# Shell command log
tail -f /var/www/html/chwezi-sm/storage/logs/shell.log

# Apache error log
tail -f /var/log/apache2/error.log
```

---

## 📖 Documentation

- [Project Brief](docs/PROJECT_BRIEF.md) - Overview and goals
- [Tech Stack](docs/TECH_STACK.md) - Technology choices
- [Architecture](docs/ARCHITECTURE.md) - System design
- [PRD/SRS](docs/CHWEZI_SERVER_MANAGER_PRD.md) - Full requirements

---

## 🗺️ Roadmap

### Version 1.0 (In Development)
**Phase 1: Authentication & RBAC (✅ COMPLETE)**
- [x] User authentication (login/logout)
- [x] Session management with timeout
- [x] Role-based access control (RBAC)
- [x] User management (root only)
- [x] Four default roles: Root, Admin, Developer, Viewer
- [x] Permission system with categories
- [x] CSRF protection
- [x] Password hashing (bcrypt)
- [x] Remember me functionality
- [x] Basic dashboard

**Phase 2-13: Coming Soon**
- [ ] Dashboard stats & server monitoring
- [ ] Git integration & SSH keys
- [ ] App deployment from GitHub
- [ ] Domain management
- [ ] SSL management
- [ ] Database management
- [ ] WordPress installer
- [ ] Cron jobs
- [ ] Log viewer
- [ ] Web terminal
- [ ] Git submodules

### Version 1.1 (Planned)
- [ ] Two-factor authentication
- [ ] Email notifications
- [ ] API access
- [ ] Multi-user support
- [ ] Backup to cloud storage

### Version 2.0 (Future)
- [ ] Multi-server management
- [ ] Docker container support
- [ ] CI/CD integration
- [ ] Performance monitoring

---

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👤 Author

**Peter Bamuhigire**
- GitHub: [@chwezi](https://github.com/chwezi)
- Website: [techguypeter.com](https://techguypeter.com)

---

## 🙏 Acknowledgments

- [TailwindCSS](https://tailwindcss.com/) - Utility-first CSS
- [Alpine.js](https://alpinejs.dev/) - Lightweight JavaScript
- [Let's Encrypt](https://letsencrypt.org/) - Free SSL certificates
- [Certbot](https://certbot.eff.org/) - ACME client

---

## 📞 Support

If you encounter any issues or have questions:

1. Check the [Troubleshooting](#troubleshooting) section
2. Search [existing issues](https://github.com/chwezi/server-manager/issues)
3. Open a [new issue](https://github.com/chwezi/server-manager/issues/new)

---

**Made with ❤️ for the developer community**
