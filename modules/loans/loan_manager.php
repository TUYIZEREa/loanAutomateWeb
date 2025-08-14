<?php
require_once __DIR__ . '/../../includes/database.php';

class LoanManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get default interest rate from settings
     */
    private function getDefaultInterestRate()
    {
        $query = "SELECT setting_value FROM settings WHERE setting_key = 'default_interest_rate'";
        $result = $this->db->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            return floatval($row['setting_value']);
        }
        return 10.0; // Default fallback
    }

    /**
     * Calculate interest amount for a loan
     */
    private function calculateInterest($principal, $interestRate, $durationMonths)
    {
        // Simple interest calculation: Principal × Rate × Time
        // Convert annual rate to monthly and calculate total interest
        $monthlyRate = $interestRate / 100 / 12;
        $totalInterest = $principal * $monthlyRate * $durationMonths;
        return round($totalInterest, 2);
    }

    public function requestLoan($userId, $amount, $purpose, $duration)
    {
        try {
            $userId = intval($userId);
            $amount = floatval($amount);
            $purpose = $this->db->escape($purpose);
            $duration = intval($duration);

            // Validate input
            if ($amount <= 0 || empty($purpose) || $duration <= 0) {
                return ['success' => false, 'error' => 'Invalid input parameters'];
            }

            // Check if user has any pending loans
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM loans WHERE user_id = ? AND status = 'active'");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['count'] > 0) {
                return ['success' => false, 'error' => 'You already have a un paid loan'];
            }

            // Get default interest rate and calculate interest
            $interestRate = $this->getDefaultInterestRate();
            $interestAmount = $this->calculateInterest($amount, $interestRate, $duration);

            // Insert loan request with interest rate
            $stmt = $this->db->prepare("INSERT INTO loans (user_id, amount, purpose, duration, interest_rate, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param('idsdi', $userId, $amount, $purpose, $duration, $interestRate);
            $result = $stmt->execute();

            if (!$result) {
                throw new Exception("Failed to create loan request");
            }

            return [
                'success' => true,
                'interest_rate' => $interestRate,
                'interest_amount' => $interestAmount,
                'total_amount' => $amount + $interestAmount
            ];
        } catch (Exception $e) {
            error_log("Error in requestLoan: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function approveLoan($loanId, $adminId)
    {
        try {
            $loanId = intval($loanId);
            $adminId = intval($adminId);

            // Get loan details
            $stmt = $this->db->prepare("SELECT l.*, u.account_number FROM loans l JOIN users u ON l.user_id = u.user_id WHERE l.loan_id = ? AND l.status = 'pending'");
            $stmt->bind_param('i', $loanId);
            $stmt->execute();
            $result = $stmt->get_result();
            $loan = $result->fetch_assoc();

            if (!$loan) {
                return ['success' => false, 'error' => 'Loan not found or not pending'];
            }

            // Calculate total amount including interest
            $principal = floatval($loan['amount']);
            $interestRate = floatval($loan['interest_rate']);
            $duration = intval($loan['duration']);
            $interestAmount = $this->calculateInterest($principal, $interestRate, $duration);
            $totalAmount = $principal + $interestAmount;

            // Get admin's current balance
            $stmt = $this->db->prepare("SELECT amount FROM users WHERE user_id = ? AND role = 'admin'");
            $stmt->bind_param('i', $adminId);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            if (!$admin) {
                return ['success' => false, 'error' => 'Admin not found'];
            }
            $adminBalance = floatval($admin['amount']);
            if ($adminBalance < $totalAmount) {
                return ['success' => false, 'error' => 'Insufficient admin balance to approve this loan. Required: ' . number_format($totalAmount, 2) . ' FRW (Principal: ' . number_format($principal, 2) . ' + Interest: ' . number_format($interestAmount, 2) . ')'];
            }

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Update loan status
                $stmt = $this->db->prepare("UPDATE loans SET status = 'active', approved_at = NOW(), approved_by = ? WHERE loan_id = ?");
                $stmt->bind_param('ii', $adminId, $loanId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update loan status");
                }

                // Deduct total amount (principal + interest) from admin's balance
                $newAdminBalance = $adminBalance - $totalAmount;
                $stmt = $this->db->prepare("UPDATE users SET amount = ? WHERE user_id = ?");
                $stmt->bind_param('di', $newAdminBalance, $adminId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to deduct from admin's balance");
                }

                // Add principal amount to user's balance (interest stays with admin)
                $stmt = $this->db->prepare("UPDATE users SET amount = amount + ? WHERE user_id = ?");
                $stmt->bind_param('di', $principal, $loan['user_id']);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update user's balance");
                }

                // Create deposit record in savings for principal only
                $receiptNumber = 'LOAN' . date('YmdHis') . rand(1000, 9999);
                $stmt = $this->db->prepare("INSERT INTO savings (user_id, account_number, amount, transaction_type, status, receipt_number, transaction_date) VALUES (?, ?, ?, 'deposit', 'completed', ?, NOW())");
                $stmt->bind_param('isds', $loan['user_id'], $loan['account_number'], $principal, $receiptNumber);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create deposit record");
                }

                // Create transaction record for user (principal only)
                $transactionRef = $this->generateUniqueReferenceNumber();
                $stmt = $this->db->prepare("INSERT INTO transactions (user_id, account_number, type, amount, status, reference_number, payment_method, transaction_date) VALUES (?, ?, 'loan', ?, 'completed', ?, 'loan', NOW())");
                $stmt->bind_param('isds', $loan['user_id'], $loan['account_number'], $principal, $transactionRef);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create loan transaction record for user");
                }

                // Create transaction record for admin (deduction of total amount)
                $adminTransactionRef = $this->generateUniqueReferenceNumber();
                $negativeTotalAmount = -$totalAmount;
                $stmt = $this->db->prepare("INSERT INTO transactions (user_id, type, amount, status, reference_number, payment_method, transaction_date) VALUES (?, 'loangiven', ?, 'completed', ?, 'loan', NOW())");
                $stmt->bind_param('ids', $adminId, $negativeTotalAmount, $adminTransactionRef);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create loan transaction record for admin");
                }

                // Create repayment schedule including interest
                $this->createRepaymentSchedule($loanId, $totalAmount, $duration);

                // Commit transaction
                $this->db->commit();

                return [
                    'success' => true,
                    'message' => 'Loan approved successfully',
                    'principal' => $principal,
                    'interest_rate' => $interestRate,
                    'interest_amount' => $interestAmount,
                    'total_amount' => $totalAmount,
                    'receipt_number' => $receiptNumber
                ];

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Error in approveLoan: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    

    public function rejectLoan($loanId)
    {
        try {
            $loanId = $this->db->escape($loanId);

            // Get loan details
            $query = "SELECT * FROM loans WHERE loan_id = '$loanId' AND status = 'pending'";
            $result = $this->db->query($query);
            $loan = $result->fetch_assoc();

            if (!$loan) {
                return ['success' => false, 'error' => 'Loan not found or not pending'];
            }

            // Update loan status
            $query = "UPDATE loans SET status = 'rejected', rejected_at = NOW() WHERE loan_id = '$loanId'";
            $result = $this->db->query($query);

            if (!$result) {
                throw new Exception("Failed to update loan status");
            }

            return ['success' => true];
        } catch (Exception $e) {
            error_log("Error in rejectLoan: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function cancelLoan($loanId, $userId)
{
    try {
        $loanId = intval($loanId);
        $userId = intval($userId);

        // Detect exact enum value used for cancelled status
        $cancelValue = 'cancelled';
        $pendingValue = 'pending';

        try {
            $colRes = $this->db->query("SHOW COLUMNS FROM loans LIKE 'status'");
            if ($colRes && method_exists($colRes, 'fetch_assoc')) {
                $col = $colRes->fetch_assoc();
                if ($col && isset($col['Type'])) {
                    $type = $col['Type'];

                    // Detect cancellation spelling
                    if (strpos($type, "'cancelled'") !== false) {
                        $cancelValue = 'cancelled';
                    } elseif (strpos($type, "'cancellled'") !== false) {
                        $cancelValue = 'cancellled';
                    } elseif (strpos($type, "'canceled'") !== false) {
                        $cancelValue = 'canceled';
                    }

                    // Detect pending spelling
                    if (stripos($type, "'pending'") !== false) {
                        $pendingValue = 'pending';
                    } elseif (stripos($type, "'Pending'") !== false) {
                        $pendingValue = 'Pending';
                    } elseif (stripos($type, "'PENDING'") !== false) {
                        $pendingValue = 'PENDING';
                    }
                }
            }
        } catch (Exception $ignored) {
            // Defaults remain 'cancelled' and 'pending'
        }

        // Prepare update query with detected values
        $stmt = $this->db->prepare(
            "UPDATE loans 
             SET status = ?, updated_at = NOW() 
             WHERE loan_id = ? 
             AND user_id = ? 
             AND status = ?"
        );
        $stmt->bind_param('siis', $cancelValue, $loanId, $userId, $pendingValue);

        $ok = $stmt->execute();
        if (!$ok || $stmt->affected_rows === 0) {
            throw new Exception("Failed to cancel loan (maybe loan is not pending or does not belong to this user)");
        }

        return ['success' => true];
    } catch (Exception $e) {
        error_log("Error in cancelLoan: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

    private function generateUniqueReferenceNumber()
    {
        do {
            $timestamp = time();
            $random = mt_rand(1000, 9999);
            $uniqueId = uniqid();
            $referenceNumber = "REP-{$timestamp}-{$random}-{$uniqueId}";

            // Check if reference number exists
            $query = "SELECT COUNT(*) as count FROM transactions WHERE reference_number = '$referenceNumber'";
            $result = $this->db->query($query);
            $row = $result->fetch_assoc();
        } while ($row['count'] > 0);

        return $referenceNumber;
    }

    public function makeRepayment($loanId, $amount)
{
    try {
        $loanId = intval($loanId); // sanitize as int
        $amount = round(floatval($amount), 2); // sanitize amount

        // Start transaction
        if (!$this->db->query("START TRANSACTION")) {
            throw new Exception("Failed to start transaction");
        }

        // Get loan details + user account number
        $stmt = $this->db->prepare("
            SELECT l.*, u.account_number, u.user_id 
            FROM loans l 
            JOIN users u ON l.user_id = u.user_id 
            WHERE l.loan_id = ? AND l.status = 'active'
        ");
        $stmt->bind_param('i', $loanId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute loan fetch query: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $loan = $result->fetch_assoc();
        $stmt->close();

        if (!$loan) {
            $this->db->query("ROLLBACK");
            return ['success' => false, 'error' => 'Loan not found or not active'];
        }

        // Get pending repayments
        $stmt = $this->db->prepare("
            SELECT repayment_id, amount 
            FROM loan_repayments 
            WHERE loan_id = ? AND status = 'pending' 
            ORDER BY due_date ASC
        ");
        $stmt->bind_param('i', $loanId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute pending repayments fetch query: " . $stmt->error);
        }
        $result = $stmt->get_result();

        $pendingRepayments = [];
        while ($row = $result->fetch_assoc()) {
            $pendingRepayments[] = $row;
        }
        $stmt->close();

        if (empty($pendingRepayments)) {
            $this->db->query("ROLLBACK");
            return ['success' => false, 'error' => 'No pending repayments found'];
        }

        // Calculate valid cumulative repayment amounts
        $validAmounts = [];
        $currentSum = 0.0;
        foreach ($pendingRepayments as $repayment) {
            $currentSum = round($currentSum + floatval($repayment['amount']), 2);
            $validAmounts[] = $currentSum;
        }

        // Check if amount matches a valid cumulative amount
        $isValidAmount = false;
        foreach ($validAmounts as $validAmount) {
            if (abs($amount - $validAmount) < 0.01) {
                $isValidAmount = true;
                break;
            }
        }
        if (!$isValidAmount) {
            $validAmountsStr = implode(', ', array_map(function ($amt) {
                return 'FRW ' . number_format($amt, 2);
            }, $validAmounts));
            $this->db->query("ROLLBACK");
            return ['success' => false, 'error' => "Invalid payment amount. Please pay one of these amounts: $validAmountsStr"];
        }

        // Get user's current balance
        $stmt = $this->db->prepare("SELECT amount FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $loan['user_id']);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute user balance fetch query: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $this->db->query("ROLLBACK");
            return ['success' => false, 'error' => 'Failed to get user details'];
        }

        $currentBalance = floatval($user['amount']);
        if ($currentBalance < $amount) {
            $this->db->query("ROLLBACK");
            return ['success' => false, 'error' => 'Insufficient balance. Current balance: FRW ' . number_format($currentBalance, 2)];
        }

        // Create withdrawal record in savings
        $withdrawalRef = 'REP' . date('YmdHis') . rand(1000, 9999);
        $stmt = $this->db->prepare("
            INSERT INTO savings (user_id, account_number, amount, transaction_type, status, receipt_number, transaction_date) 
            VALUES (?, ?, ?, 'withdrawal', 'completed', ?, NOW())
        ");
        $withdrawAmount = -$amount; // negative amount for withdrawal
        $stmt->bind_param('isds', $loan['user_id'], $loan['account_number'], $withdrawAmount, $withdrawalRef);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create withdrawal record: " . $stmt->error);
        }
        $stmt->close();

        // Update user's balance
        $newBalance = $currentBalance - $amount;
        $stmt = $this->db->prepare("UPDATE users SET amount = ? WHERE user_id = ?");
        $stmt->bind_param('di', $newBalance, $loan['user_id']);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update user's balance: " . $stmt->error);
        }
        $stmt->close();

        // Credit admin who approved the loan (if any)
        $adminId = intval($loan['approved_by']);
        if ($adminId > 0) {
            // Update admin balance
            $stmt = $this->db->prepare("UPDATE users SET amount = amount + ? WHERE user_id = ? AND role = 'admin'");
            $stmt->bind_param('di', $amount, $adminId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to credit admin's balance: " . $stmt->error);
            }
            $stmt->close();

            // Create transaction record for admin
            $adminTransactionRef = $this->generateUniqueReferenceNumber();
            $stmt = $this->db->prepare("
                INSERT INTO transactions (user_id, type, amount, status, reference_number, payment_method, transaction_date)
                VALUES (?, 'paid', ?, 'completed', ?, 'cash', NOW())
            ");
            $stmt->bind_param('ids', $adminId, $amount, $adminTransactionRef);
            if (!$stmt->execute()) {
                throw new Exception("Failed to create admin repayment transaction record: " . $stmt->error);
            }
            $stmt->close();
        }

        // Apply repayment amounts to pending repayments one by one
        $remainingAmount = $amount;
        foreach ($pendingRepayments as $repayment) {
            if ($remainingAmount <= 0) break;

            $repaymentAmount = min($remainingAmount, round(floatval($repayment['amount']), 2));
            $repaymentId = $repayment['repayment_id'];

            // Update repayment record to paid
            $stmt = $this->db->prepare("
                UPDATE loan_repayments SET status = 'paid', paid_at = NOW() WHERE repayment_id = ?
            ");
            $stmt->bind_param('i', $repaymentId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update repayment record: " . $stmt->error);
            }
            $stmt->close();

            // Create transaction record for user repayment
            $transactionRef = $this->generateUniqueReferenceNumber();
            $stmt = $this->db->prepare("
                INSERT INTO transactions (user_id, account_number, type, amount, status, reference_number, payment_method, transaction_date)
                VALUES (?, ?, 'paid', ?, 'completed', ?, 'cash', NOW())
            ");
            $stmt->bind_param('isds', $loan['user_id'], $loan['account_number'], $repaymentAmount, $transactionRef);
            if (!$stmt->execute()) {
                throw new Exception("Failed to create user repayment transaction record: " . $stmt->error);
            }
            $stmt->close();

            $remainingAmount = round($remainingAmount - $repaymentAmount, 2);
        }

        // Check if all repayments completed
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS count FROM loan_repayments WHERE loan_id = ? AND status != 'paid'
        ");
        $stmt->bind_param('i', $loanId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to check remaining repayments: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (isset($row['count']) && $row['count'] == 0) {
            $stmt = $this->db->prepare("
                UPDATE loans SET status = 'completed', completed_at = NOW() WHERE loan_id = ?
            ");
            $stmt->bind_param('i', $loanId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update loan status to completed: " . $stmt->error);
            }
            $stmt->close();
        }

        // Commit transaction
        if (!$this->db->query("COMMIT")) {
            throw new Exception("Failed to commit transaction");
        }

        return [
            'success' => true,
            'message' => 'Repayment successful',
            'amount_paid' => $amount,
            'new_balance' => $newBalance,
            'receipt_number' => $withdrawalRef
        ];

    } catch (Exception $e) {
        $this->db->query("ROLLBACK");
        error_log("Error in makeRepayment: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


    public function getLoanDetails($loanId)
    {
        try {
            $loanId = intval($loanId);
            $query = "SELECT l.*, u.full_name, u.phone_number 
                     FROM loans l 
                     JOIN users u ON l.user_id = u.user_id 
                     WHERE l.loan_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
            $result = $stmt->get_result();
            $loan = $result->fetch_assoc();
            
            if ($loan) {
                // Calculate interest details
                $principal = floatval($loan['amount']);
                $interestRate = floatval($loan['interest_rate']);
                $duration = intval($loan['duration']);
                $interestAmount = $this->calculateInterest($principal, $interestRate, $duration);
                $totalAmount = $principal + $interestAmount;
                
                $loan['principal'] = $principal;
                $loan['interest_amount'] = $interestAmount;
                $loan['total_amount'] = $totalAmount;
                $loan['monthly_payment'] = $this->calculateMonthlyPayment($totalAmount, $duration);
            }
            
            return $loan;
        } catch (Exception $e) {
            error_log("Error in getLoanDetails: " . $e->getMessage());
            return null;
        }
    }

    public function getRepaymentSchedule($loanId)
    {
        try {
            $loanId = $this->db->escape($loanId);

            $query = "SELECT * FROM loan_repayments 
                     WHERE loan_id = '$loanId' 
                     ORDER BY due_date ASC";
            $result = $this->db->query($query);

            $schedule = [];
            while ($row = $result->fetch_assoc()) {
                $schedule[] = $row;
            }

            return $schedule;
        } catch (Exception $e) {
            error_log("Error in getRepaymentSchedule: " . $e->getMessage());
            return [];
        }
    }

    private function createRepaymentSchedule($loanId, $totalAmount, $duration)
    {
        // Calculate monthly payment including interest
        // Use amortization formula for equal monthly payments
        $monthlyPayment = $this->calculateMonthlyPayment($totalAmount, $duration);
        
        $currentDate = new DateTime();

        for ($i = 1; $i <= $duration; $i++) {
            $dueDate = $currentDate->modify('+1 month')->format('Y-m-d');

            // For the last payment, adjust to ensure total equals exactly the total amount
            if ($i == $duration) {
                // Calculate what's been allocated so far
                $allocatedSoFar = $monthlyPayment * ($duration - 1);
                $finalPayment = $totalAmount - $allocatedSoFar;
                $paymentAmount = round($finalPayment, 2);
            } else {
                $paymentAmount = round($monthlyPayment, 2);
            }

            $query = "INSERT INTO loan_repayments (loan_id, amount, due_date, status) 
                     VALUES (?, ?, ?, 'pending')";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ids", $loanId, $paymentAmount, $dueDate);
            $result = $stmt->execute();

            if (!$result) {
                throw new Exception("Failed to create repayment schedule: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    /**
     * Calculate monthly payment using amortization formula
     */
    private function calculateMonthlyPayment($principal, $durationMonths)
    {
        // For now, use a simple calculation with default interest rate
        // In a real implementation, we'd need to get the actual interest rate from the loan
        $monthlyRate = 0.10 / 12; // Assume 10% annual rate for now
        
        if ($monthlyRate > 0) {
            // Amortization formula: P * (r * (1 + r)^n) / ((1 + r)^n - 1)
            $numerator = $monthlyRate * pow(1 + $monthlyRate, $durationMonths);
            $denominator = pow(1 + $monthlyRate, $durationMonths) - 1;
            $monthlyPayment = $principal * ($numerator / $denominator);
        } else {
            // No interest, simple division
            $monthlyPayment = $principal / $durationMonths;
        }
        
        return round($monthlyPayment, 2);
    }
}
?>