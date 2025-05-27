#!/bin/bash
# MLM System Setup Script
# Specialized setup for Multi-Level Marketing functionality

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
MLM_MODULE_NAME="unilevelmlm"
MLM_DATABASE="mlm_system"
MLM_USER="mlm_admin"
MLM_PASS="MLM_$(openssl rand -base64 12 | tr -d '=+/' | cut -c1-16)"
PROJECT_DIR="/var/www/html/umd"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[MLM-SETUP]${NC} $1"
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
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root for MLM system setup"
        print_status "Please run: sudo $0"
        exit 1
    fi
}

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking MLM system prerequisites..."
    
    # Check if Drupal is installed
    if [[ ! -f "$PROJECT_DIR/drupal-cms/web/index.php" ]]; then
        print_error "Drupal installation not found at $PROJECT_DIR/drupal-cms"
        print_status "Please run the Ubuntu 24.04 setup script first"
        exit 1
    fi
    
    # Check if Composer is available
    if ! command -v composer &> /dev/null; then
        print_error "Composer is required but not installed"
        exit 1
    fi
    
    # Check if Drush is available
    if [[ ! -f "$PROJECT_DIR/drupal-cms/vendor/bin/drush" ]]; then
        print_error "Drush not found. Please install Drupal dependencies first"
        exit 1
    fi
    
    print_success "Prerequisites check completed"
}

# Function to setup MLM database
setup_mlm_database() {
    print_status "Setting up MLM database..."
    
    # Create MLM-specific database
    mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS ${MLM_DATABASE};"
    mysql -u root -p -e "CREATE USER IF NOT EXISTS '${MLM_USER}'@'localhost' IDENTIFIED BY '${MLM_PASS}';"
    mysql -u root -p -e "GRANT ALL PRIVILEGES ON ${MLM_DATABASE}.* TO '${MLM_USER}'@'localhost';"
    mysql -u root -p -e "FLUSH PRIVILEGES;"
    
    print_success "MLM database configured"
}

# Function to install MLM dependencies
install_mlm_dependencies() {
    print_status "Installing MLM-specific dependencies..."
    
    cd "$PROJECT_DIR/drupal-cms"
    
    # Install required Drupal modules for MLM
    composer require drupal/views_data_export
    composer require drupal/charts
    composer require drupal/webform
    composer require drupal/rules
    composer require drupal/commerce
    composer require drupal/payment
    composer require drupal/address
    composer require drupal/profile
    
    # Install development dependencies
    composer require --dev drupal/devel
    composer require --dev drupal/admin_toolbar
    
    print_success "MLM dependencies installed"
}

# Function to enable MLM modules
enable_mlm_modules() {
    print_status "Enabling MLM modules..."
    
    cd "$PROJECT_DIR/drupal-cms"
    
    # Enable core required modules
    vendor/bin/drush en -y views views_ui
    vendor/bin/drush en -y field field_ui
    vendor/bin/drush en -y user
    vendor/bin/drush en -y system
    vendor/bin/drush en -y node
    
    # Enable MLM-specific modules
    vendor/bin/drush en -y views_data_export
    vendor/bin/drush en -y charts
    vendor/bin/drush en -y webform
    vendor/bin/drush en -y rules
    vendor/bin/drush en -y commerce
    vendor/bin/drush en -y payment
    vendor/bin/drush en -y address
    vendor/bin/drush en -y profile
    
    # Enable development modules
    vendor/bin/drush en -y devel
    vendor/bin/drush en -y admin_toolbar
    vendor/bin/drush en -y admin_toolbar_tools
    
    print_success "MLM modules enabled"
}

# Function to configure MLM module
configure_mlm_module() {
    print_status "Configuring MLM module..."
    
    cd "$PROJECT_DIR/drupal-cms"
    
    # Copy MLM module to Drupal modules directory
    if [[ -d "../umd" ]]; then
        cp -r ../umd web/modules/custom/unilevelmlm/
        chown -R www-data:www-data web/modules/custom/unilevelmlm/
        chmod -R 755 web/modules/custom/unilevelmlm/
    fi
    
    # Enable the MLM module
    vendor/bin/drush en -y unilevelmlm
    
    # Import MLM configuration
    if [[ -f "web/modules/custom/unilevelmlm/config/install/unilevelmlm.settings.yml" ]]; then
        vendor/bin/drush cim -y
    fi
    
    print_success "MLM module configured"
}

# Function to setup MLM permissions
setup_mlm_permissions() {
    print_status "Setting up MLM permissions..."
    
    cd "$PROJECT_DIR/drupal-cms"
    
    # Create MLM roles
    vendor/bin/drush role:create mlm_administrator "MLM Administrator"
    vendor/bin/drush role:create mlm_manager "MLM Manager"
    vendor/bin/drush role:create mlm_member "MLM Member"
    vendor/bin/drush role:create mlm_leader "MLM Leader"
    
    # Assign permissions to roles
    vendor/bin/drush role:perm:add mlm_administrator "administer unilevelmlm"
    vendor/bin/drush role:perm:add mlm_administrator "manage mlm users"
    vendor/bin/drush role:perm:add mlm_administrator "view mlm reports"
    vendor/bin/drush role:perm:add mlm_administrator "process mlm payouts"
    
    vendor/bin/drush role:perm:add mlm_manager "manage mlm users"
    vendor/bin/drush role:perm:add mlm_manager "view mlm reports"
    
    vendor/bin/drush role:perm:add mlm_member "access mlm dashboard"
    vendor/bin/drush role:perm:add mlm_member "view own mlm data"
    
    vendor/bin/drush role:perm:add mlm_leader "access mlm dashboard"
    vendor/bin/drush role:perm:add mlm_leader "view own mlm data"
    vendor/bin/drush role:perm:add mlm_leader "view team mlm data"
    
    print_success "MLM permissions configured"
}

# Function to setup MLM content types
setup_mlm_content_types() {
    print_status "Setting up MLM content types..."
    
    cd "$PROJECT_DIR/drupal-cms"
    
    # Create MLM-specific content types
    vendor/bin/drush generate:content-type mlm_product "MLM Product"
    vendor/bin/drush generate:content-type mlm_commission "MLM Commission"
    vendor/bin/drush generate:content-type mlm_payout "MLM Payout"
    vendor/bin/drush generate:content-type mlm_rank "MLM Rank"
    
    print_success "MLM content types created"
}

# Function to setup MLM views
setup_mlm_views() {
    print_status "Setting up MLM views..."
    
    cd "$PROJECT_DIR/drupal-cms"
    
    # Import MLM views configuration
    if [[ -d "web/modules/custom/unilevelmlm/config/install" ]]; then
        vendor/bin/drush cim -y
    fi
    
    # Clear cache to ensure views are available
    vendor/bin/drush cr
    
    print_success "MLM views configured"
}

# Function to setup MLM theme
setup_mlm_theme() {
    print_status "Setting up MLM theme..."
    
    cd "$PROJECT_DIR/drupal-cms"
    
    # Install Bootstrap theme for better MLM UI
    composer require drupal/bootstrap
    vendor/bin/drush en -y bootstrap
    
    # Set Bootstrap as default theme
    vendor/bin/drush config:set system.theme default bootstrap -y
    
    # Configure theme settings for MLM
    vendor/bin/drush config:set bootstrap.settings cdn_provider jsdelivr -y
    vendor/bin/drush config:set bootstrap.settings cdn_version 4.6.0 -y
    
    print_success "MLM theme configured"
}

# Function to create sample MLM data
create_sample_data() {
    print_status "Creating sample MLM data..."
    
    cd "$PROJECT_DIR/drupal-cms"
    
    # Create admin user for MLM
    vendor/bin/drush user:create mlm_admin --mail="admin@mlm.local" --password="admin123"
    vendor/bin/drush user:role:add mlm_administrator mlm_admin
    
    # Create sample MLM members
    vendor/bin/drush user:create mlm_member1 --mail="member1@mlm.local" --password="member123"
    vendor/bin/drush user:role:add mlm_member mlm_member1
    
    vendor/bin/drush user:create mlm_member2 --mail="member2@mlm.local" --password="member123"
    vendor/bin/drush user:role:add mlm_member mlm_member2
    
    vendor/bin/drush user:create mlm_leader1 --mail="leader1@mlm.local" --password="leader123"
    vendor/bin/drush user:role:add mlm_leader mlm_leader1
    
    print_success "Sample MLM data created"
}

# Function to configure MLM settings
configure_mlm_settings() {
    print_status "Configuring MLM system settings..."
    
    cd "$PROJECT_DIR/drupal-cms"
    
    # Configure MLM settings
    vendor/bin/drush config:set unilevelmlm.settings commission_levels 10 -y
    vendor/bin/drush config:set unilevelmlm.settings minimum_payout 50.00 -y
    vendor/bin/drush config:set unilevelmlm.settings processing_fee 2.50 -y
    vendor/bin/drush config:set unilevelmlm.settings payout_frequency weekly -y
    
    # Configure commission rates
    vendor/bin/drush config:set unilevelmlm.settings commission_rates.level_1 10.00 -y
    vendor/bin/drush config:set unilevelmlm.settings commission_rates.level_2 5.00 -y
    vendor/bin/drush config:set unilevelmlm.settings commission_rates.level_3 3.00 -y
    vendor/bin/drush config:set unilevelmlm.settings commission_rates.level_4 2.00 -y
    vendor/bin/drush config:set unilevelmlm.settings commission_rates.level_5 1.00 -y
    
    print_success "MLM settings configured"
}

# Function to setup MLM cron jobs
setup_mlm_cron() {
    print_status "Setting up MLM cron jobs..."
    
    # Create MLM-specific cron jobs
    cat > /etc/cron.d/mlm-system << EOF
# MLM System Cron Jobs
# Commission calculations every hour
0 * * * * www-data cd $PROJECT_DIR/drupal-cms && vendor/bin/drush mlm:calculate-commissions

# Payout processing daily at 2 AM
0 2 * * * www-data cd $PROJECT_DIR/drupal-cms && vendor/bin/drush mlm:process-payouts

# Rank advancement check daily at 3 AM
0 3 * * * www-data cd $PROJECT_DIR/drupal-cms && vendor/bin/drush mlm:check-rank-advancement

# MLM reports generation weekly on Sunday at 4 AM
0 4 * * 0 www-data cd $PROJECT_DIR/drupal-cms && vendor/bin/drush mlm:generate-reports
EOF
    
    print_success "MLM cron jobs configured"
}

# Function to display final information
display_final_info() {
    print_header "MLM System Setup Complete!"
    
    echo -e "${GREEN}Your MLM system is ready!${NC}"
    echo ""
    echo "=== MLM Access Information ==="
    echo "MLM Admin URL: https://$(hostname)/admin/config/unilevelmlm"
    echo "MLM Dashboard: https://$(hostname)/mlm/dashboard"
    echo "MLM Reports: https://$(hostname)/mlm/reports"
    echo ""
    echo "=== MLM Database ==="
    echo "Database: ${MLM_DATABASE}"
    echo "Username: ${MLM_USER}"
    echo "Password: ${MLM_PASS}"
    echo ""
    echo "=== MLM User Accounts ==="
    echo "MLM Admin: mlm_admin / admin123"
    echo "MLM Member 1: mlm_member1 / member123"
    echo "MLM Member 2: mlm_member2 / member123"
    echo "MLM Leader: mlm_leader1 / leader123"
    echo ""
    echo "=== MLM Features ==="
    echo "✅ Unilevel compensation plan (10 levels)"
    echo "✅ Commission calculation engine"
    echo "✅ Genealogy tree visualization"
    echo "✅ Payment processing system"
    echo "✅ Dashboard and reporting"
    echo "✅ User role management"
    echo "✅ Automated cron jobs"
    echo ""
    echo "=== Next Steps ==="
    echo "1. Access MLM admin panel to configure settings"
    echo "2. Create your MLM products and pricing"
    echo "3. Set up payment gateways"
    echo "4. Configure commission structure"
    echo "5. Test MLM functionality with sample users"
    echo ""
    echo "=== Documentation ==="
    echo "MLM Features: docs/MLM_FEATURES.md"
    echo "MLM Branch Guide: docs/MLM_BRANCH_README.md"
    echo "API Documentation: docs/MLM_API_DOCUMENTATION.md"
    
    # Save MLM setup information
    cat > /root/mlm-setup-info.txt << EOF
MLM System Setup Information
Generated: $(date)

MLM Database: ${MLM_DATABASE}
MLM User: ${MLM_USER}
MLM Password: ${MLM_PASS}

MLM Admin URL: https://$(hostname)/admin/config/unilevelmlm
MLM Dashboard: https://$(hostname)/mlm/dashboard

MLM Features: Enabled and configured
Cron Jobs: Configured for automated processing
Sample Data: Created for testing

MLM Branch: Active
Version: 2.0.0
Drupal Version: 11.1.x
EOF
    
    print_success "MLM setup information saved to /root/mlm-setup-info.txt"
}

# Main execution
main() {
    print_header "MLM System Setup for UMD Drupal Project"
    
    check_root
    check_prerequisites
    setup_mlm_database
    install_mlm_dependencies
    enable_mlm_modules
    configure_mlm_module
    setup_mlm_permissions
    setup_mlm_content_types
    setup_mlm_views
    setup_mlm_theme
    create_sample_data
    configure_mlm_settings
    setup_mlm_cron
    display_final_info
    
    print_success "MLM system setup completed successfully!"
}

# Run main function
main "$@"
