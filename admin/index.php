<?php
session_start();
require_once '../connect.php';
require_once '../functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Not logged in, redirect to main login
    header("Location: " . SITE_URL . "signin.php");
    exit;
}

// Check if user is admin
$user = getCurrentUser();
if ($user['role'] !== 'admin') {
    // Not admin, redirect to their appropriate dashboard
    $_SESSION['errors'] = ["Access Denied: Admin privileges required."];
    header("Location: " . SITE_URL . "dashboard.php");
    exit;
}

// User is admin, redirect to admin dashboard
header("Location: dashboard.php");
exit;
