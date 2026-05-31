<?php
include 'db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    access INT(10) UNSIGNED NOT NULL,
    data TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if ($conn->query($sql)) {
    echo "Sessions table created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
