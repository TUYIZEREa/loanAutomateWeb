<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: login.php');
    exit;
}

try {
    $db = Database::getInstance();

    // Add rate limiting columns to users table
    $columns = [
        'login_attempts' => "ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0",
        'last_login_attempt' => "ALTER TABLE users ADD COLUMN last_login_attempt TIMESTAMP NULL",
        'is_locked' => "ALTER TABLE users ADD COLUMN is_locked BOOLEAN DEFAULT 0",
        'lockout_until' => "ALTER TABLE users ADD COLUMN lockout_until TIMESTAMP NULL",
        'transaction_pin' => "ALTER TABLE users ADD COLUMN transaction_pin VARCHAR(255) NULL"
    ];

    foreach ($columns as $column => $query) {
        try {
            $result = $db->query("SHOW COLUMNS FROM users LIKE '$column'");
            if ($result->num_rows === 0) {
                $db->query($query);
                echo "Added column: $column\n";
            } else {
                echo "Column $column already exists\n";
            }
        } catch (Exception $e) {
            echo "Error adding column $column: " . $e->getMessage() . "\n";
        }
    }

    // Ensure loans.status includes 'cancelled'
    try {
        $res = $db->query("SHOW COLUMNS FROM loans LIKE 'status'");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (strpos($row['Type'], "'cancelled'") === false) {
                // Attempt to alter enum to include cancelled
                $db->query("ALTER TABLE loans MODIFY COLUMN status ENUM('pending','active','completed','rejected','cancelled') NOT NULL DEFAULT 'pending'");
                echo "Updated loans.status enum to include 'cancelled'\n";
            }
        }
    } catch (Exception $e) {
        echo "Warning updating loans.status enum: " . $e->getMessage() . "\n";
    }

    echo "Database schema update completed!\n";
    
} catch (Exception $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
} 