<?php
include 'db_connect.php';

$sqls = [
    "ALTER TABLE questions ADD COLUMN grade_year VARCHAR(50) AFTER education_level",
    "CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        level VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($sqls as $sql) {
    if ($conn->query($sql)) {
        echo "Success: $sql\n";
    } else {
        echo "Error: " . $conn->error . " ($sql)\n";
    }
}
?>
