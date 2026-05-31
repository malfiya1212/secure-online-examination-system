<?php
include 'db_connect.php';

// Add columns for detailed student info if they don't exist
$alter_sql = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS section VARCHAR(50)",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS level VARCHAR(50)",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS grade VARCHAR(50)",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100)"
];

foreach ($alter_sql as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Column updated successfully.<br>";
    } else {
        // Ignore "Duplicate column name" error
        if ($conn->errno !== 1060) {
            echo "Error updating table: " . $conn->error . "<br>";
        }
    }
}

$conn->close();
echo "Schema update complete.";
?>
