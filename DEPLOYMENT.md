# Deployment Guide - server.chwezi.co.za

This guide walks you through deploying Chwezi Server Manager to your production server using SSH authentication.

## Prerequisites

- SSH access to server.chwezi.co.za
- sudo privileges
- GitHub account (peterbamuhigire)

## Deployment Steps

### Step 1: Copy Scripts to Server

**From your local machine:**

```bash
# Copy both scripts to the server
scp setup-ssh-github.sh administrator@server.chwezi.co.za:/tmp/
scp deploy-chwezi.sh administrator@server.chwezi.co.za:/tmp/
```

### Step 2: SSH into Server

```bash
ssh administrator@server.chwezi.co.za
```

### Step 3: Setup GitHub SSH Authentication

```bash
# Make the script executable
chmod +x /tmp/setup-ssh-github.sh

# Run the SSH setup script
bash /tmp/setup-ssh-github.sh
```

**What this script does:**
1. ✅ Checks for existing SSH keys
2. ✅ Generates new SSH key (if needed)
3. ✅ Displays your public key
4. ✅ Guides you to add it to GitHub
5. ✅ Tests the connection

**Follow the prompts:**
- Enter your GitHub email when asked
- Copy the SSH public key shown
- Add it to https://github.com/settings/keys
- Press Enter to test the connection

### Step 4: Deploy Chwezi Server Manager

Once SSH authentication is working:

```bash
# Make the deployment script executable
sudo chmod +x /tmp/deploy-chwezi.sh

# Run the deployment
sudo /tmp/deploy-chwezi.sh
```

**What the deployment script does:**
1. ✅ Backs up Apache configurations
2. ✅ Clones repository using SSH (git@github.com:peterbamuhigire/server-manager.git)
3. ✅ Updates Apache HTTP config
4. ✅ Updates Apache HTTPS config
5. ✅ Sets proper permissions
6. ✅ Enables required Apache modules
7. ✅ Tests configuration
8. ✅ Restarts Apache

### Step 5: Run Installation

After deployment completes successfully:

```bash
cd /var/www/html/server-manager
sudo bash scripts/install.sh
```

**The installer will:**
- Create MySQL database and user
- Run database migrations
- Create root user account
- Generate .env configuration
- Set proper permissions

**You'll be prompted for:**
- Database name (default: chwezi_sm)
- Database username (default: chwezi_sm)
- Database password (create a strong one)
- Root user email
- Root user password

### Step 6: Access Your Server Manager

Visit: **https://server.chwezi.co.za**

Login with the credentials you created during installation.

---

## Quick Reference

### Scripts Location

After copying to server:
- `/tmp/setup-ssh-github.sh` - SSH key setup
- `/tmp/deploy-chwezi.sh` - Main deployment script

### Important Files

- **Apache HTTP config:** `/etc/apache2/sites-available/server.chwezi.co.za.conf`
- **Apache HTTPS config:** `/etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf`
- **Application directory:** `/var/www/html/server-manager`
- **Public directory:** `/var/www/html/server-manager/public`
- **Environment file:** `/var/www/html/server-manager/.env`

### Useful Commands

```bash
# View Apache error log
sudo tail -f /var/log/apache2/error.log

# View application log
sudo tail -f /var/www/html/server-manager/storage/logs/app.log

# Check Apache virtual hosts
sudo apache2ctl -S | grep server.chwezi.co.za

# Test Apache configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2

# Check Apache status
sudo systemctl status apache2

# Pull latest changes (after initial deployment)
cd /var/www/html/server-manager
sudo -u www-data git pull origin main
```

---

## Troubleshooting

### SSH Authentication Failed

```bash
# Re-run SSH setup
bash /tmp/setup-ssh-github.sh

# Verify SSH key is added to GitHub
cat ~/.ssh/id_ed25519.pub

# Test GitHub connection manually
ssh -T git@github.com
```

### Apache Configuration Issues

```bash
# View current DocumentRoot
grep DocumentRoot /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf

# Restore from backup if needed
sudo cp /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf.backup.TIMESTAMP \
       /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf

# Test configuration
sudo apache2ctl configtest
```

### Permission Issues

```bash
cd /var/www/html/server-manager

# Fix ownership
sudo chown -R www-data:www-data .

# Fix permissions
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;
sudo chmod -R 775 storage
sudo chmod -R 775 database
```

### 403 Forbidden Error

```bash
# Check permissions
ls -la /var/www/html/server-manager/public

# Ensure Apache can read the directory
sudo chmod 755 /var/www/html/server-manager/public

# Check Apache error log
sudo tail -50 /var/log/apache2/error.log
```

### 500 Internal Server Error

```bash
# Check application log
sudo tail -50 /var/www/html/server-manager/storage/logs/app.log

# Check Apache error log
sudo tail -50 /var/log/apache2/error.log

# Verify .env file exists and is readable
ls -la /var/www/html/server-manager/.env
```

---

## Security Notes

1. **Keep credentials secure:**
   - Store database password safely
   - Use strong passwords for root user
   - Don't commit `.env` to git

2. **SSL is enabled:**
   - Site automatically redirects to HTTPS
   - Certificate managed by Let's Encrypt

3. **Regular updates:**
   ```bash
   cd /var/www/html/server-manager
   sudo -u www-data git pull origin main
   ```

4. **Backup database regularly:**
   - Use the built-in backup feature in Server Manager
   - Or set up automated backups via cron

---

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review Apache error logs
3. Review application logs
4. Ensure all prerequisites are met

---

## Version

- **Application:** Chwezi Server Manager v1.0
- **Server:** server.chwezi.co.za
- **Repository:** git@github.com:peterbamuhigire/server-manager.git
- **Last Updated:** January 24, 2026
