<?php
include 'db_connect.php';

// 1. System Nodes table - to track active PCs/Nodes
$sql_nodes = "CREATE TABLE IF NOT EXISTS system_nodes (
    node_id VARCHAR(50) NOT NULL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Online',
    version VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

// 2. System Logs table - centralized cluster auditing
$sql_logs = "CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    node_id VARCHAR(50) NOT NULL,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if ($conn->query($sql_nodes) && $conn->query($sql_logs)) {
    echo "Monitoring and Logging tables created successfully!";
} else {
    echo "Error creating tables: " . $conn->error;
}
?>
