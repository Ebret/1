<?php
/**
 * ExtremeLife MLM Admin Tables Setup
 * Creates necessary database tables for admin authentication system
 */

require_once 'system_config.php';

try {
    $config = new SystemConfig();
    $pdo = $config->getDBConnection();
    
    echo "Setting up ExtremeLife MLM Admin Tables...\n\n";
    
    // Create mlm_admins table
    $sql_admins = "
    CREATE TABLE IF NOT EXISTS mlm_admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        failed_attempts INT DEFAULT 0,
        locked_until TIMESTAMP NULL,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status),
        INDEX idx_role (role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_admins);
    echo "✅ Created mlm_admins table\n";
    
    // Create mlm_admin_tokens table for remember me functionality
    $sql_tokens = "
    CREATE TABLE IF NOT EXISTS mlm_admin_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES mlm_admins(id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_admin_id (admin_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_tokens);
    echo "✅ Created mlm_admin_tokens table\n";
    
    // Create mlm_admin_sessions table for session management
    $sql_sessions = "
    CREATE TABLE IF NOT EXISTS mlm_admin_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        session_id VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES mlm_admins(id) ON DELETE CASCADE,
        INDEX idx_session_id (session_id),
        INDEX idx_admin_id (admin_id),
        INDEX idx_last_activity (last_activity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_sessions);
    echo "✅ Created mlm_admin_sessions table\n";
    
    // Create mlm_admin_logs table for audit trail
    $sql_logs = "
    CREATE TABLE IF NOT EXISTS mlm_admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES mlm_admins(id) ON DELETE SET NULL,
        INDEX idx_admin_id (admin_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_logs);
    echo "✅ Created mlm_admin_logs table\n";
    
    // Check if default admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mlm_admins WHERE email = ?");
    $stmt->execute(['admin@extremelifeherbal.com']);
    $admin_exists = $stmt->fetchColumn();
    
    if (!$admin_exists) {
        // Create default admin account
        $default_password = 'ExtremeLife2024!';
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO mlm_admins (email, password, first_name, last_name, role, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'admin@extremelifeherbal.com',
            $hashed_password,
            'System',
            'Administrator',
            'super_admin',
            'active'
        ]);
        
        echo "✅ Created default admin account\n";
        echo "   Email: admin@extremelifeherbal.com\n";
        echo "   Password: {$default_password}\n";
        echo "   ⚠️  IMPORTANT: Change this password immediately after first login!\n\n";
    } else {
        echo "ℹ️  Default admin account already exists\n\n";
    }
    
    // Create additional admin accounts if needed
    $additional_admins = [
        [
            'email' => 'support@extremelifeherbal.com',
            'password' => 'Support2024!',
            'first_name' => 'Support',
            'last_name' => 'Team',
            'role' => 'admin'
        ]
    ];
    
    foreach ($additional_admins as $admin_data) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mlm_admins WHERE email = ?");
        $stmt->execute([$admin_data['email']]);
        
        if (!$stmt->fetchColumn()) {
            $hashed_password = password_hash($admin_data['password'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO mlm_admins (email, password, first_name, last_name, role, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $admin_data['email'],
                $hashed_password,
                $admin_data['first_name'],
                $admin_data['last_name'],
                $admin_data['role'],
                'active'
            ]);
            
            echo "✅ Created admin account: {$admin_data['email']}\n";
            echo "   Password: {$admin_data['password']}\n";
        }
    }
    
    // Clean up expired tokens
    $pdo->exec("DELETE FROM mlm_admin_tokens WHERE expires_at < NOW()");
    echo "✅ Cleaned up expired tokens\n";
    
    // Clean up old sessions (older than 30 days)
    $pdo->exec("DELETE FROM mlm_admin_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo "✅ Cleaned up old sessions\n";
    
    echo "\n🎉 Admin tables setup completed successfully!\n\n";
    
    echo "📋 ADMIN LOGIN INFORMATION:\n";
    echo "URL: https://extremelifeherbal.com/mlm/admin_login.php\n";
    echo "Default Admin: admin@extremelifeherbal.com\n";
    echo "Support Admin: support@extremelifeherbal.com\n\n";
    
    echo "🔒 SECURITY FEATURES ENABLED:\n";
    echo "• Password hashing with PHP password_hash()\n";
    echo "• Account lockout after 5 failed attempts\n";
    echo "• Session management and timeout\n";
    echo "• Remember me functionality with secure tokens\n";
    echo "• Audit logging for all admin actions\n";
    echo "• IP address and user agent tracking\n\n";
    
    echo "⚠️  IMPORTANT SECURITY NOTES:\n";
    echo "1. Change default passwords immediately\n";
    echo "2. Use strong, unique passwords\n";
    echo "3. Enable HTTPS in production\n";
    echo "4. Regularly review admin logs\n";
    echo "5. Remove unused admin accounts\n\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up admin tables: " . $e->getMessage() . "\n";
    echo "Please check your database configuration and try again.\n";
}
?>
