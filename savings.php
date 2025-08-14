<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/modules/savings/savings_manager.php';
require_once __DIR__ . '/modules/withdrawal/withdrawal_manager.php';
require_once 'includes/maintenance_check.php';

// --- AJAX: Return updated balance ---
if (isset($_GET['fetch_balance']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    $auth = new Auth();
    header('Content-Type: application/json');
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $user = $auth->getCurrentUser();

    $balance = $user['amount'] ?? 0;

    echo json_encode(['success' => true, 'balance' => $balance]);
    exit;
}

// --- AJAX: Handle transfer and withdraw POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $savingsManager = new SavingsManager();
    $user = $auth->getCurrentUser();
    $accountNumber = $user['account_number'];

    header('Content-Type: application/json');

    if ($_POST['action'] === 'confirm_send') {
        $amount = $_POST['amount'] ?? 0;
        $receiverAccountNumber = $_POST['receiver_account'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Please enter your password']);
            exit;
        }

        // Verify password
        $query = "SELECT password FROM users WHERE user_id = ?";
        $stmt = Database::getInstance()->prepare($query);
        $stmt->bind_param("s", $user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();

        if (!password_verify($password, $userData['password'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
            exit;
        }

        $result = $savingsManager->transfer($accountNumber, $receiverAccountNumber, $amount);
        if ($result['success']) {
            echo json_encode(['success' => true, 'receipt_number' => $result['receipt_number']]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'An error occurred while sending amount']);
            exit;
        }
    }

    if ($_POST['action'] === 'confirm_withdraw') {
        $amount = $_POST['amount'] ?? 0;
        $password = $_POST['password'] ?? '';

        if (empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Please enter your password']);
            exit;
        }

        // Verify password
        $query = "SELECT password FROM users WHERE user_id = ?";
        $stmt = Database::getInstance()->prepare($query);
        $stmt->bind_param("s", $user['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();

        if (!password_verify($password, $userData['password'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
            exit;
        }

        // Create withdrawal request instead of direct withdrawal
        $withdrawalManager = new WithdrawalManager();
        $result = $withdrawalManager->createWithdrawalRequest($user['user_id'], $accountNumber, $amount);
        if ($result['success']) {
            echo json_encode([
                'success' => true, 
                'request_id' => $result['request_id'],
                'amount' => $result['amount'],
                'charges' => $result['charges'],
                'total_amount' => $result['total_amount'],
                'message' => $result['message']
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'An error occurred while processing your withdrawal request']);
            exit;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// --- Normal page load and form handling below ---

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('login.php'));
    exit;
}

    $savingsManager = new SavingsManager();
    $withdrawalManager = new WithdrawalManager();
    $user = $auth->getCurrentUser();
    $accountNumber = $user['account_number'];
    $error = '';
    $success = '';

// Get all customers except current user
$query = "SELECT user_id, full_name, phone_number, account_number FROM users WHERE user_id != '{$user['user_id']}' AND role = 'customer' ORDER BY full_name";
$result = Database::getInstance()->query($query);
$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

// Get current balance from user table directly (to show immediately on load)
$balance = $user['amount'] ?? 0;

    // Get transaction history
    $filters = [];
    if (isset($_GET['start_date'])) {
        $filters['start_date'] = $_GET['start_date'];
    }
    if (isset($_GET['end_date'])) {
        $filters['end_date'] = $_GET['end_date'];
    }
    if (isset($_GET['type'])) {
        $filters['type'] = $_GET['type'];
    }

    try {
        $transactions = $savingsManager->getTransactionHistory($accountNumber, $filters);
    } catch (Exception $e) {
        $error = 'Error retrieving transaction history. Please try again.';
        error_log("Transaction history error: " . $e->getMessage());
        $transactions = [];
    }

    // Get user's withdrawal requests
    try {
        $withdrawalRequests = $withdrawalManager->getUserWithdrawalRequests($user['user_id']);
    } catch (Exception $e) {
        $withdrawalRequests = [];
    }

require_once __DIR__ . '/templates/header.php';
?>

<div class="container-fluid">
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

    <div class="row">
        <!-- Balance Card -->
        <div class="col-md-4 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Current Balance</h5>
                    <div id="balanceCollapse" class="collapse">
                        <h2 class="display-4">FRW <?php echo number_format($balance, 2); ?></h2>
                    </div>
                    <button class="btn btn-light btn-sm mt-2 toggle-balance" type="button" data-bs-toggle="collapse"
                        data-bs-target="#balanceCollapse" aria-expanded="false" aria-controls="balanceCollapse"
                        title="Toggle Balance Visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                    <p class="mb-0">Account: <?php echo htmlspecialchars($accountNumber); ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-5 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal"
                            data-bs-target="#sendModal">
                            Send Amount
                        </button>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                            data-bs-target="#withdrawModal">
                            Withdraw
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Transaction History</h5>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date"
                        value="<?php echo $_GET['start_date'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date"
                        value="<?php echo $_GET['end_date'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Transaction Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <option value="deposit" <?php echo (isset($_GET['type']) && $_GET['type'] === 'deposit') ? 'selected' : ''; ?>>Deposit</option>
                        <option value="withdrawal" <?php echo (isset($_GET['type']) && $_GET['type'] === 'withdrawal') ? 'selected' : ''; ?>>Withdrawal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">Apply Filters</button>
                </div>
            </form>

            <!-- Transactions Table -->
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
                                <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                <td>
                                    <span
                                        class="badge bg-<?php echo $transaction['transaction_type'] === 'deposit' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($transaction['transaction_type']); ?>
                                    </span>
                                </td>
                                <td>FRW <?php echo number_format($transaction['amount'], 2); ?></td>
                                <td>
                                    <span
                                        class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['receipt_number']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Withdrawal Requests -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Withdrawal Requests</h5>
        </div>
        <div class="card-body">
            <?php if (empty($withdrawalRequests)): ?>
                <p class="text-muted">No withdrawal requests found</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Amount</th>
                                <th>Charges</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Processed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawalRequests as $request): ?>
                                <tr>
                                    <td>#<?php echo $request['request_id']; ?></td>
                                    <td>FRW <?php echo number_format($request['amount'], 2); ?></td>
                                    <td>FRW <?php echo number_format($request['charges'], 2); ?></td>
                                    <td>FRW <?php echo number_format($request['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($request['status']) {
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'expired' => 'secondary',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($request['requested_at'])); ?></td>
                                    <td>
                                        <?php if ($request['approved_at']): ?>
                                            <?php echo date('M d, Y H:i', strtotime($request['approved_at'])); ?>
                                        <?php elseif ($request['rejected_at']): ?>
                                            <?php echo date('M d, Y H:i', strtotime($request['rejected_at'])); ?>
                                        <?php elseif ($request['expired_at']): ?>
                                            <?php echo date('M d, Y H:i', strtotime($request['expired_at'])); ?>
                                        <?php else: ?>
                                            -
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

<!-- Send Amount Modal -->
<div class="modal fade" id="sendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="sendForm">
                <input type="hidden" name="action" value="send">
                <div class="modal-header">
                    <h5 class="modal-title">Send Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="receiver_account" class="form-label">Select Receiver</label>
                        <select class="form-select" id="receiver_account" name="receiver_account" required>
                            <option value="">Select a receiver</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo htmlspecialchars($customer['account_number']); ?>">
                                    <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['account_number'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="send_amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">FRW</span>
                            <input type="number" class="form-control" id="send_amount" name="amount" step="0.01"
                                min="0.01" max="<?php echo $balance; ?>" required>
                        </div>
                        <div class="form-text">Available balance: FRW <?php echo number_format($balance, 2); ?></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Continue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Send Confirmation Modal -->
<div class="modal fade" id="sendConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="sendConfirmForm">
                <input type="hidden" name="action" value="confirm_send">
                <input type="hidden" name="amount" id="confirm_send_amount" />
                <input type="hidden" name="receiver_account" id="confirm_receiver_account" />
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Send</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Please confirm the following transaction:</p>
                    <div class="mb-3">
                        <strong>Amount:</strong> FRW <span id="confirm_amount_display"></span>
                    </div>
                    <div class="mb-3">
                        <strong>To:</strong> <span id="confirm_receiver_display"></span>
                    </div>
                    <div class="mb-3">
                        <label for="send_password" class="form-label">Enter Your Password</label>
                        <input type="password" class="form-control" id="send_password" name="password" required
                            placeholder="Enter your password to confirm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="withdrawForm">
                <input type="hidden" name="action" value="withdraw">
                <div class="modal-header">
                    <h5 class="modal-title">Withdraw Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="withdraw_amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">FRW</span>
                            <input type="number" class="form-control" id="withdraw_amount" name="amount" step="0.01"
                                min="0.01" max="<?php echo $balance; ?>" required>
                        </div>
                        <div class="form-text">Available balance: FRW <?php echo number_format($balance, 2); ?></div>
                    </div>
                    
                    <!-- Charges Preview -->
                    <div class="alert alert-info" id="chargesPreview" style="display: none;">
                        <h6>Withdrawal Charges Preview:</h6>
                        <div id="chargesDetails"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Continue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdraw Confirmation Modal -->
<div class="modal fade" id="withdrawConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="withdrawConfirmForm">
                <input type="hidden" name="action" value="confirm_withdraw" />
                <input type="hidden" name="amount" id="confirm_withdraw_amount" />
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Withdrawal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Please confirm the following transaction:</p>
                    <div class="mb-3">
                        <strong>Amount:</strong> FRW <span id="confirm_withdraw_amount_display"></span>
                    </div>
                    <div class="mb-3">
                        <label for="withdraw_password" class="form-label">Enter Your Password</label>
                        <input type="password" class="form-control" id="withdraw_password" name="password" required
                            placeholder="Enter your password to confirm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Confirm Withdrawal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle balance eye icon
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

    // Send form -> Show confirmation modal with data
    document.getElementById('sendForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const amount = document.getElementById('send_amount').value;
        const receiverSelect = document.getElementById('receiver_account');
        const receiverText = receiverSelect.options[receiverSelect.selectedIndex].text;

        // Fill confirmation modal
        document.getElementById('confirm_send_amount').value = amount;
        document.getElementById('confirm_receiver_account').value = receiverSelect.value;
        document.getElementById('confirm_amount_display').textContent = new Intl.NumberFormat().format(amount);
        document.getElementById('confirm_receiver_display').textContent = receiverText;

        // Hide send modal, show confirm modal
        bootstrap.Modal.getInstance(document.getElementById('sendModal')).hide();
        new bootstrap.Modal(document.getElementById('sendConfirmModal')).show();
    });

    // Withdraw form -> Show confirmation modal with data
    document.getElementById('withdrawForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const amount = document.getElementById('withdraw_amount').value;

        document.getElementById('confirm_withdraw_amount').value = amount;
        document.getElementById('confirm_withdraw_amount_display').textContent = new Intl.NumberFormat().format(amount);

        bootstrap.Modal.getInstance(document.getElementById('withdrawModal')).hide();
        new bootstrap.Modal(document.getElementById('withdrawConfirmModal')).show();
    });

    // Calculate withdrawal charges in real-time
    document.getElementById('withdraw_amount').addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        if (amount > 0) {
            calculateWithdrawalCharges(amount);
        } else {
            document.getElementById('chargesPreview').style.display = 'none';
        }
    });

    function calculateWithdrawalCharges(amount) {
        // This is a simplified calculation - in a real implementation, 
        // you would fetch the actual charges from the server
        let charges = 0;
        if (amount <= 1000) {
            charges = 50;
        } else if (amount <= 5000) {
            charges = 100;
        } else if (amount <= 10000) {
            charges = 200;
        } else if (amount <= 50000) {
            charges = 500;
        } else {
            charges = 1000;
        }

        const totalAmount = amount + charges;
        const chargesPreview = document.getElementById('chargesPreview');
        const chargesDetails = document.getElementById('chargesDetails');

        chargesDetails.innerHTML = `
            <p><strong>Withdrawal Amount:</strong> FRW ${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
            <p><strong>Charges:</strong> FRW ${charges.toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
            <p><strong>Total to Deduct:</strong> FRW ${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
        `;
        chargesPreview.style.display = 'block';
    }

    // AJAX submit sendConfirmForm
    document.getElementById('sendConfirmForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('sendConfirmModal')).hide();
                alert('Amount sent successfully. Receipt: ' + data.receipt_number);
                fetchBalance();
                document.getElementById('sendForm').reset();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(() => alert('Error processing request.'));
    });

    // AJAX submit withdrawConfirmForm
    document.getElementById('withdrawConfirmForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('withdrawConfirmModal')).hide();
                alert('Withdrawal request submitted successfully!\n\nRequest ID: #' + data.request_id + '\nAmount: FRW ' + new Intl.NumberFormat().format(data.amount) + '\nCharges: FRW ' + new Intl.NumberFormat().format(data.charges) + '\nTotal: FRW ' + new Intl.NumberFormat().format(data.total_amount) + '\n\nStatus: Awaiting admin approval');
                fetchBalance();
                document.getElementById('withdrawForm').reset();
                // Reload page to show updated withdrawal requests
                setTimeout(() => location.reload(), 2000);
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(() => alert('Error processing request.'));
    });

    // Update balance on page function
    function updateBalanceOnPage(newBalance) {
        const balanceCollapse = document.getElementById('balanceCollapse');
        if (balanceCollapse) {
            balanceCollapse.querySelector('h2.display-4').textContent = 'FRW ' + Number(newBalance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    // Update balance max and text inside Send and Withdraw modals
    function updateModalBalances(newBalance) {
        const sendAmountInput = document.getElementById('send_amount');
        const sendBalanceText = sendAmountInput ? sendAmountInput.closest('.modal-body').querySelector('.form-text') : null;

        const withdrawAmountInput = document.getElementById('withdraw_amount');
        const withdrawBalanceText = withdrawAmountInput ? withdrawAmountInput.closest('.modal-body').querySelector('.form-text') : null;

        if (sendAmountInput) {
            sendAmountInput.max = newBalance;
        }
        if (sendBalanceText) {
            sendBalanceText.textContent = 'Available balance: FRW ' + Number(newBalance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        if (withdrawAmountInput) {
            withdrawAmountInput.max = newBalance;
        }
        if (withdrawBalanceText) {
            withdrawBalanceText.textContent = 'Available balance: FRW ' + Number(newBalance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    // Fetch balance via AJAX
    function fetchBalance() {
        fetch('?fetch_balance=1', {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateBalanceOnPage(data.balance);
                updateModalBalances(data.balance);
            }
        })
        .catch(err => console.error('Error fetching balance:', err));
    }
});
</script>


<?php require_once __DIR__ . '/templates/footer.php'; ?>
