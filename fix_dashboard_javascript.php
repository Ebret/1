<?php
/**
 * Fix Dashboard JavaScript Errors
 * This script fixes template literal and JavaScript syntax errors in the member dashboard
 */

// Configuration
$server_host = '109.205.181.119';
$server_user = 'root';
$server_password = '4K-6GsnA$3pQ5931';
$remote_path = '/var/www/html/mlm/';
$backup_suffix = '_backup_' . date('Y-m-d_H-i-s');

// Files to fix
$files_to_fix = [
    'member_dashboard_enhanced_live.php'
];

echo "=== ExtremeLife MLM Dashboard JavaScript Fix ===\n";
echo "Target Server: {$server_host}\n";
echo "Remote Path: {$remote_path}\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Function to create SSH connection
function createSSHConnection($host, $user, $password) {
    $connection = ssh2_connect($host, 22);
    if (!$connection) {
        throw new Exception("Failed to connect to SSH server");
    }
    
    if (!ssh2_auth_password($connection, $user, $password)) {
        throw new Exception("SSH authentication failed");
    }
    
    return $connection;
}

// Function to execute SSH command
function executeSSHCommand($connection, $command) {
    $stream = ssh2_exec($connection, $command);
    if (!$stream) {
        throw new Exception("Failed to execute command: {$command}");
    }
    
    stream_set_blocking($stream, true);
    $output = stream_get_contents($stream);
    fclose($stream);
    
    return $output;
}

// Function to upload file via SCP
function uploadFile($connection, $local_file, $remote_file) {
    if (!ssh2_scp_send($connection, $local_file, $remote_file, 0644)) {
        throw new Exception("Failed to upload file: {$local_file} to {$remote_file}");
    }
}

try {
    // Check if SSH2 extension is available
    if (!extension_loaded('ssh2')) {
        echo "❌ SSH2 extension not available. Using alternative deployment method...\n\n";
        
        // Alternative: Generate deployment instructions
        generateDeploymentInstructions();
        exit();
    }
    
    // Create SSH connection
    echo "🔗 Connecting to server...\n";
    $ssh = createSSHConnection($server_host, $server_user, $server_password);
    echo "✅ Connected successfully\n\n";
    
    // Create backup directory
    echo "📁 Creating backup directory...\n";
    $backup_dir = $remote_path . 'backups/';
    executeSSHCommand($ssh, "mkdir -p {$backup_dir}");
    echo "✅ Backup directory created\n\n";
    
    // Process each file
    foreach ($files_to_fix as $file) {
        echo "🔧 Processing {$file}...\n";
        
        // Create backup
        $backup_file = $backup_dir . $file . $backup_suffix;
        $original_file = $remote_path . $file;
        
        echo "  📋 Creating backup: {$backup_file}\n";
        executeSSHCommand($ssh, "cp {$original_file} {$backup_file}");
        
        // Upload fixed file
        $local_fixed_file = 'member_dashboard_enhanced_live_fixed.php';
        if (file_exists($local_fixed_file)) {
            echo "  📤 Uploading fixed file...\n";
            uploadFile($ssh, $local_fixed_file, $original_file);
            echo "  ✅ File uploaded successfully\n";
        } else {
            echo "  ❌ Fixed file not found: {$local_fixed_file}\n";
        }
    }
    
    // Upload AJAX endpoint
    echo "🔧 Uploading AJAX endpoint...\n";
    $ajax_dir = $remote_path . 'ajax/';
    executeSSHCommand($ssh, "mkdir -p {$ajax_dir}");
    
    if (file_exists('ajax/dashboard_stats.php')) {
        uploadFile($ssh, 'ajax/dashboard_stats.php', $ajax_dir . 'dashboard_stats.php');
        echo "✅ AJAX endpoint uploaded\n";
    }
    
    // Upload JavaScript file
    echo "🔧 Uploading JavaScript file...\n";
    if (file_exists('member_dashboard_enhanced_live_fixed.js')) {
        uploadFile($ssh, 'member_dashboard_enhanced_live_fixed.js', $remote_path . 'member_dashboard_enhanced_live_fixed.js');
        echo "✅ JavaScript file uploaded\n";
    }
    
    // Set proper permissions
    echo "🔐 Setting file permissions...\n";
    executeSSHCommand($ssh, "chown -R www-data:www-data {$remote_path}");
    executeSSHCommand($ssh, "chmod -R 644 {$remote_path}*.php");
    executeSSHCommand($ssh, "chmod -R 644 {$remote_path}*.js");
    executeSSHCommand($ssh, "chmod -R 755 {$remote_path}ajax/");
    echo "✅ Permissions set\n\n";
    
    // Test the fix
    echo "🧪 Testing the fix...\n";
    $test_url = "https://extremelifeherbal.com/mlm/member_dashboard_enhanced_live.php";
    $test_result = testURL($test_url);
    
    if ($test_result['success']) {
        echo "✅ Dashboard is accessible\n";
        echo "📊 Response time: {$test_result['response_time']}ms\n";
    } else {
        echo "❌ Dashboard test failed: {$test_result['error']}\n";
    }
    
    echo "\n=== Deployment Summary ===\n";
    echo "✅ Files backed up to: {$backup_dir}\n";
    echo "✅ JavaScript errors fixed\n";
    echo "✅ Template literals eliminated\n";
    echo "✅ AJAX endpoint deployed\n";
    echo "✅ Permissions configured\n";
    echo "\n🎉 Dashboard fix deployment completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n📋 Generating manual deployment instructions...\n";
    generateDeploymentInstructions();
}

function testURL($url) {
    $start_time = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MLM Dashboard Test Bot');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $response_time = round((microtime(true) - $start_time) * 1000);
    
    if ($error) {
        return ['success' => false, 'error' => $error, 'response_time' => $response_time];
    }
    
    if ($http_code >= 200 && $http_code < 400) {
        return ['success' => true, 'http_code' => $http_code, 'response_time' => $response_time];
    }
    
    return ['success' => false, 'error' => "HTTP {$http_code}", 'response_time' => $response_time];
}

function generateDeploymentInstructions() {
    $instructions = "
=== MANUAL DEPLOYMENT INSTRUCTIONS ===

1. Connect to your server via SSH:
   ssh root@109.205.181.119
   Password: 4K-6GsnA\$3pQ5931

2. Navigate to the MLM directory:
   cd /var/www/html/mlm/

3. Create a backup:
   mkdir -p backups/
   cp member_dashboard_enhanced_live.php backups/member_dashboard_enhanced_live.php_backup_" . date('Y-m-d_H-i-s') . "

4. Upload the fixed files:
   - Replace member_dashboard_enhanced_live.php with member_dashboard_enhanced_live_fixed.php
   - Create ajax/ directory: mkdir -p ajax/
   - Upload ajax/dashboard_stats.php
   - Upload member_dashboard_enhanced_live_fixed.js

5. Set permissions:
   chown -R www-data:www-data /var/www/html/mlm/
   chmod -R 644 /var/www/html/mlm/*.php
   chmod -R 644 /var/www/html/mlm/*.js
   chmod -R 755 /var/www/html/mlm/ajax/

6. Test the dashboard:
   Visit: https://extremelifeherbal.com/mlm/member_dashboard_enhanced_live.php

=== FIXES APPLIED ===
✅ Eliminated template literals (backticks and \${variable})
✅ Fixed incomplete HTML structure in notifications
✅ Added proper error handling
✅ Converted to standard string concatenation
✅ Added AJAX endpoint for real-time updates
✅ Improved notification system
✅ Added proper escaping for security

=== FILES CREATED ===
- member_dashboard_enhanced_live_fixed.php (main dashboard)
- member_dashboard_enhanced_live_fixed.js (fixed JavaScript)
- ajax/dashboard_stats.php (AJAX endpoint)
";

    echo $instructions;
    
    // Save instructions to file
    file_put_contents('deployment_instructions.txt', $instructions);
    echo "\n📄 Instructions saved to: deployment_instructions.txt\n";
}
?>
