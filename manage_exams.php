<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'teacher')) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Handle Status Toggle
if (isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status_sql = $user_role === 'teacher' 
        ? "UPDATE exams SET status = IF(status='active', 'inactive', 'active') WHERE id=$id AND created_by=$user_id"
        : "UPDATE exams SET status = IF(status='active', 'inactive', 'active') WHERE id=$id";
    $conn->query($status_sql);
    header("Location: manage_exams.php");
    exit();
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($user_role === 'teacher') {
        $conn->query("DELETE FROM exams WHERE id=$id AND created_by=$user_id");
    } else {
        $conn->query("DELETE FROM exams WHERE id=$id");
    }
    header("Location: manage_exams.php");
    exit();
}

// Fetch Exams
if ($user_role === 'admin') {
    $sql = "SELECT e.*, u.name as teacher_name 
            FROM exams e 
            LEFT JOIN users u ON e.created_by = u.id 
            ORDER BY e.created_at DESC";
} else {
    $sql = "SELECT e.*, u.name as teacher_name 
            FROM exams e 
            LEFT JOIN users u ON e.created_by = u.id 
            WHERE e.created_by = $user_id
            ORDER BY e.created_at DESC";
}
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Lifecycle | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: #1e293b; color: white; position: fixed; height: 100vh; padding: 32px 0; }
        .admin-main { flex: 1; margin-left: 280px; padding: 40px; background: #f8fafc; }
        .sidebar-link { display: flex; align-items: center; gap: 16px; padding: 14px 32px; color: #94a3b8; text-decoration: none; transition: 0.2s; font-weight: 500; }
        .sidebar-link:hover, .sidebar-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        
        .exam-table th { background: #f8fafc; text-align: left; padding: 16px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #f1f5f9; }
        .exam-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; }
        
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; }
        .active-badge { background: #dcfce7; color: #15803d; }
        .inactive-badge { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div style="padding: 0 32px 32px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 32px;">
                <div class="brand"><i class="fas fa-<?php echo $user_role === 'admin' ? 'shield-halved' : 'chalkboard-user'; ?>"></i> <?php echo $user_role === 'admin' ? 'Master Node' : 'Faculty Node'; ?></div>
                <p style="font-size: 0.75rem; color: #64748b; margin-top: 8px;">Institutional Intelligence</p>
            </div>
            <nav>
                <?php if ($user_role === 'admin'): ?>
                    <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="manage_students.php" class="sidebar-link"><i class="fas fa-user-graduate"></i> Scholars</a>
                    <a href="manage_teachers.php" class="sidebar-link"><i class="fas fa-chalkboard-teacher"></i> Faculty</a>
                    <a href="manage_exams.php" class="sidebar-link active"><i class="fas fa-file-invoice"></i> Assessments</a>
                    <a href="manage_subjects.php" class="sidebar-link"><i class="fas fa-book"></i> Curriculum</a>
                    <a href="user_approvals.php" class="sidebar-link"><i class="fas fa-user-check"></i> Approvals</a>
                    <a href="admin_settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
                    <a href="admin_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                    <a href="admin_cluster_monitor.php" class="sidebar-link"><i class="fas fa-network-wired"></i> Cluster Health</a>
                <?php else: ?>
                    <a href="teacher_dashboard.php" class="sidebar-link"><i class="fas fa-chart-pie"></i> Overview</a>
                    <a href="teacher_question_bank.php" class="sidebar-link"><i class="fas fa-database"></i> Question Bank</a>
                    <a href="manage_exams.php" class="sidebar-link active"><i class="fas fa-file-pen"></i> Assessments</a>
                    <a href="teacher_results.php" class="sidebar-link"><i class="fas fa-square-poll-vertical"></i> Insights</a>
                    <a href="my_students.php" class="sidebar-link"><i class="fas fa-user-group"></i> My Students</a>
                    <a href="teacher_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <?php endif; ?>
                <a href="logout.php" class="sidebar-link" style="margin-top: 40px; color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a;">Assessment Control Hub</h1>
                    <p style="color: #64748b;">Manage the full lifecycle of academic examinations</p>
                </div>
                <?php if ($user_role === 'teacher'): ?>
                    <a href="create_exam.php" class="btn-block" style="width: auto; padding: 12px 24px;"><i class="fas fa-plus"></i> Create Assessment</a>
                <?php endif; ?>
            </header>

            <div class="card" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 32px;">
                <table style="width: 100%; border-collapse: collapse;" class="exam-table">
                    <thead>
                        <tr>
                            <th>Assessment Title</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Target Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><span style="color: #64748b;"><?php echo htmlspecialchars($row['subject'] ?? 'N/A'); ?></span></td>
                                <td>
                                    <?php 
                                        $status = $row['status'] ?? 'inactive';
                                        $badge_class = ($status === 'active') ? 'active-badge' : 'inactive-badge';
                                    ?>
                                    <span class="status-badge <?php echo $badge_class; ?>">
                                        <?php echo strtoupper((string)$status); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['teacher_name'] ?? 'Admin'); ?></td>
                                <td><span style="background: #eff6ff; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700;"><?php echo strtoupper($row['level'] ?? 'N/A'); ?></span></td>
                                <td style="display: flex; gap: 8px;">
                                    <a href="?action=toggle&id=<?php echo $row['id']; ?>" class="btn-block" style="width:36px; height:36px; padding:0; background:<?php echo $row['status'] === 'active' ? '#f0fdf4' : '#fff7ed'; ?>; color:<?php echo $row['status'] === 'active' ? '#16a34a' : '#ea580c'; ?>;" title="<?php echo $row['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fas fa-power-off"></i>
                                    </a>
                                    <a href="add_question.php?exam_id=<?php echo $row['id']; ?>" class="btn-block" style="width:36px; height:36px; padding:0; background:#eff6ff; color:#2563eb;" title="Manage Items"><i class="fas fa-pencil"></i></a>
                                    <a href="teacher_results.php?exam_id=<?php echo $row['id']; ?>" class="btn-block" style="width:36px; height:36px; padding:0; background:#f1f5f9; color:#64748b;" title="View Insights"><i class="fas fa-chart-line"></i></a>
                                    <button class="btn-block" style="width:36px; height:36px; padding:0; background:#fef2f2; color:#ef4444;" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Trash"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">No active assessments detected in the repository.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <footer style="margin-top: 60px; padding-top: 32px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 0.875rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <p>© <?php echo date('Y'); ?> ExamSystem Pro • Global Instance</p>
                    <div style="display: flex; gap: 20px;">
                        <span><i class="fas fa-server"></i> Node: <?php echo defined('SYSTEM_NODE_ID') ? SYSTEM_NODE_ID : 'CLUSTER'; ?></span>
                        <span><i class="fas fa-network-wired"></i> IP: <?php echo $_SERVER['SERVER_ADDR'] ?? '127.0.0.1'; ?></span>
                    </div>
                </div>
            </footer>
        </main>
    </div>

    <script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Archive Assessment?',
            text: "This will permanently remove the exam and all student response data.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Archive Now',
            cancelButtonText: 'Abort'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `manage_exams.php?action=delete&id=${id}`;
            }
        })
    }
    </script>
</body>
</html>
