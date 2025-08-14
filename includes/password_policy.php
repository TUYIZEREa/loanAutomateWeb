<?php
class PasswordPolicy
{
    private $minLength;
    private $requireUppercase;
    private $requireLowercase;
    private $requireNumbers;
    private $requireSpecialChars;
    private $maxAge; // in days
    private $historySize; // number of previous passwords to check

    public function __construct(
        $minLength = 5,
        $requireUppercase = false,
        $requireLowercase = false,
        $requireNumbers = true,
        $requireSpecialChars = false,
        $maxAge = 90,
        $historySize = 5
    ) {
        $this->minLength = $minLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
        $this->maxAge = $maxAge;
        $this->historySize = $historySize;
    }

    public function validatePassword($password, $userId = null)
    {
        $errors = [];

        // Check if password is exactly 5 digits
        if (!preg_match('/^\d{5}$/', $password)) {
            $errors[] = "Password must be exactly 5 digits";
        }

        // No other checks needed if it's strictly 5 digits

        // Check password history if userId is provided
        if ($userId && $this->isPasswordInHistory($password, $userId)) {
            $errors[] = "Password cannot be one of your last {$this->historySize} passwords";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public function isPasswordExpired($userId)
    {
        $db = Database::getInstance();
        $userId = $db->escape($userId);

        $query = "SELECT password_changed_at FROM users WHERE user_id = '$userId'";
        $result = $db->query($query);
        $user = $result->fetch_assoc();

        if (!$user || !$user['password_changed_at']) {
            return true;
        }

        $lastChange = strtotime($user['password_changed_at']);
        $now = time();
        $daysSinceChange = ($now - $lastChange) / (60 * 60 * 24);

        return $daysSinceChange >= $this->maxAge;
    }

    private function isPasswordInHistory($password, $userId)
    {
        $db = Database::getInstance();
        $userId = $db->escape($userId);

        $query = "SELECT password FROM password_history 
                 WHERE user_id = '$userId' 
                 ORDER BY changed_at DESC 
                 LIMIT {$this->historySize}";

        $result = $db->query($query);

        while ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                return true;
            }
        }

        return false;
    }

    public function addToPasswordHistory($userId, $hashedPassword)
    {
        $db = Database::getInstance();
        $userId = $db->escape($userId);
        $hashedPassword = $db->escape($hashedPassword);

        $query = "INSERT INTO password_history (user_id, password, changed_at) 
                 VALUES ('$userId', '$hashedPassword', NOW())";

        $db->query($query);

        // Update user's password_changed_at
        $query = "UPDATE users SET password_changed_at = NOW() WHERE user_id = '$userId'";
        $db->query($query);
    }

    public function getPasswordRequirements()
    {
        return ["Password must be exactly 5 digits"];
    }
}