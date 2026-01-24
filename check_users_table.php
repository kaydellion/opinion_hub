<?php
require_once 'connect.php';

echo "<h1>Users Table Agent Criteria Check</h1>";

// Check users table structure
echo "<h2>users table columns:</h2>";
$result = $conn->query("DESCRIBE users");
if ($result) {
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
    'agent_status',
    'agent_age_criteria',
    'agent_gender_criteria',
    'agent_state_criteria',
    'agent_lga_criteria',
    'agent_location_all',
    'agent_occupation_criteria',
    'agent_education_criteria',
    'agent_employment_criteria',
    'agent_income_criteria'
];

echo "<h2>Agent Criteria Columns Check:</h2>";
$structure_result = $conn->query("DESCRIBE users");
$existing_columns = [];
while ($row = $structure_result->fetch_assoc()) {
    $existing_columns[] = $row['Field'];
}

foreach ($agent_columns as $col) {
    if (in_array($col, $existing_columns)) {
        echo "<p style='color: green;'>✅ $col - EXISTS</p>";
    } else {
        echo "<p style='color: red;'>❌ $col - MISSING</p>";
    }
}

// Show sample agent data
echo "<h2>Sample Agent Data:</h2>";
$agent_result = $conn->query("SELECT id, first_name, last_name, role, agent_status FROM users WHERE role = 'agent' LIMIT 3");
if ($agent_result && $agent_result->num_rows > 0) {
    while ($agent = $agent_result->fetch_assoc()) {
        echo "<h3>Agent: {$agent['first_name']} {$agent['last_name']} (ID: {$agent['id']})</h3>";
        echo "<p>Role: {$agent['role']}, Status: {$agent['agent_status']}</p>";

        // Get full agent data
        $full_agent = $conn->query("SELECT * FROM users WHERE id = {$agent['id']}")->fetch_assoc();
        echo "<h4>Agent Criteria Data:</h4>";
        echo "<ul>";
        foreach ($agent_columns as $col) {
            $value = isset($full_agent[$col]) ? $full_agent[$col] : 'NOT SET';
            echo "<li><strong>$col:</strong> $value</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>No agents found in the database.</p>";
}

$conn->close();
?>
