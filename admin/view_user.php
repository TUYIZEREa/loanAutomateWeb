<?php
require_once '../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Get user ID from URL
$user_id = isset($_GET['id']) ? $_GET['id'] : '';

if (!$user_id) {
    header('Location: users.php');
    exit;
}

// Get user details
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: users.php');
    exit;
}

// Get user's loans
$query = "SELECT * FROM loans WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$loans = [];
while ($row = $result->fetch_assoc()) {
    $loans[] = $row;
}

// Get user's transactions
$query = "SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

// Calculate statistics
$total_loans = count($loans);
$active_loans = count(array_filter($loans, fn($loan) => $loan['status'] === 'active'));
$total_loan_amount = array_sum(array_column($loans, 'amount'));
$total_repayments = array_sum(array_column($loans, 'amount_paid'));

require_once '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- User Information -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-circle bg-primary text-white mb-3">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p class="text-muted">
                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'agent' ? 'warning' : 'info'); ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <p><?php echo htmlspecialchars($user['phone_number']); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Member Since</label>
                        <p><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit User
                        </a>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Users
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loan Statistics -->
        <div class="col-md-8 mb-4">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Loans</h5>
                            <h2 class="display-4"><?php echo $total_loans; ?></h2>
                            <p class="mb-0"><?php echo $active_loans; ?> Active Loans</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Loan Amount</h5>
                                                <h2 class="display-4">FRW <?php echo number_format($total_loan_amount, 2); ?></h2>
                    <p class="mb-0">FRW <?php echo number_format($total_repayments, 2); ?> Repaid</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loans List -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Loan History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($loans)): ?>
                        <p class="text-muted">No loans found</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Amount</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loans as $loan): ?>
                                        <tr>
                                            <td>#<?php echo $loan['loan_id']; ?></td>
                                            <td>FRW <?php echo number_format($loan['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($loan['purpose']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($loan['status']) {
                                                        'active' => 'success',
                                                        'pending' => 'warning',
                                                        'completed' => 'info',
                                                        'rejected' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($loan['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($loan['created_at'])); ?></td>
                                            <td>
                                                <a href="../loans.php?id=<?php echo $loan['loan_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                        <p class="text-muted">No transactions found</p>
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
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
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
                                            <td>
                                                <a href="../receipt.php?id=<?php echo $transaction['transaction_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
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
    </div>
</div>

<style>
.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto;
}
</style>

<?php require_once '../templates/footer.php'; ?> 