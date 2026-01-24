#!/bin/bash

###############################################################################
# Chwezi Server Manager Deployment Script
# Server: server.chwezi.co.za
# Description: Deploys the Server Manager application to production
###############################################################################

set -e  # Exit on error

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║   Chwezi Server Manager - Deployment Script                   ║"
echo "║   Server: server.chwezi.co.za                                  ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

###############################################################################
# Step 1: Check if running as root/sudo
###############################################################################
if [ "$EUID" -ne 0 ]; then
    print_error "Please run as root or with sudo"
    exit 1
fi

print_success "Running with sudo privileges"

###############################################################################
# Step 2: Backup existing configs
###############################################################################
echo ""
print_info "Backing up Apache configurations..."

if [ -f /etc/apache2/sites-available/server.chwezi.co.za.conf ]; then
    cp /etc/apache2/sites-available/server.chwezi.co.za.conf \
       /etc/apache2/sites-available/server.chwezi.co.za.conf.backup.$(date +%Y%m%d_%H%M%S)
    print_success "HTTP config backed up"
fi

if [ -f /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf ]; then
    cp /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf \
       /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf.backup.$(date +%Y%m%d_%H%M%S)
    print_success "HTTPS config backed up"
fi

###############################################################################
# Step 3: Clone repository (if not exists)
###############################################################################
echo ""
print_info "Checking for existing server-manager installation..."

if [ -d /var/www/html/server-manager ]; then
    print_info "server-manager directory already exists"
    read -p "Do you want to pull latest changes? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        cd /var/www/html/server-manager
        git pull origin main
        print_success "Pulled latest changes"
    fi
else
    print_info "Cloning repository..."
    cd /var/www/html

    # Clone using SSH
    print_info "Using SSH authentication (git@github.com:peterbamuhigire/server-manager.git)"
    git clone git@github.com:peterbamuhigire/server-manager.git server-manager

    if [ $? -eq 0 ]; then
        print_success "Repository cloned successfully"
    else
        print_error "Failed to clone repository"
        echo ""
        print_error "SSH authentication failed. Please ensure:"
        print_error "1. SSH key is generated on this server"
        print_error "2. Public key is added to your GitHub account"
        echo ""
        print_info "To generate SSH key, run:"
        echo "   ssh-keygen -t ed25519 -C \"your_email@example.com\""
        echo ""
        print_info "To display public key, run:"
        echo "   cat ~/.ssh/id_ed25519.pub"
        echo ""
        print_info "Add the public key to: https://github.com/settings/keys"
        echo ""
        exit 1
    fi
fi

###############################################################################
# Step 4: Set ownership
###############################################################################
echo ""
print_info "Setting ownership to www-data..."
chown -R www-data:www-data /var/www/html/server-manager
print_success "Ownership set"

###############################################################################
# Step 5: Update Apache HTTP configuration
###############################################################################
echo ""
print_info "Updating Apache HTTP configuration..."

if [ -f /etc/apache2/sites-available/server.chwezi.co.za.conf ]; then
    # Update DocumentRoot
    sed -i 's|DocumentRoot /var/www/html$|DocumentRoot /var/www/html/server-manager/public|g' \
        /etc/apache2/sites-available/server.chwezi.co.za.conf

    # Check if Directory block exists, if not add it
    if ! grep -q "Directory /var/www/html/server-manager/public" /etc/apache2/sites-available/server.chwezi.co.za.conf; then
        # Add Directory block before </VirtualHost>
        sed -i '/<\/VirtualHost>/i \    <Directory /var/www/html/server-manager/public>\n        Options -Indexes +FollowSymLinks\n        AllowOverride All\n        Require all granted\n    </Directory>\n' \
            /etc/apache2/sites-available/server.chwezi.co.za.conf
    fi

    print_success "HTTP config updated"
else
    print_error "HTTP config file not found: /etc/apache2/sites-available/server.chwezi.co.za.conf"
fi

###############################################################################
# Step 6: Update Apache HTTPS configuration
###############################################################################
echo ""
print_info "Updating Apache HTTPS configuration..."

if [ -f /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf ]; then
    # Update DocumentRoot
    sed -i 's|DocumentRoot /var/www/html$|DocumentRoot /var/www/html/server-manager/public|g' \
        /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf

    # Check if Directory block exists, if not add it
    if ! grep -q "Directory /var/www/html/server-manager/public" /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf; then
        # Add Directory block before </VirtualHost>
        sed -i '/<\/VirtualHost>/i \    <Directory /var/www/html/server-manager/public>\n        Options -Indexes +FollowSymLinks\n        AllowOverride All\n        Require all granted\n    </Directory>\n' \
            /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf
    fi

    print_success "HTTPS config updated"
else
    print_error "HTTPS config file not found: /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf"
fi

###############################################################################
# Step 7: Display updated configurations
###############################################################################
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "Updated Configurations:"
echo "═══════════════════════════════════════════════════════════════"

if [ -f /etc/apache2/sites-available/server.chwezi.co.za.conf ]; then
    echo ""
    echo "HTTP Config:"
    grep "DocumentRoot" /etc/apache2/sites-available/server.chwezi.co.za.conf
fi

if [ -f /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf ]; then
    echo ""
    echo "HTTPS Config:"
    grep "DocumentRoot" /etc/apache2/sites-available/server.chwezi.co.za-le-ssl.conf
fi

echo ""
echo "═══════════════════════════════════════════════════════════════"

###############################################################################
# Step 8: Enable required Apache modules
###############################################################################
echo ""
print_info "Enabling required Apache modules..."

a2enmod rewrite >/dev/null 2>&1 || true
a2enmod headers >/dev/null 2>&1 || true

print_success "Apache modules enabled"

###############################################################################
# Step 9: Set proper permissions
###############################################################################
echo ""
print_info "Setting file and directory permissions..."

cd /var/www/html/server-manager

# Set ownership
chown -R www-data:www-data .

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make storage and database writable
chmod -R 775 storage 2>/dev/null || true
chmod -R 775 database 2>/dev/null || true

print_success "Permissions set"

###############################################################################
# Step 10: Test Apache configuration
###############################################################################
echo ""
print_info "Testing Apache configuration..."

apache2ctl configtest 2>&1 | tee /tmp/apache-test.log

if [ ${PIPESTATUS[0]} -eq 0 ]; then
    print_success "Apache configuration test passed"
else
    print_error "Apache configuration test failed!"
    echo ""
    cat /tmp/apache-test.log
    echo ""
    print_error "Please fix the configuration errors before proceeding"
    exit 1
fi

###############################################################################
# Step 11: Restart Apache
###############################################################################
echo ""
print_info "Restarting Apache..."

systemctl restart apache2

if [ $? -eq 0 ]; then
    print_success "Apache restarted successfully"
else
    print_error "Apache failed to restart"
    systemctl status apache2 --no-pager -l
    exit 1
fi

###############################################################################
# Step 12: Verify Apache is running
###############################################################################
echo ""
print_info "Verifying Apache status..."

if systemctl is-active --quiet apache2; then
    print_success "Apache is running"
else
    print_error "Apache is not running!"
    exit 1
fi

###############################################################################
# Step 13: Check if application is accessible
###############################################################################
echo ""
print_info "Checking application files..."

if [ -f /var/www/html/server-manager/public/index.php ]; then
    print_success "Application files found"
else
    print_error "Application index.php not found!"
    exit 1
fi

###############################################################################
# Step 14: Display next steps
###############################################################################
echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                  DEPLOYMENT SUCCESSFUL! 🎉                     ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
print_success "Server Manager deployed to server.chwezi.co.za"
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "NEXT STEPS:"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "1. Run the installation script:"
echo "   cd /var/www/html/server-manager"
echo "   sudo bash scripts/install.sh"
echo ""
echo "2. The installer will:"
echo "   - Create MySQL database and user"
echo "   - Run database migrations"
echo "   - Create root user account"
echo "   - Generate .env configuration"
echo ""
echo "3. Access your server manager:"
echo "   🌐 https://server.chwezi.co.za"
echo ""
echo "4. Login with the credentials provided by the installer"
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "📝 Configuration backups saved with timestamp"
echo "📁 Application location: /var/www/html/server-manager"
echo "📋 View Apache logs: sudo tail -f /var/log/apache2/error.log"
echo ""
print_info "Tip: Hard refresh your browser (Ctrl+Shift+R) if you see old content"
echo ""
