<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/utilities.php';

$auth = new Auth();
$isLoggedIn = $auth->isLoggedIn();
$user = null;

if ($isLoggedIn) {
    $user = $auth->getCurrentUser();
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="container text-center mt-5">
    <h1 class="display-4">Welcome to Loan Automate System</h1>
    <p class="lead">
        <?php if ($isLoggedIn): ?>
            Hello, <?php echo htmlspecialchars($user['full_name']); ?>! Manage your finances with ease.
        <?php else: ?>
            Your trusted platform for secure savings and efficient loan management.
        <?php endif; ?>
    </p>

    <div class="mt-4">
        <?php if ($isLoggedIn): ?>
            <a href="dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-lg me-2">Login</a>
            <a href="register.php" class="btn btn-secondary btn-lg">Register</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>