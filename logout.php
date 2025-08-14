<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/utilities.php';

$auth = new Auth();
$auth->logout();

// Redirect to login page
header('Location: ' . url('login.php'));
exit; 