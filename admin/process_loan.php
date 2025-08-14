<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/utilities.php';
require_once __DIR__ . '/../modules/loans/loan_manager.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ' . url('login.php'));
    exit;
}

$loanManager = new LoanManager();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $loanId = $_POST['loan_id'] ?? 0;

    if ($action === 'approve' && $loanId > 0) {
        $result = $loanManager->approveLoan($loanId, $_SESSION['user_id']);
        if ($result['success']) {
            $success = 'Loan approved successfully! Principal: ' . number_format($result['principal'], 2) . ' FRW, Interest: ' . number_format($result['interest_amount'], 2) . ' FRW, Total: ' . number_format($result['total_amount'], 2) . ' FRW';
        } else {
            $error = $result['error'];
        }
    } elseif ($action === 'reject' && $loanId > 0) {
        $result = $loanManager->rejectLoan($loanId);
        if ($result['success']) {
            $success = 'Loan rejected successfully';
        } else {
            $error = $result['error'];
        }
    }
}

// Redirect back to loans page
if ($success) {
    $_SESSION['success'] = $success;
} elseif ($error) {
    $_SESSION['error'] = $error;
}

header('Location: ' . url('loans.php'));
exit; 
?> 