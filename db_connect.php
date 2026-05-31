<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Centralized Session Management for Distributed Systems
// Ensure $conn is defined before including the handler
require_once 'session_handler.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Distributed System Observability (Heartbeat & Logging)
require_once 'distributed_logger.php';

/**
 * Advanced Distributed Concept: Physical Clock Synchronization
 * Fetches the synchronized time from the database server to handle clock drift between nodes.
 */
function get_synchronized_time($conn) {
    $result = $conn->query("SELECT NOW() as db_time");
    $row = $result->fetch_assoc();
    return $row['db_time'];
}

// Make the synchronized time available as a constant for the current request
define('SYNC_TIME', get_synchronized_time($conn));
?>
