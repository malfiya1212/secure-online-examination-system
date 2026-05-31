<?php
/**
 * Distributed System Health & Fault Tolerance API
 * Provides a status report of the node's health for load balancers or cluster monitors.
 */
require_once 'db_connect.php';

header('Content-Type: application/json');

$health = [
    'node_id' => SYSTEM_NODE_ID,
    'status' => 'Healthy',
    'timestamp' => SYNC_TIME,
    'services' => [
        'database' => [
            'status' => $conn->ping() ? 'Online' : 'Offline',
            'latency_ms' => null // Latency check could be added here
        ],
        'sessions' => [
            'type' => 'Distributed (Database-backed)',
            'status' => 'Active'
        ]
    ],
    'environment' => [
        'php_version' => PHP_VERSION,
        'os' => PHP_OS,
        'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg() : 'N/A'
    ]
];

// If database is down, mark node as "Unhealthy"
if (!$conn->ping()) {
    $health['status'] = 'Unhealthy';
    http_response_code(503);
} else {
    http_response_code(200);
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>
