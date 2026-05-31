<?php
include 'db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'system_settings' created/checked successfully.<br>";
    
    // Insert defaults if empty
    $check = $conn->query("SELECT * FROM system_settings");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO system_settings (setting_key, setting_value) VALUES ('system_name', 'Online Exam System')");
        $conn->query("INSERT INTO system_settings (setting_key, setting_value) VALUES ('teacher_registration', 'yes')");
    }
} else {
    echo "Error creating table: " . $conn->error;
}
?>
