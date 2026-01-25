<?php
// Check polls table structure
require_once __DIR__ . '/connect.php';

echo "<h2>Polls Table Structure Check</h2>";

// Get table structure
$result = $conn->query("DESCRIBE polls");

if ($result) {
    echo "<h3>Current columns in polls table:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $has_disclaimer = false;
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
        
        if ($row['Field'] === 'disclaimer') {
            $has_disclaimer = true;
        }
    }
    echo "</table>";
    
    if (!$has_disclaimer) {
        echo "<h3 style='color: red;'>❌ Disclaimer column is missing!</h3>";
        
        // Add the disclaimer column
        echo "<h3>Adding disclaimer column...</h3>";
        $add_sql = "ALTER TABLE polls ADD COLUMN disclaimer TEXT NULL AFTER description";
        
        if ($conn->query($add_sql)) {
            echo "<p style='color: green;'>✅ Disclaimer column added successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add disclaimer column: " . $conn->error . "</p>";
        }
    } else {
        echo "<h3 style='color: green;'>✅ Disclaimer column exists!</h3>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Failed to get table structure: " . $conn->error . "</p>";
}

// Check for other missing columns that might be needed
echo "<h3>Checking for other missing columns...</h3>";

$required_columns = [
    'agent_age_criteria' => 'TEXT DEFAULT \'[\"all\"]\'',
    'agent_gender_criteria' => 'TEXT DEFAULT \'[\"both\"]\'',
    'agent_state_criteria' => 'VARCHAR(100) DEFAULT \'\'',
    'agent_lga_criteria' => 'VARCHAR(100) DEFAULT \'\'',
    'agent_location_all' => 'TINYINT(1) DEFAULT 1',
    'agent_occupation_criteria' => 'TEXT DEFAULT \'[\"all\"]\'',
    'agent_education_criteria' => 'TEXT DEFAULT \'[\"all\"]\'',
    'agent_employment_criteria' => 'TEXT DEFAULT \'[\"both\"]\'',
    'agent_income_criteria' => 'TEXT DEFAULT \'[\"all\"]\'',
    'agent_commission' => 'DECIMAL(10, 2) DEFAULT 1000',
    'results_for_sale' => 'BOOLEAN DEFAULT FALSE',
    'results_sale_price' => 'DECIMAL(10, 2) DEFAULT 0'
];

// Get current columns
$existing_columns = [];
$result = $conn->query("SHOW COLUMNS FROM polls");
while ($row = $result->fetch_assoc()) {
    $existing_columns[] = $row['Field'];
}

foreach ($required_columns as $column => $definition) {
    if (!in_array($column, $existing_columns)) {
        echo "<p style='color: orange;'>⚠️ Adding missing column: $column</p>";
        $add_sql = "ALTER TABLE polls ADD COLUMN $column $definition";
        if ($conn->query($add_sql)) {
            echo "<p style='color: green;'>✅ Added $column</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add $column: " . $conn->error . "</p>";
        }
    }
}

echo "<h3>✅ Polls table check completed!</h3>";
echo "<p><a href='client/create-poll.php'>← Try creating a poll again</a></p>";
?>
