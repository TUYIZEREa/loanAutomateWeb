<?php
require_once 'includes/auth.php';
require_once 'modules/savings/savings_manager.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: login.php');
    exit;
}

$savingsManager = new SavingsManager();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerAccount = $_POST['customer_account'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $adminId = $_SESSION['user_id'];

    $result = $savingsManager->adminDeposit($adminId, $customerAccount, $amount, $description);
    if ($result['success']) {
        $message = "Deposit successful! Receipt: " . $result['receipt_number'];
    } else {
        $message = "Error: " . $result['error'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Deposit to User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Admin Deposit to User</h3>
    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="POST" class="card p-4">
        <div class="mb-3">
            <label class="form-label">Customer Account Number:</label>
            <input type="text" name="customer_account" required class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Amount:</label>
            <input type="number" name="amount" step="0.01" min="0.01" required class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Description (optional):</label>
            <input type="text" name="description" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Deposit</button>
        <a href="dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
    </form>
</div>
</body>
</html> 