<?php
// Define base URL
$base_url = '/LOAN_AUTOMATE';

// Define the directory for profile pictures relative to the project root
define('PROFILE_PICS_DIR', 'assets/profile_pics/');

// Function to generate URL
function url($path = '')
{
    global $base_url;
    return $base_url . '/' . ltrim($path, '/');
}