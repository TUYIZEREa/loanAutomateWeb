<?php
// Include necessary files
require_once '../includes/database.php';
include '../templates/header.php';

// Function to get total loans issued
function getTotalLoansIssued($conn)
{
    $query = "SELECT COUNT(*) as total FROM loans WHERE status = 'approved'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Function to get total savings
function getTotalSavings($conn)
{
    $query = "SELECT SUM(amount) as total FROM savings";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Function to get total repayments
function getTotalRepayments($conn)
{
    $query = "SELECT SUM(amount) as total FROM loan_repayments";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Function to get number of active borrowers
function getActiveBorrowers($conn)
{
    $query = "SELECT COUNT(*) as total FROM loans WHERE status = 'active'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Function to get number of savers
function getSavers($conn)
{
    $query = "SELECT COUNT(DISTINCT user_id) as total FROM savings";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Function to get total outstanding loan amount
function getOutstandingLoans($conn)
{
    $query = "SELECT SUM(amount) as total FROM loans WHERE status = 'active'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Function to get portfolio at risk (PAR)
function getPortfolioAtRisk($conn)
{
    $query = "SELECT COUNT(*) as total FROM loans WHERE status = 'past_due'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Function to get loan repayment rate
function getLoanRepaymentRate($conn)
{
    $query = "SELECT COUNT(*) as total FROM loans WHERE status = 'repaid'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Function to get operating income
function getOperatingIncome($conn)
{
    $query = "SELECT SUM(amount) as total FROM transactions WHERE type IN ('savings', 'repayment')";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Function to get profit or loss
function getProfitOrLoss($conn)
{
    $income = getOperatingIncome($conn);
    $expenses = getExpenses($conn);
    return $income - $expenses;
}

// Function to get expenses
function getExpenses($conn)
{
    $query = "SELECT SUM(amount) as total FROM transactions WHERE type = 'expense'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Get summary data
$totalLoansIssued = getTotalLoansIssued($conn);
$totalSavings = getTotalSavings($conn);
$totalRepayments = getTotalRepayments($conn);
$activeBorrowers = getActiveBorrowers($conn);
$savers = getSavers($conn);
$outstandingLoans = getOutstandingLoans($conn);
$portfolioAtRisk = getPortfolioAtRisk($conn);
$loanRepaymentRate = getLoanRepaymentRate($conn);
$operatingIncome = getOperatingIncome($conn);
$profitOrLoss = getProfitOrLoss($conn);

// Close the database connection
// $conn->close(); // Removed to prevent double closure

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MFI Summary Report</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .footer {
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 10px;
            position: fixed;
            width: 100%;
            bottom: 0;
        }

        .card {
            margin: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>MFI Summary Report</h1>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-money-bill-wave"></i> Total Loans Issued
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $totalLoansIssued; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-piggy-bank"></i> Total Savings
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $totalSavings; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-hand-holding-usd"></i> Total Repayments
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $totalRepayments; ?></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users"></i> Active Borrowers
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $activeBorrowers; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-plus"></i> Savers
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $savers; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-file-invoice-dollar"></i> Outstanding Loans
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $outstandingLoans; ?></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-exclamation-triangle"></i> Portfolio at Risk
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $portfolioAtRisk; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i> Loan Repayment Rate
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $loanRepaymentRate; ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-dollar-sign"></i> Operating Income
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $operatingIncome; ?></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-balance-scale"></i> Profit or Loss
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $profitOrLoss; ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../templates/footer.php'; ?>
</body>

</html>