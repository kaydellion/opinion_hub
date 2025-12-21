<?php
require_once 'connect.php';
require_once 'functions.php';

requireRole(['client', 'admin']);

$user = getCurrentUser();
$export_type = sanitize($_GET['type'] ?? '');
$poll_id = isset($_GET['poll_id']) ? (int)$_GET['poll_id'] : 0;

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="export_' . $export_type . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

switch ($export_type) {
    case 'poll_responses':
        if ($poll_id === 0) {
            echo "Invalid poll ID";
            exit;
        }
        
        // Verify ownership
        $poll = getPoll($poll_id);
        if (!$poll || ($poll['created_by'] != $user['id'] && $user['role'] !== 'admin')) {
            echo "Access denied";
            exit;
        }
        
        // CSV header
        fputcsv($output, ['Response ID', 'Participant Name', 'Email', 'Phone', 'IP Address', 'Submitted At']);
        
        // Get responses
        $responses = $conn->query("SELECT * FROM poll_responses WHERE poll_id = $poll_id ORDER BY responded_at DESC");
        while ($row = $responses->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['participant_name'],
                $row['participant_email'],
                $row['participant_phone'],
                $row['ip_address'],
                $row['responded_at']
            ]);
        }
        break;
        
    case 'polls':
        // Export all polls
        if ($user['role'] !== 'admin' && $user['role'] !== 'client') {
            echo "Access denied";
            exit;
        }
        
        fputcsv($output, ['Poll ID', 'Title', 'Category', 'Status', 'Total Responses', 'Start Date', 'End Date', 'Created At']);
        
        $where = $user['role'] === 'admin' ? '' : "WHERE created_by = {$user['id']}";
        $polls = $conn->query("SELECT p.*, c.name as category_name 
                               FROM polls p 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               $where
                               ORDER BY p.created_at DESC");
        
        while ($row = $polls->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['title'],
                $row['category_name'] ?? '',
                $row['status'],
                $row['total_responses'],
                $row['start_date'],
                $row['end_date'],
                $row['created_at']
            ]);
        }
        break;
        
    case 'agents':
        if ($user['role'] !== 'admin' && $user['role'] !== 'client') {
            echo "Access denied";
            exit;
        }
        
        fputcsv($output, ['Agent ID', 'Name', 'Email', 'Phone', 'Gender', 'State', 'LGA', 'Education', 'Total Earnings', 'Tasks Completed', 'Status', 'Joined']);
        
        $agents = $conn->query("SELECT a.*, 
                                CONCAT(u.first_name, ' ', u.last_name) as full_name, 
                                u.email, 
                                u.phone,
                                u.gender,
                                u.state,
                                u.lga,
                                u.educational_level,
                                u.created_at as user_created_at
                                FROM agents a 
                                JOIN users u ON a.user_id = u.id 
                                ORDER BY a.created_at DESC");
        
        while ($row = $agents->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['full_name'],
                $row['email'],
                $row['phone'] ?? '',
                $row['gender'] ?? '',
                $row['state'] ?? '',
                $row['lga'] ?? '',
                $row['educational_level'] ?? '',
                $row['total_earnings'],
                $row['tasks_completed'],
                $row['approval_status'],
                $row['created_at']
            ]);
        }
        break;
        
    case 'transactions':
        if ($user['role'] !== 'admin') {
            echo "Access denied";
            exit;
        }
        
        fputcsv($output, ['Transaction ID', 'User', 'Reference', 'Amount', 'Type', 'Status', 'Payment Method', 'Created At']);
        
        $transactions = $conn->query("SELECT t.*, 
                                      CONCAT(u.first_name, ' ', u.last_name) as user_name, 
                                      u.email 
                                      FROM transactions t 
                                      JOIN users u ON t.user_id = u.id 
                                      ORDER BY t.created_at DESC");
        
        while ($row = $transactions->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['user_name'] . ' (' . $row['email'] . ')',
                $row['reference'],
                $row['amount'],
                $row['type'],
                $row['status'],
                $row['payment_method'],
                $row['created_at']
            ]);
        }
        break;
        
    case 'messages':
        if ($user['role'] !== 'admin' && $user['role'] !== 'client') {
            echo "Access denied";
            exit;
        }
        
        fputcsv($output, ['Message ID', 'Type', 'Recipient', 'Message', 'Status', 'Credits Used', 'Sent At']);
        
        $where = $user['role'] === 'admin' ? '' : "WHERE user_id = {$user['id']}";
        $messages = $conn->query("SELECT * FROM message_logs $where ORDER BY created_at DESC LIMIT 10000");
        
        while ($row = $messages->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['message_type'],
                $row['recipient'],
                substr($row['message'], 0, 100),
                $row['status'],
                $row['credits_used'],
                $row['created_at']
            ]);
        }
        break;
        
    default:
        echo "Invalid export type";
        exit;
}

fclose($output);
exit;
