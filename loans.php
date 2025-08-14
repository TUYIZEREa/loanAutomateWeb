<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/modules/loans/loan_manager.php';
require_once __DIR__ . '/includes/maintenance_check.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ' . url('login.php'));
    exit;
}

$loanManager = new LoanManager();
$userId = $_SESSION['user_id'];
$user = $auth->getCurrentUser();
$error = '';
$success = '';
$isAdmin = $auth->hasRole('admin');

// Handle loan request (only for non-admin users)
if (!$isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'request') {
        // This is just the initial form submission, no processing needed
        // The actual processing will happen in the confirmation step via JavaScript
        // No PHP processing or redirect here.
    } else if ($_POST['action'] === 'confirm_loan') {
        $amount = $_POST['amount'] ?? 0;
        $purpose = $_POST['purpose'] ?? '';
        $duration = $_POST['duration'] ?? 0;
        $password = $_POST['password'] ?? '';

        if (empty($password)) {
            $error = 'Please enter your password';
        } else if (empty($purpose)) {
            $error = 'Please provide a purpose for the loan';
        } else if ($amount <= 0) {
            $error = 'Invalid loan amount';
        } else if ($duration < 1 || $duration > 36) {
            $error = 'Invalid loan duration';
        } else {
            // Verify password
            $query = "SELECT password FROM users WHERE user_id = ?";
            $stmt = Database::getInstance()->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();

            if (!password_verify($password, $userData['password'])) {
                $error = 'Invalid password';
            } else {
                try {
                    $result = $loanManager->requestLoan($userId, $amount, $purpose, $duration);
                    if ($result['success']) {
                        $success = 'Loan request submitted successfully';
                    } else {
                        $error = $result['error'];
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred while processing your loan application. Please try again.';
                    error_log("Loan application exception: " . $e->getMessage());
                }
            }
        }
    }
}

// Handle loan repayment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'repay') {
    $loanId = $_POST['loan_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;

    if ($amount <= 0) {
        $error = 'Amount must be greater than 0';
    } else {
        $result = $loanManager->makeRepayment($loanId, $amount);
        if ($result['success']) {
            $success = 'Repayment successful';
        } else {
            $error = $result['error'];
        }
    }
}

// Handle loan approval (admin only)
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve') {
    $loanId = $_POST['loan_id'] ?? 0;
    $result = $loanManager->approveLoan($loanId, $_SESSION['user_id']);
    if ($result['success']) {
        $success = 'Loan approved successfully';
    } else {
        $error = $result['error'];
    }
}

// Handle loan rejection (admin only)
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    $loanId = $_POST['loan_id'] ?? 0;
    $result = $loanManager->rejectLoan($loanId);
    if ($result['success']) {
        $success = 'Loan rejected successfully';
    } else {
        $error = $result['error'];
    }
}

// Handle loan cancellation (only for non-admin users with pending loans)
if (!$isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_loan') {
    $loanId = isset($_POST['loan_id']) ? (int)$_POST['loan_id'] : 0;
    if ($loanId > 0) {
        try {
            error_log('[ESS] cancel_loan POST received loan_id=' . $loanId . ' user_id=' . $userId);
            // Verify the loan belongs to the user and is still pending
            $query = "SELECT user_id, status FROM loans WHERE loan_id = ?";
            $stmt = Database::getInstance()->prepare($query);
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
            $result = $stmt->get_result();
            $loan = $result->fetch_assoc();

            if ($loan && $loan['user_id'] == $userId && $loan['status'] === 'pending') {
                // Extra safeguard: verify again before updating
                $verifyStmt = Database::getInstance()->prepare("SELECT status FROM loans WHERE loan_id = ? AND user_id = ?");
                $verifyStmt->bind_param("ii", $loanId, $userId);
                $verifyStmt->execute();
                $verify = $verifyStmt->get_result()->fetch_assoc();
                if (!$verify || $verify['status'] !== 'pending') {
                    $error = 'Loan is not pending or not yours.';
                } else {
                    $result = $loanManager->cancelLoan($loanId, $userId);
                    error_log('[ESS] cancel_loan update attempted for loan_id=' . $loanId . ' result=' . json_encode($result));
                }
                if (isset($result) && $result['success']) {
                    $_SESSION['success'] = 'Loan application cancelled successfully.';
                    header('Location: ' . url('loans.php'));
                    exit;
                } else if (isset($result)) {
                    $error = $result['error'];
                    error_log('[ESS] cancel_loan failed: ' . $error);
                }
                if ($result['success']) {
                    $_SESSION['success'] = 'Loan application cancelled successfully.';
                    header('Location: ' . url('loans.php'));
                    exit;
                } else {
                    $error = $result['error'];
                }
            } else {
                $error = 'Invalid loan or loan cannot be cancelled.';
                error_log('[ESS] cancel_loan invalid: loan not pending or not owned');
            }
        } catch (Exception $e) {
            $error = 'An error occurred while trying to cancel the loan. Please try again.';
            error_log("Loan cancellation exception: " . $e->getMessage());
        }
    } else {
        $error = 'Invalid loan ID.';
    }
}

// Get loans based on user role
if ($isAdmin) {
    // Admin sees all loans
    $query = "SELECT l.*, u.full_name, u.email FROM loans l 
              JOIN users u ON l.user_id = u.user_id 
              ORDER BY l.created_at DESC";
} else {
    // Regular users see only their active or pending loans
    $query = "SELECT * FROM loans WHERE user_id = '$userId' AND status IN ('active', 'pending') ORDER BY created_at DESC";
}

$result = Database::getInstance()->query($query);
$loans = [];
while ($row = $result->fetch_assoc()) {
    $loans[] = $row;
}

// Check for existing pending loans for the current user
$hasPendingLoan = false;
if (!$isAdmin) {
    $query = "SELECT COUNT(*) as pending_count FROM loans WHERE user_id = ? AND status = 'pending'";
    $stmt = Database::getInstance()->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $pendingLoanData = $result->fetch_assoc();
    if ($pendingLoanData['pending_count'] > 0) {
        $hasPendingLoan = true;
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="row">
    <?php if (!$isAdmin): ?>
        <!-- Loan Request Form (only for non-admin users) -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Request a Loan</h5>
                </div>
                <div class="card-body">
                    <?php if ($hasPendingLoan): ?>
                        <div class="alert alert-info">
                            You have a pending loan application. You cannot apply for a new loan until your current application
                            is reviewed.
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" id="loanRequestForm">
                            <input type="hidden" name="action" value="request">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">FRW</span>
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01"
                                        required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose</label>
                                <select class="form-select" id="purpose" name="purpose" required>
                                    <option value="">Select purpose</option>
                                    <option value="Business Investment">Business Investment</option>
                                    <option value="Education">Education</option>
                                    <option value="Home Improvement">Home Improvement</option>
                                    <option value="Medical Expenses">Medical Expenses</option>
                                    <option value="Debt Consolidation">Debt Consolidation</option>
                                    <option value="Emergency Fund">Emergency Fund</option>
                                    <option value="Vehicle Purchase">Vehicle Purchase</option>
                                    <option value="Wedding Expenses">Wedding Expenses</option>
                                    <option value="Agriculture">Agriculture</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3" id="otherPurposeDiv" style="display: none;">
                                <label for="other_purpose" class="form-label">Specify Other Purpose</label>
                                <textarea class="form-control" id="other_purpose" name="other_purpose" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="duration" class="form-label">Duration</label>
                                <select class="form-select" id="duration" name="duration" required>
                                    <option value="">Select duration</option>
                                    <option value="3">3 months</option>
                                    <option value="6">6 months</option>
                                    <option value="12">12 months</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Continue</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Loans List -->
   <div class="<?php echo $isAdmin ? 'col-md-12' : 'col-md-8'; ?>">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><?php echo $isAdmin ? 'All Loans' : 'My Loans'; ?></h5>
        </div>
        <div class="card-body">
            <?php if (empty($loans)): ?>
                <p class="text-muted">No loans found</p>
            <?php else: ?>
                <div class="d-flex" style="overflow-x: auto; gap: 1rem; padding-bottom: 1rem;">
                    <?php foreach ($loans as $loan): ?>
                        <div class="card" style="min-width: 350px; flex: 0 0 auto;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h6 class="card-title mb-0">Loan #<?php echo $loan['loan_id']; ?></h6>
                                    <span class="badge bg-<?php
                                        echo match ($loan['status']) {
                                            'approved' => 'success',
                                            'pending' => 'warning',
                                            'rejected' => 'danger',
                                            'completed' => 'info',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($loan['status']); ?>
                                    </span>
                                </div>

                                <?php if ($isAdmin): ?>
                                    <p class="mb-1">Customer: <?php echo htmlspecialchars($loan['full_name']); ?></p>
                                    <p class="mb-1">Email: <?php echo htmlspecialchars($loan['email']); ?></p>
                                <?php endif; ?>

                                <p class="mb-1">Amount: FRW <?php echo number_format($loan['amount'], 2); ?></p>
                                <?php if ($loan['interest_rate'] > 0): ?>
                                    <p class="mb-1">Interest Rate: <?php echo number_format($loan['interest_rate'], 2); ?>%</p>
                                    <?php 
                                    $interestAmount = ($loan['amount'] * $loan['interest_rate'] / 100 / 12) * $loan['duration'];
                                    $totalAmount = $loan['amount'] + $interestAmount;
                                    ?>
                                    <p class="mb-1">Interest Amount: FRW <?php echo number_format($interestAmount, 2); ?></p>
                                    <p class="mb-1">Total to Repay: FRW <?php echo number_format($totalAmount, 2); ?></p>
                                <?php endif; ?>
                                <p class="mb-1">Purpose: <?php echo htmlspecialchars($loan['purpose']); ?></p>
                                <p class="mb-1">Duration: <?php echo $loan['duration']; ?> months</p>

                                <?php if ($loan['status'] === 'active'): ?>
                                    <?php
                                    $schedule = $loanManager->getRepaymentSchedule($loan['loan_id']);
                                    $totalPaid = 0;
                                    $totalDue = 0;
                                    foreach ($schedule as $payment) {
                                        $totalDue += $payment['amount'];
                                        if ($payment['status'] === 'paid') {
                                            $totalPaid += $payment['amount'];
                                        }
                                    }
                                    ?>
                                    <div class="mt-3">
                                        <div class="progress mb-2">
                                            <div class="progress-bar" role="progressbar"
                                                style="width: <?php echo ($totalDue > 0) ? round(($totalPaid / $totalDue) * 100) : 0; ?>%">
                                                <?php echo ($totalDue > 0) ? round(($totalPaid / $totalDue) * 100) : 0; ?>%
                                            </div>
                                        </div>
                                        <p class="mb-1">
                                            Paid: FRW 
                                            <span id="loan_paid_<?php echo $loan['loan_id']; ?>" class="collapse">
                                                <?php echo number_format($totalPaid, 2); ?>
                                            </span>
                                            <button class="btn btn-sm btn-light toggle-amount" type="button"
                                                aria-expanded="false"
                                                aria-controls="loan_paid_<?php echo $loan['loan_id']; ?>"
                                                title="Toggle Paid Amount Visibility">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </p>
                                        <p class="mb-1">
                                            Remaining: FRW 
                                            <span id="loan_remaining_<?php echo $loan['loan_id']; ?>" class="collapse">
                                                <?php echo number_format($totalDue - $totalPaid, 2); ?>
                                            </span>
                                            <button class="btn btn-sm btn-light toggle-amount" type="button"
                                                aria-expanded="false"
                                                aria-controls="loan_remaining_<?php echo $loan['loan_id']; ?>"
                                                title="Toggle Remaining Amount Visibility">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </p>

                                        <?php if (!$isAdmin): ?>
                                            <form method="POST" action="" class="mt-3">
                                                <input type="hidden" name="action" value="repay">
                                                <input type="hidden" name="loan_id" value="<?php echo $loan['loan_id']; ?>">
                                                <div class="input-group">
                                                    <span class="input-group-text">FRW</span>
                                                    <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                                                    <button type="submit" class="btn btn-success">Repay</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!$isAdmin && $loan['status'] === 'pending'): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-danger w-100 cancel-loan-btn" type="button"
                                            data-bs-toggle="modal" data-bs-target="#cancelLoanConfirmModal"
                                            data-loan-id="<?php echo $loan['loan_id']; ?>">
                                            Cancel Loan
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <?php if ($isAdmin && $loan['status'] === 'pending'): ?>
                                    <div class="mt-3">
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="loan_id" value="<?php echo $loan['loan_id']; ?>">
                                            <button type="submit" class="btn btn-success">Approve</button>
                                        </form>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="loan_id" value="<?php echo $loan['loan_id']; ?>">
                                            <button type="submit" class="btn btn-danger">Reject</button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <?php if ($loan['status'] === 'active'): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-info w-100" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#schedule<?php echo $loan['loan_id']; ?>">
                                            View Repayment Schedule
                                        </button>
                                        <div class="collapse mt-2" id="schedule<?php echo $loan['loan_id']; ?>">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Due Date</th>
                                                            <th>Amount</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($schedule as $index => $payment): ?>
                                                            <?php 
                                                                $paymentId = isset($payment['id']) ? $payment['id'] : $index;
                                                            ?>
                                                            <tr>
                                                                <td><?php echo date('M d, Y', strtotime($payment['due_date'])); ?></td>
                                                                <td>
                                                                    FRW 
                                                                    <span id="repayment_amount_<?php echo $loan['loan_id']; ?>_<?php echo $paymentId; ?>"
                                                                        class="collapse"
                                                                        title="Toggle Repayment Amount Visibility">
                                                                        <?php echo number_format($payment['amount'], 2); ?>
                                                                    </span>
                                                                    <button class="btn btn-sm btn-light toggle-amount" type="button"
                                                                        aria-expanded="false"
                                                                        aria-controls="repayment_amount_<?php echo $loan['loan_id']; ?>_<?php echo $paymentId; ?>"
                                                                        title="Toggle Repayment Amount Visibility">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo $payment['status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                                        <?php echo ucfirst($payment['status']); ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Loan Confirmation Modal -->
<div class="modal fade" id="loanConfirmModal" tabindex="-1" aria-labelledby="loanConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo url('loans.php'); ?>" id="cancelLoanForm">
                <input type="hidden" name="action" value="confirm_loan">
                <input type="hidden" name="amount" id="confirm_loan_amount">
                <input type="hidden" name="purpose" id="confirm_loan_purpose">
                <input type="hidden" name="duration" id="confirm_loan_duration">
                <div class="modal-header">
                    <h5 class="modal-title" id="loanConfirmModalLabel">Confirm Loan Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please confirm the details of your loan request:</p>
                    <div class="mb-3">
                        <strong>Amount:</strong> FRW <span id="display_confirm_loan_amount"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Purpose:</strong> <span id="display_confirm_loan_purpose"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Duration:</strong> <span id="display_confirm_loan_duration"></span> months
                    </div>
                    <div class="mb-3">
                        <label for="loan_password" class="form-label">Enter Your Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="loan_password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleLoanPasswordVisibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php /* footer moved to end to ensure modals and scripts are within body */ ?>

<!-- Cancel Loan Confirmation Modal -->
<div class="modal fade" id="cancelLoanConfirmModal" tabindex="-1" aria-labelledby="cancelLoanConfirmModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Added an ID to the form so button can link to it -->
            <form id="cancelLoanForm" method="POST" action="">
                <!-- Hidden fields -->
                <input type="hidden" name="action" value="cancel_loan">
                <input type="hidden" name="loan_id" id="confirm_cancel_loan_id">

                <div class="modal-header">
                    <h5 class="modal-title" id="cancelLoanConfirmModalLabel">Confirm Loan Cancellation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p>Are you sure you want to cancel this loan application?</p>
                    <p>Loan ID: <strong id="display_cancel_loan_id"></strong></p>
                    <div class="alert alert-warning" role="alert">
                        This action cannot be undone.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Loan</button>
                    <!-- This button now submits the correct form -->
                    <button type="submit" id="confirmCancelBtn" class="btn btn-danger">Yes, Cancel Loan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script to pass loan ID into modal before showing
    document.querySelectorAll('.cancel-loan-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            let loanId = this.getAttribute('data-loan-id');
            document.getElementById('confirm_cancel_loan_id').value = loanId;
            document.getElementById('display_cancel_loan_id').textContent = loanId;
        });
    });
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const loanRequestForm = document.getElementById('loanRequestForm');
        const loanConfirmModal = new bootstrap.Modal(document.getElementById('loanConfirmModal'));
        const purposeSelect = document.getElementById('purpose');
        const otherPurposeDiv = document.getElementById('otherPurposeDiv');
        const otherPurposeInput = document.getElementById('other_purpose');

        // Handle purpose selection
        purposeSelect.addEventListener('change', function () {
            if (this.value === 'Other') {
                otherPurposeDiv.style.display = 'block';
                otherPurposeInput.setAttribute('required', 'required');
            } else {
                otherPurposeDiv.style.display = 'none';
                otherPurposeInput.removeAttribute('required');
            }
        });

        if (loanRequestForm) {
            loanRequestForm.addEventListener('submit', function (event) {
                event.preventDefault();

                const amount = document.getElementById('amount').value;
                const purpose = purposeSelect.value;
                const duration = document.getElementById('duration').value;
                let finalPurpose = purpose;

                // If "Other" is selected, use the textarea value
                if (purpose === 'Other') {
                    finalPurpose = otherPurposeInput.value.trim();
                    if (!finalPurpose) {
                        alert('Please specify the other purpose.');
                        return;
                    }
                }

                // Basic client-side validation
                if (!amount || !purpose || !duration) {
                    alert('Please fill in all fields.');
                    return;
                }
                if (parseFloat(amount) <= 0) {
                    alert('Amount must be greater than 0.');
                    return;
                }

                // Populate confirmation modal
                document.getElementById('confirm_loan_amount').value = amount;
                document.getElementById('confirm_loan_purpose').value = finalPurpose;
                document.getElementById('confirm_loan_duration').value = duration;
                document.getElementById('display_confirm_loan_amount').textContent = new Intl.NumberFormat().format(amount);
                document.getElementById('display_confirm_loan_purpose').textContent = finalPurpose;
                document.getElementById('display_confirm_loan_duration').textContent = duration;

                // Clear password field for security
                document.getElementById('loan_password').value = '';

                loanConfirmModal.show();
            });
        }

        // Toggle password visibility
        document.addEventListener('click', function (event) {
            const toggleButton = event.target.closest('#toggleLoanPasswordVisibility');
            if (toggleButton) {
                console.log('Toggle button clicked!');
                const loanPasswordInput = document.getElementById('loan_password');
                if (loanPasswordInput) {
                    const type = loanPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    loanPasswordInput.setAttribute('type', type);
                    toggleButton.querySelector('i').classList.toggle('fa-eye');
                    toggleButton.querySelector('i').classList.toggle('fa-eye-slash');
                }
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const amountToggles = document.querySelectorAll('.toggle-amount');

        amountToggles.forEach(toggle => {
            const icon = toggle.querySelector('i');
            const targetId = toggle.getAttribute('aria-controls');
            const targetElement = document.getElementById(targetId);

            // Ensure the element is hidden and set initial icon state on load
            if (targetElement) {
                targetElement.classList.remove('show'); // Ensure it's hidden
                toggle.setAttribute('aria-expanded', 'false');
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }

            // Handle click event for manual toggling
            toggle.addEventListener('click', function (event) {
                event.preventDefault(); // Prevent default Bootstrap behavior

                if (targetElement.classList.contains('show')) {
                    targetElement.classList.remove('show');
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    toggle.setAttribute('aria-expanded', 'false');
                } else {
                    targetElement.classList.add('show');
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // JavaScript for Cancel Loan Confirmation Modal
        const cancelLoanButtons = document.querySelectorAll('.cancel-loan-btn');
        const cancelLoanConfirmModal = new bootstrap.Modal(document.getElementById('cancelLoanConfirmModal'));
        const confirmCancelLoanIdInput = document.getElementById('confirm_cancel_loan_id');
        const displayCancelLoanId = document.getElementById('display_cancel_loan_id');

        cancelLoanButtons.forEach(button => {
            button.addEventListener('click', function () {
                const loanId = this.getAttribute('data-loan-id');
                confirmCancelLoanIdInput.value = loanId;
                displayCancelLoanId.textContent = loanId;
                cancelLoanConfirmModal.show();
            });
        });

        const confirmCancelBtn = document.getElementById('confirmCancelBtn');
        const cancelForm = document.getElementById('cancelLoanForm');
        if (confirmCancelBtn && cancelForm) {
            confirmCancelBtn.addEventListener('click', function () {
                // Explicitly submit to avoid any interference
                cancelForm.submit();
            });
        }
    });
</script>