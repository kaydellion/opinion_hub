<?php
// Test script to verify agent filtering columns were added
require_once 'connect.php';

echo "<h1>Database Column Test</h1>";
echo "<pre>";

// Test if agent_age_criteria column exists in polls table
$result = $conn->query("DESCRIBE polls");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

$required_columns = [
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

echo "Checking polls table columns:\n";
$missing = [];
foreach ($required_columns as $col) {
    if (in_array($col, $columns)) {
        echo "✓ $col - EXISTS\n";
    } else {
        echo "✗ $col - MISSING\n";
        $missing[] = $col;
    }
}

if (empty($missing)) {
    echo "\n✅ All required columns exist in polls table!\n";
    echo "The poll creation error should now be resolved.\n";
} else {
    echo "\n❌ Missing columns: " . implode(', ', $missing) . "\n";
    echo "You need to run the fix_database.php script again.\n";
}

$conn->close();
echo "</pre>";
?>



