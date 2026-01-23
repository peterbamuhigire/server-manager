# Chwezi Server Manager - Quick Start Guide

**Phase 1 ✅ COMPLETE** - Authentication & RBAC Ready to Use!

---

## 🚀 30-Second Setup (Windows/WAMP)

```bash
# 1. Create database
CREATE DATABASE chwezi_sm;

# 2. Run migrations
mysql -u root -p chwezi_sm < database/migrations/001_create_auth_tables.sql

# 3. Copy .env
copy .env.example .env

# 4. Create root user
php scripts/create-root-user.php peter peter@example.com Pass123 chwezi_sm root ""

# 5. Access
http://localhost/server-manager/public
```

---

## 🐧 30-Second Setup (Ubuntu Server)

```bash
# Automated installation
cd /var/www/server-manager
sudo bash scripts/install.sh
```

The installer will:
- Create database and user
- Run migrations
- Create root user
- Configure .env
- Set permissions
- Optionally configure Apache & SSL

---

## 📋 What You Can Do Now

### As Root User
- ✅ Login/logout with secure authentication
- ✅ Create users and assign roles
- ✅ Edit users (change password, email, role)
- ✅ Enable/disable user accounts
- ✅ Delete users (with safety checks)
- ✅ View all users and their status
- ✅ Change your own password
- ✅ View dashboard with system info

### Security Features Active
- ✅ CSRF protection on all forms
- ✅ Password hashing (bcrypt)
- ✅ Session timeout (30 minutes)
- ✅ Remember me (30 days)
- ✅ Input validation
- ✅ SQL injection prevention
- ✅ XSS prevention

### Roles Available
- **Root** - Full system access (you)
- **Admin** - All permissions except user management
- **Developer** - Apps, domains, SSL, databases, git, logs
- **Viewer** - Read-only access

---

## 🧪 Quick Test

```bash
# 1. Login as root
http://localhost/server-manager/public/login

# 2. Create a test user
Navigate to "Users" → "Create User"
Username: testuser
Email: test@example.com
Password: Test123456
Role: Developer

# 3. Logout and login as testuser
Verify "Users" menu is NOT visible (non-root)

# 4. Try to access /users directly
Should get "403 Forbidden"

# 5. Login as root again
Delete the test user
```

---

## 📂 Important Files

```
/.env                    # Your configuration (create from .env.example)
/public/index.php        # Application entry point
/database/migrations/    # SQL schema files
/scripts/install.sh      # Installation script
/scripts/create-root-user.php  # Create root user
```

---

## 🔑 Default Credentials

Set during installation:
- **Username:** (you choose)
- **Password:** (you choose)
- **Role:** Root (automatically assigned)

---

## 📖 Full Documentation

- **README.md** - Project overview
- **INSTALLATION_GUIDE.md** - Detailed setup and testing
- **STATUS.md** - Current implementation status
- **PHASE_1_SUMMARY.md** - Complete feature list
- **CHANGELOG.md** - Version history

---

## ⚠️ Troubleshooting

### Can't access application
- Check Apache is running
- Verify .htaccess exists in public/
- Enable mod_rewrite: `sudo a2enmod rewrite`

### Database connection error
- Check .env credentials
- Verify database exists: `mysql -u root -p chwezi_sm`

### Permission denied (logs)
```bash
# Linux
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Windows/WAMP
Right-click storage → Properties → Security → Everyone → Full Control
```

### CSRF validation failed
- Clear browser cookies
- Ensure CSRF_ENABLED=true in .env

---

## 🎯 What's Next?

**Phase 2 (Coming Soon):**
- Server monitoring (CPU, RAM, Disk)
- Activity logging
- Dashboard statistics

**Phase 3-13:**
- Git integration
- App deployment
- Domain & SSL management
- Database management
- WordPress installer
- Cron jobs
- Log viewer
- Web terminal
- Polish & launch

---

## 💡 Pro Tips

1. **Always create users through the UI** - Don't manually edit the database
2. **Use strong passwords** - Minimum 8 chars, uppercase, lowercase, numbers
3. **Test RBAC** - Login as different role users to verify permissions
4. **Keep .env secure** - Never commit to git (already in .gitignore)
5. **Backup database** - Before major changes
6. **Check logs** - storage/logs/app-YYYY-MM-DD.log

---

## 🎉 Success!

You now have a working authentication and user management system!

**Phase 1 Complete** ✅

Ready to proceed to Phase 2 when you are.

---

**Questions?** Check the full INSTALLATION_GUIDE.md or STATUS.md
