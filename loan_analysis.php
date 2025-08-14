<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: login.php');
    exit;
}

// Fetch active loan data
$query = "SELECT u.full_name, SUM(l.amount) as total_loan
          FROM loans l
          JOIN users u ON l.user_id = u.user_id
          WHERE l.status = 'active'
          GROUP BY l.user_id";
$result = Database::getInstance()->query($query);
$activeLabels = [];
$activeData = [];
while ($row = $result->fetch_assoc()) {
    $activeLabels[] = $row['full_name'];
    $activeData[] = $row['total_loan'];
}

// Fetch completed loan data
$query = "SELECT u.full_name, SUM(l.amount) as total_loan
          FROM loans l
          JOIN users u ON l.user_id = u.user_id
          WHERE l.status = 'completed'
          GROUP BY l.user_id";
$result = Database::getInstance()->query($query);
$completedLabels = [];
$completedData = [];
while ($row = $result->fetch_assoc()) {
    $completedLabels[] = $row['full_name'];
    $completedData[] = $row['total_loan'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Loan Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Loan Analysis by User</h2>
        <div class="row">
            <div class="col-md-6 mb-4">
                <h5 class="text-center">Active Loans</h5>
                <div style="max-width:300px;margin:auto;">
                    <canvas id="activeLoanPieChart" width="300" height="300"></canvas>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <h5 class="text-center">Completed Loans</h5>
                <div style="max-width:300px;margin:auto;">
                    <canvas id="completedLoanPieChart" width="300" height="300"></canvas>
                </div>
            </div>
        </div>
        <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
    <script>
        // Active Loans Pie Chart
        const ctxActive = document.getElementById('activeLoanPieChart').getContext('2d');
        const activeLoanPieChart = new Chart(ctxActive, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($activeLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($activeData); ?>,
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d', '#17a2b8',
                        '#6610f2', '#fd7e14', '#20c997', '#e83e8c', '#343a40', '#adb5bd'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Active Loans Distribution by User' }
                }
            }
        });
        // Completed Loans Pie Chart
        const ctxCompleted = document.getElementById('completedLoanPieChart').getContext('2d');
        const completedLoanPieChart = new Chart(ctxCompleted, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($completedLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($completedData); ?>,
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d', '#17a2b8',
                        '#6610f2', '#fd7e14', '#20c997', '#e83e8c', '#343a40', '#adb5bd'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Completed Loans Distribution by User' }
                }
            }
        });
    </script>
</body>
</html> 