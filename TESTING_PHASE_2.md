# Phase 2 Testing Guide

## Database Migration

### Step 1: Run Migration
1. Open phpMyAdmin or MySQL command line
2. Select the `chwezi_sm` database
3. Run the migration script:
   ```
   File: database/migrations/002_create_monitoring_tables.sql
   ```
4. Verify tables created:
   - `system_metrics`
   - `activity_logs`

### Step 2: Verify Table Structure

**system_metrics:**
```sql
DESCRIBE system_metrics;
```
Should show: id, metric_type, metric_value, recorded_at

**activity_logs:**
```sql
DESCRIBE activity_logs;
```
Should show: id, user_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at

---

## Feature Testing

### 1. Dashboard Metrics Display

**Test:** Access the dashboard
- **URL:** http://localhost/server-manager/public
- **Login:** Use your root user credentials

**Expected Results:**
- ✅ CPU usage shows percentage (not 0)
- ✅ RAM usage shows percentage (not 0)
- ✅ Disk usage shows percentage (not 0)
- ✅ Progress bars display with appropriate colors:
  - Green (0-60%)
  - Yellow (60-80%)
  - Red (80-100%)
- ✅ Disk info shows: "X GB used of Y GB (Z GB free)"
- ✅ Apache status shows "Running" (green dot)
- ✅ MySQL status shows "Running" (green dot)

### 2. System Information

**Expected Results:**
- ✅ Application Version shows "1.0.0 (Phase 2)"
- ✅ Environment shows "dev"
- ✅ PHP Version displays correctly
- ✅ Your IP Address shows your local IP
- ✅ Last Login shows timestamp or "Never"

### 3. Activity Logging - Login

**Test:** Login to the system

**Expected Results:**
- ✅ Recent Activity widget shows "login" event
- ✅ Your username appears in the activity
- ✅ Timestamp is current

**Database Verification:**
```sql
SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5;
```
- ✅ Login event recorded
- ✅ IP address captured
- ✅ User agent captured

### 4. Activity Logging - User Management

**Test:** Create a new user (if root)
1. Navigate to Users → Create New User
2. Fill in the form and submit
3. Return to dashboard

**Expected Results:**
- ✅ Recent Activity shows "create user" event
- ✅ Username of new user visible

**Database Verification:**
```sql
SELECT * FROM activity_logs WHERE action = 'create_user' ORDER BY created_at DESC LIMIT 1;
```
- ✅ Event recorded
- ✅ entity_type = 'user'
- ✅ entity_id = new user's ID
- ✅ details column contains JSON with username and role

### 5. Activity Logging - User Update

**Test:** Edit a user
1. Go to Users → Edit
2. Change email or username
3. Save changes
4. Return to dashboard

**Expected Results:**
- ✅ Recent Activity shows "update user" event

### 6. Activity Logging - User Status Toggle

**Test:** Disable/Enable a user
1. Go to Users list
2. Click "Disable" button on a user
3. Return to dashboard

**Expected Results:**
- ✅ Recent Activity shows "toggle user status" event
- ✅ Details show new status

### 7. Activity Logging - Logout

**Test:** Logout
1. Click Logout
2. Login again
3. Check Recent Activity

**Expected Results:**
- ✅ "logout" event appears before "login" event

### 8. Metrics Collection Script

**Test:** Run the metrics collection script

**Windows (WAMP):**
```cmd
cd C:\wamp64\www\server-manager
C:\wamp64\bin\php\php8.x.x\php.exe scripts/collect-metrics.php
```

**Linux:**
```bash
cd /var/www/server-manager
php scripts/collect-metrics.php
```

**Expected Output:**
```
[2026-01-24 XX:XX:XX] Starting metrics collection...
[2026-01-24 XX:XX:XX] Metrics collected successfully:
  - CPU: XX.XX%
  - RAM: XX.XX%
  - Disk: XX.XX%
  - Apache: Running
  - MySQL: Running
```

**Database Verification:**
```sql
SELECT * FROM system_metrics ORDER BY recorded_at DESC LIMIT 10;
```
- ✅ 3 new records (cpu, ram, disk)
- ✅ metric_type values: 'cpu', 'ram', 'disk'
- ✅ metric_value contains percentages
- ✅ recorded_at is current timestamp

### 9. Responsive Design

**Test:** View dashboard on different screen sizes

**Expected Results:**
- ✅ Desktop: All widgets visible in grid layout
- ✅ Tablet: Widgets stack appropriately
- ✅ Mobile: Single column layout, all content accessible

### 10. Color-Coded Health Indicators

**Test:** Monitor CPU/RAM/Disk under different loads

**Expected Results:**
- ✅ Green bar when usage < 60%
- ✅ Yellow bar when usage 60-80%
- ✅ Red bar when usage > 80%

---

## Performance Testing

### 1. Dashboard Load Time

**Test:** Refresh dashboard multiple times

**Expected Results:**
- ✅ Page loads in < 2 seconds
- ✅ No visible lag
- ✅ Metrics appear immediately

### 2. Database Query Performance

**Test:** Check slow query log

```sql
-- Activity logs query performance
EXPLAIN SELECT al.*, u.username
FROM activity_logs al
INNER JOIN users u ON al.user_id = u.id
ORDER BY al.created_at DESC
LIMIT 10;
```

**Expected Results:**
- ✅ Uses indexes
- ✅ No full table scans
- ✅ Query executes in < 100ms

---

## Error Handling Testing

### 1. Missing Tables

**Test:** Comment out migration, access dashboard

**Expected Results:**
- ✅ Graceful error handling
- ✅ Error logged
- ✅ No PHP fatal errors

### 2. Database Connection Issues

**Test:** Stop MySQL, access dashboard

**Expected Results:**
- ✅ Error message displayed
- ✅ App doesn't crash

---

## Security Testing

### 1. Activity Log Integrity

**Test:** Try to manipulate activity logs

**Expected Results:**
- ✅ Foreign key constraints prevent invalid user_id
- ✅ Only authenticated users can create logs
- ✅ IP and user agent cannot be spoofed via UI

### 2. Metrics Collection Security

**Test:** Access collect-metrics.php via browser

**Expected Results:**
- ✅ Script rejects non-CLI requests
- ✅ Error message: "This script can only be run from the command line"

---

## Browser Compatibility

**Test in multiple browsers:**
- ✅ Chrome/Edge: All features work
- ✅ Firefox: All features work
- ✅ Safari: All features work (if available)

---

## Checklist Summary

### Database
- [ ] Migration runs without errors
- [ ] Tables created with correct structure
- [ ] Indexes present
- [ ] Foreign keys working

### Dashboard Metrics
- [ ] CPU usage displays correctly
- [ ] RAM usage displays correctly
- [ ] Disk usage displays correctly
- [ ] Progress bars visible
- [ ] Color coding works
- [ ] Service status accurate

### Activity Logging
- [ ] Login logged
- [ ] Logout logged
- [ ] User create logged
- [ ] User update logged
- [ ] User delete logged
- [ ] User status toggle logged
- [ ] IP address captured
- [ ] User agent captured

### Scripts
- [ ] collect-metrics.php runs successfully
- [ ] Metrics stored in database
- [ ] Error handling works
- [ ] CLI-only check works

### Performance
- [ ] Dashboard loads quickly
- [ ] No lag or freezing
- [ ] Database queries optimized

### Security
- [ ] Activity logs secure
- [ ] No SQL injection vulnerabilities
- [ ] CSRF protection still working

---

## Known Issues to Watch For

1. **Windows CPU Metric**: May be less accurate than Linux
2. **Service Status**: Requires shell_exec enabled
3. **First Load**: Activity logs empty until actions performed
4. **Cron Setup**: Manual setup required

---

## Success Criteria

All checkboxes above should be checked (✅) for Phase 2 to be considered fully tested and production-ready.

**Tester:** _________________
**Date:** _________________
**Environment:** [ ] Dev [ ] Demo [ ] Production
**Result:** [ ] Pass [ ] Fail

**Notes:**
_______________________________________________________________
_______________________________________________________________
_______________________________________________________________
