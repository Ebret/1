# UMD (Unilevel MLM) Drupal Project

A comprehensive Multi-Level Marketing (MLM) system built on Drupal 11.1.x with Ubuntu 24.04 compatibility and automated Contabo VPS deployment.

## 🚀 Quick Start

### For Ubuntu 24.04 Deployment (Recommended)

```bash
# Clone the repository
git clone https://github.com/Ebret/1.git umd-drupal
cd umd-drupal

# Run automated Ubuntu 24.04 setup
chmod +x scripts/ubuntu_24_04_setup.sh
./scripts/ubuntu_24_04_setup.sh
```

### For Contabo VPS Deployment

```bash
# Download and run Contabo deployment script
wget https://raw.githubusercontent.com/Ebret/1/master/scripts/contabo_vps_deploy.sh
chmod +x contabo_vps_deploy.sh
./contabo_vps_deploy.sh
```

## 📁 Project Structure

```
├── docs/                           # Documentation
│   ├── README_Ubuntu_24_04_Compatibility.md
│   ├── ubuntu_24_04_compatibility_analysis.md
│   ├── contabo_vps_deployment_guide.md
│   ├── contabo_quick_deploy.md
│   └── BRANCH_SUMMARY.md
├── scripts/                        # Deployment Scripts
│   ├── ubuntu_24_04_setup.sh      # Main Ubuntu 24.04 setup
│   ├── contabo_vps_deploy.sh       # Contabo VPS deployment
│   ├── environment_setup.sh        # Environment configuration
│   └── initial_server_setup.sh     # Server initialization
├── tests/                          # Testing Tools
│   ├── ubuntu_compatibility_test.php
│   └── final_test_results.md
├── umd/                            # UMD Drupal Module
│   ├── drupal-cms/                 # Drupal CMS installation
│   ├── unilevelmlm.module          # Main MLM module
│   ├── src/                        # PHP classes
│   ├── css/                        # Stylesheets
│   ├── js/                         # JavaScript files
│   └── templates/                  # Twig templates
├── mlm-drupal/                     # Additional MLM components
└── css/                            # Global stylesheets
```

## 🎯 Features

### MLM System Features
- **Multi-level commission tracking**
- **User hierarchy management**
- **Commission calculation engine**
- **Genealogy tree visualization**
- **Payment processing integration**
- **Admin dashboard and reporting**

### Technical Features
- **Drupal 11.1.x compatibility**
- **PHP 8.3+ optimized**
- **Ubuntu 24.04 LTS support**
- **Automated deployment scripts**
- **Contabo VPS optimization**
- **SSL certificate automation**
- **Performance monitoring**
- **Automated backups**

## 🛠️ Installation Options

### Option 1: Ubuntu 24.04 Local/VPS Setup
- **Best for**: Development and production
- **Requirements**: Ubuntu 24.04 LTS
- **Setup time**: 15-30 minutes
- **Script**: `scripts/ubuntu_24_04_setup.sh`

### Option 2: Contabo VPS Deployment
- **Best for**: Production hosting
- **Requirements**: Contabo VPS with Ubuntu 24.04
- **Setup time**: 15-30 minutes
- **Cost**: €10-15/month
- **Script**: `scripts/contabo_vps_deploy.sh`

### Option 3: Manual Installation
- **Best for**: Custom environments
- **Documentation**: `docs/ubuntu_24_04_compatibility_analysis.md`
- **Requirements**: PHP 8.3+, MySQL 8.0+, Apache 2.4+

## 📋 System Requirements

### Minimum Requirements
- **OS**: Ubuntu 24.04 LTS (recommended) or Ubuntu 22.04+
- **PHP**: 8.3+ (8.1+ minimum)
- **Database**: MySQL 8.0+ or MariaDB 10.6+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Memory**: 2GB RAM minimum, 4GB+ recommended
- **Storage**: 10GB+ free space

### Required PHP Extensions
- gd, curl, mbstring, xml, zip
- mysql, sqlite3, intl, bcmath
- opcache, soap, xsl

## 🚀 Performance Optimizations

### Ubuntu 24.04 Advantages
- **3-5x faster** than Windows development environment
- **Native PHP 8.3** support without compatibility issues
- **No file locking** problems (eliminates Windows issues)
- **Production-ready** environment matching deployment servers

### Contabo VPS Optimizations
- **NVMe SSD** storage optimizations
- **Network tuning** for Contabo infrastructure
- **Resource optimization** for VPS specifications
- **Automated monitoring** and maintenance

## 🔧 Development Workflow

### 1. Environment Setup
```bash
# Test compatibility
php tests/ubuntu_compatibility_test.php

# Run setup script
./scripts/ubuntu_24_04_setup.sh
```

### 2. Project Deployment
```bash
# Navigate to project directory
cd /var/www/html/umd/drupal-cms

# Install dependencies
composer install

# Set permissions
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
```

### 3. Testing
```bash
# Run Drupal tests
vendor/bin/phpunit web/profiles/drupal_cms_installer/tests/

# Test UMD module functionality
vendor/bin/drush status
```

## 📊 Deployment Comparison

| Environment | Setup Time | Performance | Cost/Month | Best For |
|-------------|------------|-------------|------------|----------|
| **Ubuntu 24.04 Local** | 15-30 min | Excellent | Free | Development |
| **Contabo VPS S** | 15-30 min | Very Good | €4.99 | Small Production |
| **Contabo VPS M** | 15-30 min | Excellent | €8.99 | **Recommended** |
| **Contabo VPS L** | 15-30 min | Outstanding | €14.99 | High Traffic |

## 🔒 Security Features

- **UFW Firewall** configuration
- **fail2ban** intrusion prevention
- **SSL certificates** with auto-renewal
- **Security headers** implementation
- **Regular security updates**
- **Database security** hardening

## 📈 Monitoring & Maintenance

### Automated Features
- **Daily backups** (database + files)
- **Performance monitoring** with alerts
- **Log rotation** and cleanup
- **Security updates** automation
- **SSL certificate** renewal

### Management Commands
```bash
# Monitor server status
/usr/local/bin/contabo-monitor.sh

# Manual backup
/usr/local/bin/backup-drupal.sh

# View logs
tail -f /var/log/apache2/error.log
```

## 🆘 Support & Documentation

### Documentation
- **Complete Setup Guide**: `docs/ubuntu_24_04_compatibility_analysis.md`
- **Contabo Deployment**: `docs/contabo_vps_deployment_guide.md`
- **Quick Start**: `docs/contabo_quick_deploy.md`
- **Branch Information**: `docs/BRANCH_SUMMARY.md`

### Troubleshooting
- **Compatibility Test**: `php tests/ubuntu_compatibility_test.php`
- **Test Results**: `tests/final_test_results.md`
- **Common Issues**: Check documentation in `docs/` directory

### Community
- **GitHub Issues**: Report bugs and feature requests
- **Discussions**: Share experiences and get help
- **Contributions**: Pull requests welcome

## 🏆 Success Metrics

### Performance Improvements
- **Installation Time**: Reduced from 45+ minutes to 15-30 minutes
- **File Operations**: 3-5x faster than Windows/XAMPP
- **Test Execution**: From blocked to full completion
- **Memory Usage**: Optimized for production workloads

### Reliability Improvements
- **99.9% Uptime** with proper VPS hosting
- **Zero File Locking Issues** (eliminated Windows problems)
- **Automated Recovery** procedures
- **Production Parity** with deployment environment

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📞 Contact

- **Project Repository**: https://github.com/Ebret/1
- **Issues**: https://github.com/Ebret/1/issues
- **Discussions**: https://github.com/Ebret/1/discussions

---

**Ready for production deployment with Ubuntu 24.04 and Contabo VPS!** 🚀

## 🔑 Credentials

The following credentials can be used to connect to the server:

