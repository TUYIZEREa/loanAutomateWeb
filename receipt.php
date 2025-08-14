<?php
require_once 'includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// Get transaction ID from URL
$transaction_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$transaction_id) {
    header('Location: dashboard.php');
    exit;
}

// Get transaction details
$query = "SELECT 
            t.*,
            u.full_name,
            u.email,
            u.phone_number,
            l.loan_id,
            l.purpose as loan_purpose
          FROM transactions t
          JOIN users u ON t.user_id = u.user_id
          LEFT JOIN loans l ON t.loan_id = l.loan_id
          WHERE t.transaction_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $transaction_id);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();

// Check if transaction exists and user has access
if (!$transaction || ($transaction['user_id'] !== $_SESSION['user_id'] && !$auth->hasRole('admin'))) {
    header('Location: dashboard.php');
    exit;
}

// Get company details from settings
$query = "SELECT * FROM settings WHERE setting_key IN ('company_name', 'company_address', 'company_phone', 'company_email')";
$result = $db->query($query);
$settings = [];
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require_once 'templates/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body p-4">
                    <!-- Receipt Header -->
                    <div class="text-center mb-4">
                        <h2 class="mb-0">
                            <?php echo htmlspecialchars($settings['company_name'] ?? 'Loan Automate'); ?>
                        </h2>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($settings['company_address'] ?? ''); ?>
                        </p>
                        <p class="text-muted mb-0">
                            Tel: <?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?><br>
                            Email: <?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>
                        </p>
                    </div>

                    <hr class="my-4">

                    <!-- Receipt Details -->
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="mb-3">Receipt To:</h6>
                            <div>
                                <strong><?php echo htmlspecialchars($transaction['full_name']); ?></strong>
                            </div>
                            <div><?php echo htmlspecialchars($transaction['email']); ?></div>
                            <div><?php echo htmlspecialchars($transaction['phone_number']); ?></div>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <h6 class="mb-3">Receipt Details:</h6>
                            <div>
                                <strong>Receipt #:</strong> <?php echo $transaction['reference_number']; ?>
                            </div>
                            <div>
                                <strong>Date:</strong>
                                <?php echo date('F d, Y H:i', strtotime($transaction['transaction_date'])); ?>
                            </div>
                            <div>
                                <strong>Status:</strong>
                                <span
                                    class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Details -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <?php
                                        $description = ucfirst($transaction['type']);
                                        if ($transaction['loan_id']) {
                                            $description .= " - Loan #" . $transaction['loan_id'];
                                            if ($transaction['loan_purpose']) {
                                                $description .= " (" . $transaction['loan_purpose'] . ")";
                                            }
                                        }
                                        echo htmlspecialchars($description);
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <div id="transactionAmountCollapse" class="collapse">
                                            FRW <?php echo number_format($transaction['amount'], 2); ?>
                                        </div>
                                        <button class="btn btn-sm btn-light toggle-amount" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#transactionAmountCollapse"
                                            aria-expanded="false" aria-controls="transactionAmountCollapse"
                                            title="Toggle Amount Visibility">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end"><strong>Total</strong></td>
                                    <td class="text-end">
                                        <div id="totalAmountCollapse" class="collapse">
                                            <strong>FRW <?php echo number_format($transaction['amount'], 2); ?></strong>
                                        </div>
                                        <button class="btn btn-sm btn-light toggle-amount" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#totalAmountCollapse"
                                            aria-expanded="false" aria-controls="totalAmountCollapse"
                                            title="Toggle Total Visibility">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($transaction['notes']): ?>
                        <div class="mb-4">
                            <strong>Notes:</strong>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($transaction['notes'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <p class="text-muted mb-0">Payment Method:
                            <?php echo ucfirst($transaction['payment_method']); ?>
                        </p>
                        <p class="text-muted mb-4">This is a computer-generated receipt. No signature is required.</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-center gap-2">
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Print Receipt
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {

        .btn,
        .navbar,
        footer {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle eye icons for amount visibility
        const amountToggles = document.querySelectorAll('.toggle-amount');
        amountToggles.forEach(toggle => {
            toggle.addEventListener('click', function () {
                const icon = this.querySelector('i');
                if (icon.classList.contains('fa-eye')) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
</script>

<?php require_once 'templates/footer.php'; ?>