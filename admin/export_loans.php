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

// Get loan data
$query = "SELECT 
            l.*,
            u.full_name as customer_name,
            u.email as customer_email,
            u.phone_number as customer_phone,
            a.full_name as approved_by_name,
            r.full_name as rejected_by_name
          FROM loans l
          JOIN users u ON l.user_id = u.user_id
          LEFT JOIN users a ON l.approved_by = a.user_id
          LEFT JOIN users r ON l.rejected_by = r.user_id
          WHERE l.created_at BETWEEN ? AND ?
          ORDER BY l.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="loans_report_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Loan ID',
    'Customer Name',
    'Customer Email',
    'Customer Phone',
    'Amount',
    'Purpose',
    'Duration (months)',
    'Interest Rate',
    'Status',
    'Created Date',
    'Approved By',
    'Approved Date',
    'Rejected By',
    'Rejected Date',
    'Amount Paid',
    'Last Payment Date',
    'Risk Score'
]);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['loan_id'],
        $row['customer_name'],
        $row['customer_email'],
        $row['customer_phone'],
        number_format($row['amount'], 2),
        $row['purpose'],
        $row['duration'],
        number_format($row['interest_rate'], 2) . '%',
        ucfirst($row['status']),
        date('Y-m-d H:i:s', strtotime($row['created_at'])),
        $row['approved_by_name'] ?? 'N/A',
        $row['approved_at'] ? date('Y-m-d H:i:s', strtotime($row['approved_at'])) : 'N/A',
        $row['rejected_by_name'] ?? 'N/A',
        $row['rejected_at'] ? date('Y-m-d H:i:s', strtotime($row['rejected_at'])) : 'N/A',
        number_format($row['amount_paid'], 2),
        $row['last_payment_date'] ? date('Y-m-d H:i:s', strtotime($row['last_payment_date'])) : 'N/A',
        $row['risk_score']
    ]);
}

// Close the output stream
fclose($output);
exit; 