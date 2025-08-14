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

// Get user data with statistics
$query = "SELECT 
            u.*,
            COUNT(DISTINCT l.loan_id) as total_loans,
            SUM(CASE WHEN l.status = 'active' THEN l.amount ELSE 0 END) as active_loan_amount,
            SUM(CASE WHEN l.status = 'completed' THEN l.amount ELSE 0 END) as completed_loan_amount,
            COUNT(DISTINCT t.transaction_id) as total_transactions,
            SUM(CASE WHEN t.type = 'savings' THEN t.amount ELSE 0 END) as total_savings,
            SUM(CASE WHEN t.type = 'repayment' THEN t.amount ELSE 0 END) as total_repayments,
            MAX(l.created_at) as last_loan_date,
            MAX(t.transaction_date) as last_transaction_date
          FROM users u
          LEFT JOIN loans l ON u.user_id = l.user_id
          LEFT JOIN transactions t ON u.user_id = t.user_id
          WHERE u.created_at BETWEEN ? AND ?
          GROUP BY u.user_id
          ORDER BY u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users_report_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'User ID',
    'Full Name',
    'Email',
    'Phone Number',
    'Role',
    'Status',
    'Registration Date',
    'Total Loans',
    'Active Loan Amount',
    'Completed Loan Amount',
    'Total Transactions',
    'Total Savings',
    'Total Repayments',
    'Last Loan Date',
    'Last Transaction Date',
    'Address',
    'City',
    'State',
    'Country',
    'Postal Code'
]);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['user_id'],
        $row['full_name'],
        $row['email'],
        $row['phone_number'],
        ucfirst($row['role']),
        ucfirst($row['status']),
        date('Y-m-d H:i:s', strtotime($row['created_at'])),
        $row['total_loans'],
        number_format($row['active_loan_amount'] ?? 0, 2),
        number_format($row['completed_loan_amount'] ?? 0, 2),
        $row['total_transactions'],
        number_format($row['total_savings'] ?? 0, 2),
        number_format($row['total_repayments'] ?? 0, 2),
        $row['last_loan_date'] ? date('Y-m-d H:i:s', strtotime($row['last_loan_date'])) : 'N/A',
        $row['last_transaction_date'] ? date('Y-m-d H:i:s', strtotime($row['last_transaction_date'])) : 'N/A',
        $row['address'] ?? '',
        $row['city'] ?? '',
        $row['state'] ?? '',
        $row['country'] ?? '',
        $row['postal_code'] ?? ''
    ]);
}

// Close the output stream
fclose($output);
exit; 