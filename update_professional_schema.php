<?php
include 'db_connect.php';

// Add Difficulty and Topic to questions table
$sql = "ALTER TABLE questions 
        ADD COLUMN IF NOT EXISTS difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
        ADD COLUMN IF NOT EXISTS topic VARCHAR(100) DEFAULT NULL;";

if ($conn->query($sql)) {
    echo "Professional question fields (difficulty, topic) added successfully!";
} else {
    // If ENUM fails or column already exists, handle gracefully
    echo "Note: Database update attempted. " . $conn->error;
}
?>
