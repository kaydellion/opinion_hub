<?php
// header.php
if (!defined('SITE_URL')) {
    include_once __DIR__ . '/connect.php';
}
$current_user = getCurrentUser();

// Auto-pause expired advertisements (runs once per session)
if (!isset($_SESSION['ads_checked'])) {
    pauseExpiredAds();
    $_SESSION['ads_checked'] = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="icon" type="image/jpeg" href="<?php echo SITE_URL . SITE_FAVICON; ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff6b35;
            --primary-dark: #e55a23;
            --secondary: #f7931e;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #fbbf24;
            --info: #3b82f6;
            --dark: #1e293b;
            --light: #fff5f0;
            --gray-100: #fef3ed;
            --gray-200: #fde8dd;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-900: #0f172a;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--gray-700);
            background: var(--light);
            line-height: 1.6;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }
        
        h1 { font-size: 32px; }
        h2 { font-size: 28px; }
        h3 { font-size: 24px; }
        h4 { font-size: 20px; }
        h5 { font-size: 16px; }
        h6 { font-size: 14px; }
        
        p, li, td, th {
            font-size: 14px;
        }
        
        small {
            font-size: 12px;
        }
        
        .navbar {
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary) !important;
            letter-spacing: -0.5px;
        }
        
        .nav-link {
            color: var(--gray-600) !important;
            font-size: 14px;
            font-weight: 500;
            margin: 0 8px;
            padding: 8px 12px !important;
            transition: all 0.2s;
            border-radius: 6px;
        }
        
        .nav-link:hover {
            color: var(--primary) !important;
            background: var(--gray-100);
        }
        
        .nav-link.active {
            color: var(--primary) !important;
            background: #fff5f0;
        }
        
        .btn {
            font-size: 14px;
            font-weight: 500;
            padding: 8px 20px;
            border-radius: 8px;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-sm {
            font-size: 12px;
            padding: 6px 12px;
        }
        
        .btn-lg {
            font-size: 16px;
            padding: 12px 28px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255,107,53,0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            background: white;
            margin-bottom: 24px;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 24px;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .card-body {
            padding: 24px;
        }
        
        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: var(--gray-700);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            font-size: 14px;
            padding: 10px 14px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        
        .badge {
            font-size: 12px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 6px;
        }
        
        .badge.bg-primary { background: var(--primary) !important; }
        .badge.bg-success { background: var(--success) !important; }
        .badge.bg-danger { background: var(--danger) !important; }
        .badge.bg-warning { background: var(--warning) !important; }
        .badge.bg-info { background: var(--info) !important; }
        
        /* Bootstrap primary color overrides */
        .bg-primary { background-color: var(--primary) !important; }
        .text-primary { color: var(--primary) !important; }
        .border-primary { border-color: var(--primary) !important; }
        .btn-primary { background-color: var(--primary) !important; border-color: var(--primary) !important; }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active { 
            background-color: var(--primary-dark) !important; 
            border-color: var(--primary-dark) !important; 
        }
        .btn-outline-primary { 
            color: var(--primary) !important; 
            border-color: var(--primary) !important; 
        }
        .btn-outline-primary:hover, .btn-outline-primary:focus, .btn-outline-primary:active { 
            background-color: var(--primary) !important; 
            border-color: var(--primary) !important; 
            color: white !important;
        }
        
        .alert {
            font-size: 14px;
            border-radius: 10px;
            border: none;
            padding: 16px 20px;
        }
        
        .table {
            font-size: 14px;
        }
        
        .table th {
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-600);
            border-bottom: 2px solid var(--gray-200);
        }
        
        a {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        a:hover {
            color: var(--primary-dark);
        }
        
        footer {
            background: var(--dark);
            color: white;
            margin-top: 80px;
            padding: 40px 0 20px;
            font-size: 14px;
        }
        
        footer h5 {
            color: white !important;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 16px;
        }
        
        footer a {
            color: var(--primary);
        }
        
        footer a:hover {
            color: #e55a23;
        }
        
        /* Mobile toggle icon - make it black */
        .navbar-toggler {
            border-color: rgba(0,0,0,0.1);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.15rem rgba(0, 0, 0, 0.15);
        }
        
        /* Advertisement Styles */
        .advertisement {
            margin: 20px 0;
            text-align: center;
            overflow: hidden;
        }
        
        .advertisement a {
            display: block;
            text-decoration: none;
        }
        
        .advertisement img.ad-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .advertisement:hover img.ad-image {
            transform: scale(1.02);
        }
        
        .ad-placeholder {
            padding: 40px 20px;
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            font-weight: 600;
            border-radius: 8px;
        }
        
        .ad-label {
            font-size: 10px;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
            <?php 
            // Check if logo file exists, otherwise show icon + text
            $logo_path = __DIR__ . '/' . SITE_LOGO;
            if (file_exists($logo_path)): 
            ?>
                <img src="<?php echo SITE_URL . SITE_LOGO; ?>" alt="<?php echo SITE_NAME; ?>" style="height: 35px; width: auto;">
            <?php else: ?>
                <i class="fas fa-chart-pie"></i> <?php echo SITE_NAME; ?>
            <?php endif; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn()) { ?>
                    <?php if ($current_user['role'] === 'admin') { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminUsersDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-users"></i> Users
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="adminUsersDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/users.php"><i class="fas fa-users"></i> All Users</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/clients.php"><i class="fas fa-briefcase"></i> Clients</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/subscription-clients.php"><i class="fas fa-crown"></i> Subscription Clients</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/agents.php"><i class="fas fa-user-secret"></i> Agents</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/agents.php?status=pending"><i class="fas fa-user-clock"></i> Pending Agent Approvals</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/manage-payouts.php"><i class="fas fa-hand-holding-usd"></i> Manage Payouts</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminContentDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-folder"></i> Content
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="adminContentDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/blog.php"><i class="fas fa-blog"></i> Blog Posts</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/blog-approval.php"><i class="fas fa-check-circle"></i> Blog Approvals</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/polls.php"><i class="fas fa-poll"></i> Manage Polls</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/poll-types.php"><i class="fas fa-chart-pie"></i> Poll Types</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/ads.php"><i class="fas fa-ad"></i> Advertisements</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/poll-results.php"><i class="fas fa-chart-bar"></i> All Poll Results</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminSystemDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i> System
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="adminSystemDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/settings.php"><i class="fas fa-sliders-h"></i> Platform Settings</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/transactions.php"><i class="fas fa-receipt"></i> All Transactions</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/manage-credits.php"><i class="fas fa-credit-card"></i> Manage Credits</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/sms-delivery-reports.php"><i class="fas fa-paper-plane"></i> SMS Delivery Reports</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>polls.php"><i class="fas fa-poll"></i> View All Polls</a></li>
                            </ul>
                        </li>
                    <?php } elseif ($current_user['role'] === 'client') { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="clientPollsDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-poll-h"></i> My Polls
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="clientPollsDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/manage-polls.php"><i class="fas fa-list"></i> Manage Polls</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/create-poll.php"><i class="fas fa-plus"></i> Create Poll</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>bookmarks.php"><i class="fas fa-bookmark"></i> My Bookmarks</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/send-invites.php"><i class="fas fa-envelope"></i> Send Invites</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/sms-delivery-status.php"><i class="fas fa-paper-plane"></i> SMS Delivery Status</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/sms-credits-management.php"><i class="fas fa-credit-card"></i> SMS Credits</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>my-purchased-results.php"><i class="fas fa-folder-open"></i> My Purchases</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="clientContactsDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-address-book"></i> Contacts
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="clientContactsDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/contacts.php"><i class="fas fa-users"></i> Manage Contacts</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/send-bulk.php"><i class="fas fa-paper-plane"></i> Bulk Messaging</a></li>
                            </ul>
                        </li>
                    <?php } elseif ($current_user['role'] === 'agent') { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="agentEarningsDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-money-bill-wave"></i> Earnings
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="agentEarningsDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/my-earnings.php"><i class="fas fa-coins"></i> My Earnings</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/request-payout.php"><i class="fas fa-hand-holding-usd"></i> Request Payout</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/payouts.php"><i class="fas fa-wallet"></i> Payout History</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/buy-sms-credits.php"><i class="fas fa-sms"></i> Buy SMS Credits</a></li>
                            </ul>
                        </li>
                    <?php } else { ?>
                        <!-- Regular user dashboard -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                    <?php } ?>
                    
                    <!-- Databank - Always visible for logged-in users -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="databankDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-database"></i> Databank
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="databankDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>databank.php"><i class="fas fa-shopping-cart"></i> Purchase Results</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>my-purchased-results.php"><i class="fas fa-folder-open"></i> My Purchases</a></li>
                        </ul>
                    </li>

                    <?php if ($current_user['role'] !== 'admin' && $current_user['role'] !== 'client') { ?>
                        <?php if ($current_user['role'] === 'agent') { ?>
                            <!-- Agent: Browse Polls with Share & Earn submenu -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="agentPollsDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-poll-h"></i> Browse Polls
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="agentPollsDropdown">
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>polls.php"><i class="fas fa-list"></i> All Polls</a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>bookmarks.php"><i class="fas fa-bookmark"></i> My Bookmarks</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><h6 class="dropdown-header"><i class="fas fa-share-alt me-2"></i>Share & Earn</h6></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/browse-polls.php"><i class="fas fa-th-large"></i> Browse Polls to Share</a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/referrals.php"><i class="fas fa-users"></i> My Referrals</a></li>
                                </ul>
                            </li>
                        <?php } else { ?>
                            <!-- Non-agent, non-client: Regular user with Polls dropdown -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userPollsDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-poll-h"></i> Polls
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="userPollsDropdown">
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>polls.php"><i class="fas fa-list"></i> Browse Polls</a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>bookmarks.php"><i class="fas fa-bookmark"></i> My Bookmarks</a></li>
                                </ul>
                            </li>
                        <?php } ?>
                    <?php } ?>
                    
                    <?php if ($current_user['role'] !== 'admin') { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="blogDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-blog"></i> Blog
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="blogDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>blog.php"><i class="fas fa-book-open"></i> View Blog</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>blog/create.php"><i class="fas fa-plus"></i> New Post</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>blog/my-posts.php"><i class="fas fa-file-alt"></i> My Posts</a></li>
                        </ul>
                    </li>
                    <?php } ?>
                    
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>notifications.php">
                            <i class="fas fa-bell"></i> 
                            <?php
                            // Get unread notifications count
                            $notif_count_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
                            $notif_stmt = $conn->prepare($notif_count_sql);
                            if ($notif_stmt) {
                                $notif_stmt->bind_param("i", $current_user['id']);
                                $notif_stmt->execute();
                                $notif_count = $notif_stmt->get_result()->fetch_assoc()['count'];
                                if ($notif_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                        <?= $notif_count > 99 ? '99+' : $notif_count ?>
                                    </span>
                                <?php endif;
                            }
                            ?>
                        </a>
                    </li>
                    
                    <?php if ($current_user['role'] !== 'client' && $current_user['role'] !== 'agent' && $current_user['role'] !== 'admin') { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>agent/become-agent.php">
                            <i class="fas fa-user-tie"></i> Become an Agent
                        </a>
                    </li>
                    <?php } ?>
                    
                    <?php if ($current_user['role'] !== 'admin') { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="moreDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i> More
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="moreDropdown">
                            <?php if ($current_user['role'] === 'client') { ?>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/manage-polls.php"><i class="fas fa-list-alt"></i> Manage Polls</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/contacts.php"><i class="fas fa-address-book"></i> Contact Lists</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/send-invites.php"><i class="fas fa-paper-plane"></i> Send Invites</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/buy-credits.php"><i class="fas fa-credit-card"></i> Buy Credits</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/subscription.php"><i class="fas fa-crown"></i> My Subscription</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php } elseif ($current_user['role'] === 'agent') { ?>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/share-poll.php"><i class="fas fa-share-alt"></i> Share Polls</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/buy-sms-credits.php"><i class="fas fa-sms"></i> Buy SMS Credits</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/payouts.php"><i class="fas fa-wallet"></i> Earnings</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php } ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                        </ul>
                    </li>
                    <?php } ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo $current_user['first_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>bookmarks.php"><i class="fas fa-bookmark"></i> My Bookmarks</a></li>
                            <?php if ($current_user['role'] === 'client') { ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/my-polls.php"><i class="fas fa-list"></i> My Polls</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/my-ads.php"><i class="fas fa-ad"></i> My Advertisements</a></li>
                            <?php } elseif ($current_user['role'] === 'agent') { ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/payouts.php"><i class="fas fa-money-bill-wave"></i> My Earnings</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/buy-sms-credits.php"><i class="fas fa-sms"></i> Buy SMS Credits</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/my-ads.php"><i class="fas fa-ad"></i> My Advertisements</a></li>
                            <?php } elseif ($current_user['role'] === 'user') { ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>client/my-ads.php"><i class="fas fa-ad"></i> My Advertisements</a></li>
                            <?php } ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php } else { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>about.php">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>polls.php">
                            <i class="fas fa-poll"></i> Polls
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>databank.php">
                            <i class="fas fa-database"></i> Databank
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-h"></i> More
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>pricing.php"><i class="fas fa-tags"></i> Pricing</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>agent/become-agent.php"><i class="fas fa-user-tie"></i> Become an Agent</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>advertise.php"><i class="fas fa-bullhorn"></i> Advertise</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>signin.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-2" href="<?php echo SITE_URL; ?>signup.php">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a>
                    </li>
                <?php } ?>
                
                <!-- Search Form -->
                <li class="nav-item">
                    <form class="d-flex" method="GET" action="<?php echo SITE_URL; ?>search.php">
                        <div class="input-group" style="width: 250px;">
                            <input type="text" class="form-control form-control-sm" name="q" placeholder="Search..." aria-label="Search">
                            <button class="btn btn-outline-primary btn-sm" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Global Success/Error Messages -->
<?php
$session_errors = $_SESSION['errors'] ?? [];
$session_success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

if (!empty($session_success)): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Success!</strong> <?php echo htmlspecialchars($session_success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($session_errors)): ?>
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Error!</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($session_errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<main>

