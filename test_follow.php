<?php
include_once 'connect.php';

// Test database connection
echo "<h1>Follow System Test</h1>";

// Test user follows table
echo "<h2>User Follows Table:</h2>";
$table_check = $conn->query("SHOW TABLES LIKE 'user_follows'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<p style='color: green;'>✓ user_follows table exists</p>";

    // Check table structure
    $result = $conn->query("DESCRIBE user_follows");
    if ($result) {
        echo "<p>Table structure:</p><ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>{$row['Field']} - {$row['Type']}</li>";
        }
        echo "</ul>";
    }

    // Count records
    $count = $conn->query("SELECT COUNT(*) as total FROM user_follows");
    if ($count) {
        $total = $count->fetch_assoc()['total'];
        echo "<p>Total records: $total</p>";
    }
} else {
    echo "<p style='color: red;'>✗ user_follows table does not exist</p>";
}

// Test category follows table
echo "<h2>Category Follows Table:</h2>";
$table_check = $conn->query("SHOW TABLES LIKE 'user_category_follows'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<p style='color: green;'>✓ user_category_follows table exists</p>";

    // Check table structure
    $result = $conn->query("DESCRIBE user_category_follows");
    if ($result) {
        echo "<p>Table structure:</p><ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>{$row['Field']} - {$row['Type']}</li>";
        }
        echo "</ul>";
    }

    // Count records
    $count = $conn->query("SELECT COUNT(*) as total FROM user_category_follows");
    if ($count) {
        $total = $count->fetch_assoc()['total'];
        echo "<p>Total records: $total</p>";
    }
} else {
    echo "<p style='color: red;'>✗ user_category_follows table does not exist</p>";
}

// Test manual insertion
echo "<h2>Test Manual Insertion:</h2>";
if (isset($_POST['test_insert'])) {
    $test_user_id = 1; // Assuming user ID 1 exists
    $test_following_id = 2; // Assuming user ID 2 exists
    $test_category_id = 1; // Assuming category ID 1 exists

    // Test user follow insert
    $stmt = $conn->prepare("INSERT INTO user_follows (follower_id, following_id) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ii", $test_user_id, $test_following_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ User follow insertion successful</p>";
        } else {
            echo "<p style='color: red;'>✗ User follow insertion failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>✗ User follow prepare failed: " . $conn->error . "</p>";
    }

    // Test category follow insert
    $stmt = $conn->prepare("INSERT INTO user_category_follows (user_id, category_id) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ii", $test_user_id, $test_category_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Category follow insertion successful</p>";
        } else {
            echo "<p style='color: red;'>✗ Category follow insertion failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>✗ Category follow prepare failed: " . $conn->error . "</p>";
    }
}
?>

<form method="POST">
    <button type="submit" name="test_insert" class="btn btn-primary">Test Manual Insertion</button>
</form>

<p><a href="index.php">Back to Home</a></p>


