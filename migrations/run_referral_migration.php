<?php
/**
 * Migration Script: Add Referral System
 * Run this file once to add referral system columns to the database
 * Access: http://localhost/opinion/migrations/run_referral_migration.php
 */

require_once '../connect.php';

// Set execution time limit
set_time_limit(300);

echo "<h2>Running Referral System Migration</h2>";
echo "<pre>";

$errors = [];
$success = [];

// 1. Add referral_code column
echo "Adding referral_code column...\n";
$sql = "ALTER TABLE users ADD COLUMN referral_code VARCHAR(20) UNIQUE";
if ($conn->query($sql) === TRUE) {
    $success[] = "✓ Added referral_code column";
    echo "✓ Added referral_code column\n";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "- referral_code column already exists\n";
    } else {
        $errors[] = "Error adding referral_code: " . $conn->error;
        echo "✗ Error adding referral_code: " . $conn->error . "\n";
    }
}

// 2. Add referred_by column
echo "Adding referred_by column...\n";
$sql = "ALTER TABLE users ADD COLUMN referred_by INT";
if ($conn->query($sql) === TRUE) {
    $success[] = "✓ Added referred_by column";
    echo "✓ Added referred_by column\n";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "- referred_by column already exists\n";
    } else {
        $errors[] = "Error adding referred_by: " . $conn->error;
        echo "✗ Error adding referred_by: " . $conn->error . "\n";
    }
}

// 3. Add total_earnings column
echo "Adding total_earnings column...\n";
$sql = "ALTER TABLE users ADD COLUMN total_earnings DECIMAL(10, 2) DEFAULT 0";
if ($conn->query($sql) === TRUE) {
    $success[] = "✓ Added total_earnings column";
    echo "✓ Added total_earnings column\n";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "- total_earnings column already exists\n";
    } else {
        $errors[] = "Error adding total_earnings: " . $conn->error;
        echo "✗ Error adding total_earnings: " . $conn->error . "\n";
    }
}

// 4. Add indexes
echo "Adding indexes...\n";
$indexes = [
    "ALTER TABLE users ADD INDEX idx_referral_code (referral_code)" => "idx_referral_code",
    "ALTER TABLE users ADD INDEX idx_referred_by (referred_by)" => "idx_referred_by"
];

foreach ($indexes as $sql => $index_name) {
    if ($conn->query($sql) === TRUE) {
        $success[] = "✓ Added index $index_name";
        echo "✓ Added index $index_name\n";
    } else {
        if (strpos($conn->error, 'Duplicate key') !== false) {
            echo "- Index $index_name already exists\n";
        } else {
            echo "✗ Error adding index $index_name: " . $conn->error . "\n";
        }
    }
}

// 5. Create agent_earnings table
echo "\nCreating agent_earnings table...\n";
$sql = "CREATE TABLE IF NOT EXISTS agent_earnings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agent_id INT NOT NULL,
    user_id INT,
    earning_type ENUM('poll_response', 'poll_share', 'referral', 'subscription', 'other') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    poll_id INT,
    reference VARCHAR(255),
    status ENUM('pending', 'approved', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(agent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX(agent_id),
    INDEX(earning_type),
    INDEX(status),
    INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    $success[] = "✓ Created agent_earnings table";
    echo "✓ Created agent_earnings table\n";
} else {
    if (strpos($conn->error, 'already exists') !== false) {
        echo "- agent_earnings table already exists\n";
    } else {
        $errors[] = "Error creating agent_earnings table: " . $conn->error;
        echo "✗ Error creating agent_earnings table: " . $conn->error . "\n";
    }
}

// 6. Add sms_credits column if not exists
echo "\nAdding sms_credits column...\n";
$sql = "ALTER TABLE users ADD COLUMN sms_credits INT DEFAULT 0";
if ($conn->query($sql) === TRUE) {
    $success[] = "✓ Added sms_credits column";
    echo "✓ Added sms_credits column\n";
} else {
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "- sms_credits column already exists\n";
    } else {
        echo "✗ Error adding sms_credits: " . $conn->error . "\n";
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "MIGRATION SUMMARY\n";
echo str_repeat("=", 50) . "\n";

if (!empty($success)) {
    echo "\nSuccessful operations:\n";
    foreach ($success as $msg) {
        echo "  $msg\n";
    }
}

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $msg) {
        echo "  ✗ $msg\n";
    }
    echo "\nMigration completed with errors.\n";
} else {
    echo "\n✓ Migration completed successfully!\n";
}

echo "\n</pre>";
echo "<p><a href='../agent/referrals.php'>Go to Referrals Page</a></p>";
echo "<p><a href='../'>Back to Homepage</a></p>";

$conn->close();
?>
