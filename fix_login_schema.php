<?php
include 'db_connect.php';

// Check if 'status' column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
if ($result->num_rows == 0) {
    // Add status column
    $sql = "ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved'";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'status' added to users table successfully.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'status' already exists.<br>";
}

$conn->close();
?>
