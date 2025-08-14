<?php
require_once 'includes/auth.php';
require_once 'includes/password_policy.php';

$auth = new Auth();
$passwordPolicy = new PasswordPolicy();
$error = '';
$success = '';

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $email = $_POST['email'] ?? '';
    $initial_balance = $_POST['initial_balance'] ?? '';

    // Validate initial balance
    $initial_balance_numeric = filter_var($initial_balance, FILTER_VALIDATE_FLOAT);
    if ($initial_balance_numeric === false || $initial_balance_numeric < 1000) {
        $error = 'Initial balance must be at least FRW 1000';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->register($username, $password, $full_name, $phone_number, $email, $initial_balance_numeric);
        if ($result['success']) {
            $success = 'Registration successful. Your account number is: ' . $result['account_number'];
            // Clear form data
            $username = $password = $confirm_password = $full_name = $phone_number = $email = $initial_balance = '';
        } else {
            $error = $result['error'];
        }
    }
}

require_once 'templates/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Register</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">
                                Password requirements:
                                <ul>
                                    <?php foreach ($passwordPolicy->getPasswordRequirements() as $requirement): ?>
                                        <li><?php echo htmlspecialchars($requirement); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number"
                                value="<?php echo htmlspecialchars($phone_number ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="initial_balance" class="form-label">Initial Balance (FRW)</label>
                            <input type="number" class="form-control" id="initial_balance" name="initial_balance"
                                step="0.01" min="1000" value="<?php echo htmlspecialchars($initial_balance ?? ''); ?>"
                                required>
                            <div class="form-text">Must be at least FRW 1000.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>