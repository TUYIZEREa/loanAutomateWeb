<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: login.php');
    exit;
}

try {
    $db = Database::getInstance();

    // Add amount column to users table
    $db->query("
        ALTER TABLE users 
        ADD COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00
    ");

    echo "Amount column added successfully to users table!\n";

    // Show the updated table structure
    echo "\nUpdated users table structure:\n";
    $result = $db->query("DESCRIBE users");
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "\n";
    }

} catch (Exception $e) {
    echo "Error adding amount column: " . $e->getMessage() . "\n";
    error_log("Column addition error: " . $e->getMessage());
}