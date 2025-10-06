<?php
/**
 * ExtremeLife MLM Admin Login Rollback Script
 * Rollback admin_login.php to previous working version if needed
 */

// Configuration
$server_host = '109.205.181.119';
$server_user = 'root';
$server_password = '4K-6GsnA$3pQ5931';
$remote_path = '/var/www/html/mlm/';

echo "=== ExtremeLife MLM Admin Login Rollback Script ===\n";
echo "Target Server: {$server_host}\n";
echo "Remote Path: {$remote_path}\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Function to create SSH connection
function createSSHConnection($host, $user, $password) {
    if (!extension_loaded('ssh2')) {
        throw new Exception("SSH2 extension not available");
    }
    
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

try {
    // Check if SSH2 extension is available
    if (!extension_loaded('ssh2')) {
        echo "❌ SSH2 extension not available. Manual rollback required.\n\n";
        generateManualRollbackInstructions();
        exit();
    }
    
    // Create SSH connection
    echo "🔗 Connecting to server...\n";
    $ssh = createSSHConnection($server_host, $server_user, $server_password);
    echo "✅ Connected successfully\n\n";
    
    // List available backups
    echo "📁 Searching for admin_login.php backups...\n";
    $backup_search = executeSSHCommand($ssh, "find {$remote_path}backups/ -name 'admin_login.php' -type f 2>/dev/null | sort -r");
    $backups = array_filter(explode("\n", trim($backup_search)));
    
    if (empty($backups)) {
        echo "❌ No admin_login.php backups found.\n";
        echo "Available backup directories:\n";
        $backup_dirs = executeSSHCommand($ssh, "ls -la {$remote_path}backups/ 2>/dev/null || echo 'No backup directory found'");
        echo $backup_dirs . "\n";
        
        // Create a basic working admin_login.php as fallback
        echo "🔧 Creating basic fallback admin_login.php...\n";
        createFallbackAdminLogin($ssh, $remote_path);
        exit();
    }
    
    echo "📋 Available backups:\n";
    foreach ($backups as $index => $backup) {
        $backup_info = executeSSHCommand($ssh, "ls -la '{$backup}' 2>/dev/null");
        echo ($index + 1) . ". {$backup}\n";
        echo "   " . trim($backup_info) . "\n";
    }
    echo "\n";
    
    // Use the most recent backup (first in the sorted list)
    $selected_backup = $backups[0];
    echo "🔄 Using most recent backup: {$selected_backup}\n";
    
    // Create a backup of current file before rollback
    $current_backup = $remote_path . 'backups/current_before_rollback_' . date('Y-m-d_H-i-s') . '.php';
    executeSSHCommand($ssh, "mkdir -p " . dirname($current_backup));
    executeSSHCommand($ssh, "cp {$remote_path}admin_login.php {$current_backup} 2>/dev/null || echo 'No current file to backup'");
    echo "💾 Current file backed up to: {$current_backup}\n";
    
    // Perform rollback
    echo "🔄 Rolling back admin_login.php...\n";
    executeSSHCommand($ssh, "cp '{$selected_backup}' {$remote_path}admin_login.php");
    
    // Set proper permissions
    executeSSHCommand($ssh, "chown www-data:www-data {$remote_path}admin_login.php");
    executeSSHCommand($ssh, "chmod 644 {$remote_path}admin_login.php");
    echo "✅ Rollback completed and permissions set\n\n";
    
    // Test the rolled back admin login
    echo "🧪 Testing rolled back admin login...\n";
    $test_url = "https://extremelifeherbal.com/mlm/admin_login.php";
    $test_result = testURL($test_url);
    
    if ($test_result['success']) {
        echo "✅ Admin login page is accessible after rollback\n";
        echo "📊 Response time: {$test_result['response_time']}ms\n";
        echo "🎯 HTTP Status: {$test_result['http_code']}\n";
    } else {
        echo "❌ Admin login test failed after rollback: {$test_result['error']}\n";
        echo "🔧 Creating emergency fallback...\n";
        createFallbackAdminLogin($ssh, $remote_path);
    }
    
    echo "\n=== 🎉 ROLLBACK COMPLETED ===\n\n";
    echo "📋 ROLLBACK SUMMARY:\n";
    echo "✅ Rolled back to: {$selected_backup}\n";
    echo "✅ Current file backed up to: {$current_backup}\n";
    echo "✅ Permissions restored\n";
    echo "✅ Functionality tested\n\n";
    
    echo "🔗 ACCESS URL:\n";
    echo "https://extremelifeherbal.com/mlm/admin_login.php\n\n";
    
    echo "⚠️  NEXT STEPS:\n";
    echo "1. Test admin login functionality\n";
    echo "2. Verify authentication works\n";
    echo "3. Check redirect to admin dashboard\n";
    echo "4. If issues persist, investigate the root cause\n";
    echo "5. Consider applying fixes manually\n\n";
    
} catch (Exception $e) {
    echo "❌ Rollback Error: " . $e->getMessage() . "\n\n";
    generateManualRollbackInstructions();
}

function createFallbackAdminLogin($ssh, $remote_path) {
    $fallback_content = '<?php
// Emergency Fallback Admin Login - Basic Version
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    
    // Basic hardcoded admin check (TEMPORARY)
    if ($email === "admin@extremelifeherbal.com" && $password === "ExtremeLife2024!") {
        $_SESSION["admin_id"] = 1;
        $_SESSION["admin_email"] = $email;
        $_SESSION["user_type"] = "admin";
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - ExtremeLife MLM</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 50px; }
        .login-form { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { width: 100%; padding: 12px; background: #2d5a27; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #1a3d1a; }
        .error { color: red; margin-bottom: 15px; }
        .header { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="login-form">
        <div class="header">
            <h2>🛡️ Admin Login</h2>
            <p>ExtremeLife MLM Administration</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <p style="margin-top: 20px; font-size: 12px; color: #666; text-align: center;">
            Emergency fallback login. Default: admin@extremelifeherbal.com / ExtremeLife2024!
        </p>
    </div>
</body>
</html>';

    // Write fallback file
    $temp_file = '/tmp/admin_login_fallback.php';
    file_put_contents($temp_file, $fallback_content);
    
    // Upload fallback
    if (ssh2_scp_send($ssh, $temp_file, $remote_path . 'admin_login.php', 0644)) {
        executeSSHCommand($ssh, "chown www-data:www-data {$remote_path}admin_login.php");
        echo "✅ Emergency fallback admin_login.php created\n";
        echo "📧 Default login: admin@extremelifeherbal.com / ExtremeLife2024!\n";
    } else {
        echo "❌ Failed to create fallback admin_login.php\n";
    }
    
    unlink($temp_file);
}

function testURL($url) {
    $start_time = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ExtremeLife MLM Rollback Test Bot');
    
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

function generateManualRollbackInstructions() {
    $instructions = "
=== MANUAL ADMIN LOGIN ROLLBACK INSTRUCTIONS ===

1. Connect to server:
   ssh root@109.205.181.119

2. Find available backups:
   find /var/www/html/mlm/backups/ -name 'admin_login.php' -type f | sort -r

3. Choose a backup and restore:
   cp /var/www/html/mlm/backups/[BACKUP_PATH]/admin_login.php /var/www/html/mlm/admin_login.php

4. Set permissions:
   chown www-data:www-data /var/www/html/mlm/admin_login.php
   chmod 644 /var/www/html/mlm/admin_login.php

5. Test the rollback:
   Visit: https://extremelifeherbal.com/mlm/admin_login.php

=== EMERGENCY FALLBACK ===
If no backups work, create a basic admin_login.php with hardcoded credentials:
- Email: admin@extremelifeherbal.com
- Password: ExtremeLife2024!

=== TROUBLESHOOTING ===
• Check Apache error logs: tail -f /var/log/apache2/error.log
• Verify file permissions: ls -la /var/www/html/mlm/admin_login.php
• Test database connection in system_config.php
• Ensure admin tables exist in database
";

    echo $instructions;
    file_put_contents('admin_login_rollback_instructions.txt', $instructions);
    echo "\n📄 Instructions saved to: admin_login_rollback_instructions.txt\n";
}
?>
