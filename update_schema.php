<?php
include 'db_connect.php';

// Add created_by to questions table
$sql = "ALTER TABLE questions ADD COLUMN created_by INT(6) UNSIGNED AFTER id";
if ($conn->query($sql) === TRUE) {
    echo "Column 'created_by' added to questions table successfully<br>";
} else {
    echo "Error adding column (or it already exists): " . $conn->error . "<br>";
}

$conn->close();
?>
