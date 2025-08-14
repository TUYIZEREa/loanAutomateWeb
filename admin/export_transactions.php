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

// Get transaction data
$query = "SELECT 
            t.*,
            u.full_name as customer_name,
            u.email as customer_email,
            u.phone_number as customer_phone,
            l.loan_id,
            l.purpose as loan_purpose
          FROM transactions t
          JOIN users u ON t.user_id = u.user_id
          LEFT JOIN loans l ON t.loan_id = l.loan_id
          WHERE t.transaction_date BETWEEN ? AND ?
          ORDER BY t.transaction_date DESC";
$stmt = $db->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transactions_report_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Transaction ID',
    'Date',
    'Customer Name',
    'Customer Email',
    'Customer Phone',
    'Type',
    'Amount',
    'Status',
    'Reference Number',
    'Payment Method',
    'Loan ID',
    'Loan Purpose',
    'Notes'
]);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['transaction_id'],
        date('Y-m-d H:i:s', strtotime($row['transaction_date'])),
        $row['customer_name'],
        $row['customer_email'],
        $row['customer_phone'],
        ucfirst($row['type']),
        number_format($row['amount'], 2),
        ucfirst($row['status']),
        $row['reference_number'],
        ucfirst($row['payment_method']),
        $row['loan_id'] ?? 'N/A',
        $row['loan_purpose'] ?? 'N/A',
        $row['notes'] ?? ''
    ]);
}

// Close the output stream
fclose($output);
exit; 