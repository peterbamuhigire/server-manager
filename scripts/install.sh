#!/bin/bash

# ============================================
# Chwezi Server Manager - Installation Script
# ============================================

set -e  # Exit on error

echo "======================================"
echo "Chwezi Server Manager - Installation"
echo "======================================"
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo "Error: This script must be run as root (use sudo)"
   exit 1
fi

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

# ============================================
# Select Environment
# ============================================

echo "Select environment:"
echo "  1) dev (localhost)"
echo "  2) demo (server.techguypeter.com)"
echo "  3) prod (server.chwezi.co.za)"
read -p "Enter choice [1-3]: " ENV_CHOICE

case $ENV_CHOICE in
    1)
        ENV="dev"
        APP_URL="http://localhost"
        APP_DEBUG="true"
        ;;
    2)
        ENV="demo"
        APP_URL="https://server.techguypeter.com"
        APP_DEBUG="false"
        ;;
    3)
        ENV="prod"
        APP_URL="https://server.chwezi.co.za"
        APP_DEBUG="false"
        ;;
    *)
        echo "Invalid choice"
        exit 1
        ;;
esac

echo ""
echo "Installing for environment: $ENV"
echo "Application URL: $APP_URL"
echo ""

# ============================================
# Database Configuration
# ============================================

echo "Database Configuration"
echo "----------------------"

read -p "MySQL root password: " -s MYSQL_ROOT_PASS
echo ""

read -p "Database name [chwezi_sm]: " DB_NAME
DB_NAME=${DB_NAME:-chwezi_sm}

read -p "Database user [chwezi_sm]: " DB_USER
DB_USER=${DB_USER:-chwezi_sm}

# Generate random database password
DB_PASS=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)

echo ""
echo "Database credentials:"
echo "  Name: $DB_NAME"
echo "  User: $DB_USER"
echo "  Password: $DB_PASS"
echo ""

# ============================================
# Create Database
# ============================================

echo "Creating database..."

mysql -u root -p"$MYSQL_ROOT_PASS" <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "Database created successfully!"
echo ""

# ============================================
# Run Migrations
# ============================================

echo "Running database migrations..."

mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$PROJECT_ROOT/database/migrations/001_create_auth_tables.sql"

echo "Migrations completed!"
echo ""

# ============================================
# Root User Creation
# ============================================

echo "Root User Setup"
echo "---------------"

read -p "Root username: " ROOT_USER
read -p "Root email: " ROOT_EMAIL

while true; do
    read -p "Root password: " -s ROOT_PASS
    echo ""
    read -p "Confirm password: " -s ROOT_PASS_CONFIRM
    echo ""

    if [ "$ROOT_PASS" = "$ROOT_PASS_CONFIRM" ]; then
        break
    else
        echo "Passwords do not match. Try again."
    fi
done

echo "Creating root user..."

php "$PROJECT_ROOT/scripts/create-root-user.php" "$ROOT_USER" "$ROOT_EMAIL" "$ROOT_PASS" "$DB_NAME" "$DB_USER" "$DB_PASS"

echo ""

# ============================================
# Create .env File
# ============================================

echo "Creating .env file..."

cat > "$PROJECT_ROOT/.env" <<EOF
# Application
APP_NAME="Chwezi Server Manager"
APP_ENV=$ENV
APP_DEBUG=$APP_DEBUG
APP_URL=$APP_URL

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

# Session
SESSION_LIFETIME=30
SESSION_SECURE=false
SESSION_NAME=chwezi_sm_session

# Security
CSRF_ENABLED=true

# Root User
ROOT_USERNAME=$ROOT_USER
ROOT_EMAIL=$ROOT_EMAIL

# Timezone
APP_TIMEZONE=Africa/Kampala

# Paths
APACHE_SITES_AVAILABLE=/etc/apache2/sites-available
APACHE_SITES_ENABLED=/etc/apache2/sites-enabled
WEB_ROOT=/var/www
CERTBOT_BIN=/usr/bin/certbot
EOF

echo ".env file created!"
echo ""

# ============================================
# Set Permissions
# ============================================

echo "Setting permissions..."

# Create storage directories if they don't exist
mkdir -p "$PROJECT_ROOT/storage/logs"
mkdir -p "$PROJECT_ROOT/storage/backups"
mkdir -p "$PROJECT_ROOT/storage/cache"

# Set ownership to www-data (Apache user)
chown -R www-data:www-data "$PROJECT_ROOT/storage"
chmod -R 775 "$PROJECT_ROOT/storage"

# Protect .env file
chmod 600 "$PROJECT_ROOT/.env"

echo "Permissions set!"
echo ""

# ============================================
# Apache Configuration (if not dev)
# ============================================

if [ "$ENV" != "dev" ]; then
    echo "Do you want to configure Apache virtual host? (y/n)"
    read -p "Choice: " SETUP_APACHE

    if [ "$SETUP_APACHE" = "y" ] || [ "$SETUP_APACHE" = "Y" ]; then
        DOMAIN=$(echo $APP_URL | sed 's|https://||' | sed 's|http://||')

        cat > "/etc/apache2/sites-available/${DOMAIN}.conf" <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    DocumentRoot $PROJECT_ROOT/public

    <Directory $PROJECT_ROOT/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}-access.log combined
</VirtualHost>
EOF

        # Enable site
        a2ensite "${DOMAIN}.conf"
        a2enmod rewrite
        systemctl reload apache2

        echo "Apache virtual host configured!"
        echo ""

        # SSL Certificate
        echo "Do you want to install SSL certificate with Certbot? (y/n)"
        read -p "Choice: " SETUP_SSL

        if [ "$SETUP_SSL" = "y" ] || [ "$SETUP_SSL" = "Y" ]; then
            certbot --apache -d "$DOMAIN" --non-interactive --agree-tos -m "$ROOT_EMAIL"
            echo "SSL certificate installed!"
        fi
    fi
fi

# ============================================
# Complete!
# ============================================

echo ""
echo "======================================"
echo "Installation Complete!"
echo "======================================"
echo ""
echo "Application URL: $APP_URL"
echo "Root Username: $ROOT_USER"
echo ""
echo "Database Credentials (save these!):"
echo "  Database: $DB_NAME"
echo "  Username: $DB_USER"
echo "  Password: $DB_PASS"
echo ""

if [ "$ENV" = "dev" ]; then
    echo "To start development server:"
    echo "  cd $PROJECT_ROOT"
    echo "  php -S localhost:8000 -t public"
    echo ""
fi

echo "You can now login at: $APP_URL/login"
echo ""
