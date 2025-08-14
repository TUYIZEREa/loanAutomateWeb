<?php
require_once 'includes/auth.php';
require_once 'modules/savings/savings_manager.php';
require_once 'modules/loans/loan_manager.php';
require_once 'includes/auth.php';
require_once 'includes/maintenance_check.php';
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$savingsManager = new SavingsManager();
$loanManager = new LoanManager();

$userId = $_SESSION['user_id'];
$user = $auth->getCurrentUser();

if ($auth->hasRole('admin')) {
    // For admin, show their 'amount' field directly, default to 0 if not set
    $balance = $user['amount'] ?? 0;
    // Fetch recent transactions from the transactions table
    $query = "SELECT * FROM transactions WHERE user_id = '$userId' ORDER BY transaction_date DESC LIMIT 5";
    $result = Database::getInstance()->query($query);
    $recentTransactions = [];
    while ($row = $result->fetch_assoc()) {
        $recentTransactions[] = $row;
    }
} else {
    // For regular users, show savings balance and recent savings transactions
    $balance = $user['amount'];
    $recentTransactions = $savingsManager->getTransactionHistory($user['account_number'], ['limit' => 5]);
}

// Get active loans
if ($auth->hasRole('admin')) {
    // Count all active loans in the system
    $query = "SELECT COUNT(*) as active_loans FROM loans WHERE status = 'active'";
    $result = Database::getInstance()->query($query);
    $row = $result->fetch_assoc();
    $activeLoansCount = $row['active_loans'];
} else {
    // Count active/pending loans for the user
    $query = "SELECT COUNT(*) as active_loans FROM loans WHERE user_id = '$userId' AND status IN ('active', 'pending')";
    $result = Database::getInstance()->query($query);
    $row = $result->fetch_assoc();
    $activeLoansCount = $row['active_loans'];
}

// Get active loan details for display
$activeLoans = [];
if ($auth->hasRole('admin')) {
    $query = "SELECT l.*, u.full_name, u.email FROM loans l 
              JOIN users u ON l.user_id = u.user_id 
              WHERE l.status = 'active' 
              ORDER BY l.created_at DESC";
} else {
    $query = "SELECT * FROM loans WHERE user_id = '$userId' AND status IN ('active', 'pending') ORDER BY created_at DESC";
}
$result = Database::getInstance()->query($query);
while ($row = $result->fetch_assoc()) {
    $activeLoans[] = $row;
}

require_once 'templates/header.php';
?>

<div class="row">
    <!-- Balance Card -->
    <div class="col-md-4 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Current Balance</h5>
                <div id="dashboardBalanceCollapse" class="collapse">
                    <h2 class="display-4">FRW <?php echo number_format($balance, 2); ?></h2>
                </div>
                <button class="btn btn-light btn-sm mt-2 toggle-balance" type="button" data-bs-toggle="collapse"
                    data-bs-target="#dashboardBalanceCollapse" aria-expanded="false"
                    aria-controls="dashboardBalanceCollapse" title="Toggle Balance Visibility">
                    <i class="fas fa-eye"></i>
                </button>
                <div class="mt-3">
                    <a href="savings.php" class="btn btn-light">View Details</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Loans Card -->
    <div class="col-md-4 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Active Loans</h5>
                <h2 class="display-4"><?php echo $activeLoansCount; ?></h2>
                <div class="mt-3">
                    <a href="loans.php" class="btn btn-light">View Details</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Card -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="d-grid gap-2">
                    <a href="savings.php?action=send" class="btn btn-success">Send Amount</a>
                    <?php if (!$auth->hasRole('admin')): ?>
                        <a href="savings.php?action=withdraw" class="btn btn-warning">Withdraw</a>
                    <?php endif; ?>
                    <?php if (!$auth->hasRole('admin')): ?>
                        <a href="loans.php?action=request" class="btn btn-primary">Request Loan</a>
                    <?php endif; ?>
                    <?php if ($auth->hasRole('admin')): ?>
                        <a href="loan_analysis.php" class="btn btn-info">Analysis Loans</a>
                    <?php endif; ?>
                    <?php if ($auth->hasRole('admin')): ?>
                        <a href="admin_deposit.php" class="btn btn-warning">Admin Deposit to User</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Transactions -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentTransactions)): ?>
                    <p class="text-muted">No recent transactions</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                        <td><?php echo ucfirst($transaction['type'] ?? $transaction['transaction_type'] ?? ''); ?>
                                        </td>
                                        <td>FRW <?php echo number_format(abs($transaction['amount']), 2); ?></td>
                                        <td>
                                            <span
                                                class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $transaction['reference_number'] ?? $transaction['receipt_number'] ?? ''; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Active Loans -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Active Loans</h5>
            </div>
            <div class="card-body">
                <?php if (count($activeLoans) == 0): ?>
                    <p class="text-muted">No active loans</p>
                <?php else: ?>
                    <div class="d-flex" style="overflow-x: auto; gap: 1rem; padding-bottom: 1rem;">
                        <?php foreach ($activeLoans as $loan): ?>
                            <div class="card" style="min-width: 250px; flex: 0 0 auto;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">Loan #<?php echo $loan['loan_id']; ?></h6>
                                        <span
                                            class="badge bg-<?php echo $loan['status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($loan['status']); ?>
                                        </span>
                                    </div>
                                    <p class="mb-2">Amount: FRW <?php echo number_format($loan['amount'], 2); ?></p>
                                    <?php if ($auth->hasRole('admin')): ?>
                                        <p class="mb-2">Customer: <?php echo htmlspecialchars($loan['full_name']); ?></p>
                                    <?php endif; ?>
                                    <a href="loans.php?id=<?php echo $loan['loan_id']; ?>"
                                        class="btn btn-sm btn-primary w-100">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle eye icon for balance visibility
        const balanceToggle = document.querySelector('.toggle-balance');
        if (balanceToggle) {
            balanceToggle.addEventListener('click', function () {
                const icon = this.querySelector('i');
                if (icon.classList.contains('fa-eye')) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }
    });
</script>

<?php require_once 'templates/footer.php'; ?>