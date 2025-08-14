<?php
require_once '../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Get pending loans
$query = "SELECT l.*, u.full_name, u.phone_number 
          FROM loans l 
          JOIN users u ON l.user_id = u.user_id 
          WHERE l.status = 'pending' 
          ORDER BY l.created_at DESC";
$result = $db->query($query);
$pendingLoans = [];
while ($row = $result->fetch_assoc()) {
    $pendingLoans[] = $row;
}

// Get recent transactions
$query = "SELECT t.*, u.full_name 
          FROM transactions t 
          JOIN users u ON t.user_id = u.user_id 
          ORDER BY t.transaction_date DESC 
          LIMIT 10";
$result = $db->query($query);
$recentTransactions = [];
while ($row = $result->fetch_assoc()) {
    $recentTransactions[] = $row;
}

// Get user statistics
$query = "SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as total_customers,
            SUM(CASE WHEN role = 'agent' THEN 1 ELSE 0 END) as total_agents
          FROM users";
$result = $db->query($query);
$userStats = $result->fetch_assoc();

// Get loan statistics
$query = "SELECT 
            COUNT(*) as total_loans,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_loans,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_loans,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_loans
          FROM loans";
$result = $db->query($query);
$loanStats = $result->fetch_assoc();

require_once '../templates/header.php';
?>

<div class="row">
    <!-- User Statistics -->
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <h2 class="display-4"><?php echo $userStats['total_users']; ?></h2>
                <p class="mb-0">
                    <?php echo $userStats['total_customers']; ?> Customers<br>
                    <?php echo $userStats['total_agents']; ?> Agents
                </p>
            </div>
        </div>
    </div>

    <!-- Loan Statistics -->
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Total Loans</h5>
                <h2 class="display-4"><?php echo $loanStats['total_loans']; ?></h2>
                <p class="mb-0">
                    <?php echo $loanStats['active_loans']; ?> Active<br>
                    <?php echo $loanStats['pending_loans']; ?> Pending
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="d-grid gap-2">
                    <a href="users.php" class="btn btn-primary">Manage Users</a>
                    <a href="loans.php" class="btn btn-success">Manage Loans</a>
                    <a href="reports.php" class="btn btn-info">View Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pending Loans -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Pending Loans</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pendingLoans)): ?>
                    <p class="text-muted">No pending loans</p>
                <?php else: ?>
                    <?php foreach ($pendingLoans as $loan): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6>Loan #<?php echo $loan['loan_id']; ?></h6>
                                <p class="mb-1">Amount: FRW <?php echo number_format($loan['amount'], 2); ?></p>
                                <p class="mb-1">Customer: <?php echo htmlspecialchars($loan['full_name']); ?></p>
                                <p class="mb-1">Phone: <?php echo htmlspecialchars($loan['phone_number']); ?></p>
                                <p class="mb-1">Purpose: <?php echo htmlspecialchars($loan['purpose']); ?></p>
                                <p class="mb-1">Risk Score: <?php echo $loan['risk_score']; ?></p>
                                
                                <form method="POST" action="process_loan.php" class="mt-3">
                                    <input type="hidden" name="loan_id" value="<?php echo $loan['loan_id']; ?>">
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['full_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($transaction['type']) {
                                                'savings' => 'success',
                                                'loan' => 'primary',
                                                'repayment' => 'info',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td>FRW <?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 