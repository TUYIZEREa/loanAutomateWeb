-- Withdrawal Request System
-- This file adds the necessary tables for the new withdrawal system

-- Table for withdrawal requests
CREATE TABLE `withdrawal_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(36) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `approved_by` varchar(36) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` varchar(36) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `requested_at` (`requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for withdrawal charges configuration
CREATE TABLE `withdrawal_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `min_amount` decimal(10,2) NOT NULL,
  `max_amount` decimal(10,2) NOT NULL,
  `charge_amount` decimal(10,2) NOT NULL,
  `charge_type` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default withdrawal charges
INSERT INTO `withdrawal_charges` (`min_amount`, `max_amount`, `charge_amount`, `charge_type`, `is_active`) VALUES
(0.01, 1000.00, 50.00, 'fixed', 1),
(1000.01, 5000.00, 100.00, 'fixed', 1),
(5000.01, 10000.00, 200.00, 'fixed', 1),
(10000.01, 50000.00, 500.00, 'fixed', 1),
(50000.01, 999999.99, 1000.00, 'fixed', 1);

-- Add withdrawal_charge_rate to settings table if it doesn't exist
ALTER TABLE `settings` ADD COLUMN IF NOT EXISTS `withdrawal_charge_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Default withdrawal charge rate in percentage';

-- Update settings to include withdrawal charge rate
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) 
VALUES ('withdrawal_charge_rate', '0.00', 'Default withdrawal charge rate in percentage')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
