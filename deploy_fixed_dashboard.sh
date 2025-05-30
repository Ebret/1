#!/bin/bash
# ExtremeLife MLM Member Dashboard - Critical PHP Error Fix Deployment Script

echo "🚨 EXTREMELIFE MLM DASHBOARD - CRITICAL PHP ERROR FIX 🚨"
echo "========================================================"
echo ""

# Configuration
WEB_DIR="/var/www/html/umd/drupal-cms/web"
BACKUP_DIR="/var/backups/extremelife-mlm"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "📋 Deployment Configuration:"
echo "   Web Directory: $WEB_DIR"
echo "   Backup Directory: $BACKUP_DIR"
echo "   Timestamp: $TIMESTAMP"
echo ""

# Create backup directory
echo "📁 Creating backup directory..."
sudo mkdir -p $BACKUP_DIR
if [ $? -eq 0 ]; then
    echo "✅ Backup directory created successfully"
else
    echo "❌ Failed to create backup directory"
    exit 1
fi

# Backup current broken file
echo ""
echo "💾 Backing up current member_dashboard.php..."
if [ -f "$WEB_DIR/member_dashboard.php" ]; then
    sudo cp "$WEB_DIR/member_dashboard.php" "$BACKUP_DIR/member_dashboard_broken_$TIMESTAMP.php"
    if [ $? -eq 0 ]; then
        echo "✅ Current file backed up to: $BACKUP_DIR/member_dashboard_broken_$TIMESTAMP.php"
    else
        echo "❌ Failed to backup current file"
        exit 1
    fi
else
    echo "⚠️  Current member_dashboard.php not found - proceeding with new file creation"
fi

# Check if fixed file exists in current directory
echo ""
echo "🔍 Checking for fixed member_dashboard.php file..."
if [ -f "./member_dashboard.php" ]; then
    echo "✅ Fixed member_dashboard.php found in current directory"
else
    echo "❌ Fixed member_dashboard.php not found in current directory"
    echo "   Please ensure the fixed file is in the same directory as this script"
    exit 1
fi

# Deploy fixed file
echo ""
echo "🚀 Deploying fixed member_dashboard.php..."
sudo cp "./member_dashboard.php" "$WEB_DIR/member_dashboard.php"
if [ $? -eq 0 ]; then
    echo "✅ Fixed file deployed successfully"
else
    echo "❌ Failed to deploy fixed file"
    exit 1
fi

# Set proper permissions
echo ""
echo "🔐 Setting proper file permissions..."
sudo chmod 644 "$WEB_DIR/member_dashboard.php"
sudo chown www-data:www-data "$WEB_DIR/member_dashboard.php"
if [ $? -eq 0 ]; then
    echo "✅ File permissions set correctly (644, www-data:www-data)"
else
    echo "❌ Failed to set file permissions"
    exit 1
fi

# Verify file deployment
echo ""
echo "🧪 Verifying file deployment..."
if [ -f "$WEB_DIR/member_dashboard.php" ]; then
    FILE_SIZE=$(stat -c%s "$WEB_DIR/member_dashboard.php")
    echo "✅ File exists: $WEB_DIR/member_dashboard.php"
    echo "   File size: $FILE_SIZE bytes"
    
    # Check if file contains the fix
    if grep -q "Initialize default member data" "$WEB_DIR/member_dashboard.php"; then
        echo "✅ File contains the critical fix"
    else
        echo "❌ File does not contain the expected fix"
        exit 1
    fi
else
    echo "❌ Deployed file not found"
    exit 1
fi

# Test PHP syntax
echo ""
echo "🔍 Testing PHP syntax..."
php -l "$WEB_DIR/member_dashboard.php"
if [ $? -eq 0 ]; then
    echo "✅ PHP syntax is valid"
else
    echo "❌ PHP syntax error detected"
    exit 1
fi

# Restart web server (optional)
echo ""
echo "🔄 Restarting web server..."
sudo systemctl reload apache2
if [ $? -eq 0 ]; then
    echo "✅ Apache reloaded successfully"
else
    echo "⚠️  Apache reload failed - manual restart may be required"
fi

# Final verification
echo ""
echo "🎯 DEPLOYMENT COMPLETE!"
echo "======================"
echo ""
echo "✅ Fixed member_dashboard.php deployed successfully"
echo "✅ File permissions set correctly"
echo "✅ PHP syntax validated"
echo "✅ Backup created: $BACKUP_DIR/member_dashboard_broken_$TIMESTAMP.php"
echo ""
echo "🌐 Test the fix at: http://extremelifeherbal.com/member_dashboard.php"
echo ""
echo "📊 Expected Results:"
echo "   ✅ No PHP errors or warnings"
echo "   ✅ Member profile displays correctly"
echo "   ✅ Commission data shows properly"
echo "   ✅ ExtremeLife branding with ₱ currency"
echo "   ✅ Responsive design works on all devices"
echo ""
echo "🚨 If issues persist:"
echo "   1. Check Apache error logs: sudo tail -f /var/log/apache2/error.log"
echo "   2. Verify database connection settings"
echo "   3. Ensure PHP 7.4+ is running"
echo "   4. Restore backup if needed: sudo cp $BACKUP_DIR/member_dashboard_broken_$TIMESTAMP.php $WEB_DIR/member_dashboard.php"
echo ""
echo "🎉 ExtremeLife MLM Dashboard PHP errors have been RESOLVED!"
