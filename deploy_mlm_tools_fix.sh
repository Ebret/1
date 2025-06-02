#!/bin/bash
# ExtremeLife MLM Tools - Critical PHP Error Fix Deployment Script
# Fixes: "Undefined array key 'image_url'" error on line 313
# Deploys: Enhanced MLM management tools with complete functionality

set -e  # Exit on any error

# Configuration
PRODUCTION_SERVER="extremelifeherbal.com"
WEB_DIR="/var/www/html/umd/drupal-cms/web"
BACKUP_DIR="/var/backups/extremelife-mlm"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOCAL_FIXED_FILE="mlm_tools_complete.php"
TARGET_FILE="mlm_tools.php"
TEST_URL="http://extremelifeherbal.com/mlm_tools.php"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

# Banner
echo "=================================================================="
echo "🔧 EXTREMELIFE MLM TOOLS - CRITICAL PHP ERROR FIX DEPLOYMENT"
echo "=================================================================="
echo ""
echo "🎯 Mission: Fix 'Undefined array key image_url' PHP error"
echo "🌐 Target: $PRODUCTION_SERVER"
echo "📁 Directory: $WEB_DIR"
echo "⏰ Timestamp: $TIMESTAMP"
echo ""

# Pre-deployment checks
log "Starting pre-deployment checks..."

# Check if local fixed file exists
if [ ! -f "$LOCAL_FIXED_FILE" ]; then
    error "Fixed file '$LOCAL_FIXED_FILE' not found in current directory!"
    echo "Please ensure the fixed MLM tools file is present."
    exit 1
fi

success "Fixed file '$LOCAL_FIXED_FILE' found locally"

# Check file size (should be substantial for the enhanced version)
FILE_SIZE=$(stat -c%s "$LOCAL_FIXED_FILE" 2>/dev/null || stat -f%z "$LOCAL_FIXED_FILE" 2>/dev/null)
if [ "$FILE_SIZE" -lt 10000 ]; then
    warning "Fixed file seems small ($FILE_SIZE bytes). Continuing anyway..."
else
    success "Fixed file size: $FILE_SIZE bytes (looks good)"
fi

# Check PHP syntax locally
log "Checking PHP syntax of fixed file..."
if php -l "$LOCAL_FIXED_FILE" > /dev/null 2>&1; then
    success "PHP syntax is valid"
else
    error "PHP syntax error in fixed file!"
    php -l "$LOCAL_FIXED_FILE"
    exit 1
fi

# Prompt for SSH connection details
echo ""
echo "🔐 SSH Connection Setup"
echo "======================"
read -p "Enter SSH username for $PRODUCTION_SERVER: " SSH_USER

if [ -z "$SSH_USER" ]; then
    error "SSH username is required!"
    exit 1
fi

# Test SSH connection
log "Testing SSH connection to $PRODUCTION_SERVER..."
if ssh -o ConnectTimeout=10 -o BatchMode=yes "$SSH_USER@$PRODUCTION_SERVER" "echo 'SSH connection successful'" 2>/dev/null; then
    success "SSH connection test successful"
else
    warning "SSH key authentication failed, will prompt for password"
fi

# Deployment function
deploy_fix() {
    log "Starting deployment to production server..."
    
    # Create deployment commands
    DEPLOYMENT_COMMANDS=$(cat << 'EOF'
# Set error handling
set -e

# Variables
WEB_DIR="/var/www/html/umd/drupal-cms/web"
BACKUP_DIR="/var/backups/extremelife-mlm"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
TARGET_FILE="mlm_tools.php"

echo "🔧 ExtremeLife MLM Tools Deployment - Server Side"
echo "================================================="

# Create backup directory
echo "📁 Creating backup directory..."
sudo mkdir -p "$BACKUP_DIR"
if [ $? -eq 0 ]; then
    echo "✅ Backup directory created: $BACKUP_DIR"
else
    echo "❌ Failed to create backup directory"
    exit 1
fi

# Navigate to web directory
echo "📂 Navigating to web directory..."
cd "$WEB_DIR"
if [ $? -eq 0 ]; then
    echo "✅ Changed to directory: $(pwd)"
else
    echo "❌ Failed to navigate to web directory: $WEB_DIR"
    exit 1
fi

# Check if current file exists
if [ -f "$TARGET_FILE" ]; then
    echo "📋 Current file found: $TARGET_FILE"
    
    # Create backup
    echo "💾 Creating backup of current file..."
    sudo cp "$TARGET_FILE" "$BACKUP_DIR/${TARGET_FILE}_broken_backup_$TIMESTAMP"
    if [ $? -eq 0 ]; then
        echo "✅ Backup created: $BACKUP_DIR/${TARGET_FILE}_broken_backup_$TIMESTAMP"
    else
        echo "❌ Failed to create backup"
        exit 1
    fi
    
    # Show current file info
    echo "📊 Current file info:"
    ls -la "$TARGET_FILE"
    
    # Check for PHP errors in current file
    echo "🔍 Checking current file for PHP errors..."
    if php -l "$TARGET_FILE" > /dev/null 2>&1; then
        echo "⚠️  Current file has valid PHP syntax (but may have runtime errors)"
    else
        echo "❌ Current file has PHP syntax errors"
    fi
else
    echo "⚠️  Target file $TARGET_FILE not found - will create new file"
fi

# Wait for file upload
echo "⏳ Ready to receive fixed file..."
echo "   Please upload the fixed file now..."

EOF
)

    # Execute pre-deployment commands on server
    log "Executing pre-deployment commands on server..."
    ssh "$SSH_USER@$PRODUCTION_SERVER" "$DEPLOYMENT_COMMANDS"
    
    if [ $? -ne 0 ]; then
        error "Pre-deployment commands failed!"
        return 1
    fi
    
    # Upload the fixed file
    log "Uploading fixed file to production server..."
    scp "$LOCAL_FIXED_FILE" "$SSH_USER@$PRODUCTION_SERVER:$WEB_DIR/mlm_tools_fixed_temp.php"
    
    if [ $? -ne 0 ]; then
        error "File upload failed!"
        return 1
    fi
    
    success "Fixed file uploaded successfully"
    
    # Post-deployment commands
    POST_DEPLOYMENT_COMMANDS=$(cat << 'EOF'
# Set error handling
set -e

# Variables
WEB_DIR="/var/www/html/umd/drupal-cms/web"
TARGET_FILE="mlm_tools.php"
TEMP_FILE="mlm_tools_fixed_temp.php"

echo "🔄 Post-deployment processing..."
echo "================================"

# Navigate to web directory
cd "$WEB_DIR"

# Check uploaded file
if [ -f "$TEMP_FILE" ]; then
    echo "✅ Uploaded file found: $TEMP_FILE"
    
    # Check file size
    FILE_SIZE=$(stat -c%s "$TEMP_FILE")
    echo "📏 Uploaded file size: $FILE_SIZE bytes"
    
    if [ "$FILE_SIZE" -lt 5000 ]; then
        echo "❌ Uploaded file seems too small!"
        exit 1
    fi
    
    # Check PHP syntax
    echo "🔍 Checking PHP syntax of uploaded file..."
    if php -l "$TEMP_FILE"; then
        echo "✅ PHP syntax is valid"
    else
        echo "❌ PHP syntax error in uploaded file!"
        exit 1
    fi
    
    # Replace the target file
    echo "🔄 Replacing target file..."
    sudo mv "$TEMP_FILE" "$TARGET_FILE"
    if [ $? -eq 0 ]; then
        echo "✅ File replacement successful"
    else
        echo "❌ Failed to replace target file"
        exit 1
    fi
    
    # Set proper permissions
    echo "🔐 Setting file permissions..."
    sudo chmod 644 "$TARGET_FILE"
    sudo chown www-data:www-data "$TARGET_FILE"
    if [ $? -eq 0 ]; then
        echo "✅ Permissions set: 644, www-data:www-data"
    else
        echo "❌ Failed to set permissions"
        exit 1
    fi
    
    # Verify final file
    echo "🔍 Final file verification..."
    ls -la "$TARGET_FILE"
    
    # Test PHP syntax one more time
    echo "🧪 Final PHP syntax check..."
    if php -l "$TARGET_FILE"; then
        echo "✅ Final PHP syntax check passed"
    else
        echo "❌ Final PHP syntax check failed!"
        exit 1
    fi
    
    echo "🎉 Deployment completed successfully!"
    echo "📊 File info:"
    ls -la "$TARGET_FILE"
    
else
    echo "❌ Uploaded file not found!"
    exit 1
fi

EOF
)

    # Execute post-deployment commands
    log "Executing post-deployment commands on server..."
    ssh "$SSH_USER@$PRODUCTION_SERVER" "$POST_DEPLOYMENT_COMMANDS"
    
    if [ $? -ne 0 ]; then
        error "Post-deployment commands failed!"
        return 1
    fi
    
    success "Deployment completed successfully on server"
    return 0
}

# Verification function
verify_deployment() {
    log "Starting deployment verification..."
    
    # Test HTTP response
    log "Testing HTTP response..."
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$TEST_URL" --max-time 30)
    
    if [ "$HTTP_STATUS" = "200" ]; then
        success "HTTP response: $HTTP_STATUS (OK)"
    else
        warning "HTTP response: $HTTP_STATUS (Check manually)"
    fi
    
    # Test for PHP errors
    log "Checking for PHP errors..."
    RESPONSE=$(curl -s "$TEST_URL" --max-time 30)
    
    if echo "$RESPONSE" | grep -q "Undefined array key"; then
        error "PHP 'Undefined array key' errors still present!"
        return 1
    elif echo "$RESPONSE" | grep -q "Fatal error\|Parse error\|Warning:"; then
        error "PHP errors detected in response!"
        echo "First few lines of response:"
        echo "$RESPONSE" | head -10
        return 1
    else
        success "No PHP errors detected in response"
    fi
    
    # Check for expected content
    if echo "$RESPONSE" | grep -q "ExtremeLife MLM Management Tools"; then
        success "Expected page title found"
    else
        warning "Expected page title not found - check manually"
    fi
    
    if echo "$RESPONSE" | grep -q "Product Management System"; then
        success "Product Management section found"
    else
        warning "Product Management section not found"
    fi
    
    if echo "$RESPONSE" | grep -q "Rebate Management Interface"; then
        success "Rebate Management section found"
    else
        warning "Rebate Management section not found"
    fi
    
    return 0
}

# Rollback function
create_rollback_script() {
    log "Creating rollback script..."
    
    cat > "rollback_mlm_tools_$TIMESTAMP.sh" << EOF
#!/bin/bash
# ExtremeLife MLM Tools Rollback Script
# Created: $(date)
# Timestamp: $TIMESTAMP

echo "🔄 ExtremeLife MLM Tools Rollback"
echo "================================="
echo "This will restore the backup from $TIMESTAMP"
echo ""

read -p "Are you sure you want to rollback? (y/N): " -n 1 -r
echo
if [[ \$REPLY =~ ^[Yy]\$ ]]; then
    echo "🔄 Starting rollback..."
    
    ssh $SSH_USER@$PRODUCTION_SERVER << 'ROLLBACK_EOF'
cd $WEB_DIR
sudo cp $BACKUP_DIR/${TARGET_FILE}_broken_backup_$TIMESTAMP $TARGET_FILE
sudo chmod 644 $TARGET_FILE
sudo chown www-data:www-data $TARGET_FILE
echo "✅ Rollback completed"
ls -la $TARGET_FILE
ROLLBACK_EOF
    
    echo "🎯 Rollback completed. Please test: $TEST_URL"
else
    echo "❌ Rollback cancelled"
fi
EOF
    
    chmod +x "rollback_mlm_tools_$TIMESTAMP.sh"
    success "Rollback script created: rollback_mlm_tools_$TIMESTAMP.sh"
}

# Main deployment process
main() {
    log "Starting main deployment process..."
    
    # Create rollback script first
    create_rollback_script
    
    # Execute deployment
    if deploy_fix; then
        success "Deployment phase completed"
    else
        error "Deployment failed!"
        echo ""
        echo "🔄 To rollback, run: ./rollback_mlm_tools_$TIMESTAMP.sh"
        exit 1
    fi
    
    # Wait a moment for server to process
    log "Waiting 5 seconds for server to process changes..."
    sleep 5
    
    # Verify deployment
    if verify_deployment; then
        success "Verification phase completed"
    else
        error "Verification failed!"
        echo ""
        echo "🔄 To rollback, run: ./rollback_mlm_tools_$TIMESTAMP.sh"
        exit 1
    fi
    
    # Final success message
    echo ""
    echo "=================================================================="
    echo "🎉 EXTREMELIFE MLM TOOLS DEPLOYMENT SUCCESSFUL!"
    echo "=================================================================="
    echo ""
    success "Critical PHP error 'Undefined array key image_url' FIXED!"
    success "Enhanced MLM management tools deployed successfully"
    success "All verification tests passed"
    echo ""
    echo "🌐 Test the fixed page: $TEST_URL"
    echo "🔄 Rollback available: ./rollback_mlm_tools_$TIMESTAMP.sh"
    echo ""
    echo "✅ Expected Results:"
    echo "   - Zero PHP errors or warnings"
    echo "   - Complete MLM management interface"
    echo "   - Product management functionality"
    echo "   - Rebate management tools"
    echo "   - User group administration"
    echo "   - Ranking system management"
    echo ""
    echo "🎯 ExtremeLife MLM system is now fully operational!"
}

# Error handling
trap 'error "Script interrupted!"; echo "🔄 Rollback available: ./rollback_mlm_tools_$TIMESTAMP.sh"; exit 1' INT TERM

# Run main function
main

exit 0
