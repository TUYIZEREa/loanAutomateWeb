<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/utilities.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../modules/savings/savings_manager.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getCurrentUser()['role'] !== 'admin') {
    header('Location: ' . url('login.php'));
    exit;
}

$savingsManager = new SavingsManager();
$error = '';
$success = '';

// Get all customers
$query = "SELECT user_id, full_name, phone_number, account_number FROM users WHERE role = 'customer' ORDER BY full_name";
$result = Database::getInstance()->query($query);
$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

// Handle deposit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'confirm_deposit') {
        $amount = $_POST['amount'] ?? 0;
        $accountNumber = $_POST['account_number'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($password)) {
            $error = 'Please enter your password';
        } else {
            // Verify password
            $query = "SELECT password FROM users WHERE user_id = ?";
            $stmt = Database::getInstance()->prepare($query);
            $stmt->bind_param("s", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();

            if (!password_verify($password, $userData['password'])) {
                $error = 'Invalid password';
            } else {
                try {
                    $result = $savingsManager->deposit($accountNumber, $amount);
                    if ($result['success']) {
                        $success = 'Deposit successful. Receipt: ' . $result['receipt_number'];
                    } else {
                        $error = $result['error'];
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred while processing your deposit. Please try again.';
                    error_log("Deposit exception: " . $e->getMessage());
                }
            }
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
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

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Make Customer Deposit</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="customer_account" class="form-label">Select Customer</label>
                        <select class="form-select" id="customer_account" name="customer_account" required>
                            <option value="">Select a customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo htmlspecialchars($customer['account_number']); ?>">
                                    <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['account_number'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Please select a customer
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">FRW</span>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01"
                                required>
                        </div>
                        <div class="invalid-feedback">
                            Please enter a valid amount
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                            placeholder="Enter any additional details about this deposit"></textarea>
                    </div>

                    <div class="col-12">
                        <label for="password" class="form-label">Enter Your Password</label>
                        <input type="password" class="form-control" id="password" name="password" required
                            placeholder="Enter your password to confirm">
                        <div class="invalid-feedback">
                            Please enter your password
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Process Deposit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Deposit Modal -->
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="depositForm">
                <input type="hidden" name="action" value="deposit">
                <div class="modal-header">
                    <h5 class="modal-title">Deposit Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="account_number" class="form-label">Select Account</label>
                        <select class="form-select" id="account_number" name="account_number" required>
                            <option value="">Select an account</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo htmlspecialchars($customer['account_number']); ?>">
                                    <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['account_number'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="deposit_amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">FRW</span>
                            <input type="number" class="form-control" id="deposit_amount" name="amount" step="0.01"
                                min="0.01" required>
                        </div>
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

<!-- Deposit Confirmation Modal -->
<div class="modal fade" id="depositConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="confirm_deposit">
                <input type="hidden" name="amount" id="confirm_deposit_amount">
                <input type="hidden" name="account_number" id="confirm_account_number">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deposit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Please confirm the following transaction:</p>
                    <div class="mb-3">
                        <strong>Amount:</strong> FRW <span id="confirm_deposit_amount_display"></span>
                    </div>
                    <div class="mb-3">
                        <strong>To Account:</strong> <span id="confirm_account_display"></span>
                    </div>
                    <div class="mb-3">
                        <label for="deposit_password" class="form-label">Enter Your Password</label>
                        <input type="password" class="form-control" id="deposit_password" name="password" required
                               placeholder="Enter your password to confirm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Deposit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()

    document.addEventListener('DOMContentLoaded', function() {
        // Handle deposit form submission
        document.getElementById('depositForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const amount = document.getElementById('deposit_amount').value;
            const accountSelect = document.getElementById('account_number');
            const accountText = accountSelect.options[accountSelect.selectedIndex].text;
            
            // Set values in confirmation modal
            document.getElementById('confirm_deposit_amount').value = amount;
            document.getElementById('confirm_account_number').value = accountSelect.value;
            document.getElementById('confirm_deposit_amount_display').textContent = new Intl.NumberFormat().format(amount);
            document.getElementById('confirm_account_display').textContent = accountText;
            
            // Hide first modal and show confirmation modal
            bootstrap.Modal.getInstance(document.getElementById('depositModal')).hide();
            new bootstrap.Modal(document.getElementById('depositConfirmModal')).show();
        });
    });
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>