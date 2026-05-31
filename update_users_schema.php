<?php
include 'db_connect.php';

// Check if 'semester' column exists in 'users' table
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'semester'");

if ($check->num_rows == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE users ADD COLUMN semester VARCHAR(20) AFTER grade_year";
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added 'semester' column to 'users' table.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "'semester' column already exists in 'users' table.<br>";
}
?>
