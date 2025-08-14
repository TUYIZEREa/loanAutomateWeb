<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'modules/loans/loan_manager.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
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

// Get user's loan history
$query = "SELECT * FROM loans WHERE user_id = ? ORDER BY created_at DESC";
$stmt = Database::getInstance()->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$loanHistory = [];
while ($row = $result->fetch_assoc()) {
    $loanHistory[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Loan - ESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php require_once 'templates/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <?php if (!$isAdmin): ?>
                <!-- Loan Request Form (only for non-admin users) -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Request a Loan</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                            <?php endif; ?>

                            <form method="POST" action="" id="loanForm">
                                <input type="hidden" name="action" value="request">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">FRW</span>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01"
                                            min="0.01" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="purpose" class="form-label">Purpose</label>
                                    <textarea class="form-control" id="purpose" name="purpose" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (months)</label>
                                    <input type="number" class="form-control" id="duration" name="duration" min="1" max="36"
                                        required>
                                </div>
                                
                                <!-- Interest Information Display -->
                                <div class="alert alert-info" id="interestInfo" style="display: none;">
                                    <h6>Loan Interest Information:</h6>
                                    <div id="interestDetails"></div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">Continue</button>
                            </form>
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
                        <?php if (empty($loanHistory)): ?>
                            <p class="text-muted">No loans found</p>
                        <?php else: ?>
                            <div class="d-flex" style="overflow-x: auto; gap: 1rem; padding-bottom: 1rem;">
                                <?php foreach ($loanHistory as $loan): ?>
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
                                                    <p class="mb-1">Paid: FRW <?php echo number_format($totalPaid, 2); ?></p>
                                                    <p class="mb-1">Remaining: FRW
                                                        <?php echo number_format($totalDue - $totalPaid, 2); ?>
                                                    </p>

                                                    <?php if (!$isAdmin): ?>
                                                        <form method="POST" action="" class="mt-3">
                                                            <input type="hidden" name="action" value="repay">
                                                            <input type="hidden" name="loan_id" value="<?php echo $loan['loan_id']; ?>">
                                                            <div class="input-group">
                                                                <span class="input-group-text">FRW</span>
                                                                <input type="number" class="form-control" name="amount" step="0.01"
                                                                    min="0.01" required>
                                                                <button type="submit" class="btn btn-success">Repay</button>
                                                            </div>
                                                        </form>
                                                    <?php endif; ?>
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
                                                    <button class="btn btn-sm btn-info w-100" type="button"
                                                        data-bs-toggle="collapse"
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
                                                                    <?php foreach ($schedule as $payment): ?>
                                                                        <tr>
                                                                            <td><?php echo date('M d, Y', strtotime($payment['due_date'])); ?>
                                                                            </td>
                                                                            <td>FRW <?php echo number_format($payment['amount'], 2); ?>
                                                                            </td>
                                                                            <td>
                                                                                <span
                                                                                    class="badge bg-<?php echo $payment['status'] === 'paid' ? 'success' : 'warning'; ?>">
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
        </div>
    </div>

    <!-- Loan Confirmation Modal -->
    <div class="modal fade" id="loanConfirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="confirm_loan">
                    <input type="hidden" name="amount" id="confirm_loan_amount">
                    <input type="hidden" name="duration" id="confirm_loan_duration">
                    <input type="hidden" name="purpose" id="confirm_loan_purpose">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Loan Application</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Please confirm the following loan application:</p>
                        <div class="mb-3">
                            <strong>Loan Amount:</strong> FRW <span id="confirm_loan_amount_display"></span>
                        </div>
                        <div class="mb-3">
                            <strong>Purpose:</strong> <span id="confirm_loan_purpose_display"></span>
                        </div>
                        <div class="mb-3">
                            <strong>Duration:</strong> <span id="confirm_loan_duration_display"></span> months
                        </div>
                        <div class="mb-3">
                            <label for="loan_password" class="form-label">Enter Your Password</label>
                            <input type="password" class="form-control" id="loan_password" name="password" required
                                placeholder="Enter your password to confirm">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate and display interest information
        function calculateInterest() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const duration = parseInt(document.getElementById('duration').value) || 0;
            const interestRate = 10; // Default interest rate from settings
            
            if (amount > 0 && duration > 0) {
                const monthlyRate = interestRate / 100 / 12;
                const interestAmount = (amount * monthlyRate * duration);
                const totalAmount = amount + interestAmount;
                const monthlyPayment = totalAmount / duration;
                
                document.getElementById('interestDetails').innerHTML = `
                    <p><strong>Principal Amount:</strong> FRW ` + amount.toLocaleString('en-US', {minimumFractionDigits: 2}) + `</p>
                    <p><strong>Interest Rate:</strong> ` + interestRate + `% per year</p>
                    <p><strong>Interest Amount:</strong> FRW ` + interestAmount.toLocaleString('en-US', {minimumFractionDigits: 2}) + `</p>
                    <p><strong>Total to Repay:</strong> FRW ` + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2}) + `</p>
                    <p><strong>Monthly Payment:</strong> FRW ` + monthlyPayment.toLocaleString('en-US', {minimumFractionDigits: 2}) + `</p>
                `;
                document.getElementById('interestInfo').style.display = 'block';
            } else {
                document.getElementById('interestInfo').style.display = 'none';
            }
        }
        
        // Add event listeners
        document.getElementById('amount').addEventListener('input', calculateInterest);
        document.getElementById('duration').addEventListener('input', calculateInterest);
    </script>
<?php require_once 'templates/footer.php'; ?>