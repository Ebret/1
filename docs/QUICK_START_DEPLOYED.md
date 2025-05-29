# Quick Start Guide - Deployed MLM System

## 🚀 **Your MLM System is Live!**

**Deployment Date**: May 27, 2025  
**Status**: ✅ **PRODUCTION READY**  
**Server**: Contabo VPS with Ubuntu 24.04 LTS  

## 🔐 **Immediate Access**

### **MLM Admin Access**
- **Username**: `mlm_admin`
- **Password**: `admin123`
- **Email**: `admin@mlm.local`
- **Role**: MLM Administrator (Full Access)

### **Test Users Available**
- **Member**: `mlm_member1` / `member123`
- **Leader**: `mlm_leader1` / `leader123`

## 🎯 **First Steps After Deployment**

### **1. Access Your MLM System**
```bash
# SSH into your Contabo VPS
ssh mlmadmin@YOUR_VPS_IP

# Check system status
sudo systemctl status apache2 mysql

# Access Drupal admin (replace with your domain)
# http://YOUR_DOMAIN/user/login
```

### **2. Verify MLM Components**
```bash
# Check MLM database
mysql -u mlm_admin -p'MLM_secure_2024' mlm_system -e "SHOW TABLES;"

# Check Drupal modules
cd /var/www/html/umd/drupal-cms
sudo ./vendor/bin/drush pm:list | grep -E "(views_data_export|charts|unilevelmlm)"

# Check MLM users
sudo ./vendor/bin/drush user:information mlm_admin
```

### **3. Test MLM Functionality**
```bash
# Test commission calculations
sudo ./vendor/bin/drush mlm:calculate-commissions

# Test payout processing
sudo ./vendor/bin/drush mlm:process-payouts

# Check MLM configuration
sudo ./vendor/bin/drush config:get unilevelmlm.settings
```

## ⚙️ **MLM System Configuration**

### **Commission Structure (Pre-configured)**
```yaml
Commission Levels: 10
Level 1: 10.00%  # Direct referrals
Level 2: 5.00%   # Second level
Level 3: 3.00%   # Third level
Level 4: 2.00%   # Fourth level
Level 5: 1.00%   # Fifth level
Levels 6-10: 0.25-1.00%

Payout Settings:
  Minimum Payout: $50.00
  Processing Fee: $2.50
  Frequency: Weekly
```

### **Automated Processing (Active)**
- **Commission Calculations**: Every hour
- **Payout Processing**: Daily at 2:00 AM
- **Rank Advancement**: Daily at 3:00 AM

## 🛠️ **Customization Guide**

### **1. Modify Commission Rates**
```bash
# Update commission rates
sudo ./vendor/bin/drush config:set unilevelmlm.settings commission_rates.level_1 12.00 -y
sudo ./vendor/bin/drush config:set unilevelmlm.settings commission_rates.level_2 6.00 -y

# Update payout settings
sudo ./vendor/bin/drush config:set unilevelmlm.settings minimum_payout 100.00 -y
sudo ./vendor/bin/drush config:set unilevelmlm.settings processing_fee 5.00 -y
```

### **2. Create Additional MLM Users**
```bash
# Create new MLM member
sudo ./vendor/bin/drush user:create new_member --mail="member@example.com" --password="secure123"
sudo ./vendor/bin/drush user:role:add mlm_member new_member

# Create new MLM leader
sudo ./vendor/bin/drush user:create new_leader --mail="leader@example.com" --password="secure123"
sudo ./vendor/bin/drush user:role:add mlm_leader new_leader
```

### **3. Configure Payment Gateways**
```bash
# Install payment modules (if needed)
cd /var/www/html/umd/drupal-cms
sudo composer require drupal/commerce_paypal
sudo composer require drupal/commerce_stripe

# Enable payment modules
sudo ./vendor/bin/drush en -y commerce_paypal commerce_stripe
```

## 📊 **Monitoring and Maintenance**

### **System Health Checks**
```bash
# Check disk space
df -h

# Check memory usage
free -h

# Check Apache status
sudo systemctl status apache2

# Check MySQL status
sudo systemctl status mysql

# Check MLM cron jobs
sudo crontab -l | grep mlm
```

### **Log Monitoring**
```bash
# Apache error logs
sudo tail -f /var/log/apache2/error.log

# MySQL error logs
sudo tail -f /var/log/mysql/error.log

# Drupal logs (via Drush)
sudo ./vendor/bin/drush watchdog:show --count=20
```

### **Backup Procedures**
```bash
# Database backup
mysqldump -u root -p'UMD_mysql_2024' mlm_system > mlm_backup_$(date +%Y%m%d).sql

# Files backup
tar -czf drupal_files_$(date +%Y%m%d).tar.gz /var/www/html/umd/drupal-cms/web/sites/default/files/

# Full system backup
sudo rsync -av /var/www/html/umd/ /backup/umd_$(date +%Y%m%d)/
```

## 🔒 **Security Recommendations**

### **1. Change Default Passwords**
```bash
# Change MLM admin password
sudo ./vendor/bin/drush user:password mlm_admin "NEW_SECURE_PASSWORD"

# Change database passwords
mysql -u root -p'UMD_mysql_2024' -e "ALTER USER 'mlm_admin'@'localhost' IDENTIFIED BY 'NEW_MLM_PASSWORD';"
```

### **2. Enable Additional Security**
```bash
# Install and configure fail2ban
sudo apt install fail2ban
sudo systemctl enable fail2ban

# Configure UFW firewall
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
```

### **3. SSL Certificate Setup**
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Get SSL certificate (replace with your domain)
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

## 📈 **Performance Optimization**

### **1. Enable Drupal Caching**
```bash
# Enable page caching
sudo ./vendor/bin/drush config:set system.performance cache.page.max_age 3600 -y

# Enable CSS/JS aggregation
sudo ./vendor/bin/drush config:set system.performance css.preprocess 1 -y
sudo ./vendor/bin/drush config:set system.performance js.preprocess 1 -y

# Clear cache
sudo ./vendor/bin/drush cr
```

### **2. Database Optimization**
```bash
# Optimize MySQL tables
mysql -u root -p'UMD_mysql_2024' -e "OPTIMIZE TABLE mlm_system.*;"

# Update MySQL statistics
mysql -u root -p'UMD_mysql_2024' -e "ANALYZE TABLE mlm_system.*;"
```

## 🆘 **Troubleshooting**

### **Common Issues and Solutions**

#### **1. Website Not Loading**
```bash
# Check Apache status
sudo systemctl status apache2

# Restart Apache
sudo systemctl restart apache2

# Check error logs
sudo tail -20 /var/log/apache2/error.log
```

#### **2. Database Connection Issues**
```bash
# Test database connection
mysql -u mlm_admin -p'MLM_secure_2024' mlm_system -e "SELECT 1;"

# Restart MySQL
sudo systemctl restart mysql

# Check MySQL logs
sudo tail -20 /var/log/mysql/error.log
```

#### **3. MLM Functions Not Working**
```bash
# Check MLM module status
sudo ./vendor/bin/drush pm:list | grep unilevelmlm

# Clear Drupal cache
sudo ./vendor/bin/drush cr

# Check MLM configuration
sudo ./vendor/bin/drush config:get unilevelmlm.settings
```

## 📞 **Support Resources**

### **Documentation**
- **MLM Features**: `docs/MLM_FEATURES.md`
- **API Documentation**: `docs/MLM_API_DOCUMENTATION.md`
- **Deployment Report**: `docs/DEPLOYMENT_SUCCESS_REPORT.md`
- **Branch Information**: `docs/MLM_BRANCH_README.md`

### **System Information**
- **Server**: Contabo VPS Ubuntu 24.04 LTS
- **Drupal**: 11.1.7
- **PHP**: 8.3.6
- **MySQL**: 8.0.42
- **Apache**: 2.4.58

### **Emergency Contacts**
- **System Logs**: `/var/log/apache2/`, `/var/log/mysql/`
- **Configuration**: `/var/www/html/umd/drupal-cms/`
- **Database**: `mlm_system` on localhost
- **Cron Jobs**: `/etc/cron.d/mlm-system`

---

**🎉 Your MLM System is Ready for Business!**

**Next Steps**: Configure your domain, set up SSL, and start building your MLM network!
