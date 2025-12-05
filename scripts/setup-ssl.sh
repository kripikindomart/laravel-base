#!/bin/bash

###############################################################################
# Auto SSL Setup Script for Laravel Multi-Tenant Custom Domains
# This script automatically obtains SSL certificates for custom tenant domains
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
WEBROOT="/var/www/laravel-base/public"
EMAIL="admin@yourdomain.com"  # Change this!

echo -e "${GREEN}=== Laravel Multi-Tenant SSL Setup ===${NC}\n"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root (use sudo)${NC}"
    exit 1
fi

# Check if certbot is installed
if ! command -v certbot &> /dev/null; then
    echo -e "${YELLOW}Certbot not found. Installing...${NC}"

    # Detect OS
    if [ -f /etc/debian_version ]; then
        apt-get update
        apt-get install -y certbot python3-certbot-nginx
    elif [ -f /etc/redhat-release ]; then
        yum install -y certbot python3-certbot-nginx
    else
        echo -e "${RED}Unsupported OS. Please install certbot manually.${NC}"
        exit 1
    fi

    echo -e "${GREEN}Certbot installed successfully!${NC}\n"
fi

# Function to setup SSL for a domain
setup_ssl() {
    local domain=$1
    local www_domain="www.$domain"

    echo -e "${YELLOW}Setting up SSL for: $domain${NC}"

    # Check if domain is accessible
    if ! ping -c 1 $domain &> /dev/null; then
        echo -e "${RED}Warning: $domain is not accessible. DNS may not be configured.${NC}"
        read -p "Continue anyway? (y/n): " continue
        if [ "$continue" != "y" ]; then
            return 1
        fi
    fi

    # Obtain certificate
    certbot certonly \
        --webroot \
        --webroot-path=$WEBROOT \
        --email $EMAIL \
        --agree-tos \
        --no-eff-email \
        --domain $domain \
        --domain $www_domain \
        --non-interactive \
        --keep-until-expiring

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ SSL certificate obtained for $domain${NC}\n"

        # Create nginx config for this domain
        create_nginx_config $domain

        # Reload nginx
        nginx -t && systemctl reload nginx

        return 0
    else
        echo -e "${RED}✗ Failed to obtain SSL certificate for $domain${NC}\n"
        return 1
    fi
}

# Function to create nginx config for custom domain
create_nginx_config() {
    local domain=$1
    local config_file="/etc/nginx/sites-available/$domain"

    cat > $config_file <<EOF
# Auto-generated SSL config for $domain
server {
    listen 80;
    listen [::]:80;
    server_name $domain www.$domain;

    # Redirect HTTP to HTTPS
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    server_name $domain www.$domain;
    root $WEBROOT;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/$domain/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$domain/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256';
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Laravel configuration
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

    # Enable the site
    ln -sf $config_file /etc/nginx/sites-enabled/$domain

    echo -e "${GREEN}Nginx config created and enabled for $domain${NC}"
}

# Function to setup SSL for main domain (wildcard)
setup_main_domain() {
    local domain=$1

    echo -e "${YELLOW}Setting up wildcard SSL for: *.$domain${NC}"

    certbot certonly \
        --dns-cloudflare \
        --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
        --email $EMAIL \
        --agree-tos \
        --no-eff-email \
        --domain $domain \
        --domain "*.$domain" \
        --non-interactive \
        --keep-until-expiring

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Wildcard SSL certificate obtained for *.$domain${NC}\n"
        return 0
    else
        echo -e "${RED}✗ Failed to obtain wildcard SSL certificate${NC}"
        echo -e "${YELLOW}Note: Wildcard SSL requires DNS validation (e.g., Cloudflare)${NC}\n"
        return 1
    fi
}

# Function to renew all certificates
renew_certificates() {
    echo -e "${YELLOW}Renewing all SSL certificates...${NC}"

    certbot renew --quiet

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ All certificates renewed successfully${NC}"
        systemctl reload nginx
    else
        echo -e "${RED}✗ Certificate renewal failed${NC}"
    fi
}

# Function to list all certificates
list_certificates() {
    echo -e "${YELLOW}Installed SSL Certificates:${NC}\n"
    certbot certificates
}

# Main menu
show_menu() {
    echo ""
    echo -e "${GREEN}What would you like to do?${NC}"
    echo "1) Setup SSL for a custom domain"
    echo "2) Setup wildcard SSL for main domain"
    echo "3) Renew all certificates"
    echo "4) List all certificates"
    echo "5) Exit"
    echo ""
}

# Main loop
while true; do
    show_menu
    read -p "Enter your choice [1-5]: " choice

    case $choice in
        1)
            read -p "Enter custom domain (e.g., acmecorp.com): " domain
            setup_ssl $domain
            ;;
        2)
            read -p "Enter main domain (e.g., yourdomain.com): " domain
            setup_main_domain $domain
            ;;
        3)
            renew_certificates
            ;;
        4)
            list_certificates
            ;;
        5)
            echo -e "${GREEN}Goodbye!${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}Invalid choice. Please try again.${NC}"
            ;;
    esac
done
