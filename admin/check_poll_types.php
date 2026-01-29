<?php
// Check if poll types exist in the database
require_once '../connect.php';

echo "<h2>Check Poll Types</h2>";

// Check for poll_types table
$poll_types_check = $conn->query("SHOW TABLES LIKE 'poll_types'");
if ($poll_types_check && $poll_types_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ poll_types table exists</p>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE poll_types");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show existing data
    $data = $conn->query("SELECT * FROM poll_types ORDER BY id");
    if ($data && $data->num_rows > 0) {
        echo "<h3>Existing Poll Types:</h3>";
        echo "<table border='1' cellpadding='3'>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Status</th></tr>";
        while ($row = $data->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No poll types found in the table.</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ poll_types table does not exist</p>";
    
    // Check if polls table has type column
    $polls_check = $conn->query("SHOW COLUMNS FROM polls LIKE 'poll_type'");
    if ($polls_check && $polls_check->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ polls table has poll_type column</p>";
    }
    
    // Check for type-related columns in polls table
    $columns = $conn->query("SHOW COLUMNS FROM polls");
    echo "<h3>Polls Table Columns (looking for type-related):</h3>";
    echo "<table border='1' cellpadding='3'>";
    echo "<tr><th>Column</th><th>Type</th></tr>";
    while ($row = $columns->fetch_assoc()) {
        $column_name = strtolower($row['Field']);
        if (strpos($column_name, 'type') !== false) {
            echo "<tr style='background-color: #ffffcc;'>";
        } else {
            echo "<tr>";
        }
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check categories table (might be used instead of types)
echo "<h3>Categories Table:</h3>";
$categories_check = $conn->query("SHOW TABLES LIKE 'categories'");
if ($categories_check && $categories_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ categories table exists</p>";
    
    $cat_data = $conn->query("SELECT * FROM categories ORDER BY name LIMIT 10");
    if ($cat_data && $cat_data->num_rows > 0) {
        echo "<table border='1' cellpadding='3'>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";
        while ($row = $cat_data->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ categories table does not exist</p>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Admin Dashboard</a></p>";
?>
