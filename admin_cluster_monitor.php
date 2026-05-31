<?php
include 'db_connect.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Fetch all registered nodes
$nodes_result = $conn->query("SELECT * FROM system_nodes ORDER BY last_heartbeat DESC");
$nodes = $nodes_result->fetch_all(MYSQLI_ASSOC);

// Fetch recent cluster logs
$logs_result = $conn->query("SELECT l.*, u.name as user_name FROM system_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 50");
$logs = $logs_result->fetch_all(MYSQLI_ASSOC);

// Count stats
$total_nodes = count($nodes);
$active_nodes = 0;
foreach($nodes as $node) {
    if (strtotime($node['last_heartbeat']) > (time() - 300)) {
        $active_nodes++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cluster Intelligence | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 280px;
            background: var(--secondary);
            color: white;
            padding: 32px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .admin-main { flex: 1; margin-left: 280px; padding: 40px; }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 32px;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(255,255,255,0.05);
            color: white;
            border-left: 4px solid var(--primary);
        }
        .sidebar-link i { font-size: 1.1rem; width: 24px; text-align: center; }
        
        .log-node { font-family: monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: 700; color: var(--primary); }
        .log-action { font-weight: 800; color: var(--secondary); }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div style="padding: 0 32px 32px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 32px;">
                <div class="brand"><i class="fas fa-shield-halved"></i> Master Node</div>
                <p style="font-size: 0.75rem; color: #64748b; margin-top: 8px;">Institutional Administration</p>
            </div>
            
            <nav>
                <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_students.php" class="sidebar-link"><i class="fas fa-user-graduate"></i> Scholars</a>
                <a href="manage_teachers.php" class="sidebar-link"><i class="fas fa-chalkboard-teacher"></i> Faculty</a>
                <a href="manage_exams.php" class="sidebar-link"><i class="fas fa-file-invoice"></i> Assessments</a>
                <a href="manage_subjects.php" class="sidebar-link"><i class="fas fa-book"></i> Curriculum</a>
                <a href="user_approvals.php" class="sidebar-link"><i class="fas fa-user-check"></i> Approvals</a>
                <a href="admin_settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
                <a href="admin_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="admin_cluster_monitor.php" class="sidebar-link active"><i class="fas fa-network-wired"></i> Cluster Health</a>
                <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <a href="logout.php" class="sidebar-link" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
                </div>
            </nav>
        </aside>

        <main class="admin-main">
            <header style="margin-bottom: 40px;">
                <h1 style="font-size: 2rem; font-weight: 800; color: var(--secondary);">Cluster Intelligence</h1>
                <p style="color: var(--text-muted);">Real-time observability of distributed node gossip and health</p>
            </header>

            <div class="stat-grid animate-fade-in" style="margin-bottom: 32px;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #eff6ff; color: #2563eb;"><i class="fas fa-server"></i></div>
                    <div class="stat-info"><h3>Total Nodes</h3><p><?php echo $total_nodes; ?></p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-signal"></i></div>
                    <div class="stat-info"><h3>Nodes Online</h3><p><?php echo $active_nodes; ?></p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff7ed; color: #f97316;"><i class="fas fa-clock"></i></div>
                    <div class="stat-info"><h3>Sync Clock</h3><p><?php echo date('H:i:s', strtotime(SYNC_TIME)); ?></p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef2f2; color: #ef4444;"><i class="fas fa-heartbeat"></i></div>
                    <div class="stat-info"><h3>System Status</h3><p>HEALTHY</p></div>
                </div>
            </div>

            <div class="card animate-fade-in" style="margin-bottom: 32px;">
                <h3 style="margin-bottom: 24px; font-weight: 800;"><i class="fas fa-list-ul" style="margin-right: 12px; color: var(--primary);"></i> Global Cluster Logs</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>NODE</th>
                                <th>ENTITY</th>
                                <th>ACTION</th>
                                <th>DETAILS</th>
                                <th>TIMESTAMP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td><span class="log-node"><?php echo htmlspecialchars($log['node_id']); ?></span></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($log['user_name'] ?? 'SYSTEM'); ?></td>
                                <td class="log-action"><?php echo htmlspecialchars($log['action']); ?></td>
                                <td style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($log['details']); ?></td>
                                <td><?php echo date('H:i:s', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card animate-fade-in" style="animation-delay: 0.1s;">
                <h3 style="margin-bottom: 24px; font-weight: 800;"><i class="fas fa-microchip" style="margin-right: 12px; color: var(--primary);"></i> Active Submissions (Mutual Exclusion)</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ASSESSMENT ID</th>
                                <th>ENTITY ID</th>
                                <th>LOCK NODE</th>
                                <th>LOCKED AT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $locks_result = $conn->query("SELECT * FROM submission_locks ORDER BY locked_at DESC LIMIT 10");
                            if ($locks_result->num_rows > 0):
                                while($lock = $locks_result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 800; color: var(--primary);">#<?php echo $lock['exam_id']; ?></td>
                                <td>Candidate #<?php echo $lock['student_id']; ?></td>
                                <td><span class="log-node"><?php echo htmlspecialchars($lock['node_id']); ?></span></td>
                                <td><?php echo $lock['locked_at']; ?></td>
                            </tr>
                            <?php endwhile; 
                            else: ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:24px;">No active submission locks globally.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
