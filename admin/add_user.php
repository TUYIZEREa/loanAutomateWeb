<?php
require_once '../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$error = '';
$success = '';

// Function to generate UUID
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);

    // Validate input
    if (empty($full_name) || empty($email) || empty($phone_number) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($amount < 0) {
        $error = 'Amount cannot be negative';
    } else {
        // Check if email already exists
        $query = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate account number
            $accountNumber = 'ACC' . date('Y') . rand(100000, 999999);
            
            // Generate UUID for user_id
            $userId = generateUUID();
            
            // Generate username from full name (first word)
            $username = strtolower(explode(' ', $full_name)[0]);
            
            // Insert new user
            $query = "INSERT INTO users (user_id, username, full_name, email, phone_number, password, role, status, account_number, amount, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bind_param('ssssssssd', $userId, $username, $full_name, $email, $phone_number, $hashed_password, $role, $accountNumber, $amount);
            
            if ($stmt->execute()) {
                // If initial amount is provided, create a savings record
                if ($amount > 0) {
                    $receiptNumber = 'RCP' . date('YmdHis') . rand(1000, 9999);
                    
                    $savingsQuery = "INSERT INTO savings (user_id, account_number, amount, transaction_type, status, is_admin_transaction, receipt_number, transaction_date) 
                                   VALUES (?, ?, ?, 'deposit', 'completed', 1, ?, NOW())";
                    $stmt = $db->prepare($savingsQuery);
                    $stmt->bind_param('ssds', $userId, $accountNumber, $amount, $receiptNumber);
                    $stmt->execute();
                    
                    // Also add to transactions table
                    $transactionQuery = "INSERT INTO transactions (user_id, account_number, type, amount, status, reference_number, payment_method, transaction_date) 
                                      VALUES (?, ?, 'savings', ?, 'completed', ?, 'cash', NOW())";
                    $stmt = $db->prepare($transactionQuery);
                    $stmt->bind_param('ssds', $userId, $accountNumber, $amount, $receiptNumber);
                    $stmt->execute();
                }
                
                $success = 'User added successfully';
                // Clear form data
                $full_name = $email = $phone_number = $password = $confirm_password = $amount = '';
            } else {
                $error = 'Error adding user: ' . $stmt->error;
            }
        }
    }
}

require_once '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add New User</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                   value="<?php echo htmlspecialchars($phone_number ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="customer" <?php echo ($role ?? '') === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                <option value="agent" <?php echo ($role ?? '') === 'agent' ? 'selected' : ''; ?>>Agent</option>
                                <option value="admin" <?php echo ($role ?? '') === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Initial Deposit Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" 
                                   value="<?php echo htmlspecialchars($amount ?? '0'); ?>" required>
                            <div class="form-text">Enter the initial deposit amount for the user</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Password must be at least 8 characters long</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Add User</button>
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 