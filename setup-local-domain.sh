#!/bin/bash

# Configuration
DOMAIN="server.peter"
PROJECT_ROOT="/var/www/html/server-manager"
PUBLIC_DIR="${PROJECT_ROOT}/public"
SSL_DIR="/etc/ssl/localcerts"
APACHE_CONF="/etc/apache2/sites-available/${DOMAIN}.conf"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}Setting up local domain: ${DOMAIN}${NC}"
echo -e "${YELLOW}This script requires sudo privileges.${NC}"

# Ensure running as root
if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}Please run as root (sudo ./setup-local-domain.sh)${NC}"
  exit 1
fi

# 1. Update /etc/hosts
echo -e "${GREEN}1. Updating /etc/hosts${NC}"
if grep -q "$DOMAIN" /etc/hosts; then
    echo "   - entry already exists in /etc/hosts"
else
    echo "127.0.0.1 $DOMAIN" >> /etc/hosts
    echo "   - added $DOMAIN to /etc/hosts"
fi

# 2. Setup SSL
echo -e "${GREEN}2. Generating SSL Certificate${NC}"
mkdir -p $SSL_DIR

KEY_FILE="$SSL_DIR/$DOMAIN.key"
CERT_FILE="$SSL_DIR/$DOMAIN.crt"

if [ ! -f "$CERT_FILE" ]; then
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "$KEY_FILE" \
        -out "$CERT_FILE" \
        -subj "/C=US/ST=Local/L=Local/O=Dev/CN=$DOMAIN" \
        -addext "subjectAltName=DNS:$DOMAIN"

    echo "   - Certificate generated at $CERT_FILE"
else
    echo "   - Certificate already exists"
fi

# 3. Trust Certificate (OS Level)
echo -e "${GREEN}3. Trusting Certificate${NC}"
cp "$CERT_FILE" /usr/local/share/ca-certificates/$DOMAIN.crt
update-ca-certificates

# 4. Create Apache Config
echo -e "${GREEN}4. Configuring Apache VHost${NC}"

cat > $APACHE_CONF <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    Redirect permanent / https://$DOMAIN/
</VirtualHost>

<VirtualHost *:443>
    ServerName $DOMAIN
    DocumentRoot $PUBLIC_DIR

    SSLEngine on
    SSLCertificateFile $CERT_FILE
    SSLCertificateKeyFile $KEY_FILE

    <Directory $PUBLIC_DIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}_error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}_access.log combined
</VirtualHost>
EOF

echo "   - Created $APACHE_CONF"

# 5. Enable Modules and Site
echo -e "${GREEN}5. Enabling Apache Modules and Site${NC}"
a2enmod ssl
a2enmod rewrite
a2ensite ${DOMAIN}.conf

# 6. Restart Apache
echo -e "${GREEN}6. Restarting Apache${NC}"
if systemctl restart apache2; then
    echo -e "${GREEN}SUCCESS!${NC}"
    echo -e "You can now access: https://$DOMAIN"
else
    # Fallback to init.d if systemctl fails (e.g. in some containers)
    if service apache2 restart; then
         echo -e "${GREEN}SUCCESS!${NC}"
         echo -e "You can now access: https://$DOMAIN"
    else
         echo -e "${RED}Failed to restart apache. Please check logs.${NC}"
    fi
fi
