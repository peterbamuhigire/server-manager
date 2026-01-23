# Test Login Credentials

**Application URL:** http://localhost/server-manager/public

---

## 🔑 Login Credentials

### Root User (Full Access)
- **Username:** `peter`
- **Password:** `Wirtz2025@`
- **Email:** peter@techguypeter.com
- **Permissions:** Everything (bypasses all permission checks)

---

### Admin User (All except user management)
- **Username:** `admin`
- **Password:** `Admin123!`
- **Email:** admin@test.com
- **Permissions:**
  - ✅ Manage apps, domains, SSL, databases
  - ✅ WordPress installer
  - ✅ Git integration
  - ✅ Cron jobs
  - ✅ View logs
  - ✅ Terminal access
  - ✅ Update settings
  - ❌ Cannot manage users

---

### Developer User (Apps, domains, databases)
- **Username:** `developer`
- **Password:** `Dev123!`
- **Email:** developer@test.com
- **Permissions:**
  - ✅ Manage apps (deploy, update)
  - ✅ Manage domains
  - ✅ Manage SSL certificates
  - ✅ Manage databases
  - ✅ Git integration
  - ✅ View logs
  - ❌ Cannot manage users
  - ❌ Cannot access terminal
  - ❌ Cannot update settings

---

### Viewer User (Read-only)
- **Username:** `viewer`
- **Password:** `View123!`
- **Email:** viewer@test.com
- **Permissions:**
  - ✅ View dashboard
  - ✅ View apps list
  - ✅ View domains list
  - ✅ View SSL certificates
  - ✅ View databases
  - ❌ Cannot create, edit, or delete anything
  - ❌ Cannot manage users

---

## 🧪 Testing Scenarios

### Test 1: Root Access
1. Login as `peter` / `Wirtz2025@`
2. Should see "Users" in sidebar
3. Can create/edit/delete users
4. Can access everything

### Test 2: Admin Restrictions
1. Login as `admin` / `Admin123!`
2. Should NOT see "Users" in sidebar
3. Try accessing `/users` directly → "Access Denied"
4. Can access most other features

### Test 3: Developer Restrictions
1. Login as `developer` / `Dev123!`
2. Limited menu items
3. Cannot access users or settings
4. Can manage apps and domains

### Test 4: Viewer Read-Only
1. Login as `viewer` / `View123!`
2. Minimal menu items
3. Cannot create/edit/delete anything
4. Can only view information

### Test 5: Activity Logging
1. Login/logout with different users
2. Login as `peter`
3. Check "Recent Activity" widget on dashboard
4. Should see all login/logout events with usernames

---

## 🗄️ Database Info

- **Database:** chwezi_sm
- **Host:** localhost
- **Port:** 3306
- **Username:** peter
- **Password:** Wirtz2025@

---

## ⚠️ Important Notes

1. **Password Requirements:**
   - Minimum 8 characters
   - At least 1 uppercase letter
   - At least 1 lowercase letter
   - At least 1 number

2. **Session Timeout:** 30 minutes of inactivity

3. **Remember Me:** Check "Remember me" for 30-day persistent login

4. **Security:**
   - All passwords are bcrypt hashed (cost 12)
   - CSRF protection on all forms
   - Session fixation protection
   - Cannot delete yourself
   - Cannot delete last root user

---

**Created:** January 24, 2026
**For:** Development/Testing Environment Only
**DO NOT use these credentials in production!**
