<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/includes/rate_limiter.php';

$auth = new Auth();
$rateLimiter = new RateLimiter();
$error = '';
$success = '';

// Check for session timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] === 'true') {
    $error = 'Session expired due to inactivity. Please log in again.';
}

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check if rate limited
    if ($rateLimiter->isRateLimited($_SERVER['REMOTE_ADDR'], $username)) {
        $timeUntilReset = $rateLimiter->getLockoutTimeRemaining($username);
        if ($timeUntilReset < 60) {
            $error = "Too many login attempts. Please try again in " . $timeUntilReset . " second" . ($timeUntilReset != 1 ? 's' : '') . ".";
        } else {
            $minutes = ceil($timeUntilReset / 60);
            $error = "Too many login attempts. Please try again in " . $minutes . " minute" . ($minutes != 1 ? 's' : '') . ".";
        }
    } else {
        if ($auth->login($username, $password)) {
            // Record successful attempt
            $rateLimiter->recordFailedAttempt($_SERVER['REMOTE_ADDR'], $username, true);

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Record failed attempt
            $rateLimiter->recordFailedAttempt($_SERVER['REMOTE_ADDR'], $username, false);

            $remainingAttempts = $rateLimiter->getRemainingAttempts($username);
            $error = "Invalid username or password. You have " . $remainingAttempts . " attempt" . ($remainingAttempts != 1 ? 's' : '') . " remaining.";
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>