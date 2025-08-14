-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 13, 2025 at 03:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `loan_automate_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_transactions`
--

CREATE TABLE `admin_transactions` (
  `id` int(11) NOT NULL,
  `admin_id` varchar(36) NOT NULL,
  `customer_account` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_transactions`
--

INSERT INTO `admin_transactions` (`id`, `admin_id`, `customer_account`, `amount`, `description`, `receipt_number`, `transaction_date`, `created_at`) VALUES
(1, '1', 'ACC2025855859', 1500.00, '1', 'ADM1754916491030153', '2025-08-11 14:48:11', '2025-08-11 12:48:11'),
(2, '1', 'ACC2025753236', 5000.00, '', 'ADM1754917109911483', '2025-08-11 14:58:29', '2025-08-11 12:58:29'),
(3, '1', 'ACC2025953639', 120000.00, '1', 'ADM1755021586081830', '2025-08-12 19:59:46', '2025-08-12 17:59:46'),
(4, '1', 'ACC2025953639', 1100.00, 'Withdrawal approval - Request #2', 'ADM202508131424257229', '2025-08-13 14:24:25', '2025-08-13 12:24:25');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','closed') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 'TUYIZERE', 'aimabletuyizere63@gmail.com', '0789105167', 'Feedback', 'good', 'read', '2025-08-13 14:50:58', '2025-08-13 14:52:45');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `loan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `purpose` text NOT NULL,
  `duration` int(11) NOT NULL,
  `status` enum('pending','active','completed','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `risk_score` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`loan_id`, `user_id`, `amount`, `purpose`, `duration`, `status`, `interest_rate`, `risk_score`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `completed_at`, `amount_paid`, `created_at`, `updated_at`) VALUES
(40, 67, 1000.00, 'Education', 3, 'completed', 0.00, NULL, 1, '2025-08-12 21:07:09', NULL, NULL, '2025-08-12 21:44:20', 0.00, '2025-08-12 21:06:15', '2025-08-12 21:44:20'),
(41, 901000, 1000.00, 'Vehicle Purchase', 3, 'completed', 0.00, NULL, 1, '2025-08-13 10:25:27', NULL, NULL, '2025-08-13 10:26:41', 0.00, '2025-08-13 10:24:45', '2025-08-13 10:26:41'),
(42, 901000, 50000.00, 'Education', 3, 'completed', 0.00, NULL, 1, '2025-08-13 10:28:02', NULL, NULL, '2025-08-13 10:35:26', 0.00, '2025-08-13 10:27:32', '2025-08-13 10:35:26'),
(43, 901000, 10000.00, 'Wedding Expenses', 3, 'completed', 0.00, NULL, 1, '2025-08-13 10:43:12', NULL, NULL, '2025-08-13 10:43:36', 0.00, '2025-08-13 10:35:44', '2025-08-13 10:43:36'),
(44, 901000, 5000.00, 'Agriculture', 3, 'cancelled', 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2025-08-13 10:44:09', '2025-08-13 10:48:06'),
(45, 901000, 5900.00, 'Emergency Fund', 3, 'cancelled', 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2025-08-13 10:49:43', '2025-08-13 10:57:53'),
(46, 901000, 350.00, 'Medical Expenses', 3, 'rejected', 0.00, NULL, NULL, NULL, NULL, '2025-08-13 12:55:11', NULL, 0.00, '2025-08-13 11:00:40', '2025-08-13 12:55:11'),
(47, 901000, 10000.00, 'Vehicle Purchase', 3, 'rejected', 10.00, NULL, NULL, NULL, NULL, '2025-08-13 12:58:28', NULL, 0.00, '2025-08-13 12:55:48', '2025-08-13 12:58:28'),
(48, 901000, 10000.00, 'Debt Consolidation', 12, 'rejected', 10.00, NULL, NULL, NULL, NULL, '2025-08-13 13:01:45', NULL, 0.00, '2025-08-13 12:59:03', '2025-08-13 13:01:45'),
(49, 901000, 10000.00, 'Emergency Fund', 12, 'pending', 5.00, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, '2025-08-13 13:02:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loan_repayments`
--

CREATE TABLE `loan_repayments` (
  `repayment_id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','paid','overdue') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_repayments`
--

INSERT INTO `loan_repayments` (`repayment_id`, `loan_id`, `amount`, `due_date`, `status`, `paid_at`, `created_at`, `updated_at`) VALUES
(70, 40, 333.33, '2025-09-12', 'paid', '2025-08-12 21:42:25', '2025-08-12 21:07:09', '2025-08-12 21:42:25'),
(71, 40, 333.33, '2025-10-12', 'paid', '2025-08-12 21:43:47', '2025-08-12 21:07:09', '2025-08-12 21:43:47'),
(72, 40, 333.34, '2025-11-12', 'paid', '2025-08-12 21:44:20', '2025-08-12 21:07:09', '2025-08-12 21:44:20'),
(73, 41, 333.33, '2025-09-13', 'paid', '2025-08-13 10:26:41', '2025-08-13 10:25:27', '2025-08-13 10:26:41'),
(74, 41, 333.33, '2025-10-13', 'paid', '2025-08-13 10:26:41', '2025-08-13 10:25:27', '2025-08-13 10:26:41'),
(75, 41, 333.34, '2025-11-13', 'paid', '2025-08-13 10:26:41', '2025-08-13 10:25:27', '2025-08-13 10:26:41'),
(76, 42, 16666.66, '2025-09-13', 'paid', '2025-08-13 10:35:26', '2025-08-13 10:28:02', '2025-08-13 10:35:26'),
(77, 42, 16666.66, '2025-10-13', 'paid', '2025-08-13 10:35:26', '2025-08-13 10:28:02', '2025-08-13 10:35:26'),
(78, 42, 16666.68, '2025-11-13', 'paid', '2025-08-13 10:35:26', '2025-08-13 10:28:02', '2025-08-13 10:35:26'),
(79, 43, 3333.33, '2025-09-13', 'paid', '2025-08-13 10:43:36', '2025-08-13 10:43:12', '2025-08-13 10:43:36'),
(80, 43, 3333.33, '2025-10-13', 'paid', '2025-08-13 10:43:36', '2025-08-13 10:43:12', '2025-08-13 10:43:36'),
(81, 43, 3333.34, '2025-11-13', 'paid', '2025-08-13 10:43:36', '2025-08-13 10:43:12', '2025-08-13 10:43:36');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_history`
--

CREATE TABLE `password_history` (
  `history_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_history`
--

INSERT INTO `password_history` (`history_id`, `user_id`, `password`, `changed_at`) VALUES
(1, 0, '$2y$10$w.KCD6LT2r3W7ypxkjCzWezyCgXofJXk4jPJaTeWJIQ3uPiIjxgGK', '2025-06-07 14:43:58'),
(2, 408, '$2y$10$r4FzmBwmyYARDMvV4AqVFut7RiT7vYcWRhaw5dBveKEYkgHPc/.Cq', '2025-06-07 15:02:05'),
(3, 854, '$2y$10$PDNj8Ee.kRfKt73M/0LHJO6DX5.zDPHYTfX37xk.qAEwbRLy2XG1S', '2025-06-19 08:57:00'),
(4, 0, '$2y$10$KECGKKMX1ujkLKro8uwRU.qUe5rl3L3OHS6LEWP0.r.STxwD9Ug3q', '2025-06-24 19:15:16'),
(5, 2147483647, '$2y$10$UuLk/5DFDn73/A486zvEXukT5sLXH7NnYOM56by0NjcILO8gptu9C', '2025-06-30 05:33:42'),
(6, 913, '$2y$10$xszcTs7z808.w2z6/W0AleiVZtvG42KhnTND5K63sbqEt4LoBBf7C', '2025-06-30 05:43:57'),
(7, 16, '$2y$10$m0lzruEcn5kp.yMwVE5w8.SJEgmpTDkaq8JpNQdYJ4mfzqPJvCqmO', '2025-08-11 07:58:38'),
(8, 34, '$2y$10$nV.eTumltLByUO8P5yz7gekRoOD19EGTDyYe5hSSGFNJB6LHUKHp6', '2025-08-11 11:21:07'),
(9, 0, '$2y$10$VDH0o0CU/auEyoFqjiaXqeSMLaiR3la30mc3mi/HpbnuTG/TrVuGm', '2025-08-11 12:56:31'),
(10, 901000, '$2y$10$XQJVSuEoXN.KautnmIfAauyT0gOs1rc1mrLo7ic9xK.KLe3LVdThO', '2025-08-12 10:01:00'),
(11, 67, '$2y$10$heIEyT3DDwJ23.QRqPt6AeQqv0NYIqujrI0XVb14q6QTXRc2ndFEG', '2025-08-12 11:25:57');

-- --------------------------------------------------------

--
-- Table structure for table `savings`
--

CREATE TABLE `savings` (
  `id` int(11) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('deposit','withdrawal','Send amount') NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `is_admin_transaction` tinyint(1) DEFAULT 0,
  `receipt_number` varchar(50) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `savings`
--

INSERT INTO `savings` (`id`, `user_id`, `account_number`, `amount`, `transaction_type`, `status`, `is_admin_transaction`, `receipt_number`, `transaction_date`, `created_at`, `updated_at`) VALUES
(79, '0', 'ACC2025753236', 1000.00, 'deposit', 'completed', 0, 'RCP202508111456313478', '2025-08-11 12:56:31', '2025-08-11 12:56:31', '2025-08-11 12:56:31'),
(80, 'a49c42ad-8cd1-4b30-9835-949b2ebe8db1', 'ACC2025753236', 9000.00, 'deposit', 'completed', 1, 'USR1754917109715770', '2025-08-11 12:58:29', '2025-08-11 12:58:29', '2025-08-11 13:06:10'),
(81, 'a49c42ad-8cd1-4b30-9835-949b2ebe8db1', 'ACC2025753236', -7000.00, 'withdrawal', 'completed', 0, 'WTH1754917636529572', '2025-08-11 13:07:16', '2025-08-11 13:07:16', '2025-08-11 13:07:16'),
(82, '0', 'ACC2025753236', 800.00, 'deposit', 'completed', 0, 'LOAN202508111510236820', '2025-08-11 13:10:23', '2025-08-11 13:10:23', '2025-08-11 13:10:23'),
(83, 'a49c42ad-8cd1-4b30-9835-949b2ebe8db1', 'ACC2025753236', -700.00, 'withdrawal', 'completed', 0, 'WTH1754919842532981', '2025-08-11 13:44:02', '2025-08-11 13:44:02', '2025-08-11 13:44:02'),
(84, 'a49c42ad-8cd1-4b30-9835-949b2ebe8db1', 'ACC2025753236', -300.00, 'withdrawal', 'completed', 0, 'WTH1754920082430717', '2025-08-11 13:48:02', '2025-08-11 13:48:02', '2025-08-11 13:48:02'),
(85, 'a49c42ad-8cd1-4b30-9835-949b2ebe8db1', 'ACC2025753236', -1000.00, 'withdrawal', 'completed', 0, 'WTH1754920615672722', '2025-08-11 13:56:55', '2025-08-11 13:56:55', '2025-08-11 13:56:55'),
(86, 'a49c42ad-8cd1-4b30-9835-949b2ebe8db1', 'ACC2025753236', -1000.00, 'withdrawal', 'completed', 0, 'WTH1754920632393901', '2025-08-11 13:57:12', '2025-08-11 13:57:12', '2025-08-11 13:57:12'),
(87, 'a49c42ad-8cd1-4b30-9835-949b2ebe8db1', 'ACC2025753236', -1000.00, 'withdrawal', 'completed', 0, 'WTH1754920640946909', '2025-08-11 13:57:20', '2025-08-11 13:57:20', '2025-08-11 13:57:20'),
(88, 'a49c42ad-8cd1-4b30-9835-949b2ebe8db1', 'ACC2025753236', -1000.00, 'withdrawal', 'completed', 0, 'WTH1754920670339351', '2025-08-11 13:57:50', '2025-08-11 13:57:50', '2025-08-11 13:57:50'),
(89, '901000', 'ACC2025953639', 2100.00, 'deposit', 'completed', 0, 'RCP202508121201005933', '2025-08-12 10:01:00', '2025-08-12 10:01:00', '2025-08-12 10:01:00'),
(90, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', -100.00, 'withdrawal', 'completed', 0, 'WTH1754994899897894', '2025-08-12 10:34:59', '2025-08-12 10:34:59', '2025-08-12 10:34:59'),
(91, '901000', 'ACC2025953639', 100000.00, 'deposit', 'completed', 0, 'LOAN202508121249394381', '2025-08-12 10:49:39', '2025-08-12 10:49:39', '2025-08-12 10:49:39'),
(92, '67', 'ACC2025886145', 2800.00, 'deposit', 'completed', 0, 'RCP202508121325577419', '2025-08-12 11:25:57', '2025-08-12 11:25:57', '2025-08-12 11:25:57'),
(93, '67', 'ACC2025886145', 4000.00, 'deposit', 'completed', 0, 'LOAN202508121327581037', '2025-08-12 11:27:58', '2025-08-12 11:27:58', '2025-08-12 11:27:58'),
(94, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', 120000.00, 'deposit', 'completed', 1, 'USR1755021586682224', '2025-08-12 17:59:46', '2025-08-12 17:59:46', '2025-08-12 17:59:46'),
(95, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', -16666.66, 'withdrawal', 'completed', 0, 'REP202508122000186244', '2025-08-12 18:00:18', '2025-08-12 18:00:18', '2025-08-12 18:00:18'),
(96, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', -83333.34, 'withdrawal', 'completed', 0, 'REP202508122001191392', '2025-08-12 18:01:19', '2025-08-12 18:01:19', '2025-08-12 18:01:19'),
(97, '901000', 'ACC2025953639', 1000.00, 'deposit', 'completed', 0, 'LOAN202508122003294359', '2025-08-12 18:03:29', '2025-08-12 18:03:29', '2025-08-12 18:03:29'),
(98, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', -1000.00, 'withdrawal', 'completed', 0, 'REP202508122004477506', '2025-08-12 18:04:47', '2025-08-12 18:04:47', '2025-08-12 18:04:47'),
(99, '901000', 'ACC2025953639', 2000.00, 'deposit', 'completed', 0, 'LOAN202508122015062015', '2025-08-12 18:15:06', '2025-08-12 18:15:06', '2025-08-12 18:15:06'),
(100, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', -2000.00, 'withdrawal', 'completed', 0, 'REP202508122016075308', '2025-08-12 18:16:07', '2025-08-12 18:16:07', '2025-08-12 18:16:07'),
(101, '901000', 'ACC2025953639', 5000.00, 'deposit', 'completed', 0, 'LOAN202508122017284038', '2025-08-12 18:17:28', '2025-08-12 18:17:28', '2025-08-12 18:17:28'),
(102, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', -5000.00, 'withdrawal', 'completed', 0, 'REP202508122019429337', '2025-08-12 18:19:42', '2025-08-12 18:19:42', '2025-08-12 18:19:42'),
(103, '901000', 'ACC2025953639', 1000.00, 'deposit', 'completed', 0, 'LOAN202508122025401742', '2025-08-12 18:25:40', '2025-08-12 18:25:40', '2025-08-12 18:25:40'),
(104, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', -1000.00, 'withdrawal', 'completed', 0, 'REP202508122026452136', '2025-08-12 18:26:45', '2025-08-12 18:26:45', '2025-08-12 18:26:45'),
(105, '67', 'ACC2025886145', 1000.00, 'deposit', 'completed', 0, 'LOAN202508122107094333', '2025-08-12 19:07:09', '2025-08-12 19:07:09', '2025-08-12 19:07:09'),
(106, '67', 'ACC2025886145', -333.33, 'withdrawal', 'completed', 0, 'REP202508122142256880', '2025-08-12 19:42:25', '2025-08-12 19:42:25', '2025-08-12 19:42:25'),
(107, '67', 'ACC2025886145', -333.33, 'withdrawal', 'completed', 0, 'REP202508122143471956', '2025-08-12 19:43:47', '2025-08-12 19:43:47', '2025-08-12 19:43:47'),
(108, '67', 'ACC2025886145', -333.34, 'withdrawal', 'completed', 0, 'REP202508122144202388', '2025-08-12 19:44:20', '2025-08-12 19:44:20', '2025-08-12 19:44:20'),
(109, '67b5a1c9-8e41-4e2a-9ccd-00938668f2c3', 'ACC2025886145', -500.00, 'withdrawal', 'completed', 0, 'TRF1755065280560611', '2025-08-13 06:08:00', '2025-08-13 06:08:00', '2025-08-13 06:08:00'),
(110, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', 500.00, 'deposit', 'completed', 0, 'TRF1755065280560611', '2025-08-13 06:08:00', '2025-08-13 06:08:00', '2025-08-13 06:08:00'),
(111, '901000', 'ACC2025953639', -500.00, 'withdrawal', 'completed', 0, 'WTH1755073454323912', '2025-08-13 08:24:14', '2025-08-13 08:24:14', '2025-08-13 08:24:14'),
(112, '901000', 'ACC2025953639', 1000.00, 'deposit', 'completed', 0, 'LOAN202508131025272087', '2025-08-13 08:25:27', '2025-08-13 08:25:27', '2025-08-13 08:25:27'),
(113, '901000', 'ACC2025953639', -1000.00, 'withdrawal', 'completed', 0, 'REP202508131026413163', '2025-08-13 08:26:41', '2025-08-13 08:26:41', '2025-08-13 08:26:41'),
(114, '901000', 'ACC2025953639', 50000.00, 'deposit', 'completed', 0, 'LOAN202508131028021789', '2025-08-13 08:28:02', '2025-08-13 08:28:02', '2025-08-13 08:28:02'),
(115, '901000', 'ACC2025953639', -50000.00, 'withdrawal', 'completed', 0, 'REP202508131035261979', '2025-08-13 08:35:26', '2025-08-13 08:35:26', '2025-08-13 08:35:26'),
(116, '901000', 'ACC2025953639', 10000.00, 'deposit', 'completed', 0, 'LOAN202508131043121025', '2025-08-13 08:43:12', '2025-08-13 08:43:12', '2025-08-13 08:43:12'),
(117, '901000', 'ACC2025953639', -10000.00, 'withdrawal', 'completed', 0, 'REP202508131043368380', '2025-08-13 08:43:36', '2025-08-13 08:43:36', '2025-08-13 08:43:36'),
(118, '0', 'ACC2025458796', 1000.00, 'deposit', 'completed', 0, 'RCP202508131314332407', '2025-08-13 11:14:33', '2025-08-13 11:14:33', '2025-08-13 11:14:33'),
(119, '8c0423e3-6b2b-4564-940e-a2cfc6bad9b9', 'ACC2025562089', 1000.00, 'deposit', 'completed', 1, 'RCP202508131320551535', '2025-08-13 11:20:55', '2025-08-13 11:20:55', '2025-08-13 11:20:55'),
(120, '2e6911dd-cf54-4912-9664-d8723da19382', 'ACC2025790450', 1000.00, 'deposit', 'completed', 1, 'RCP202508131335407371', '2025-08-13 11:35:40', '2025-08-13 11:35:40', '2025-08-13 11:35:40'),
(121, '901000', 'ACC2025953639', -1000.00, 'withdrawal', 'completed', 0, 'WTH1755085853236741', '2025-08-13 11:50:53', '2025-08-13 11:50:53', '2025-08-13 11:50:53'),
(122, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', -1200.00, 'withdrawal', 'completed', 1, 'WTH202508131424253597', '2025-08-13 12:24:25', '2025-08-13 12:24:25', '2025-08-13 12:24:25');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `withdrawal_charge_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Default withdrawal charge rate in percentage'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `created_at`, `updated_at`, `withdrawal_charge_rate`) VALUES
('company_address', 'Kigali-Rwanda', '2025-05-09 22:08:15', '2025-05-11 10:39:13', 0.00),
('company_email', 'admin@loanautomate.com', '2025-05-09 16:55:44', '2025-08-13 15:14:27', 0.00),
('company_name', 'Loan Automate', '2025-05-09 16:55:44', '2025-08-13 15:14:01', 0.00),
('company_phone', '0784844687', '2025-05-09 22:08:15', '2025-05-11 10:39:13', 0.00),
('default_interest_rate', '5', '2025-05-09 22:08:15', '2025-08-13 13:01:37', 0.00),
('enable_auto_approval', '0', '2025-05-09 22:08:15', '2025-06-30 07:36:46', 0.00),
('interest_rate', '10', '2025-05-09 16:55:44', NULL, 0.00),
('maintenance_mode', '1', '2025-05-09 22:08:15', '2025-08-13 15:22:57', 0.00),
('max_loan_amount', '100000', '2025-05-09 16:55:44', NULL, 0.00),
('max_loan_duration', '36', '2025-05-09 16:55:44', NULL, 0.00),
('min_loan_amount', '1000', '2025-05-09 16:55:44', NULL, 0.00),
('min_loan_duration', '3', '2025-05-09 16:55:44', NULL, 0.00),
('risk_assessment_threshold', '700', '2025-05-09 22:08:15', NULL, 0.00),
('smtp_encryption', 'tls', '2025-05-09 16:55:44', NULL, 0.00),
('smtp_host', 'smtp.example.com', '2025-05-09 16:55:44', NULL, 0.00),
('smtp_password', '', '2025-05-09 16:55:44', NULL, 0.00),
('smtp_port', '587', '2025-05-09 16:55:44', NULL, 0.00),
('smtp_username', 'noreply@example.com', '2025-05-09 16:55:44', NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` varchar(36) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `type` enum('savings','repayment','admin_deposit','loangiven','paid') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `reference_number` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `user_id`, `account_number`, `loan_id`, `type`, `amount`, `status`, `transaction_date`, `reference_number`, `payment_method`, `notes`, `created_at`, `updated_at`) VALUES
(107, '0', 'ACC2025753236', NULL, 'savings', 2000.00, 'completed', '2025-08-11 14:56:31', 'RCP202508111456313478', 'cash', NULL, '2025-08-11 14:56:31', '2025-08-11 15:04:56'),
(108, '1', '', NULL, 'admin_deposit', -5000.00, 'completed', '2025-08-11 14:58:29', 'ADM1754917109911483', 'internal', NULL, '2025-08-11 14:58:29', NULL),
(109, '0', '', NULL, '', 7000.00, 'completed', '2025-08-11 14:58:29', 'USR1754917109715770', 'internal', NULL, '2025-08-11 14:58:29', '2025-08-11 15:04:05'),
(110, '0', 'ACC2025753236', NULL, '', 800.00, 'completed', '2025-08-11 15:10:23', 'REP-1754917823-2386-6899ebbfc01e0', 'loan', NULL, '2025-08-11 15:10:23', NULL),
(111, '1', '', NULL, 'loangiven', -800.00, 'completed', '2025-08-11 15:10:23', 'REP-1754917823-8451-6899ebbfc06ac', 'loan', NULL, '2025-08-11 15:10:23', NULL),
(112, '901000', 'ACC2025953639', NULL, 'savings', 2100.00, 'completed', '2025-08-12 12:01:00', 'RCP202508121201005933', 'cash', NULL, '2025-08-12 12:01:00', NULL),
(113, '901000', 'ACC2025953639', NULL, '', 100000.00, 'completed', '2025-08-12 12:49:39', 'REP-1754995779-8674-689b1c43d0b8c', 'loan', NULL, '2025-08-12 12:49:39', NULL),
(114, '1', '', NULL, 'loangiven', -100000.00, 'completed', '2025-08-12 12:49:39', 'REP-1754995779-3031-689b1c43d17b6', 'loan', NULL, '2025-08-12 12:49:39', NULL),
(115, '67', 'ACC2025886145', NULL, 'savings', 2800.00, 'completed', '2025-08-12 13:25:57', 'RCP202508121325577419', 'cash', NULL, '2025-08-12 13:25:57', NULL),
(116, '67', 'ACC2025886145', NULL, '', 4000.00, 'completed', '2025-08-12 13:27:58', 'REP-1754998078-9985-689b253e0f9e5', 'loan', NULL, '2025-08-12 13:27:58', NULL),
(117, '1', '', NULL, 'loangiven', -4000.00, 'completed', '2025-08-12 13:27:58', 'REP-1754998078-3550-689b253e0fc38', 'loan', NULL, '2025-08-12 13:27:58', NULL),
(118, '1', '', NULL, 'admin_deposit', -120000.00, 'completed', '2025-08-12 19:59:46', 'ADM1755021586081830', 'internal', NULL, '2025-08-12 19:59:46', NULL),
(119, '901000', '', NULL, '', 120000.00, 'completed', '2025-08-12 19:59:46', 'USR1755021586682224', 'internal', NULL, '2025-08-12 19:59:46', NULL),
(120, '1', '', NULL, 'paid', 16666.66, 'completed', '2025-08-12 20:00:18', 'REP-1755021618-1191-689b813244547', 'cash', NULL, '2025-08-12 20:00:18', NULL),
(121, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 16666.66, 'completed', '2025-08-12 20:00:18', 'REP-1755021618-7402-689b8132446a9', 'cash', NULL, '2025-08-12 20:00:18', NULL),
(122, '1', '', NULL, 'paid', 83333.34, 'completed', '2025-08-12 20:01:19', 'REP-1755021679-4346-689b816f76453', 'cash', NULL, '2025-08-12 20:01:19', NULL),
(123, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 16666.66, 'completed', '2025-08-12 20:01:19', 'REP-1755021679-1392-689b816f766c8', 'cash', NULL, '2025-08-12 20:01:19', NULL),
(124, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 16666.66, 'completed', '2025-08-12 20:01:19', 'REP-1755021679-1522-689b816f7683d', 'cash', NULL, '2025-08-12 20:01:19', NULL),
(125, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 16666.66, 'completed', '2025-08-12 20:01:19', 'REP-1755021679-9391-689b816f77a1d', 'cash', NULL, '2025-08-12 20:01:19', NULL),
(126, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 16666.66, 'completed', '2025-08-12 20:01:19', 'REP-1755021679-4921-689b816f77b7b', 'cash', NULL, '2025-08-12 20:01:19', NULL),
(127, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 16666.70, 'completed', '2025-08-12 20:01:19', 'REP-1755021679-1040-689b816f77c4d', 'cash', NULL, '2025-08-12 20:01:19', NULL),
(128, '901000', 'ACC2025953639', NULL, '', 1000.00, 'completed', '2025-08-12 20:03:29', 'REP-1755021809-7032-689b81f1d2fb7', 'loan', NULL, '2025-08-12 20:03:29', NULL),
(129, '1', '', NULL, 'loangiven', -1000.00, 'completed', '2025-08-12 20:03:29', 'REP-1755021809-7150-689b81f1d33fa', 'loan', NULL, '2025-08-12 20:03:29', NULL),
(130, '1', '', NULL, 'paid', 1000.00, 'completed', '2025-08-12 20:04:47', 'REP-1755021887-1120-689b823f061b3', 'cash', NULL, '2025-08-12 20:04:47', NULL),
(131, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 333.33, 'completed', '2025-08-12 20:04:47', 'REP-1755021887-7628-689b823f062b1', 'cash', NULL, '2025-08-12 20:04:47', NULL),
(132, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 333.33, 'completed', '2025-08-12 20:04:47', 'REP-1755021887-4254-689b823f0636d', 'cash', NULL, '2025-08-12 20:04:47', NULL),
(133, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 333.34, 'completed', '2025-08-12 20:04:47', 'REP-1755021887-5761-689b823f073e3', 'cash', NULL, '2025-08-12 20:04:47', NULL),
(134, '901000', 'ACC2025953639', NULL, '', 2000.00, 'completed', '2025-08-12 20:15:06', 'REP-1755022506-5469-689b84aa4d76f', 'loan', NULL, '2025-08-12 20:15:06', NULL),
(135, '1', '', NULL, 'loangiven', -2000.00, 'completed', '2025-08-12 20:15:06', 'REP-1755022506-6017-689b84aa4d839', 'loan', NULL, '2025-08-12 20:15:06', NULL),
(136, '1', '', NULL, 'paid', 2000.00, 'completed', '2025-08-12 20:16:07', 'REP-1755022567-6334-689b84e7ca7e5', 'cash', NULL, '2025-08-12 20:16:07', NULL),
(137, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 666.66, 'completed', '2025-08-12 20:16:07', 'REP-1755022567-9644-689b84e7caa1e', 'cash', NULL, '2025-08-12 20:16:07', NULL),
(138, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 666.66, 'completed', '2025-08-12 20:16:07', 'REP-1755022567-6824-689b84e7cabf3', 'cash', NULL, '2025-08-12 20:16:07', NULL),
(139, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 666.68, 'completed', '2025-08-12 20:16:07', 'REP-1755022567-1882-689b84e7cbe38', 'cash', NULL, '2025-08-12 20:16:07', NULL),
(140, '901000', 'ACC2025953639', NULL, '', 5000.00, 'completed', '2025-08-12 20:17:28', 'REP-1755022648-2761-689b85385af0b', 'loan', NULL, '2025-08-12 20:17:28', NULL),
(141, '1', '', NULL, 'loangiven', -5000.00, 'completed', '2025-08-12 20:17:28', 'REP-1755022648-3138-689b85385b01b', 'loan', NULL, '2025-08-12 20:17:28', NULL),
(142, '1', '', NULL, 'paid', 5000.00, 'completed', '2025-08-12 20:19:42', 'REP-1755022782-5343-689b85be36524', 'cash', NULL, '2025-08-12 20:19:42', NULL),
(143, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 1666.66, 'completed', '2025-08-12 20:19:42', 'REP-1755022782-6218-689b85be366f1', 'cash', NULL, '2025-08-12 20:19:42', NULL),
(144, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 1666.66, 'completed', '2025-08-12 20:19:42', 'REP-1755022782-2040-689b85be3794a', 'cash', NULL, '2025-08-12 20:19:42', NULL),
(145, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 1666.68, 'completed', '2025-08-12 20:19:42', 'REP-1755022782-7482-689b85be37db8', 'cash', NULL, '2025-08-12 20:19:42', NULL),
(146, '901000', 'ACC2025953639', NULL, '', 1000.00, 'completed', '2025-08-12 20:25:40', 'REP-1755023140-7177-689b8724a98d0', 'loan', NULL, '2025-08-12 20:25:40', NULL),
(147, '1', '', NULL, 'loangiven', -1000.00, 'completed', '2025-08-12 20:25:40', 'REP-1755023140-9445-689b8724a9a1c', 'loan', NULL, '2025-08-12 20:25:40', NULL),
(148, '1', '', NULL, 'paid', 1000.00, 'completed', '2025-08-12 20:26:45', 'REP-1755023205-5096-689b8765b383d', 'cash', NULL, '2025-08-12 20:26:45', NULL),
(149, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 333.33, 'completed', '2025-08-12 20:26:45', 'REP-1755023205-2543-689b8765b394f', 'cash', NULL, '2025-08-12 20:26:45', NULL),
(150, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 333.33, 'completed', '2025-08-12 20:26:45', 'REP-1755023205-2760-689b8765b47c0', 'cash', NULL, '2025-08-12 20:26:45', NULL),
(151, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, 'paid', 333.34, 'completed', '2025-08-12 20:26:45', 'REP-1755023205-3605-689b8765b4bac', 'cash', NULL, '2025-08-12 20:26:45', NULL),
(152, '67', 'ACC2025886145', NULL, '', 1000.00, 'completed', '2025-08-12 21:07:09', 'REP-1755025629-7358-689b90dd52354', 'loan', NULL, '2025-08-12 21:07:09', NULL),
(153, '1', '', NULL, 'loangiven', -1000.00, 'completed', '2025-08-12 21:07:09', 'REP-1755025629-7680-689b90dd524fc', 'loan', NULL, '2025-08-12 21:07:09', NULL),
(154, '1', '', NULL, 'paid', 333.33, 'completed', '2025-08-12 21:42:25', 'REP-1755027745-5237-689b99213f085', 'cash', NULL, '2025-08-12 21:42:25', NULL),
(155, '67', 'ACC2025886145', NULL, 'paid', 333.33, 'completed', '2025-08-12 21:42:25', 'REP-1755027745-6514-689b992140f93', 'cash', NULL, '2025-08-12 21:42:25', NULL),
(156, '1', '', NULL, 'paid', 333.33, 'completed', '2025-08-12 21:43:47', 'REP-1755027827-9566-689b99732c92d', 'cash', NULL, '2025-08-12 21:43:47', NULL),
(157, '67', 'ACC2025886145', NULL, 'paid', 333.33, 'completed', '2025-08-12 21:43:47', 'REP-1755027827-5887-689b99732d0fd', 'cash', NULL, '2025-08-12 21:43:47', NULL),
(158, '1', '', NULL, 'paid', 333.34, 'completed', '2025-08-12 21:44:20', 'REP-1755027860-5543-689b9994edc08', 'cash', NULL, '2025-08-12 21:44:20', NULL),
(159, '67', 'ACC2025886145', NULL, 'paid', 333.34, 'completed', '2025-08-12 21:44:20', 'REP-1755027860-5322-689b9994edde7', 'cash', NULL, '2025-08-12 21:44:20', NULL),
(160, '901000', 'ACC2025953639', NULL, '', 1000.00, 'completed', '2025-08-13 10:25:27', 'REP-1755073527-4491-689c4bf784f4d', 'loan', NULL, '2025-08-13 10:25:27', NULL),
(161, '1', '', NULL, 'loangiven', -1000.00, 'completed', '2025-08-13 10:25:27', 'REP-1755073527-3958-689c4bf78571a', 'loan', NULL, '2025-08-13 10:25:27', NULL),
(162, '1', '', NULL, 'paid', 1000.00, 'completed', '2025-08-13 10:26:41', 'REP-1755073601-5301-689c4c41ea8d9', 'cash', NULL, '2025-08-13 10:26:41', NULL),
(163, '901000', 'ACC2025953639', NULL, 'paid', 333.33, 'completed', '2025-08-13 10:26:41', 'REP-1755073601-8300-689c4c41eae6f', 'cash', NULL, '2025-08-13 10:26:41', NULL),
(164, '901000', 'ACC2025953639', NULL, 'paid', 333.33, 'completed', '2025-08-13 10:26:41', 'REP-1755073601-3952-689c4c41eb007', 'cash', NULL, '2025-08-13 10:26:41', NULL),
(165, '901000', 'ACC2025953639', NULL, 'paid', 333.34, 'completed', '2025-08-13 10:26:41', 'REP-1755073601-5424-689c4c41eb16e', 'cash', NULL, '2025-08-13 10:26:41', NULL),
(166, '901000', 'ACC2025953639', NULL, '', 50000.00, 'completed', '2025-08-13 10:28:02', 'REP-1755073682-7406-689c4c920d781', 'loan', NULL, '2025-08-13 10:28:02', NULL),
(167, '1', '', NULL, 'loangiven', -50000.00, 'completed', '2025-08-13 10:28:02', 'REP-1755073682-2796-689c4c920dd93', 'loan', NULL, '2025-08-13 10:28:02', NULL),
(168, '1', '', NULL, 'paid', 50000.00, 'completed', '2025-08-13 10:35:26', 'REP-1755074126-5134-689c4e4e6b547', 'cash', NULL, '2025-08-13 10:35:26', NULL),
(169, '901000', 'ACC2025953639', NULL, 'paid', 16666.66, 'completed', '2025-08-13 10:35:26', 'REP-1755074126-6287-689c4e4e6bbae', 'cash', NULL, '2025-08-13 10:35:26', NULL),
(170, '901000', 'ACC2025953639', NULL, 'paid', 16666.66, 'completed', '2025-08-13 10:35:26', 'REP-1755074126-5225-689c4e4e6c168', 'cash', NULL, '2025-08-13 10:35:26', NULL),
(171, '901000', 'ACC2025953639', NULL, 'paid', 16666.68, 'completed', '2025-08-13 10:35:26', 'REP-1755074126-7437-689c4e4e6c623', 'cash', NULL, '2025-08-13 10:35:26', NULL),
(172, '901000', 'ACC2025953639', NULL, '', 10000.00, 'completed', '2025-08-13 10:43:12', 'REP-1755074592-1520-689c5020933c3', 'loan', NULL, '2025-08-13 10:43:12', NULL),
(173, '1', '', NULL, 'loangiven', -10000.00, 'completed', '2025-08-13 10:43:12', 'REP-1755074592-8225-689c502093a4f', 'loan', NULL, '2025-08-13 10:43:12', NULL),
(174, '1', '', NULL, 'paid', 10000.00, 'completed', '2025-08-13 10:43:36', 'REP-1755074616-2717-689c503812b35', 'cash', NULL, '2025-08-13 10:43:36', NULL),
(175, '901000', 'ACC2025953639', NULL, 'paid', 3333.33, 'completed', '2025-08-13 10:43:36', 'REP-1755074616-7178-689c503812f1c', 'cash', NULL, '2025-08-13 10:43:36', NULL),
(176, '901000', 'ACC2025953639', NULL, 'paid', 3333.33, 'completed', '2025-08-13 10:43:36', 'REP-1755074616-8920-689c503813a8e', 'cash', NULL, '2025-08-13 10:43:36', NULL),
(177, '901000', 'ACC2025953639', NULL, 'paid', 3333.34, 'completed', '2025-08-13 10:43:36', 'REP-1755074616-7830-689c503813dd9', 'cash', NULL, '2025-08-13 10:43:36', NULL),
(178, '0', 'ACC2025458796', NULL, 'savings', 1000.00, 'completed', '2025-08-13 13:14:33', 'RCP202508131314332407', 'cash', NULL, '2025-08-13 13:14:33', NULL),
(179, '8c0423e3-6b2b-4564-940e-a2cfc6bad9b9', 'ACC2025562089', NULL, 'savings', 1000.00, 'completed', '2025-08-13 13:20:55', 'RCP202508131320551535', 'cash', NULL, '2025-08-13 13:20:55', NULL),
(180, '2e6911dd-cf54-4912-9664-d8723da19382', 'ACC2025790450', NULL, 'savings', 1000.00, 'completed', '2025-08-13 13:35:40', 'RCP202508131335407371', 'cash', NULL, '2025-08-13 13:35:40', NULL),
(181, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', NULL, '', -1200.00, 'completed', '2025-08-13 14:24:25', 'WTH202508131424253597', 'cash', NULL, '2025-08-13 14:24:25', NULL),
(182, '1', 'ACC2025953639', NULL, '', 1100.00, 'completed', '2025-08-13 14:24:25', 'ADM202508131424257229', 'internal', NULL, '2025-08-13 14:24:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(36) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `account_number` varchar(20) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `last_login_attempt` timestamp NULL DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `lockout_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `profile_picture`, `phone_number`, `role`, `status`, `account_number`, `amount`, `created_at`, `updated_at`, `password_changed_at`, `login_attempts`, `last_login_attempt`, `is_locked`, `lockout_until`) VALUES
('', '', '$2y$10$OznMmdTipkc.Rsk.xyzgkeDqBGeCLbbGrQapHEx9G0Yh4UXY3U4/i', 'NIYOKWIZERWA Benjamin', 'niyoben568@gmail.com', NULL, '0784844687', 'customer', 'active', 'ACC2025458796', 1000.00, '2025-08-13 11:14:33', '2025-08-13 11:14:33', NULL, 0, NULL, 0, NULL),
('1', 'Ismael', '$2y$10$J5IKlx/yuvlJAZmv96zZMu15bRC/KOsB3HCEY0rXdf94YhldAf04K', 'NIYOMUKIZA Ismael', NULL, '1.jpg', '0782844685', 'admin', 'active', 'ACC2025725077', 644300.00, '2025-05-10 09:22:37', '2025-08-13 12:24:25', NULL, 0, NULL, 0, NULL),
('2e6911dd-cf54-4912-9664-d8723da19382', 'john', '$2y$10$3vKARmnfkfyMihBqKFIO4.e6ami/qGs2KUphhoJ7UOdv2vo3/n5dO', 'JOHN KWIZERA', 'john@gmail.com', NULL, '0784844687', 'customer', 'active', 'ACC2025790450', 1000.00, '2025-08-13 11:35:40', '2025-08-13 11:35:40', NULL, 0, NULL, 0, NULL),
('67b5a1c9-8e41-4e2a-9ccd-00938668f2c3', 'abc', '$2y$10$heIEyT3DDwJ23.QRqPt6AeQqv0NYIqujrI0XVb14q6QTXRc2ndFEG', 'abc', NULL, NULL, '0789105167', 'customer', 'active', 'ACC2025886145', 1300.00, '2025-08-12 11:25:57', '2025-08-13 06:08:00', '2025-08-12 11:25:57', 0, NULL, 0, NULL),
('8c0423e3-6b2b-4564-940e-a2cfc6bad9b9', 'niyokwizerwa', '$2y$10$z77jguxK8TFLjam970sK3.O0NR2HIN.QzKGQBPMra01DmFjzuVoja', 'NIYOKWIZERWA Ben', 'niyoben56@gmail.com', NULL, '0784844687', 'customer', 'active', 'ACC2025562089', 1000.00, '2025-08-13 11:20:55', '2025-08-13 11:20:55', NULL, 0, NULL, 0, NULL),
('901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'Aime', '$2y$10$XQJVSuEoXN.KautnmIfAauyT0gOs1rc1mrLo7ic9xK.KLe3LVdThO', 'Aime', 'aime@gmail.com', NULL, '0784844687', 'customer', 'active', 'ACC2025953639', 10800.00, '2025-08-12 10:01:00', '2025-08-13 12:24:25', '2025-08-12 10:01:00', 0, NULL, 0, NULL),
('a49c42ad-8cd1-4b30-9835-949b2ebe8db1', 'Aimable', '$2y$10$VDH0o0CU/auEyoFqjiaXqeSMLaiR3la30mc3mi/HpbnuTG/TrVuGm', 'TUYIZERE Aimable ', 'aimabletuyizere63@gmail.com', NULL, '0784844687', 'customer', 'active', 'ACC2025753236', 10000.00, '2025-08-11 12:56:31', '2025-08-11 13:57:50', '2025-08-11 12:56:31', 0, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_backup`
--

CREATE TABLE `users_backup` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `account_number` varchar(20) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_charges`
--

CREATE TABLE `withdrawal_charges` (
  `id` int(11) NOT NULL,
  `min_amount` decimal(10,2) NOT NULL,
  `max_amount` decimal(10,2) NOT NULL,
  `charge_amount` decimal(10,2) NOT NULL,
  `charge_type` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `withdrawal_charges`
--

INSERT INTO `withdrawal_charges` (`id`, `min_amount`, `max_amount`, `charge_amount`, `charge_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 0.01, 1000.00, 50.00, 'percentage', 1, '2025-08-13 12:00:58', '2025-08-13 12:37:15'),
(2, 1000.01, 5000.00, 100.00, 'fixed', 1, '2025-08-13 12:00:58', '2025-08-13 12:00:58'),
(3, 5000.01, 10000.00, 200.00, 'fixed', 1, '2025-08-13 12:00:58', '2025-08-13 12:00:58'),
(4, 10000.01, 50000.00, 500.00, 'fixed', 1, '2025-08-13 12:00:58', '2025-08-13 12:00:58'),
(5, 50000.01, 999999.99, 1000.00, 'fixed', 1, '2025-08-13 12:00:58', '2025-08-13 12:00:58'),
(11, 1000000.01, 1500000.00, 1500.00, 'fixed', 1, '2025-08-13 12:35:33', '2025-08-13 12:35:33');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `request_id` int(11) NOT NULL,
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
  `expired_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `withdrawal_requests`
--

INSERT INTO `withdrawal_requests` (`request_id`, `user_id`, `account_number`, `amount`, `charges`, `total_amount`, `status`, `requested_at`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `rejection_reason`, `expired_at`) VALUES
(1, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', 1000.00, 50.00, 1050.00, 'expired', '2025-08-13 14:19:40', NULL, NULL, NULL, NULL, NULL, '2025-08-13 14:22:36'),
(2, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', 1100.00, 100.00, 1200.00, 'approved', '2025-08-13 14:23:56', '1', '2025-08-13 14:24:25', NULL, NULL, NULL, NULL),
(3, '901e3c84-cb47-4838-8b2a-d6b8ce7c3387', 'ACC2025953639', 700.00, 50.00, 750.00, 'expired', '2025-08-13 14:25:51', NULL, NULL, NULL, NULL, NULL, '2025-08-13 14:26:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_transactions`
--
ALTER TABLE `admin_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_customer_account` (`customer_account`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`loan_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `rejected_by` (`rejected_by`);

--
-- Indexes for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD PRIMARY KEY (`repayment_id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_user_changed` (`user_id`,`changed_at`);

--
-- Indexes for table `savings`
--
ALTER TABLE `savings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_account_number` (`account_number`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `idx_account_number` (`account_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `account_number` (`account_number`),
  ADD UNIQUE KEY `idx_account_number` (`account_number`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- Indexes for table `withdrawal_charges`
--
ALTER TABLE `withdrawal_charges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `requested_at` (`requested_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_transactions`
--
ALTER TABLE `admin_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `loan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  MODIFY `repayment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_history`
--
ALTER TABLE `password_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `savings`
--
ALTER TABLE `savings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=183;

--
-- AUTO_INCREMENT for table `withdrawal_charges`
--
ALTER TABLE `withdrawal_charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD CONSTRAINT `loan_repayments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`loan_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
