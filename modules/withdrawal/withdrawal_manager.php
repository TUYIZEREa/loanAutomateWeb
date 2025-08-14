<?php

class WithdrawalManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Calculate withdrawal charges based on amount
     */
    public function calculateCharges($amount)
    {
        $query = "SELECT * FROM withdrawal_charges WHERE is_active = 1 AND ? BETWEEN min_amount AND max_amount ORDER BY charge_amount ASC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("d", $amount);
        $stmt->execute();
        $result = $stmt->get_result();
        $charge = $result->fetch_assoc();

        if ($charge) {
            if ($charge['charge_type'] === 'percentage') {
                return ($amount * $charge['charge_amount']) / 100;
            } else {
                return $charge['charge_amount'];
            }
        }

        // Default charge if no specific rule found
        return 0.00;
    }

    /**
     * Create a withdrawal request
     */
    public function createWithdrawalRequest($userId, $accountNumber, $amount)
    {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than 0'];
        }

        try {
            // Check if user has sufficient balance
            $balance = $this->getUserBalance($userId);
            if ($balance < $amount) {
                return ['success' => false, 'error' => 'Insufficient balance'];
            }

            // Calculate charges
            $charges = $this->calculateCharges($amount);
            $totalAmount = $amount + $charges;

            // Check if user has enough balance including charges
            if ($balance < $totalAmount) {
                return ['success' => false, 'error' => 'Insufficient balance to cover withdrawal and charges'];
            }

            // Create withdrawal request
            $query = "INSERT INTO withdrawal_requests (user_id, account_number, amount, charges, total_amount, status, requested_at) 
                     VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ssddd", $userId, $accountNumber, $amount, $charges, $totalAmount);

            if ($stmt->execute()) {
                $requestId = $stmt->insert_id;
                return [
                    'success' => true, 
                    'request_id' => $requestId,
                    'amount' => $amount,
                    'charges' => $charges,
                    'total_amount' => $totalAmount,
                    'message' => 'Withdrawal request submitted successfully. Awaiting admin approval.'
                ];
            }

            return ['success' => false, 'error' => 'Failed to create withdrawal request'];
        } catch (Exception $e) {
            error_log("Withdrawal request error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create withdrawal request: ' . $e->getMessage()];
        }
    }

    /**
     * Get user balance
     */
    private function getUserBalance($userId)
    {
        $query = "SELECT amount FROM users WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        return $user ? floatval($user['amount']) : 0;
    }

    /**
     * Approve withdrawal request
     */
    public function approveWithdrawalRequest($requestId, $adminId)
    {
        try {
            // Get withdrawal request
            $query = "SELECT * FROM withdrawal_requests WHERE request_id = ? AND status = 'pending'";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            $request = $result->fetch_assoc();

            if (!$request) {
                return ['success' => false, 'error' => 'Withdrawal request not found or already processed'];
            }

            // Check if request is expired (older than 1 minute)
            $requestTime = strtotime($request['requested_at']);
            $currentTime = time();
            if (($currentTime - $requestTime) > 60) { // 60 seconds = 1 minute
                $this->rejectWithdrawalRequest($requestId, $adminId, 'Request expired (older than 1 minute)');
                return ['success' => false, 'error' => 'Withdrawal request has expired'];
            }

            // Check if user still has sufficient balance
            $balance = $this->getUserBalance($request['user_id']);
            if ($balance < $request['total_amount']) {
                $this->rejectWithdrawalRequest($requestId, $adminId, 'Insufficient balance');
                return ['success' => false, 'error' => 'User no longer has sufficient balance'];
            }

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Update withdrawal request status
                $query = "UPDATE withdrawal_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE request_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("si", $adminId, $requestId);
                $stmt->execute();

                // Deduct amount from user's balance
                $newBalance = $balance - $request['total_amount'];
                $query = "UPDATE users SET amount = ? WHERE user_id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("ds", $newBalance, $request['user_id']);
                $stmt->execute();

                // Add withdrawal amount to admin's balance
                $query = "UPDATE users SET amount = amount + ? WHERE user_id = ? AND role = 'admin'";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("ds", $request['amount'], $adminId);
                $stmt->execute();

                // Record the withdrawal in savings table
                $receiptNumber = $this->generateUniqueReferenceNumber('WTH');
                $query = "INSERT INTO savings (user_id, account_number, amount, transaction_type, receipt_number, status, is_admin_transaction) 
                         VALUES (?, ?, ?, 'withdrawal', ?, 'completed', 1)";
                $stmt = $this->db->prepare($query);
                $negativeAmount = -$request['total_amount'];
                $stmt->bind_param("ssds", $request['user_id'], $request['account_number'], $negativeAmount, $receiptNumber);
                $stmt->execute();

                // Record admin transaction
                $adminReceiptNumber = $this->generateUniqueReferenceNumber('ADM');
                $query = "INSERT INTO admin_transactions (admin_id, customer_account, amount, description, receipt_number, transaction_date) 
                         VALUES (?, ?, ?, ?, ?, NOW())";
                $description = "Withdrawal approval - Request #" . $requestId;
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("ssdss", $adminId, $request['account_number'], $request['amount'], $description, $adminReceiptNumber);
                $stmt->execute();

                // Record transaction for user
                $query = "INSERT INTO transactions (user_id, account_number, type, amount, status, reference_number, payment_method, transaction_date) 
                         VALUES (?, ?, 'withdrawal', ?, 'completed', ?, 'cash', NOW())";
                $stmt = $this->db->prepare($query);
                $negativeAmount = -$request['total_amount'];
                $stmt->bind_param("ssds", $request['user_id'], $request['account_number'], $negativeAmount, $receiptNumber);
                $stmt->execute();

                // Record transaction for admin
                $query = "INSERT INTO transactions (user_id, account_number, type, amount, status, reference_number, payment_method, transaction_date) 
                         VALUES (?, ?, 'withdrawal_approval', ?, 'completed', ?, 'internal', NOW())";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("ssds", $adminId, $request['account_number'], $request['amount'], $adminReceiptNumber);
                $stmt->execute();

                $this->db->commit();

                return [
                    'success' => true,
                    'message' => 'Withdrawal request approved successfully',
                    'receipt_number' => $receiptNumber,
                    'admin_receipt' => $adminReceiptNumber,
                    'amount' => $request['amount'],
                    'charges' => $request['charges'],
                    'total_amount' => $request['total_amount']
                ];

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Approve withdrawal error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to approve withdrawal: ' . $e->getMessage()];
        }
    }

    /**
     * Reject withdrawal request
     */
    public function rejectWithdrawalRequest($requestId, $adminId, $reason = '')
    {
        try {
            $query = "UPDATE withdrawal_requests SET status = 'rejected', rejected_by = ?, rejected_at = NOW(), rejection_reason = ? WHERE request_id = ? AND status = 'pending'";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ssi", $adminId, $reason, $requestId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                return ['success' => true, 'message' => 'Withdrawal request rejected successfully'];
            }
            
            return ['success' => false, 'error' => 'Withdrawal request not found or already processed'];
        } catch (Exception $e) {
            error_log("Reject withdrawal error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to reject withdrawal: ' . $e->getMessage()];
        }
    }

    /**
     * Get pending withdrawal requests
     */
    public function getPendingWithdrawalRequests()
    {
        $query = "SELECT wr.*, u.full_name, u.email, u.phone_number 
                 FROM withdrawal_requests wr 
                 JOIN users u ON wr.user_id COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci 
                 WHERE wr.status = 'pending' 
                 ORDER BY wr.requested_at ASC";
        
        $result = $this->db->query($query);
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        return $requests;
    }

    /**
     * Get user's withdrawal requests
     */
    public function getUserWithdrawalRequests($userId)
    {
        $query = "SELECT * FROM withdrawal_requests WHERE user_id = ? ORDER BY requested_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        return $requests;
    }

    /**
     * Auto-expire old withdrawal requests (older than 1 minute)
     */
    public function expireOldRequests()
    {
        $query = "UPDATE withdrawal_requests 
                 SET status = 'expired', expired_at = NOW() 
                 WHERE status = 'pending' 
                 AND requested_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        
        $this->db->query($query);
        return $this->db->getAffectedRows();
    }

    /**
     * Generate unique reference number
     */
    private function generateUniqueReferenceNumber($prefix)
    {
        do {
            $number = $prefix . date('YmdHis') . mt_rand(1000, 9999);
            $query = "SELECT COUNT(*) as count FROM savings WHERE receipt_number = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $number);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
        } while ($row['count'] > 0);
        
        return $number;
    }

    /**
     * Get withdrawal statistics
     */
    public function getWithdrawalStats()
    {
        $stats = [];
        
        // Total pending requests
        $query = "SELECT COUNT(*) as count FROM withdrawal_requests WHERE status = 'pending'";
        $result = $this->db->query($query);
        $stats['pending_count'] = $result->fetch_assoc()['count'];
        
        // Total approved today
        $query = "SELECT COUNT(*) as count, SUM(amount) as total_amount FROM withdrawal_requests 
                 WHERE status = 'approved' AND DATE(approved_at) = CURDATE()";
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        $stats['approved_today_count'] = $row['count'];
        $stats['approved_today_amount'] = $row['total_amount'] ?? 0;
        
        // Total rejected today
        $query = "SELECT COUNT(*) as count FROM withdrawal_requests 
                 WHERE status = 'rejected' AND DATE(rejected_at) = CURDATE()";
        $result = $this->db->query($query);
        $stats['rejected_today_count'] = $result->fetch_assoc()['count'];
        
        return $stats;
    }
}
