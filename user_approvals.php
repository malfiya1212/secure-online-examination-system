<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Handle Actions
$msg = "";
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $new_status = ($_GET['action'] == 'approve') ? 'approved' : 'rejected';
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    if($stmt->execute()) {
        $msg = "User " . ($new_status == 'approved' ? 'authorized' : 'rejected') . " successfully.";
    }
    
    // Redirect to clear GET params after some time or via JS
}

// Fetch Pending Users
$sql = "SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Authorization | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <a href="user_approvals.php" class="sidebar-link active"><i class="fas fa-user-check"></i> Approvals</a>
                <a href="admin_settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
                <a href="admin_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="admin_cluster_monitor.php" class="sidebar-link"><i class="fas fa-network-wired"></i> Cluster Health</a>
                <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <a href="logout.php" class="sidebar-link" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
                </div>
            </nav>
        </aside>

        <main class="admin-main">
            <header style="margin-bottom: 40px;">
                <h1 style="font-size: 2rem; font-weight: 800; color: var(--secondary);">Authorization Queue</h1>
                <p style="color: var(--text-muted);">Review and authorize pending institutional access requests</p>
            </header>

            <div class="card animate-fade-in">
                <?php if ($result->num_rows == 0): ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <i class="fas fa-check-circle" style="font-size: 4rem; color: #10b981; margin-bottom: 20px;"></i>
                        <h2 style="color: var(--secondary);">Queue Clear</h2>
                        <p style="color: var(--text-muted);">All access requests have been processed.</p>
                    </div>
                <?php else: ?>
                <div style="margin-bottom: 24px;">
                    <span class="badge badge-blue">Pending Authorization: <?php echo $result->num_rows; ?></span>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>CANDIDATE INFO</th>
                                <th>INTENT ROLE</th>
                                <th>REQUEST CHRONOLOGY</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="padding: 20px 24px;">
                                    <div style="font-weight: 700; color: var(--secondary);"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $row['role'] == 'teacher' ? 'badge-blue' : 'badge-success'; ?>" style="background: <?php echo $row['role'] == 'teacher' ? '#3b82f6' : '#10b981'; ?>; color: white;">
                                        <?php echo strtoupper($row['role'] ?? 'USER'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.9rem;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('H:i', strtotime($row['created_at'])); ?></div>
                                </td>
                                <td>
                                    <button class="btn-nav-primary" style="padding: 8px 16px; font-size: 0.85rem; background: #10b981;" onclick="confirmAction('approve', <?php echo $row['id']; ?>)">Authorize</button>
                                    <button class="btn-nav-primary" style="padding: 8px 16px; font-size: 0.85rem; background: #ef4444;" onclick="confirmAction('reject', <?php echo $row['id']; ?>)">Deny</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function confirmAction(action, id) {
            const isApprove = action === 'approve';
            Swal.fire({
                title: isApprove ? 'Authorize Access?' : 'Reject Request?',
                text: isApprove ? "The user will be granted full access to the cluster based on their role." : "The request will be denied and moved to rejected status.",
                icon: isApprove ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: isApprove ? 'Confirm Authorization' : 'Confirm Rejection'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?action=${action}&id=${id}`;
                }
            })
        }

        <?php if($msg): ?>
        Swal.fire({
            icon: 'success',
            title: 'Protocol Updated',
            text: '<?php echo $msg; ?>',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        <?php endif; ?>
    </script>
</body>
</html>
