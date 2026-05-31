<?php
include 'db_connect.php';

$cols = [
    'education_level' => "ALTER TABLE questions ADD COLUMN education_level VARCHAR(50) AFTER type",
    'subject'         => "ALTER TABLE questions ADD COLUMN subject VARCHAR(100) AFTER education_level"
];

foreach ($cols as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM questions LIKE '$col'");
    if ($check->num_rows == 0) {
        if ($conn->query($sql)) {
            echo "✅ Column '$col' added to 'questions' table.\n";
        } else {
            echo "❌ Error adding '$col': " . $conn->error . "\n";
        }
    } else {
        echo "ℹ️ Column '$col' already exists.\n";
    }
}
?>
