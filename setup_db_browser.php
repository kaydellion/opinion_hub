<?php
// Database setup script for agent filtering system
// Access this file in your browser: http://localhost/opinion_hub/setup_db_browser.php

echo "<h1>Agent Filtering System - Database Setup</h1>";
echo "<pre>";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "opinion_hub";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database successfully!\n\n";

// Add agent filtering columns to polls table
$sql1 = "ALTER TABLE polls
ADD COLUMN agent_age_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected age groups',
ADD COLUMN agent_gender_criteria TEXT DEFAULT '[\"both\"]' COMMENT 'JSON array of selected genders',
ADD COLUMN agent_state_criteria VARCHAR(100) DEFAULT '' COMMENT 'Selected state for location filtering',
ADD COLUMN agent_lga_criteria VARCHAR(100) DEFAULT '' COMMENT 'Selected LGA for location filtering',
ADD COLUMN agent_location_all TINYINT(1) DEFAULT 1 COMMENT 'Whether to include all Nigeria locations',
ADD COLUMN agent_occupation_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected occupations',
ADD COLUMN agent_education_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected education levels',
ADD COLUMN agent_employment_criteria TEXT DEFAULT '[\"both\"]' COMMENT 'JSON array of selected employment status',
ADD COLUMN agent_income_criteria TEXT DEFAULT '[\"all\"]' COMMENT 'JSON array of selected income ranges'";

echo "Adding agent filtering columns to polls table...\n";
if ($conn->query($sql1) === TRUE) {
    echo "✓ SUCCESS: Agent filtering columns added to polls table!\n";
} else {
    echo "✗ ERROR adding agent filtering columns: " . $conn->error . "\n";
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "Note: Columns may already exist, continuing...\n";
    }
}
echo "\n";

// Add agent profile columns to users table
$sql2 = "ALTER TABLE users
ADD COLUMN date_of_birth DATE DEFAULT NULL COMMENT 'Agent date of birth for age calculation',
ADD COLUMN gender ENUM('male', 'female') DEFAULT NULL COMMENT 'Agent gender',
ADD COLUMN state VARCHAR(50) DEFAULT NULL COMMENT 'Agent state of residence',
ADD COLUMN lga VARCHAR(100) DEFAULT NULL COMMENT 'Agent local government area',
ADD COLUMN occupation VARCHAR(100) DEFAULT NULL COMMENT 'Agent occupation/profession',
ADD COLUMN education_qualification VARCHAR(100) DEFAULT NULL COMMENT 'Agent highest education qualification',
ADD COLUMN employment_status ENUM('employed', 'unemployed') DEFAULT NULL COMMENT 'Agent employment status',
ADD COLUMN income_range VARCHAR(50) DEFAULT NULL COMMENT 'Agent monthly income range'";

echo "Adding agent profile columns to users table...\n";
if ($conn->query($sql2) === TRUE) {
    echo "✓ SUCCESS: Agent profile columns added to users table!\n";
} else {
    echo "✗ ERROR adding agent profile columns: " . $conn->error . "\n";
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "Note: Columns may already exist, continuing...\n";
    }
}
echo "\n";

// Create indexes for better performance
$indexes = [
    "CREATE INDEX idx_polls_agent_age ON polls (agent_age_criteria(50))",
    "CREATE INDEX idx_polls_agent_gender ON polls (agent_gender_criteria(50))",
    "CREATE INDEX idx_polls_agent_state ON polls (agent_state_criteria)",
    "CREATE INDEX idx_polls_agent_occupation ON polls (agent_occupation_criteria(100))",
    "CREATE INDEX idx_polls_agent_education ON polls (agent_education_criteria(100))",
    "CREATE INDEX idx_polls_agent_employment ON polls (agent_employment_criteria(50))",
    "CREATE INDEX idx_polls_agent_income ON polls (agent_income_criteria(100))",
    "CREATE INDEX idx_users_agent_dob ON users (date_of_birth)",
    "CREATE INDEX idx_users_agent_gender ON users (gender)",
    "CREATE INDEX idx_users_agent_state ON users (state)",
    "CREATE INDEX idx_users_agent_occupation ON users (occupation)",
    "CREATE INDEX idx_users_agent_education ON users (education_qualification)",
    "CREATE INDEX idx_users_agent_employment ON users (employment_status)",
    "CREATE INDEX idx_users_agent_income ON users (income_range)"
];

echo "Creating database indexes for better performance...\n";
$index_errors = 0;
$index_success = 0;

foreach ($indexes as $index_sql) {
    if ($conn->query($index_sql) === TRUE) {
        $index_success++;
    } else {
        if (strpos($conn->error, "Duplicate key name") === false) {
            echo "✗ ERROR creating index: " . $conn->error . "\n";
            $index_errors++;
        }
    }
}

echo "✓ $index_success indexes created successfully";
if ($index_errors > 0) {
    echo " ($index_errors already existed)";
}
echo "\n\n";

$conn->close();

echo "</pre>";
echo "<h2>Setup Complete! 🎉</h2>";
echo "<p>The agent filtering system database setup is now complete!</p>";
echo "<p><strong>What you can do now:</strong></p>";
echo "<ul>";
echo "<li>✅ Create polls with detailed agent filtering criteria</li>";
echo "<li>✅ Register agents with comprehensive profiles</li>";
echo "<li>✅ View matched polls based on agent qualifications</li>";
echo "<li>✅ Poll creation will work without database errors</li>";
echo "</ul>";
echo "<p><a href='client/create-poll.php'>Try creating a poll now</a></p>";
echo "<p><a href='signup.php'>Register as an agent to test the system</a></p>";
?>





