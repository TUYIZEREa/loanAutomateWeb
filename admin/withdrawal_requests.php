<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/utilities.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../modules/withdrawal/withdrawal_manager.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ' . url('login.php'));
    exit;
}

$withdrawalManager = new WithdrawalManager();
$error = '';
$success = '';

// Handle withdrawal request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $adminId = $_SESSION['user_id'];

    if ($action === 'approve') {
        $result = $withdrawalManager->approveWithdrawalRequest($requestId, $adminId);
        if ($result['success']) {
            $success = 'Withdrawal request approved successfully. Receipt: ' . $result['receipt_number'];
        } else {
            $error = $result['error'];
        }
    } elseif ($action === 'reject') {
        $reason = $_POST['reason'] ?? '';
        $result = $withdrawalManager->rejectWithdrawalRequest($requestId, $adminId, $reason);
        if ($result['success']) {
            $success = 'Withdrawal request rejected successfully';
        } else {
            $error = $result['error'];
        }
    }
}

// Auto-expire old requests
$expiredCount = $withdrawalManager->expireOldRequests();

// Get pending withdrawal requests
$pendingRequests = $withdrawalManager->getPendingWithdrawalRequests();

// Get withdrawal statistics
$stats = $withdrawalManager->getWithdrawalStats();

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Withdrawal Requests Management</h1>
        <div>
            <span class="badge bg-warning"><?php echo $stats['pending_count']; ?> Pending</span>
            <span class="badge bg-success"><?php echo $stats['approved_today_count']; ?> Approved Today</span>
            <span class="badge bg-danger"><?php echo $stats['rejected_today_count']; ?> Rejected Today</span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($expiredCount > 0): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle"></i> <?php echo $expiredCount; ?> withdrawal request(s) expired automatically (older than 1 minute)
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending Requests</h5>
                    <h2 class="display-4"><?php echo $stats['pending_count']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Approved Today</h5>
                    <h2 class="display-4"><?php echo $stats['approved_today_count']; ?></h2>
                    <p class="mb-0">FRW <?php echo number_format($stats['approved_today_amount'], 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Rejected Today</h5>
                    <h2 class="display-4"><?php echo $stats['rejected_today_count']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Admin Balance</h5>
                    <?php
                    $adminBalanceQuery = "SELECT SUM(amount) as total FROM users WHERE role = 'admin'";
                    $adminBalanceResult = Database::getInstance()->query($adminBalanceQuery);
                    $adminBalance = $adminBalanceResult->fetch_assoc()['total'] ?? 0;
                    ?>
                    <h2 class="display-4">FRW <?php echo number_format($adminBalance, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Withdrawal Requests -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Pending Withdrawal Requests</h5>
        </div>
        <div class="card-body">
            <?php if (empty($pendingRequests)): ?>
                <p class="text-muted">No pending withdrawal requests</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Amount</th>
                                <th>Charges</th>
                                <th>Total</th>
                                <th>Requested</th>
                                <th>Time Left</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRequests as $request): ?>
                                <?php
                                $requestTime = strtotime($request['requested_at']);
                                $currentTime = time();
                                $timeElapsed = $currentTime - $requestTime;
                                $timeLeft = 60 - $timeElapsed; // 60 seconds = 1 minute
                                $isExpired = $timeLeft <= 0;
                                ?>
                                <tr class="<?php echo $isExpired ? 'table-warning' : ''; ?>">
                                    <td>#<?php echo $request['request_id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($request['full_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($request['account_number']); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($request['email']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($request['phone_number']); ?></small>
                                    </td>
                                    <td>FRW <?php echo number_format($request['amount'], 2); ?></td>
                                    <td>FRW <?php echo number_format($request['charges'], 2); ?></td>
                                    <td><strong>FRW <?php echo number_format($request['total_amount'], 2); ?></strong></td>
                                    <td><?php echo date('M d, Y H:i:s', strtotime($request['requested_at'])); ?></td>
                                    <td>
                                        <?php if ($isExpired): ?>
                                            <span class="badge bg-danger">EXPIRED</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning" id="timer-<?php echo $request['request_id']; ?>">
                                                <?php echo $timeLeft; ?>s
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$isExpired): ?>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        onclick="approveRequest(<?php echo $request['request_id']; ?>)">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="rejectRequest(<?php echo $request['request_id']; ?>)">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Expired</span>
                                        <?php endif; ?>
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

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" id="reject_request_id">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Withdrawal Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason (Optional)</label>
                        <textarea class="form-control" id="rejection_reason" name="reason" rows="3" 
                                  placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-refresh timers
function updateTimers() {
    const timers = document.querySelectorAll('[id^="timer-"]');
    timers.forEach(timer => {
        const requestId = timer.id.replace('timer-', '');
        const currentTime = Math.floor(Date.now() / 1000);
        const requestTime = Math.floor(new Date('<?php echo date('Y-m-d H:i:s'); ?>').getTime() / 1000);
        const timeElapsed = currentTime - requestTime;
        const timeLeft = 60 - timeElapsed;
        
        if (timeLeft <= 0) {
            timer.innerHTML = 'EXPIRED';
            timer.className = 'badge bg-danger';
            // Reload page after 5 seconds to show expired status
            setTimeout(() => location.reload(), 5000);
        } else {
            timer.innerHTML = timeLeft + 's';
        }
    });
}

// Update timers every second
setInterval(updateTimers, 1000);

// Auto-refresh page every 30 seconds to check for new requests
setInterval(() => location.reload(), 30000);

function approveRequest(requestId) {
    if (confirm('Are you sure you want to approve this withdrawal request?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="request_id" value="${requestId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectRequest(requestId) {
    document.getElementById('reject_request_id').value = requestId;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
