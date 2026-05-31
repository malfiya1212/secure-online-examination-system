<?php
include 'db_connect.php';

// Check if 'status' column exists in 'exams' table
$check = $conn->query("SHOW COLUMNS FROM exams LIKE 'status'");

if ($check->num_rows == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE exams ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER created_by";
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added 'status' column to 'exams' table.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "'status' column already exists in 'exams' table.<br>";
}

// Also check for 'subject' column while we are at it, as that was part of a previous fix
$check_subj = $conn->query("SHOW COLUMNS FROM exams LIKE 'subject'");
if ($check_subj->num_rows == 0) {
    $sql = "ALTER TABLE exams ADD COLUMN subject VARCHAR(100) AFTER level";
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added 'subject' column to 'exams' table.<br>";
    } else {
        echo "Error adding subject column: " . $conn->error . "<br>";
    }
} else {
     echo "'subject' column already exists.<br>";
}

echo "Database schema check completed.";
?>
