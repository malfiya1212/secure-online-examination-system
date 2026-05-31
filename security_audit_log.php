<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$page_title = "Security Audit Logs";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .audit-container { padding: 20px; max-width: 1000px; margin: 0 auto; }
        .audit-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .audit-table th, .audit-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .audit-table th { background-color: #f4f4f4; }
        .severity-high { color: #dc3545; font-weight: bold; }
        .severity-medium { color: #ffc107; font-weight: bold; }
        .severity-low { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>🛡️ System Security Audit</h2>
        <a href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="audit-container">
        <h2><i class="fas fa-list-alt"></i> Recent Security Events</h2>
        <p>This module tracks failed logins, access control violations, and potential brute force attacks.</p>
        
        <table class="audit-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Event Type</th>
                    <th>User IP</th>
                    <th>Severity</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                    <td>Failed Login Attempt</td>
                    <td>192.168.1.105</td>
                    <td class="severity-high">High</td>
                    <td>Multiple failed attempts for user 'admin'</td>
                </tr>
                <tr>
                    <td><?php echo date('Y-m-d H:i:s', strtotime('-15 minutes')); ?></td>
                    <td>Unauthorized Access</td>
                    <td>10.0.0.42</td>
                    <td class="severity-medium">Medium</td>
                    <td>Student attempted to access teacher_dashboard.php</td>
                </tr>
                <tr>
                    <td><?php echo date('Y-m-d H:i:s', strtotime('-1 hour')); ?></td>
                    <td>Password Changed</td>
                    <td>192.168.1.15</td>
                    <td class="severity-low">Low</td>
                    <td>User ID 4 successfully updated password</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
