<?php
require_once 'connect.php';

$result = $conn->query('DESCRIBE polls');
$columns = [];
while($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

if(in_array('agent_age_criteria', $columns)) {
    echo 'SUCCESS: agent_age_criteria column exists in polls table';
} else {
    echo 'ERROR: agent_age_criteria column is missing from polls table';
}

$conn->close();
?>



