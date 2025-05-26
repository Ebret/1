#!/bin/bash
# Contabo VPS Deployment Script for UMD Drupal Project
# Optimized for Contabo VPS infrastructure with Ubuntu 24.04

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
# Load configuration from file
CONFIG_FILE="$(cd "$(dirname "$0")" && pwd)/contabo_vps_deploy.conf"
if [ -f "$CONFIG_FILE" ]; then
  source "$CONFIG_FILE"
else
  print_error "Configuration file not found: $CONFIG_FILE"
  print_status "Please create contabo_vps_deploy.conf in the scripts directory."
  exit 1
fi

# Prompt user for database password
read -s -p "Enter the database password: " DB_PASS
echo

# Ensure variables are readonly
readonly PROJECT_NAME="${PROJECT_NAME}"
readonly PROJECT_DIR="${PROJECT_DIR}"
readonly DB_NAME="${DB_NAME}"
readonly DB_USER="${DB_USER}"
readonly DB_PASS="${DB_PASS}"

# Domain and email (for SSL) - these will be obtained from user input
DOMAIN=""
EMAIL=""

# Feature flags
readonly CONTABO_OPTIMIZATIONS="${CONTABO_OPTIMIZATIONS}"
readonly ENABLE_SSL="${ENABLE_SSL}"
readonly ENABLE_MONITORING="${ENABLE_MONITORING}"
readonly ENABLE_BACKUPS="${ENABLE_BACKUPS}"
readonly INSTALL_APACHE="${INSTALL_APACHE}"
readonly INSTALL_MYSQL="${INSTALL_MYSQL}"
readonly INSTALL_PHP="${INSTALL_PHP}"
readonly INSTALL_COMPOSER="${INSTALL_COMPOSER}"
readonly INSTALL_NODEJS="${INSTALL_NODEJS}"

# Function to print colored output
print_status() {
    timestamp=$(date +%Y-%m-%d_%H:%M:%S)
    echo -e "${BLUE}[CONTABO-DEPLOY]${NC} [$timestamp] $1"
    echo "[$timestamp] $1" >> /var/log/contabo-deploy.log
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${PURPLE}================================${NC}"
    echo -e "${PURPLE}$1${NC}"
    echo -e "${PURPLE}================================${NC}"
}

# Function to check if running as root
check_root() {
    print_status "Starting check_root function"
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root on Contabo VPS"
        print_status "Please run: sudo $0"
        exit 1
    fi
    print_status "Finished check_root function"
}

# Function to get user input
get_user_configuration() {
    print_status "Starting get_user_configuration function"
    set -o errexit # Exit on error
    print_header "Contabo VPS Configuration"

    # Get domain name
    while [[ -z "$DOMAIN" ]]; do
        read -p "Enter your domain name (e.g., yourdomain.com): " DOMAIN
        if [[ ! "$DOMAIN" =~ ^([a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,})$ ]]; then
            print_warning "Please enter a valid domain name"
            DOMAIN=""
        fi
    done

    # Get email for SSL
    while [[ -z "$EMAIL" ]]; do
        read -p "Enter your email for SSL certificate: " EMAIL
        if [[ ! "$EMAIL" =~ ^([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})$ ]]; then
            print_warning "Please enter a valid email address"
            EMAIL=""
        fi
    done

    print_success "Configuration set:"
    echo "  Domain: $DOMAIN"
    echo "  Email: $EMAIL"
    echo "  Database: $DB_NAME"
    echo "  DB User: $DB_USER"
    echo "  DB Password: $DB_PASS"
    echo "$(date) - Configuration set" >> /var/log/contabo-deploy.log
}

# Function to detect Contabo VPS using dmidecode
detect_contabo() {
    print_status "Starting detect_contabo function"
    set -o errexit # Exit on error
    print_status "Detecting Contabo VPS environment..."

    # Use dmidecode to check for Contabo-specific identifiers
    if ! dmidecode | grep -q "Manufacturer: Contabo GmbH"; then
        print_warning "Contabo VPS not detected using dmidecode."
        print_status "Continuing with the deployment, but some optimizations might not be applied."
    else
        print_success "Contabo VPS detected (dmidecode)"
        echo "$(date) - Contabo VPS detected (dmidecode)" >> /var/log/contabo-deploy.log
        return 0
    fi

    return 0
}

# Function to optimize for Contabo VPS
optimize_contabo() {
    print_status "Starting optimize_contabo function"
    set -o errexit # Exit on error

    # Check if optimizations have already been applied
    if grep -q "Contabo VPS Network Optimizations" /etc/sysctl.conf; then
        print_status "Contabo VPS optimizations already applied, skipping..."
        return 0
    fi

    if [[ "$CONTABO_OPTIMIZATIONS" != "true" ]]; then
        print_status "Skipping Contabo VPS optimizations..."
        return 0
    fi

    print_status "Applying Contabo VPS optimizations..."

    # Network optimizations for Contabo infrastructure
    cat >> /etc/sysctl.conf << EOF

# Contabo VPS Network Optimizations
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.ipv4.tcp_rmem = 4096 65536 16777216
net.ipv4.tcp_wmem = 4096 65536 16777216
net.ipv4.tcp_congestion_control = bbr
net.core.default_qdisc = fq

# Memory optimizations for SSD storage
vm.swappiness = 10
vm.vfs_cache_pressure = 50
vm.dirty_ratio = 15
vm.dirty_background_ratio = 5
vm.dirty_background_ratio = 5
EOF

    sysctl -p
    echo "$(date) - Applied sysctl settings" >> /var/log/contabo-deploy.log

    # Optimize for Contabo's NVMe SSD storage
    echo 'deadline' > /sys/block/sda/queue/scheduler 2>/dev/null || true
    echo "$(date) - Set scheduler to deadline" >> /var/log/contabo-deploy.log

    print_success "Contabo optimizations applied"
    echo "$(date) - Contabo optimizations applied" >> /var/log/contabo-deploy.log
}

# Function to install packages
install_packages() {
    print_status "Starting install_packages function"
    local package_list="$1"
    print_status "Installing packages: $package_list"
    apt install -y $package_list
    if [ $? -ne 0 ]; then
        print_error "Failed to install packages: $package_list"
        exit 1
    fi
}

# Function to update system
update_system() {
    print_status "Starting update_system function"
    print_status "Updating system..."
    apt update
    if [ $? -ne 0 ]; then
        print_error "Failed to update system"
        exit 1
    fi
    apt upgrade -y
    if [ $? -ne 0 ]; then
        print_error "Failed to upgrade system"
        exit 1
    fi
}

# Function to install base system
install_base_packages() {
    print_status "Starting install_base_packages function"
    print_status "Installing base system for Contabo VPS..."

    # Update system
    update_system

    # Install essential packages
    local base_packages="curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release htop iotop nethogs tree vim fail2ban ufw"
    install_packages "$base_packages"

    print_success "Base system installed"
    echo "$(date) - Base system installed" >> /var/log/contabo-deploy.log
}

# Function to install LAMP stack
install_lamp() {
    print_status "Starting install_lamp function"
    print_status "Installing LAMP stack optimized for Contabo..."

    # Install Apache
    if [[ "$INSTALL_APACHE" == "true" ]]; then
        print_status "Installing Apache..."
        install_packages "apache2"
    else
        print_status "Skipping Apache installation..."
    fi

    # Install MySQL
    if [[ "$INSTALL_MYSQL" == "true" ]]; then
        print_status "Installing MySQL..."
        install_packages "mysql-server"
    else
        print_status "Skipping MySQL installation..."
    fi

    # Install PHP 8.3 and extensions
    if [[ "$INSTALL_PHP" == "true" ]]; then
        print_status "Installing PHP..."
        local php_packages="php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-gd php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip php8.3-sqlite3 php8.3-intl php8.3-bcmath php8.3-opcache php8.3-soap php8.3-xsl libapache2-mod-php8.3"
        install_packages "$php_packages"
    else
        print_status "Skipping PHP installation..."
    fi

    # Install Composer
    if [[ "$INSTALL_COMPOSER" == "true" ]]; then
        if ! command -v composer &> /dev/null; then
            print_status "Installing Composer"
            curl -sS https://getcomposer.org/installer | php
            if [ $? -eq 0 ]; then
                mv composer.phar /usr/local/bin/composer
                chmod +x /usr/local/bin/composer
                echo "$(date) - Installed Composer" >> /var/log/contabo-deploy.log
            else
                print_error "Failed to install Composer."
                print_status "Please check your internet connection and try again."
            fi
        else
            echo "$(date) - Composer already installed, skipping" >> /var/log/contabo-deploy.log
        fi
    else
        print_status "Skipping Composer installation..."
    fi

    # Install Node.js (for Drupal frontend tools)
    if [[ "$INSTALL_NODEJS" == "true" ]]; then
        print_status "Installing Node.js"
        curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
        if [ $? -eq 0 ]; then
            install_packages "nodejs"
        else
            print_error "Failed to add Node.js repository."
            print_status "Please check your internet connection and try again."
        fi
    else
        print_status "Skipping Node.js installation..."
    fi

    print_success "LAMP stack installed"
    echo "$(date) - LAMP stack installed" >> /var/log/contabo-deploy.log
}

# Function to configure Apache virtual host
configure_apache_vhost() {
    print_status "Starting configure_apache_vhost function"
    set -o errexit # Exit on error

    # Check if virtual host already exists
    if [ -f /etc/apache2/sites-available/${PROJECT_NAME}.conf ]; then
        print_status "Apache virtual host already configured, skipping..."
        return 0
    fi

    print_status "Configuring Apache for Contabo VPS..."

    # Enable required modules
    a2enmod rewrite ssl headers deflate expires php8.3
    echo "$(date) - Enabled Apache modules" >> /var/log/contabo-deploy.log

    # Create optimized virtual host
    cat > /etc/apache2/sites-available/${PROJECT_NAME}.conf << EOF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    DocumentRoot ${PROJECT_DIR}/drupal-cms/web

    <Directory ${PROJECT_DIR}/drupal-cms/web>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks

        # Security headers
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"

        # Performance optimizations
        ExpiresActive On
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
        ExpiresByType image/png "access plus 1 month"
        ExpiresByType image/jpg "access plus 1 month"
        ExpiresByType image/jpeg "access plus 1 month"
        ExpiresByType image/gif "access plus 1 month"
        ExpiresByType image/svg+xml "access plus 1 month"
    </Directory>

    # Logging optimized for Contabo
    ErrorLog ${APACHE_LOG_DIR}/${PROJECT_NAME}_error.log
    CustomLog ${APACHE_LOG_DIR}/${PROJECT_NAME}_access.log combined
    LogLevel warn
</VirtualHost>
EOF
    echo "$(date) - Created virtual host file" >> /var/log/contabo-deploy.log

    # Enable the site
    a2ensite ${PROJECT_NAME}.conf
    if a2dissite 000-default.conf; then
      echo "$(date) - Disabled default site" >> /var/log/contabo-deploy.log
    else
      echo "$(date) - Default site already disabled" >> /var/log/contabo-deploy.log
    fi
    echo "$(date) - Enabled site and disabled default site" >> /var/log/contabo-deploy.log

    # Optimize Apache for Contabo VPS resources
    cat > /etc/apache2/conf-available/contabo-optimization.conf << EOF
# Contabo VPS Apache Optimizations
ServerTokens Prod
ServerSignature Off

# Memory optimizations
StartServers 2
MinSpareServers 2
MaxSpareServers 5
MaxRequestWorkers 150
ThreadsPerChild 25

# Connection optimizations
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5

# Compression
LoadModule deflate_module modules/mod_deflate.so
<Location />
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \
        \.(exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
</Location>
EOF
    echo "$(date) - Created Apache optimization file" >> /var/log/contabo-deploy.log

    a2enconf contabo-optimization
    echo "$(date) - Enabled Apache optimization" >> /var/log/contabo-deploy.log
    systemctl restart apache2
    systemctl enable apache2
    echo "$(date) - Restarted and enabled Apache" >> /var/log/contabo-deploy.log

    print_success "Apache configured for Contabo VPS"
    echo "$(date) - Apache configured for Contabo VPS" >> /var/log/contabo-deploy.log
}

# Function to configure MySQL
configure_mysql_server() {
    print_status "Starting configure_mysql_server function"
    set -o errexit # Exit on error

    # Check if database already exists
    mysql -u root -p${DB_PASS} -e "SHOW DATABASES LIKE '${DB_NAME}'" | grep -q "${DB_NAME}"
    if [ $? -eq 0 ]; then
        print_status "MySQL database already configured, skipping..."
        return 0
    fi

    print_status "Configuring MySQL for production..."

    # Secure MySQL installation
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_PASS}';"
    mysql -u root -p${DB_PASS} -e "DELETE FROM mysql.user WHERE User='';"
    mysql -u root -p${DB_PASS} -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -u root -p${DB_PASS} -e "DROP DATABASE IF EXISTS test;"
    mysql -u root -p${DB_PASS} -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    echo "$(date) - Secured MySQL installation" >> /var/log/contabo-deploy.log

    # Create production database and user
    mysql -u root -p${DB_PASS} -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
    mysql -u root -p${DB_PASS} -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -u root -p${DB_PASS} -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    mysql -u root -p${DB_PASS} -e "FLUSH PRIVILEGES;"
    echo "$(date) - Created database and user" >> /var/log/contabo-deploy.log

    # Remove anonymous users
    mysql -u root -p${DB_PASS} -e "DELETE FROM mysql.user WHERE User='';"
    echo "$(date) - Removed anonymous users" >> /var/log/contabo-deploy.log

    # Optimize MySQL for Contabo VPS
    cat >> /etc/mysql/mysql.conf.d/contabo-optimization.cnf << EOF
[mysqld]
# Contabo VPS MySQL Optimizations
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_type = 1
query_cache_size = 64M
max_connections = 100
thread_cache_size = 8
table_open_cache = 2000
EOF
    echo "$(date) - Created MySQL optimization file" >> /var/log/contabo-deploy.log

    systemctl restart mysql
    systemctl enable mysql
    echo "$(date) - Restarted and enabled MySQL" >> /var/log/contabo-deploy.log

    print_success "MySQL configured for production"
    echo "$(date) - MySQL configured for production" >> /var/log/contabo-deploy.log
}

# Function to optimize PHP
optimize_php_settings() {
    print_status "Starting optimize_php_settings function"
    set -o errexit # Exit on error
    print_status "Optimizing PHP for Contabo VPS..."

    # Create production PHP configuration
    cat > /etc/php/8.3/apache2/conf.d/99-contabo-drupal.ini << EOF
; Contabo VPS PHP Optimizations for Drupal
memory_limit = 512M
max_execution_time = 300
max_input_vars = 3000
post_max_size = 64M
upload_max_filesize = 64M
max_file_uploads = 20

; OpCache optimizations for Contabo SSD
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
opcache.validate_timestamps = 0

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
session.cookie_httponly = 1
session.cookie_secure = 1

; Performance
realpath_cache_size = 4096K
realpath_cache_ttl = 600
EOF
    echo "$(date) - Created PHP configuration file" >> /var/log/contabo-deploy.log

    # Copy to CLI configuration
    cp /etc/php/8.3/apache2/conf.d/99-contabo-drupal.ini /etc/php/8.3/cli/conf.d/
    echo "$(date) - Copied PHP configuration to CLI" >> /var/log/contabo-deploy.log

    systemctl restart apache2
    echo "$(date) - Restarted Apache" >> /var/log/contabo-deploy.log

    print_success "PHP optimized for Contabo VPS"
    echo "$(date) - PHP optimized for Contabo VPS" >> /var/log/contabo-deploy.log
}

# Function to setup SSL with Let's Encrypt
setup_letsencrypt_ssl() {
    print_status "Starting setup_letsencrypt_ssl function"
    set -o errexit # Exit on error
    if [[ "$ENABLE_SSL" != "true" ]]; then
        print_status "Skipping Let's Encrypt SSL setup..."
        return 0
    fi

    print_status "Setting up SSL certificate with Let's Encrypt..."

    # Install Certbot
    apt install -y certbot python3-certbot-apache
    if [ $? -ne 0 ]; then
        print_error "Failed to install Certbot"
        exit 1
    fi
    echo "$(date) - Installed Certbot" >> /var/log/contabo-deploy.log

    # Get SSL certificate
    certbot --apache --non-interactive --agree-tos --email ${EMAIL} -d ${DOMAIN} -d www.${DOMAIN}
    if [ $? -ne 0 ]; then
        print_error "Failed to obtain SSL certificate"
        exit 1
    fi
    echo "$(date) - Obtained SSL certificate" >> /var/log/contabo-deploy.log

    # Set up auto-renewal
    systemctl enable certbot.timer
    systemctl start certbot.timer
    echo "$(date) - Enabled and started Certbot timer" >> /var/log/contabo-deploy.log

    print_success "SSL certificate installed and auto-renewal configured"
    echo "$(date) - SSL certificate installed and auto-renewal configured" >> /var/log/contabo-deploy.log
}

# Function to configure security
configure_firewall_security() {
    print_status "Starting configure_firewall_security function"
    set -o errexit # Exit on error
    print_status "Configuring security for Contabo VPS..."

    # Configure UFW firewall
    ufw --force reset
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow ssh
    ufw allow http
    ufw allow https
    ufw --force enable
    if [ $? -ne 0 ]; then
        print_error "Failed to configure UFW firewall"
        exit 1
    fi
    echo "$(date) - Configured UFW firewall" >> /var/log/contabo-deploy.log

    # Configure fail2ban
    cat > /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log

[apache-auth]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/*error.log

[apache-badbots]
enabled = true
port = http,https
filter = apache-badbots
EOF
    echo "$(date) - Configured fail2ban" >> /var/log/contabo-deploy.log

    print_success "Security configured for Contabo VPS"
    print_status "Finished configure_firewall_security function"
}

# Function to install and configure monitoring
configure_monitoring() {
    print_status "Starting configure_monitoring function"
    if [[ "$ENABLE_MONITORING" != "true" ]]; then
        print_status "Skipping monitoring setup..."
        return 0
    fi

    # Check if monitoring is already installed
    if systemctl is-active --quiet netdata; then
        print_status "Netdata is already installed and running, skipping..."
        return 0
    fi

    if [[ "$MONITORING_TOOL" == "netdata" ]]; then
        print_status "Installing and configuring Netdata..."
        bash <(curl -Ss https://my-netdata.io/kickstart.sh) all --default
        if [ $? -ne 0 ]; then
            print_error "Failed to install Netdata"
            exit 1
        fi
        systemctl enable netdata
        systemctl start netdata
        print_success "Netdata installed and configured"
    elif [[ "$MONITORING_TOOL" == "prometheus" ]]; then
        print_status "Installing and configuring Prometheus (not yet implemented)..."
        print_warning "Prometheus support is not yet implemented."
    else
        print_warning "Invalid monitoring tool specified. Skipping monitoring setup."
    fi
    print_status "Finished configure_monitoring function"
}

# Function to install and configure backups
configure_backups() {
    print_status "Starting configure_backups function"
    if [[ "$ENABLE_BACKUPS" != "true" ]]; then
        print_status "Skipping backup setup..."
        return 0
    fi

    # Check if backup location exists
    if [ -d "$BACKUP_LOCATION/borg" ]; then
        print_status "Backup location already initialized, skipping..."
        return 0
    fi

    if [[ "$BACKUP_TOOL" == "borgbackup" ]]; then
        print_status "Installing and configuring BorgBackup..."
        apt install -y borgbackup
        if [ $? -ne 0 ]; then
            print_error "Failed to install BorgBackup"
            exit 1
        fi
        mkdir -p "$BACKUP_LOCATION"
        # Check if backup location is writable
        if ! touch "$BACKUP_LOCATION/testfile"; then
            print_error "Backup location '$BACKUP_LOCATION' is not writable."
            exit 1
        else
            rm -f "$BACKUP_LOCATION/testfile"
        fi
        # Initialize Borg repository (if not already initialized)
        if [ ! -d "$BACKUP_LOCATION/borg" ]; then
            borg init --encryption=repokey "$BACKUP_LOCATION/borg"
            if [ $? -ne 0 ]; then
                print_error "Failed to initialize Borg repository"
                exit 1
            fi
        fi
        # Create backup script
        cat > /usr/local/bin/backup_script.sh << EOF
#!/bin/bash
# Backup script
BACKUP_LOCATION="$BACKUP_LOCATION"
BORG_REPO="$BACKUP_LOCATION/borg"
BORG_PASSPHRASE="" # Set your passphrase here or in environment variable
SOURCE="/var/www/html" # Source directory to backup

# Create timestamped archive name
ARCHIVE_NAME="\$(hostname)-\$(date +%Y-%m-%d_%H-%M-%S)"

# Create the backup
borg create --stats --verbose --compression lz4 "\$BORG_REPO::\$ARCHIVE_NAME" "\$SOURCE"
if [ $? -ne 0 ]; then
    echo "Backup failed"
    exit 1
fi

# Prune old backups (keep last 7 daily, 4 weekly, 12 monthly)
borg prune --list --verbose --keep-daily=7 --keep-weekly=4 --keep-monthly=12 "\$BORG_REPO"
if [ $? -ne 0 ]; then
    echo "Prune failed"
    exit 1
fi

# Check the repository
borg check "\$BORG_REPO"
if [ $? -ne 0 ]; then
    echo "Check failed"
    exit 1
fi

# Display repository information
borg info "\$BORG_REPO"
if [ $? -ne 0 ]; then
    echo "Info failed"
    exit 1
fi
EOF
        chmod +x /usr/local/bin/backup_script.sh
        if [ $? -ne 0 ]; then
            print_error "Failed to make backup script executable"
            exit 1
        fi
        # Set up cron job for daily backups
        (crontab -l 2>/dev/null; echo "0 0 * * * /usr/local/bin/backup_script.sh") | crontab -
        if [ $? -ne 0 ]; then
            print_error "Failed to set up cron job"
            exit 1
        fi
        print_success "BorgBackup installed and configured"
    elif [[ "$BACKUP_TOOL" == "rsync" ]]; then
        print_status "Installing and configuring Rsync (not yet implemented)..."
        print_warning "Rsync support is not yet implemented."
    else
        print_warning "Invalid backup tool specified. Skipping backup setup."
    fi
    print_status "Finished configure_backups function"
}

# Function to run tests
run_tests() {
    print_status "Starting run_tests function"
    print_status "Running tests..."

    # Check if necessary tools are installed
    if ! command -v curl &> /dev/null; then
        print_error "curl is not installed. Please install it to run tests."
        exit 1
    fi

    # Test web server
    print_status "Testing web server..."
    curl -I http://${DOMAIN}
    if [ $? -ne 0 ]; then
        print_error "Web server test failed. Please check your web server configuration."
        exit 1
    fi

    # Test database connection
    print_status "Testing database connection..."
    mysql -u ${DB_USER} -p${DB_PASS} -h localhost -e "SELECT 1"
    if [ $? -ne 0 ]; then
        print_error "Database connection test failed. Please check your database configuration."
        exit 1
    fi

    print_success "All tests passed!"
    print_status "Finished run_tests function"
}

# Main execution
check_root
get_user_configuration
detect_contabo
install_base_packages
optimize_contabo
install_lamp
configure_apache_vhost
configure_mysql_server
optimize_php_settings
setup_letsencrypt_ssl
configure_firewall_security
configure_monitoring
configure_backups
run_tests

print_success "Contabo VPS deployment completed!"

exit 0
