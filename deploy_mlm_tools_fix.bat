@echo off
REM ExtremeLife MLM Tools - Critical PHP Error Fix Deployment Script (Windows)
REM Fixes: "Undefined array key 'image_url'" error on line 313
REM Deploys: Enhanced MLM management tools with complete functionality

setlocal enabledelayedexpansion

REM Configuration
set PRODUCTION_SERVER=extremelifeherbal.com
set WEB_DIR=/var/www/html/umd/drupal-cms/web
set LOCAL_FIXED_FILE=mlm_tools_complete.php
set TARGET_FILE=mlm_tools.php
set TEST_URL=http://extremelifeherbal.com/mlm_tools.php

REM Get timestamp
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "TIMESTAMP=%dt:~0,4%%dt:~4,2%%dt:~6,2%_%dt:~8,2%%dt:~10,2%%dt:~12,2%"

echo ==================================================================
echo 🔧 EXTREMELIFE MLM TOOLS - CRITICAL PHP ERROR FIX DEPLOYMENT
echo ==================================================================
echo.
echo 🎯 Mission: Fix 'Undefined array key image_url' PHP error
echo 🌐 Target: %PRODUCTION_SERVER%
echo 📁 Directory: %WEB_DIR%
echo ⏰ Timestamp: %TIMESTAMP%
echo.

REM Pre-deployment checks
echo 📋 Starting pre-deployment checks...

REM Check if local fixed file exists
if not exist "%LOCAL_FIXED_FILE%" (
    echo ❌ Fixed file '%LOCAL_FIXED_FILE%' not found in current directory!
    echo Please ensure the fixed MLM tools file is present.
    pause
    exit /b 1
)

echo ✅ Fixed file '%LOCAL_FIXED_FILE%' found locally

REM Check file size
for %%A in ("%LOCAL_FIXED_FILE%") do set FILE_SIZE=%%~zA
if %FILE_SIZE% LSS 10000 (
    echo ⚠️  Fixed file seems small (%FILE_SIZE% bytes). Continuing anyway...
) else (
    echo ✅ Fixed file size: %FILE_SIZE% bytes (looks good)
)

REM Get SSH username
echo.
echo 🔐 SSH Connection Setup
echo ======================
set /p SSH_USER=Enter SSH username for %PRODUCTION_SERVER%: 

if "%SSH_USER%"=="" (
    echo ❌ SSH username is required!
    pause
    exit /b 1
)

echo.
echo 🚀 DEPLOYMENT INSTRUCTIONS
echo =========================
echo.
echo This script will guide you through the deployment process.
echo You'll need to execute commands manually on the server.
echo.
pause

echo 📋 STEP 1: Connect to Production Server
echo =======================================
echo.
echo Execute this command to connect to the server:
echo.
echo ssh %SSH_USER%@%PRODUCTION_SERVER%
echo.
echo Press any key when connected to the server...
pause

echo.
echo 📋 STEP 2: Server-Side Backup and Preparation
echo =============================================
echo.
echo Copy and paste these commands on the server:
echo.
echo # Navigate to web directory
echo cd %WEB_DIR%
echo.
echo # Create backup directory
echo sudo mkdir -p /var/backups/extremelife-mlm
echo.
echo # Create timestamped backup
echo sudo cp %TARGET_FILE% /var/backups/extremelife-mlm/%TARGET_FILE%_broken_backup_%TIMESTAMP%.php
echo.
echo # Verify backup
echo ls -la /var/backups/extremelife-mlm/
echo.
echo # Check current file for errors
echo php -l %TARGET_FILE%
echo.
echo Press any key when backup is complete...
pause

echo.
echo 📋 STEP 3: Upload Fixed File
echo ============================
echo.
echo Open a NEW command prompt/terminal and execute:
echo.
echo scp %LOCAL_FIXED_FILE% %SSH_USER%@%PRODUCTION_SERVER%:%WEB_DIR%/mlm_tools_temp.php
echo.
echo This will upload the fixed file to the server.
echo.
echo Press any key when upload is complete...
pause

echo.
echo 📋 STEP 4: Deploy and Set Permissions
echo =====================================
echo.
echo Back on the server, execute these commands:
echo.
echo # Replace the broken file
echo sudo mv mlm_tools_temp.php %TARGET_FILE%
echo.
echo # Set proper permissions
echo sudo chmod 644 %TARGET_FILE%
echo sudo chown www-data:www-data %TARGET_FILE%
echo.
echo # Verify permissions
echo ls -la %TARGET_FILE%
echo.
echo # Test PHP syntax
echo php -l %TARGET_FILE%
echo.
echo Press any key when deployment is complete...
pause

echo.
echo 📋 STEP 5: Verification
echo =======================
echo.
echo 1. Open your web browser
echo 2. Visit: %TEST_URL%
echo 3. Verify the following:
echo.
echo ✅ Page loads without PHP errors
echo ✅ "ExtremeLife MLM Management Tools" title appears
echo ✅ Statistics dashboard shows data
echo ✅ Product management section visible
echo ✅ Rebate calculator functional
echo ✅ User group table displays
echo ✅ No "Undefined array key" errors
echo.
set /p VERIFICATION=Did the verification pass? (Y/N): 

if /i "%VERIFICATION%"=="Y" (
    goto :success
) else (
    goto :rollback_instructions
)

:success
echo.
echo ==================================================================
echo 🎉 EXTREMELIFE MLM TOOLS DEPLOYMENT SUCCESSFUL!
echo ==================================================================
echo.
echo ✅ Critical PHP error 'Undefined array key image_url' FIXED!
echo ✅ Enhanced MLM management tools deployed successfully
echo ✅ All verification tests passed
echo.
echo 🌐 Live page: %TEST_URL%
echo.
echo ✅ Expected Results:
echo    - Zero PHP errors or warnings
echo    - Complete MLM management interface
echo    - Product management functionality
echo    - Rebate management tools
echo    - User group administration
echo    - Ranking system management
echo.
echo 🎯 ExtremeLife MLM system is now fully operational!
echo.
goto :end

:rollback_instructions
echo.
echo 🔄 ROLLBACK INSTRUCTIONS
echo ========================
echo.
echo If there are issues, execute this on the server:
echo.
echo # Restore backup
echo sudo cp /var/backups/extremelife-mlm/%TARGET_FILE%_broken_backup_%TIMESTAMP%.php %TARGET_FILE%
echo.
echo # Set permissions
echo sudo chmod 644 %TARGET_FILE%
echo sudo chown www-data:www-data %TARGET_FILE%
echo.
echo # Verify rollback
echo ls -la %TARGET_FILE%
echo.

:end
echo.
echo 📋 DEPLOYMENT LOG
echo =================
echo Timestamp: %TIMESTAMP%
echo Server: %PRODUCTION_SERVER%
echo User: %SSH_USER%
echo Fixed File: %LOCAL_FIXED_FILE%
echo Target: %TARGET_FILE%
echo Backup: /var/backups/extremelife-mlm/%TARGET_FILE%_broken_backup_%TIMESTAMP%.php
echo.
echo 📞 For support, refer to PRODUCTION_DEPLOYMENT_GUIDE.md
echo.
pause
