<?php
class RateLimiter
{
    private $db;
    private $maxAttempts = 3;
    private $lockoutTime = 60; // 1 minute in seconds

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function isRateLimited($ipAddress, $username)
    {
        // Check if user exists and is locked
        $query = "SELECT 
                    last_login_attempt,
                    login_attempts,
                    is_locked,
                    lockout_until
                 FROM users 
                 WHERE username = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false; // User doesn't exist, not rate limited
        }

        $user = $result->fetch_assoc();

        // If user is locked, check if lockout period has expired
        if ($user['is_locked']) {
            if ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
                return true; // Still locked
            } else {
                // Lockout period has expired, reset the lock
                $this->resetAttempts($username);
                return false;
            }
        }

        // Check if too many attempts in the last minute
        if ($user['login_attempts'] >= $this->maxAttempts) {
            $lastAttempt = strtotime($user['last_login_attempt']);
            if (time() - $lastAttempt < $this->lockoutTime) {
                return true;
            } else {
                $this->resetAttempts($username);
                return false;
            }
        }

        return false;
    }

    public function recordFailedAttempt($ipAddress, $username, $success = false)
    {
        if ($success) {
            // Reset attempts on successful login
            $this->resetAttempts($username);
            return;
        }

        // Get current attempts
        $query = "SELECT login_attempts FROM users WHERE username = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return; // User doesn't exist
        }

        $user = $result->fetch_assoc();
        $currentAttempts = $user['login_attempts'] + 1;

        // Calculate lockout time - exactly 1 minute from now
        $lockoutTime = date('Y-m-d H:i:s', time() + 60);

        // Update attempts and lock status
        $query = "UPDATE users 
                 SET login_attempts = ?,
                     last_login_attempt = CURRENT_TIMESTAMP,
                     is_locked = CASE 
                        WHEN ? >= ? THEN 1 
                        ELSE is_locked 
                     END,
                     lockout_until = CASE 
                        WHEN ? >= ? THEN ?
                        ELSE lockout_until 
                     END
                 WHERE username = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param(
            "iiiiiss",
            $currentAttempts,
            $currentAttempts,
            $this->maxAttempts,
            $currentAttempts,
            $this->maxAttempts,
            $lockoutTime,
            $username
        );
        $stmt->execute();

        // Debug information
        error_log("Updated lockout info for $username:");
        error_log("Current attempts: $currentAttempts");
        error_log("Lockout time set to: $lockoutTime");
    }

    public function resetAttempts($username)
    {
        $query = "UPDATE users 
                 SET login_attempts = 0,
                     is_locked = 0,
                     lockout_until = NULL,
                     last_login_attempt = NULL
                 WHERE username = ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
    }

    public function getRemainingAttempts($username)
    {
        $query = "SELECT login_attempts FROM users WHERE username = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return $this->maxAttempts;
        }

        $user = $result->fetch_assoc();
        return max(0, $this->maxAttempts - $user['login_attempts']);
    }

    public function getLockoutTimeRemaining($username)
    {
        $query = "SELECT lockout_until, last_login_attempt, login_attempts, is_locked 
                 FROM users 
                 WHERE username = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return 0;
        }

        $user = $result->fetch_assoc();

        // Debug information
        error_log("User lockout info for $username:");
        error_log("is_locked: " . ($user['is_locked'] ? 'true' : 'false'));
        error_log("lockout_until: " . $user['lockout_until']);
        error_log("last_login_attempt: " . $user['last_login_attempt']);
        error_log("login_attempts: " . $user['login_attempts']);
        error_log("Current time: " . date('Y-m-d H:i:s'));

        // If user is locked and has a lockout time
        if ($user['is_locked'] && $user['lockout_until']) {
            $remaining = strtotime($user['lockout_until']) - time();
            error_log("Remaining time from lockout_until: " . $remaining . " seconds");
            return max(0, $remaining);
        }

        // If user has max attempts but no lockout time set
        if ($user['login_attempts'] >= $this->maxAttempts) {
            $lastAttempt = strtotime($user['last_login_attempt']);
            $remaining = ($lastAttempt + $this->lockoutTime) - time();
            error_log("Remaining time from last_attempt: " . $remaining . " seconds");
            return max(0, $remaining);
        }

        return 0;
    }
}