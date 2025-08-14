<?php
require_once '../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Get date range from request
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Get overall statistics
$query = "SELECT 
            COUNT(DISTINCT u.user_id) as total_users,
            COUNT(DISTINCT CASE WHEN u.role = 'customer' THEN u.user_id END) as total_customers,
            COUNT(DISTINCT CASE WHEN u.role = 'agent' THEN u.user_id END) as total_agents,
            COUNT(DISTINCT l.loan_id) as total_loans,
            SUM(CASE WHEN l.status = 'active' THEN l.amount ELSE 0 END) as active_loan_amount,
            SUM(CASE WHEN l.status = 'completed' THEN l.amount ELSE 0 END) as completed_loan_amount,
            SUM(CASE WHEN t.type = 'savings' THEN t.amount ELSE 0 END) as total_savings,
            SUM(CASE WHEN t.type = 'repayment' THEN t.amount ELSE 0 END) as total_repayments
          FROM users u
          LEFT JOIN loans l ON u.user_id = l.user_id
          LEFT JOIN transactions t ON u.user_id = t.user_id
          WHERE (t.transaction_date BETWEEN ? AND ? OR t.transaction_date IS NULL)
            AND (l.created_at BETWEEN ? AND ? OR l.created_at IS NULL)";
$stmt = $db->prepare($query);
$stmt->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get monthly loan statistics
$query = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as loan_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END) as active_amount,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount
          FROM loans
          WHERE created_at BETWEEN ? AND ?
          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
          ORDER BY month DESC
          LIMIT 12";
$stmt = $db->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$monthly_loans = [];
while ($row = $result->fetch_assoc()) {
    $monthly_loans[] = $row;
}

// Get monthly transaction statistics
$query = "SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN type = 'savings' THEN amount ELSE 0 END) as savings_amount,
            SUM(CASE WHEN type = 'repayment' THEN amount ELSE 0 END) as repayment_amount
          FROM transactions
          WHERE transaction_date BETWEEN ? AND ?
          GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
          ORDER BY month DESC
          LIMIT 12";
$stmt = $db->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$monthly_transactions = [];
while ($row = $result->fetch_assoc()) {
    $monthly_transactions[] = $row;
}

require_once '../templates/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Reports & Analytics</h1>
        <div>
            <form method="GET" class="d-flex gap-2">
                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Users</h5>
                    <h2 class="display-4"><?php echo number_format($stats['total_users']); ?></h2>
                    <p class="mb-0">
                        <?php echo number_format($stats['total_customers']); ?> Customers<br>
                        <?php echo number_format($stats['total_agents']); ?> Agents
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Loans</h5>
                    <h2 class="display-4"><?php echo number_format($stats['total_loans']); ?></h2>
                    <p class="mb-0">
                        FRW <?php echo number_format($stats['active_loan_amount'], 2); ?> Active<br>
                        FRW <?php echo number_format($stats['completed_loan_amount'], 2); ?> Completed
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Savings</h5>
                    <h2 class="display-4">FRW <?php echo number_format($stats['total_savings'], 2); ?></h2>
                    <p class="mb-0">Total Savings Deposits</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Repayments</h5>
                    <h2 class="display-4">FRW <?php echo number_format($stats['total_repayments'], 2); ?></h2>
                    <p class="mb-0">Total Loan Repayments</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Statistics -->
    <div class="row">
        <!-- Monthly Loans -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Loan Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Loans</th>
                                    <th>Total Amount</th>
                                    <th>Active</th>
                                    <th>Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_loans as $month): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                        <td><?php echo number_format($month['loan_count']); ?></td>
                                                                <td>FRW <?php echo number_format($month['total_amount'], 2); ?></td>
                        <td>FRW <?php echo number_format($month['active_amount'], 2); ?></td>
                        <td>FRW <?php echo number_format($month['completed_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Transactions -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Transaction Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Transactions</th>
                                    <th>Savings</th>
                                    <th>Repayments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_transactions as $month): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                        <td><?php echo number_format($month['transaction_count']); ?></td>
                                                                <td>FRW <?php echo number_format($month['savings_amount'], 2); ?></td>
                        <td>FRW <?php echo number_format($month['repayment_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Export Reports</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Loan Report</h5>
                            <p class="card-text">Export detailed loan information including status, amounts, and repayments.</p>
                            <a href="export_loans.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-primary">
                                <i class="fas fa-download"></i> Export Loans
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Transaction Report</h5>
                            <p class="card-text">Export all transactions including savings and loan repayments.</p>
                            <a href="export_transactions.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-primary">
                                <i class="fas fa-download"></i> Export Transactions
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">User Report</h5>
                            <p class="card-text">Export user information including roles, status, and activity.</p>
                            <a href="export_users.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-primary">
                                <i class="fas fa-download"></i> Export Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?> 