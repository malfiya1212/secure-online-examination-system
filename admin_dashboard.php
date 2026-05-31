<?php
include 'db_connect.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // If not logged in or not admin, redirect to login
    header("Location: login.html");
    exit();
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Fetch dynamic data
// 1. Total Students
$sql_students = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
$result_students = $conn->query($sql_students);
$total_students = $result_students->fetch_assoc()['count'];

// 2. Total Teachers
$sql_teachers = "SELECT COUNT(*) as count FROM users WHERE role = 'teacher'";
$result_teachers = $conn->query($sql_teachers);
$total_teachers = $result_teachers->fetch_assoc()['count'];

// 3. Total Exams
$sql_exams = "SELECT COUNT(*) as count FROM exams";
$result_exams = $conn->query($sql_exams);
$total_exams = $result_exams->fetch_assoc()['count'];

// 4. Total Subjects (Assuming specific column or distinct subjects in exams)
$sql_subjects = "SELECT COUNT(DISTINCT subject) as count FROM exams";
$result_subjects = $conn->query($sql_subjects);
$total_subjects = $result_subjects->fetch_assoc()['count'];

// 5. Pending Approvals
$sql_pending = "SELECT COUNT(*) as count FROM users WHERE status = 'pending'";
$result_pending = $conn->query($sql_pending);
$pending_approvals = $result_pending ? $result_pending->fetch_assoc()['count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Control | ExamSystem Pro</title>
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
                <a href="admin_dashboard.php" class="sidebar-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_students.php" class="sidebar-link"><i class="fas fa-user-graduate"></i> Scholars</a>
                <a href="manage_teachers.php" class="sidebar-link"><i class="fas fa-chalkboard-teacher"></i> Faculty</a>
                <a href="manage_exams.php" class="sidebar-link"><i class="fas fa-file-invoice"></i> Assessments</a>
                <a href="manage_subjects.php" class="sidebar-link"><i class="fas fa-book"></i> Curriculum</a>
                <a href="user_approvals.php" class="sidebar-link"><i class="fas fa-user-check"></i> Approvals</a>
                <a href="admin_settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
                <a href="admin_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="admin_cluster_monitor.php" class="sidebar-link"><i class="fas fa-network-wired"></i> Cluster Health</a>
                <a href="security_dashboard.php" class="sidebar-link" style="color: #10b981;"><i class="fas fa-shield-alt"></i> Security Center</a>
                <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <a href="logout.php" class="sidebar-link" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
                </div>
            </nav>
        </aside>

        <main class="admin-main">
            <header style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 800; color: var(--secondary);">System Overview</h1>
                    <p style="color: var(--text-muted);">Root Administration Node</p>
                </div>
                <div class="badge badge-blue" style="padding: 8px 16px;">Administrator: <?php echo htmlspecialchars($admin_name); ?></div>
            </header>

            <div class="stat-grid animate-fade-in">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #eff6ff; color: #2563eb;"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-info"><h3>Total Scholars</h3><p><?php echo $total_students; ?></p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-info"><h3>Faculty Members</h3><p><?php echo $total_teachers; ?></p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fff7ed; color: #f97316;"><i class="fas fa-file-invoice"></i></div>
                    <div class="stat-info"><h3>Global Exams</h3><p><?php echo $total_exams; ?></p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef2f2; color: #ef4444;"><i class="fas fa-clock"></i></div>
                    <div class="stat-info"><h3>Pending Entry</h3><p><?php echo $pending_approvals; ?></p></div>
                </div>
            </div>

            <section style="margin-top: 40px;">
                <div class="card">
                    <h2 style="font-weight: 800; color: var(--secondary); margin-bottom: 20px;">Institutional Governance</h2>
                    <p style="color: var(--text-muted); line-height: 1.8; font-size: 1.05rem;">
                        As the Master node administrator, you maintain the integrity of the distributed assessment network. 
                    Your mandates include the orchestration of faculty workloads, the rigorous auditing of question banks, 
                    and the final validation of institutional credentials during the onboarding phase. 
                    Ensure that all synchronization protocols are strictly observed across the cluster.
                    </p>
                </div>
            </section>

            <footer class="dist-footer" style="margin-top: 60px; background: transparent; border: none; padding-bottom: 0;">
                <div class="node-info-pills">
                    <span><i class="fas fa-server"></i> Master: <strong><?php echo gethostname(); ?></strong></span>
                    <span><i class="fas fa-network-wired"></i> IP: <strong><?php echo $_SERVER['SERVER_ADDR'] ?? '127.0.0.1'; ?></strong></span>
                    <span><i class="fas fa-fingerprint"></i> Sess: <strong><?php echo substr(session_id(), 0, 8); ?></strong></span>
                </div>
                <p style="margin-top: 16px; font-size: 0.8rem; color: #94a3b8;">ExamSystem Pro • Centralized Distributed Control V<?php echo defined('SYSTEM_VERSION') ? SYSTEM_VERSION : '2.0.0'; ?></p>
            </footer>
        </main>
    </div>
</body>
</html>

</html>