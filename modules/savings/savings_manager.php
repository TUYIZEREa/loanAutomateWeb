<?php

class SavingsManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function getUserIdByAccountNumber($accountNumber)
    {
        $query = "SELECT user_id FROM users WHERE account_number = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $accountNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['user_id'];
        }
        throw new Exception("Invalid account number");
    }


public function getBalance($userId)
{
    $query = "SELECT amount as balance FROM users WHERE user_id = ?";

    $stmt = $this->db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row === null) {
        return 0;
    }

    return $row['balance'];
}


    public function deposit($accountNumber, $amount, $isAdmin = false)
    {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Amount must be greater than 0'];
        }

        try {
            $userId = $this->getUserIdByAccountNumber($accountNumber);
            $receiptNumber = $this->generateUniqueReferenceNumber('DEP');

            $query = "INSERT INTO savings (user_id, account_number, amount, transaction_type, receipt_number, status, is_admin_transaction) 
                     VALUES (?, ?, ?, 'deposit', ?, 'completed', ?)";
            $stmt = $this->db->prepare($query);
            $isAdminInt = $isAdmin ? 1 : 0;
            $stmt->bind_param("isdsi", $userId, $accountNumber, $amount, $receiptNumber, $isAdminInt);

            if ($stmt->execute()) {
                return ['success' => true, 'receipt_number' => $receiptNumber];
            }

            return ['success' => false, 'error' => 'Failed to process deposit'];
        } catch (Exception $e) {
            error_log("Deposit error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to process deposit: ' . $e->getMessage()];
        }
    }

    public function adminDeposit($adminId, $customerAccountNumber, $amount, $description = '')
    {
        // Verify admin user
        $query = "SELECT role FROM users WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if (!$admin || $admin['role'] !== 'admin') {
            return ['success' => false, 'error' => 'Unauthorized: Admin privileges required'];
        }

        // Verify customer account exists
        try {
            $customerId = $this->getUserIdByAccountNumber($customerAccountNumber);
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Invalid customer account number'];
        }

        // Get admin's current balance before processing deposit
        $query = "SELECT amount FROM users WHERE user_id = ? AND role = 'admin'";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $adminResult = $stmt->get_result();
        $adminData = $adminResult->fetch_assoc();
        if (!$adminData || $adminData['amount'] < $amount) {
            return ['success' => false, 'error' => 'Insufficient admin balance to complete this deposit.'];
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Deduct amount from admin's balance
            $newAdminBalance = $adminData['amount'] - $amount;
            $query = "UPDATE users SET amount = ? WHERE user_id = ? AND role = 'admin'";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("di", $newAdminBalance, $adminId);
            $stmt->execute();

            // Add amount to user's balance in users table
            $query = "UPDATE users SET amount = amount + ? WHERE account_number = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ds", $amount, $customerAccountNumber);
            $stmt->execute();

            // Generate receipt/reference numbers
            $adminReceiptNumber = $this->generateUniqueReferenceNumber('ADM');
            $userReceiptNumber = $this->generateUniqueReferenceNumber('USR');
            $type = 'deposit';
            $status = 'completed';
            $paymentMethod = 'internal';
            $transactionDate = date('Y-m-d H:i:s');

            // Insert into savings table for user (positive amount)
            $query = "INSERT INTO savings (user_id, account_number, amount, transaction_type, receipt_number, status, is_admin_transaction) 
                     VALUES (?, ?, ?, 'deposit', ?, 'completed', 1)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("isds", $customerId, $customerAccountNumber, $amount, $userReceiptNumber);
            $stmt->execute();

            // Insert into admin_transactions table
            $query = "INSERT INTO admin_transactions (admin_id, customer_account, amount, description, receipt_number, transaction_date) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("isdsss", $adminId, $customerAccountNumber, $amount, $description, $adminReceiptNumber, $transactionDate);
            $stmt->execute();

            // Insert into transactions table for admin (negative amount)
            $query = "INSERT INTO transactions (user_id, type, amount, status, reference_number, payment_method, transaction_date)
                      VALUES (?, 'admin_deposit', ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $negativeAmount = -abs($amount);
            $stmt->bind_param("idssss", $adminId, $negativeAmount, $status, $adminReceiptNumber, $paymentMethod, $transactionDate);
            $stmt->execute();

            // Insert into transactions table for user (positive amount)
            $query = "INSERT INTO transactions (user_id, type, amount, status, reference_number, payment_method, transaction_date)
                      VALUES (?, 'deposit', ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("idssss", $customerId, $amount, $status, $userReceiptNumber, $paymentMethod, $transactionDate);
            $stmt->execute();

            $this->db->commit();
            return ['success' => true, 'receipt_number' => $adminReceiptNumber];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Admin deposit error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to process admin deposit: ' . $e->getMessage()];
        }
    }

    public function withdraw($accountNumber, $amount)
{
    if ($amount <= 0) {
        return ['success' => false, 'error' => 'Amount must be greater than 0'];
    }

    try {
        $userId = $this->getUserIdByAccountNumber($accountNumber);
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Invalid account number'];
    }

    $balance = $this->getBalance($userId);

    if ($amount > $balance) {
        return ['success' => false, 'error' => 'Insufficient balance'];
    }

    $receiptNumber = $this->generateUniqueReferenceNumber('WTH');

    // Start transaction to keep consistency
    $this->db->beginTransaction();

    try {
        // Insert withdrawal record
        $query = "INSERT INTO savings (user_id, account_number, amount, transaction_type, receipt_number, status) 
                  VALUES (?, ?, ?, 'withdrawal', ?, 'completed')";
        $stmt = $this->db->prepare($query);
        $negativeAmount = -$amount;
        $stmt->bind_param("isds", $userId, $accountNumber, $negativeAmount, $receiptNumber);
        $stmt->execute();

        // Update users table balance
        $newBalance = $balance - $amount;
        $updateQuery = "UPDATE users SET amount = ? WHERE user_id = ?";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->bind_param("di", $newBalance, $userId);
        $updateStmt->execute();

        $this->db->commit();

        return ['success' => true, 'receipt_number' => $receiptNumber];
    } catch (Exception $e) {
        $this->db->rollback();
        error_log("Withdraw error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to process withdrawal: ' . $e->getMessage()];
    }
}


   public function transfer($senderAccountNumber, $receiverAccountNumber, $amount)
{
    if ($amount <= 0) {
        return ['success' => false, 'error' => 'Amount must be greater than 0'];
    }

    // Get sender's user ID and balance
    try {
        $senderUserId = $this->getUserIdByAccountNumber($senderAccountNumber);
        $receiverUserId = $this->getUserIdByAccountNumber($receiverAccountNumber);
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Invalid account number'];
    }

    $senderBalance = $this->getBalance($senderUserId);

    if ($amount > $senderBalance) {
        return ['success' => false, 'error' => 'Insufficient balance'];
    }

    $receiptNumber = $this->generateUniqueReferenceNumber('TRF');

    $this->db->beginTransaction();

    try {
        // Deduct from sender
        $query = "INSERT INTO savings (user_id, account_number, amount, transaction_type, receipt_number, status) 
                 VALUES (?, ?, ?, 'withdrawal', ?, 'completed')";
        $stmt = $this->db->prepare($query);
        $negativeAmount = -$amount;
        $stmt->bind_param("isds", $senderUserId, $senderAccountNumber, $negativeAmount, $receiptNumber);
        $stmt->execute();

        // Add to receiver
        $query = "INSERT INTO savings (user_id, account_number, amount, transaction_type, receipt_number, status) 
                 VALUES (?, ?, ?, 'deposit', ?, 'completed')";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("isds", $receiverUserId, $receiverAccountNumber, $amount, $receiptNumber);
        $stmt->execute();

        // Update sender's balance
        $newSenderBalance = $senderBalance - $amount;
        $updateSenderQuery = "UPDATE users SET amount = ? WHERE user_id = ?";
        $updateStmt = $this->db->prepare($updateSenderQuery);
        $updateStmt->bind_param("di", $newSenderBalance, $senderUserId);
        $updateStmt->execute();

        // Update receiver's balance
        $receiverBalance = $this->getBalance($receiverUserId);
        $newReceiverBalance = $receiverBalance + $amount;
        $updateReceiverQuery = "UPDATE users SET amount = ? WHERE user_id = ?";
        $updateStmt = $this->db->prepare($updateReceiverQuery);
        $updateStmt->bind_param("di", $newReceiverBalance, $receiverUserId);
        $updateStmt->execute();

        $this->db->commit();

        return ['success' => true, 'receipt_number' => $receiptNumber];
    } catch (Exception $e) {
        $this->db->rollback();
        error_log("Transfer error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to process transfer: ' . $e->getMessage()];
    }
}


    public function getTransactionHistory($accountNumber, $filters = [])
    {
        $query = "SELECT * FROM savings WHERE account_number = ?";
        $params = [$accountNumber];
        $types = "s";

        if (!empty($filters['start_date'])) {
            $query .= " AND transaction_date >= ?";
            $params[] = $filters['start_date'];
            $types .= "s";
        }

        if (!empty($filters['end_date'])) {
            $query .= " AND transaction_date <= ?";
            $params[] = $filters['end_date'];
            $types .= "s";
        }

        if (!empty($filters['type'])) {
            $query .= " AND transaction_type = ?";
            $params[] = $filters['type'];
            $types .= "s";
        }

        $query .= " ORDER BY transaction_date DESC";

        if (!empty($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = $filters['limit'];
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }

        return $transactions;
    }

    private function generateUniqueReferenceNumber($prefix = 'TRF')
    {
        do {
            $timestamp = time();
            $random = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $reference = $prefix . $timestamp . $random;

            // Check if reference number exists in savings table
            $query = "SELECT COUNT(*) as count FROM savings WHERE receipt_number = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $reference);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['count'] == 0) {
                return $reference;
            }

            // If duplicate found, wait a bit and try again
            usleep(100); // Wait 100 microseconds
        } while (true);
    }
}
?>