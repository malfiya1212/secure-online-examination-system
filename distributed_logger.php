<?php
/**
 * Distributed System Logger & Cluster Monitoring
 * This file implements academic distributed concepts:
 * 1. Node Heartbeat (Observability)
 * 2. Centralized Cluster Logging (Auditing)
 */

if (!defined('SYSTEM_NODE_ID')) {
    include_once 'config.php';
}

/**
 * Register or update the current node status (Heartbeat)
 */
function system_heartbeat($conn) {
    if (!$conn) return;

    $node_id = SYSTEM_NODE_ID;
    $ip = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
    $version = SYSTEM_VERSION;
    $status = 'Online';

    try {
        $stmt = $conn->prepare("REPLACE INTO system_nodes (node_id, ip_address, status, version) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $node_id, $ip, $status, $version);
            $stmt->execute();
        }
    } catch (mysqli_sql_exception $e) {
        // Silently ignore if table doesn't exist yet
    }
}

/**
 * Log a cluster-wide event
 */
function log_cluster_event($conn, $action, $details = null, $user_id = null) {
    if (!$conn) return;

    $node_id = SYSTEM_NODE_ID;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    // Auto-detect user ID if session is active
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

    try {
        $stmt = $conn->prepare("INSERT INTO system_logs (node_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sisss", $node_id, $user_id, $action, $details, $ip);
            $stmt->execute();
        }
    } catch (mysqli_sql_exception $e) {
        // Silently ignore if table doesn't exist yet
    }
}

// Automatically trigger heartbeat when this file is included
if (isset($conn)) {
    system_heartbeat($conn);
}
?>
