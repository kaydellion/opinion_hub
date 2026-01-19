<?php
// Quick database fix script for agent filtering columns
// Run this in your browser at http://localhost/opinion_hub/fix_database.php

require_once 'connect.php';

echo "<h1>Database Fix for Agent Filtering Columns</h1>";
echo "<pre>";

// Add agent filtering columns to polls table
$sql1 = "ALTER TABLE polls
ADD COLUMN IF NOT EXISTS agent_age_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected age groups',
ADD COLUMN IF NOT EXISTS agent_gender_criteria TEXT DEFAULT '[\"both\"]' COMMENT 'JSON array of selected genders',
ADD COLUMN IF NOT EXISTS agent_state_criteria VARCHAR(100) DEFAULT '' COMMENT 'Selected state for location filtering',
ADD COLUMN IF NOT EXISTS agent_lga_criteria VARCHAR(100) DEFAULT '' COMMENT 'Selected LGA for location filtering',
ADD COLUMN IF NOT EXISTS agent_location_all TINYINT(1) DEFAULT 1 COMMENT 'Whether to include all Nigeria locations',
ADD COLUMN IF NOT EXISTS agent_occupation_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected occupations',
ADD COLUMN IF NOT EXISTS agent_education_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected education levels',
ADD COLUMN IF NOT EXISTS agent_employment_criteria TEXT DEFAULT '[\"both\"]' COMMENT 'JSON array of selected employment status',
ADD COLUMN IF NOT EXISTS agent_income_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected income ranges'";

echo "Adding agent filtering columns to polls table...\n";
if ($conn->query($sql1) === TRUE) {
    echo "✓ Agent filtering columns added to polls table successfully.\n";
} else {
    echo "✗ Error adding agent filtering columns: " . $conn->error . "\n";
}

// Add agent profile columns to users table
$sql2 = "ALTER TABLE users
ADD COLUMN IF NOT EXISTS date_of_birth DATE DEFAULT NULL COMMENT 'Agent date of birth for age calculation',
ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female') DEFAULT NULL COMMENT 'Agent gender',
ADD COLUMN IF NOT EXISTS state VARCHAR(50) DEFAULT NULL COMMENT 'Agent state of residence',
ADD COLUMN IF NOT EXISTS lga VARCHAR(100) DEFAULT NULL COMMENT 'Agent local government area',
ADD COLUMN IF NOT EXISTS occupation VARCHAR(100) DEFAULT NULL COMMENT 'Agent occupation/profession',
ADD COLUMN IF NOT EXISTS education_qualification VARCHAR(100) DEFAULT NULL COMMENT 'Agent highest education qualification',
ADD COLUMN IF NOT EXISTS employment_status ENUM('employed', 'unemployed') DEFAULT NULL COMMENT 'Agent employment status',
ADD COLUMN IF NOT EXISTS income_range VARCHAR(50) DEFAULT NULL COMMENT 'Agent monthly income range'";

echo "\nAdding agent profile columns to users table...\n";
if ($conn->query($sql2) === TRUE) {
    echo "✓ Agent profile columns added to users table successfully.\n";
} else {
    echo "✗ Error adding agent profile columns: " . $conn->error . "\n";
}

// Create indexes for better performance
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_polls_agent_age ON polls (agent_age_criteria(50))",
    "CREATE INDEX IF NOT EXISTS idx_polls_agent_gender ON polls (agent_gender_criteria(50))",
    "CREATE INDEX IF NOT EXISTS idx_polls_agent_state ON polls (agent_state_criteria)",
    "CREATE INDEX IF NOT EXISTS idx_polls_agent_occupation ON polls (agent_occupation_criteria(100))",
    "CREATE INDEX IF NOT EXISTS idx_polls_agent_education ON polls (agent_education_criteria(100))",
    "CREATE INDEX IF NOT EXISTS idx_polls_agent_employment ON polls (agent_employment_criteria(50))",
    "CREATE INDEX IF NOT EXISTS idx_polls_agent_income ON polls (agent_income_criteria(100))",
    "CREATE INDEX IF NOT EXISTS idx_users_agent_dob ON users (date_of_birth)",
    "CREATE INDEX IF NOT EXISTS idx_users_agent_gender ON users (gender)",
    "CREATE INDEX IF NOT EXISTS idx_users_agent_state ON users (state)",
    "CREATE INDEX IF NOT EXISTS idx_users_agent_occupation ON users (occupation)",
    "CREATE INDEX IF NOT EXISTS idx_users_agent_education ON users (education_qualification)",
    "CREATE INDEX IF NOT EXISTS idx_users_agent_employment ON users (employment_status)",
    "CREATE INDEX IF NOT EXISTS idx_users_agent_income ON users (income_range)"
];

echo "\nCreating database indexes...\n";
$index_errors = 0;
foreach ($indexes as $index_sql) {
    if ($conn->query($index_sql) !== TRUE) {
        echo "✗ Error creating index: " . $conn->error . "\n";
        $index_errors++;
    }
}

if ($index_errors === 0) {
    echo "✓ All database indexes created successfully.\n";
}

$conn->close();

echo "\n</pre>";
echo "<h2>✅ Database setup completed!</h2>";
echo "<p>The agent filtering system is now ready. You can now create polls with agent filtering criteria.</p>";
echo "<p><a href='client/create-poll.php'>Go to Create Poll</a></p>";
?>



