# MLM Branch - Multi-Level Marketing System

This branch contains the complete Multi-Level Marketing (MLM) system implementation for Drupal 11.1.x with advanced features and modern UI components.

## 🎯 MLM Branch Overview

### Purpose
The MLM branch focuses specifically on the Multi-Level Marketing functionality, including:
- **Unilevel MLM compensation plan**
- **Advanced genealogy tree visualization**
- **Commission calculation engine**
- **User management and hierarchy**
- **Payment processing integration**
- **Modern dashboard and reporting**

### Branch Structure
```
mlm/                                # MLM Branch
├── docs/
│   ├── MLM_BRANCH_README.md       # This file
│   ├── MLM_FEATURES.md            # Detailed feature documentation
│   ├── MLM_API_DOCUMENTATION.md   # API reference
│   └── MLM_DEPLOYMENT_GUIDE.md    # MLM-specific deployment
├── umd/                           # Core UMD Module
│   ├── unilevelmlm.module         # Main MLM module
│   ├── src/                       # PHP classes and controllers
│   ├── templates/                 # Twig templates
│   ├── css/                       # MLM-specific styles
│   ├── js/                        # JavaScript functionality
│   └── config/                    # Configuration files
├── mlm-drupal/                    # Extended MLM Components
│   ├── src/                       # Additional PHP classes
│   ├── templates/                 # Extended templates
│   ├── css/                       # Enhanced styling
│   ├── js/                        # Advanced JavaScript
│   └── config/                    # MLM configuration
└── scripts/
    ├── mlm_setup.sh              # MLM-specific setup
    └── mlm_data_migration.sh     # Data migration tools
```

## 🚀 MLM Features

### Core MLM Functionality
- **✅ Unilevel Compensation Plan**
  - Multi-level commission structure
  - Configurable commission rates per level
  - Real-time commission calculations
  - Bonus and incentive systems

- **✅ User Hierarchy Management**
  - Sponsor-downline relationships
  - Automatic placement algorithms
  - User role and permission management
  - Profile and KYC verification

- **✅ Genealogy Tree Visualization**
  - Interactive tree display
  - Real-time updates
  - Mobile-responsive design
  - Search and filter capabilities

- **✅ Commission Engine**
  - Automated commission calculations
  - Multiple commission types
  - Payout scheduling
  - Tax and fee deductions

### Advanced Features
- **✅ Dashboard and Analytics**
  - Real-time performance metrics
  - Earnings and payout reports
  - Team performance analytics
  - Goal tracking and achievements

- **✅ Payment Integration**
  - Multiple payment gateways
  - Automated payouts
  - E-wallet functionality
  - Transaction history

- **✅ Modern UI/UX**
  - Responsive design
  - Dark theme support
  - Mobile-first approach
  - Accessibility compliance

## 🛠️ Technical Implementation

### Module Architecture
```
unilevelmlm/
├── unilevelmlm.module             # Main module file
├── unilevelmlm.info.yml           # Module information
├── unilevelmlm.routing.yml        # Route definitions
├── unilevelmlm.links.menu.yml     # Menu links
├── unilevelmlm.libraries.yml      # CSS/JS libraries
├── src/
│   ├── Controller/                # Page controllers
│   ├── Form/                      # Form classes
│   ├── Plugin/                    # Plugin implementations
│   └── Service/                   # Business logic services
├── templates/                     # Twig templates
├── css/                          # Stylesheets
├── js/                           # JavaScript files
└── config/
    ├── install/                  # Default configuration
    └── schema/                   # Configuration schema
```

### Key Components

#### 1. Commission Calculation Engine
- **File**: `src/Service/CommissionCalculator.php`
- **Purpose**: Handles all commission calculations
- **Features**: Multi-level calculations, bonus systems, real-time updates

#### 2. Genealogy Tree Manager
- **File**: `src/Service/GenealogyManager.php`
- **Purpose**: Manages user hierarchy and tree operations
- **Features**: Tree building, placement algorithms, relationship tracking

#### 3. Dashboard Controller
- **File**: `src/Controller/DashboardController.php`
- **Purpose**: Main dashboard functionality
- **Features**: Analytics, reports, user interface

#### 4. Payment Processing
- **File**: `src/Service/PaymentProcessor.php`
- **Purpose**: Handles payments and payouts
- **Features**: Gateway integration, automated processing, transaction logging

## 📊 MLM Configuration

### Commission Structure
```yaml
# config/install/unilevelmlm.settings.yml
commission_levels: 10
commission_rates:
  level_1: 10.00
  level_2: 5.00
  level_3: 3.00
  level_4: 2.00
  level_5: 1.00
  level_6: 1.00
  level_7: 0.50
  level_8: 0.50
  level_9: 0.25
  level_10: 0.25

bonus_systems:
  referral_bonus: 50.00
  leadership_bonus: 100.00
  achievement_bonus: 200.00

payout_settings:
  minimum_payout: 50.00
  payout_frequency: weekly
  processing_fee: 2.50
```

### User Roles and Permissions
- **MLM Administrator**: Full system access
- **MLM Manager**: User management and reports
- **MLM Member**: Standard member access
- **MLM Viewer**: Read-only access

## 🎨 UI/UX Features

### Modern Dashboard
- **Real-time statistics** with live updates
- **Interactive charts** using Chart.js
- **Responsive grid layout** for all devices
- **Dark/light theme** toggle

### Genealogy Tree
- **Interactive tree visualization** with zoom and pan
- **User profile cards** with quick actions
- **Search and filter** functionality
- **Mobile-optimized** touch interface

### Payment Interface
- **Secure payment forms** with validation
- **Transaction history** with filtering
- **Payout requests** with status tracking
- **E-wallet management** interface

## 🔧 Development Setup

### MLM-Specific Setup
```bash
# Switch to MLM branch
git checkout mlm

# Run MLM setup script
chmod +x scripts/mlm_setup.sh
./scripts/mlm_setup.sh

# Install MLM dependencies
cd umd
composer install

# Enable MLM module
vendor/bin/drush en unilevelmlm -y

# Import MLM configuration
vendor/bin/drush cim -y
```

### Development Tools
```bash
# Run MLM tests
vendor/bin/phpunit modules/custom/unilevelmlm/tests/

# Generate MLM documentation
vendor/bin/drush generate:documentation unilevelmlm

# Clear MLM cache
vendor/bin/drush cr
```

## 📈 Performance Optimizations

### Database Optimizations
- **Indexed genealogy tables** for fast tree queries
- **Cached commission calculations** for performance
- **Optimized user hierarchy** storage
- **Efficient payout processing** algorithms

### Frontend Optimizations
- **Lazy loading** for genealogy trees
- **AJAX-powered** dashboard updates
- **Compressed assets** for faster loading
- **CDN integration** for static resources

## 🔒 Security Features

### Data Protection
- **Encrypted sensitive data** (SSN, bank details)
- **Secure payment processing** with PCI compliance
- **User authentication** with 2FA support
- **Audit logging** for all transactions

### Access Control
- **Role-based permissions** for different user types
- **IP whitelisting** for admin access
- **Session management** with timeout controls
- **CSRF protection** on all forms

## 📋 Testing Strategy

### Automated Testing
- **Unit tests** for commission calculations
- **Integration tests** for payment processing
- **Functional tests** for user workflows
- **Performance tests** for large genealogy trees

### Manual Testing
- **User acceptance testing** for MLM workflows
- **Security testing** for payment systems
- **Performance testing** under load
- **Cross-browser testing** for compatibility

## 🚀 Deployment Guide

### Production Deployment
1. **Environment Setup**
   ```bash
   # Deploy to production server
   git clone -b mlm https://github.com/Ebret/1.git
   cd 1
   ./scripts/ubuntu_24_04_setup.sh
   ```

2. **MLM Configuration**
   ```bash
   # Configure MLM settings
   ./scripts/mlm_setup.sh

   # Import production configuration
   vendor/bin/drush cim -y
   ```

3. **Security Hardening**
   ```bash
   # Set proper permissions
   chmod 755 umd/
   chmod 644 umd/config/install/*.yml

   # Configure SSL for payments
   certbot --apache -d yourdomain.com
   ```

## 📞 MLM Support

### Documentation
- **Feature Documentation**: `docs/MLM_FEATURES.md`
- **API Reference**: `docs/MLM_API_DOCUMENTATION.md`
- **Deployment Guide**: `docs/MLM_DEPLOYMENT_GUIDE.md`

### Community
- **GitHub Issues**: Report MLM-specific bugs
- **Discussions**: MLM feature requests and ideas
- **Wiki**: Community-maintained MLM documentation

### Professional Support
- **MLM Consulting**: Custom MLM plan implementation
- **Training**: MLM system administration training
- **Maintenance**: Ongoing MLM system support

## 🎉 **Deployment Status - PRODUCTION READY**

### **✅ Successfully Deployed on Contabo VPS**
- **Deployment Date**: May 27, 2025
- **Server**: Contabo VPS with Ubuntu 24.04 LTS
- **Status**: ✅ **PRODUCTION READY**
- **MLM System**: ✅ **FULLY OPERATIONAL**

### **🚀 Live System Information**
- **LAMP Stack**: Apache 2.4, MySQL 8.0, PHP 8.3 ✅
- **Drupal Version**: 11.1.7 ✅
- **MLM Database**: mlm_system ✅
- **MLM Users**: Admin, Manager, Member, Leader roles created ✅
- **Cron Jobs**: Automated processing configured ✅

### **🔐 Access Credentials**
- **MLM Admin**: mlm_admin / admin123
- **MLM Member**: mlm_member1 / member123
- **MLM Leader**: mlm_leader1 / leader123
- **Database**: mlm_system (user: mlm_admin)

### **📊 Installed Components**
- ✅ **Core MLM Module**: unilevelmlm
- ✅ **Data Export**: views_data_export
- ✅ **Charts**: drupal/charts
- ✅ **CSV Export**: csv_serialization
- ✅ **Commission Engine**: Configured (10 levels)
- ✅ **User Roles**: MLM hierarchy implemented
- ✅ **Automated Processing**: Cron jobs active

---

**MLM Branch Status**: ✅ **PRODUCTION DEPLOYED**
**Latest Version**: v2.0.0
**Drupal Compatibility**: 11.1.x ✅ **TESTED**
**PHP Requirement**: 8.3+ ✅ **VERIFIED**
**Deployment**: ✅ **CONTABO VPS LIVE**
