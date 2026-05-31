<?php
include 'db_connect.php';

// Create Distributed Lock table for Mutual Exclusion
$sql = "CREATE TABLE IF NOT EXISTS submission_locks (
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    node_id VARCHAR(50) NOT NULL,
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (exam_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if ($conn->query($sql)) {
    echo "Submission Locks table created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
