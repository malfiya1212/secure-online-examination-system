<?php
include 'db_connect.php';

// Contact Messages Table
$sql = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'contact_messages' created successfully<br>";
} else {
    echo "Error creating table contact_messages: " . $conn->error . "<br>";
}

$conn->close();
?>
