<?php
$page_title = "Dashboard";
include_once 'header.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "login.php");
    exit;
}

$user = getCurrentUser();

// Redirect to appropriate dashboard based on role
switch ($user['role']) {
    case 'admin':
        include 'dashboards/admin-dashboard.php';
        break;
    case 'client':
        include 'dashboards/client-dashboard.php';
        break;
    case 'agent':
        include 'dashboards/agent-dashboard.php';
        break;
    default:
        include 'dashboards/user-dashboard.php';
        break;
}

include_once 'footer.php';
?>
