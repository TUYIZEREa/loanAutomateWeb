<?php



// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define inactivity timeout (3 minutes)
define('SESSION_TIMEOUT', 180);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/password_policy.php';
require_once __DIR__ . '/utilities.php';

class Auth
{
    private $db;
    private $passwordPolicy;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->passwordPolicy = new PasswordPolicy();

        // Check for session timeout on every page load (except for login/logout/register)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            // Last activity was more than 3 minutes ago, destroy session
            session_unset();
            session_destroy();
            // Redirect to login page with a timeout message
            header('Location: ' . url('login.php?timeout=true'));
            exit();
        }

        // Update last activity time on current page load
        $_SESSION['last_activity'] = time();
    }

    private function generateAccountNumber()
    {
        do {
            // Generate a 10-digit account number
            $accountNumber = 'ACC' . date('Y') . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Check if account number already exists
            $query = "SELECT COUNT(*) as count FROM users WHERE account_number = '$accountNumber'";
            $result = $this->db->query($query);
            $row = $result->fetch_assoc();
            $exists = $row['count'] > 0;
        } while ($exists);

        return $accountNumber;
    }

    public function login($username, $password)
    {
        $username = $this->db->escape($username);
        $query = "SELECT * FROM users WHERE username = '$username'";
        $result = $this->db->query($query);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
        }
        return false;
    }

    public function register($username, $password, $fullName, $phoneNumber, $email, $initialBalance, $role = 'customer')
    {
        try {
            // Validate email format only if email is provided
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Invalid email format'];
            }

            // First check if username already exists
            $username = $this->db->escape($username);
            $checkQuery = "SELECT COUNT(*) as count FROM users WHERE username = ?";
            $stmt = $this->db->prepare($checkQuery);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $checkRow = $result->fetch_assoc();

            if ($checkRow['count'] > 0) {
                return ['success' => false, 'error' => 'Username already exists'];
            }

            // Check if email already exists only if email is provided
            if (!empty($email)) {
                $email = $this->db->escape($email);
                $checkEmailQuery = "SELECT COUNT(*) as count FROM users WHERE email = ?";
                $stmt = $this->db->prepare($checkEmailQuery);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $checkEmailRow = $result->fetch_assoc();

                if ($checkEmailRow['count'] > 0) {
                    return ['success' => false, 'error' => 'Email already exists'];
                }
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $fullName = $this->db->escape($fullName);
            $phoneNumber = $this->db->escape($phoneNumber);
            $role = $this->db->escape($role);
            $initialBalance = floatval($initialBalance);

            // Generate unique account number
            $accountNumber = $this->generateAccountNumber();

            // Set email to null if empty
            $email = trim($email);
            if ($email === '') {
                $email = null;
            }

            $query = "INSERT INTO users (username, password, full_name, phone_number, email, role, account_number, amount, password_changed_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("sssssssd", $username, $hashedPassword, $fullName, $phoneNumber, $email, $role, $accountNumber, $initialBalance);

            if ($stmt->execute()) {
                // Retrieve auto-incremented user ID
                $userId = $this->db->getLastInsertId();
                // Add password to history
                $this->passwordPolicy->addToPasswordHistory($userId, $hashedPassword);

                // Create a savings record for initial balance
                if ($initialBalance > 0) {
                    $receiptNumber = 'RCP' . date('YmdHis') . rand(1000, 9999);

                    $savingsQuery = "INSERT INTO savings (user_id, account_number, amount, transaction_type, status, receipt_number) 
                                   VALUES (?, ?, ?, 'deposit', 'completed', ?)";
                    $stmt = $this->db->prepare($savingsQuery);
                    $stmt->bind_param("isds", $userId, $accountNumber, $initialBalance, $receiptNumber);
                    $stmt->execute();

                    // Also add to transactions table
                    $transactionQuery = "INSERT INTO transactions (user_id, account_number, type, amount, status, reference_number, payment_method) 
                                      VALUES (?, ?, 'savings', ?, 'completed', ?, 'cash')";
                    $stmt = $this->db->prepare($transactionQuery);
                    $stmt->bind_param("isds", $userId, $accountNumber, $initialBalance, $receiptNumber);
                    $stmt->execute();
                }

                return ['success' => true, 'account_number' => $accountNumber];
            }

            // If query failed, get the specific error
            $error = $this->db->getLastError();
            error_log("Registration query failed: " . $error);
            return ['success' => false, 'error' => 'Registration failed: ' . $error];
        } catch (Exception $e) {
            error_log("Registration exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword)
    {
        try {
            // Get current user
            $userId = $this->db->escape($userId);
            $query = "SELECT password FROM users WHERE user_id = '$userId'";
            $result = $this->db->query($query);
            $user = $result->fetch_assoc();

            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'error' => 'Current password is incorrect'];
            }

            // Validate new password
            $passwordValidation = $this->passwordPolicy->validatePassword($newPassword, $userId);
            if (!$passwordValidation['valid']) {
                return ['success' => false, 'error' => implode(', ', $passwordValidation['errors'])];
            }

            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password
            $query = "UPDATE users SET password = '$hashedPassword', password_changed_at = NOW() WHERE user_id = '$userId'";
            if ($this->db->query($query)) {
                // Add to password history
                $this->passwordPolicy->addToPasswordHistory($userId, $hashedPassword);
                return ['success' => true];
            }

            return ['success' => false, 'error' => 'Failed to update password'];
        } catch (Exception $e) {
            error_log("Password change exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to change password: ' . $e->getMessage()];
        }
    }

    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function logout()
    {
        session_destroy();
        return true;
    }

    public function getCurrentUser()
    {
        if ($this->isLoggedIn()) {
            $userId = $_SESSION['user_id'];
            $query = "SELECT user_id, username, full_name, email, profile_picture, phone_number, account_number, role, status, amount, created_at, updated_at, password_changed_at FROM users WHERE user_id = '$userId'";
            $result = $this->db->query($query);
            return $result->fetch_assoc();
        }
        return null;
    }

    public function hasRole($role)
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
}
?>