<?php
require_once 'connect.php';

echo "<h1>Users Table Column Check</h1>";

// Check users table structure
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<h2>Users table columns:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Could not describe users table: " . $conn->error . "</p>";
}

// Check for agent-related columns
$agent_columns = [
    'agent_status', 'date_of_birth', 'gender', 'state', 'lga', 'occupation',
    'education_qualification', 'employment_status', 'income_range',
    'agent_age_criteria', 'agent_gender_criteria', 'agent_state_criteria',
    'agent_lga_criteria', 'agent_location_all', 'agent_occupation_criteria',
    'agent_education_criteria', 'agent_employment_criteria', 'agent_income_criteria'
];

echo "<h2>Agent-related columns check:</h2>";
$missing_columns = [];

foreach ($agent_columns as $col) {
    $result = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
    if ($result->num_rows == 0) {
        $missing_columns[] = $col;
        echo "<p style='color: red;'>❌ Missing: $col</p>";
    } else {
        echo "<p style='color: green;'>✅ Found: $col</p>";
    }
}

if (!empty($missing_columns)) {
    echo "<h3>Missing Columns (" . count($missing_columns) . "):</h3>";
    echo "<ul>";
    foreach ($missing_columns as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";

    echo "<h3>SQL to add missing columns:</h3>";
    echo "<pre>";
    foreach ($missing_columns as $col) {
        if (in_array($col, ['agent_age_criteria', 'agent_gender_criteria', 'agent_occupation_criteria', 'agent_education_criteria', 'agent_employment_criteria', 'agent_income_criteria'])) {
            echo "ALTER TABLE users ADD COLUMN $col TEXT NULL;\n";
        } elseif ($col === 'agent_state_criteria' || $col === 'agent_lga_criteria') {
            echo "ALTER TABLE users ADD COLUMN $col VARCHAR(255) NULL;\n";
        } elseif ($col === 'agent_location_all') {
            echo "ALTER TABLE users ADD COLUMN $col TINYINT(1) DEFAULT 1;\n";
        } elseif ($col === 'agent_status') {
            echo "ALTER TABLE users ADD COLUMN $col ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';\n";
        } elseif ($col === 'date_of_birth') {
            echo "ALTER TABLE users ADD COLUMN $col DATE NULL;\n";
        } elseif (in_array($col, ['gender', 'state', 'lga', 'occupation', 'education_qualification', 'employment_status', 'income_range'])) {
            echo "ALTER TABLE users ADD COLUMN $col VARCHAR(255) NULL;\n";
        }
    }
    echo "</pre>";
}

$conn->close();
?>



