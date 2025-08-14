<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/utilities.php';

$auth = new Auth();

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

$user = null;
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Automate</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo url('assets/css/style.css'); ?>" rel="stylesheet">
    <style>
        .navbar-nav .nav-link.active {
            font-weight: bold;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .dropdown-item.active {
            background-color: #0d6efd;
            color: white;
        }

        /* Style for profile picture in header */
        .header-profile-pic {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo url(); ?>">LA</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if ($auth->isLoggedIn()): ?>
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
                                href="<?php echo url('dashboard.php'); ?>">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'savings.php' ? 'active' : ''; ?>"
                                href="<?php echo url('savings.php'); ?>">
                                <i class="fas fa-piggy-bank"></i> Savings
                            </a>
                        </li>
                        <?php if (!$auth->hasRole('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'loans.php' ? 'active' : ''; ?>"
                                    href="<?php echo url('loans.php'); ?>">
                                    <i class="fas fa-hand-holding-usd"></i> Loans
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($auth->hasRole('admin')): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i> Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item <?php echo $current_page === 'users.php' ? 'active' : ''; ?>"
                                            href="<?php echo url('admin/users.php'); ?>">
                                            <i class="fas fa-users"></i> Users
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item <?php echo $current_page === 'withdrawal_requests.php' ? 'active' : ''; ?>"
                                            href="<?php echo url('admin/withdrawal_requests.php'); ?>">
                                            <i class="fas fa-money-bill-wave"></i> Withdrawal Requests
                                        </a>
                                    </li>
                                                            <li>
                            <a class="dropdown-item <?php echo $current_page === 'withdrawal_charges.php' ? 'active' : ''; ?>"
                                href="<?php echo url('admin/withdrawal_charges.php'); ?>">
                                <i class="fas fa-percentage"></i> Withdrawal Charges
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?php echo $current_page === 'contact_messages.php' ? 'active' : ''; ?>"
                                href="<?php echo url('admin/contact_messages.php'); ?>">
                                <i class="fas fa-envelope"></i> Contact Messages
                            </a>
                        </li>
                                    <li>
                                        <a class="dropdown-item <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>"
                                            href="<?php echo url('admin/settings.php'); ?>">
                                            <i class="fas fa-cogs"></i> Settings
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <?php
                                $profile_pic_src = PROFILE_PICS_DIR . ($user['profile_picture'] ?? 'default_avatar.png');
                                if (!file_exists($profile_pic_src) || is_dir($profile_pic_src)) {
                                    $profile_pic_src = PROFILE_PICS_DIR . 'default_avatar.png';
                                }
                                ?>
                                <img src="<?php echo url($profile_pic_src); ?>" alt="Profile Picture"
                                    class="header-profile-pic">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo url('profile.php'); ?>">Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo url('change_password.php'); ?>">Change Password</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php echo url('logout.php'); ?>">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="container-fluid py-4">