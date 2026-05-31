<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['user_name'] ?? 'Faculty';

// Fetch stats
$sql_exams = "SELECT COUNT(*) as count FROM exams WHERE created_by = ?";
$stmt_exams = $conn->prepare($sql_exams);
$stmt_exams->bind_param("i", $teacher_id);
$stmt_exams->execute();
$total_exams = $stmt_exams->get_result()->fetch_assoc()['count'];

$sql_students = "SELECT COUNT(DISTINCT r.student_id) as count 
                 FROM results r 
                 JOIN exams e ON r.exam_id = e.id 
                 WHERE e.created_by = ?";
$stmt_students = $conn->prepare($sql_students);
$stmt_students->bind_param("i", $teacher_id);
$stmt_students->execute();
$active_students = $stmt_students->get_result()->fetch_assoc()['count'];

$sql_avg = "SELECT AVG(r.score / r.total_marks * 100) as avg_score 
            FROM results r 
            JOIN exams e ON r.exam_id = e.id 
            WHERE e.created_by = ?";
$stmt_avg = $conn->prepare($sql_avg);
$stmt_avg->bind_param("i", $teacher_id);
$stmt_avg->execute();
$average_score = round($stmt_avg->get_result()->fetch_assoc()['avg_score'] ?? 0, 1) . '%';

$sql_activity = "SELECT title, 'Exam created' as action, created_at FROM exams WHERE created_by = ? ORDER BY created_at DESC LIMIT 5";
$stmt_act = $conn->prepare($sql_activity);
$stmt_act->bind_param("i", $teacher_id);
$stmt_act->execute();
$activities = $stmt_act->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Node | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: #1e293b; color: white; position: fixed; height: 100vh; padding: 32px 0; }
        .admin-main { flex: 1; margin-left: 280px; padding: 40px; background: #f8fafc; }
        .sidebar-link { display: flex; align-items: center; gap: 16px; padding: 14px 32px; color: #94a3b8; text-decoration: none; transition: 0.2s; font-weight: 500; }
        .sidebar-link:hover, .sidebar-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        
        .stat-card { background: white; padding: 32px; border-radius: 20px; border: 1px solid #e2e8f0; }
        .quick-btn { background: white; border: 1px solid #e2e8f0; padding: 24px; border-radius: 20px; text-decoration: none; color: inherit; transition: 0.2s; text-align: center; }
        .quick-btn:hover { border-color: var(--primary); transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div style="padding: 0 32px 32px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 32px;">
                <div class="brand"><i class="fas fa-chalkboard-user"></i> Faculty Node</div>
                <p style="font-size: 0.75rem; color: #64748b; margin-top: 8px;">Institutional Intelligence</p>
            </div>
            <nav>
                <a href="teacher_dashboard.php" class="sidebar-link active"><i class="fas fa-chart-pie"></i> Overview</a>
                <a href="teacher_question_bank.php" class="sidebar-link"><i class="fas fa-database"></i> Question Bank</a>
                <a href="manage_exams.php" class="sidebar-link"><i class="fas fa-file-pen"></i> Assessments</a>
                <a href="teacher_results.php" class="sidebar-link"><i class="fas fa-square-poll-vertical"></i> Insights</a>
                <a href="my_students.php" class="sidebar-link"><i class="fas fa-user-group"></i> My Students</a>
                <a href="teacher_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="logout.php" class="sidebar-link" style="margin-top: 40px; color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a;">Welcome, Professor <?php echo htmlspecialchars((string)($teacher_name ?? 'Faculty')); ?></h1>
                    <p style="color: #64748b;">Orchestrate your curriculum and monitor academic growth</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <a href="create_exam.php" class="btn-block" style="width: auto; padding: 12px 24px;"><i class="fas fa-plus"></i> New Assessment</a>
                </div>
            </header>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px;">
                <div class="stat-card">
                    <div style="color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Active Assessments</div>
                    <div style="font-size: 2rem; font-weight: 800; color: #1e293b; margin-top: 8px;"><?php echo $total_exams; ?></div>
                </div>
                <div class="stat-card">
                    <div style="color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Discoverable Scholars</div>
                    <div style="font-size: 2rem; font-weight: 800; color: #1e293b; margin-top: 8px;"><?php echo $active_students; ?></div>
                </div>
                <div class="stat-card">
                    <div style="color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Average Performance</div>
                    <div style="font-size: 2rem; font-weight: 800; color: var(--primary); margin-top: 8px;"><?php echo $average_score; ?></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px;">
                <div class="card" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 32px;">
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 24px;">Recent Node Activity</h2>
                    <?php foreach ($activities as $act): ?>
                        <div style="display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
                            <div style="width: 40px; height: 40px; background: #eff6ff; color: #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-file-circle-plus"></i></div>
                            <div>
                                <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($act['title']); ?></div>
                                <div style="font-size: 0.8rem; color: #94a3b8;"><?php echo $act['action']; ?> • <?php echo date('M d, H:i', strtotime($act['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <a href="teacher_question_bank.php" class="quick-btn">
                        <i class="fas fa-database" style="font-size: 1.5rem; color: var(--primary); margin-bottom: 12px;"></i>
                        <h3 style="font-size: 1rem; font-weight: 700;">Question Repository</h3>
                        <p style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">Manage assessment items</p>
                    </a>
                    <a href="manage_exams.php" class="quick-btn">
                        <i class="fas fa-scroll" style="font-size: 1.5rem; color: #f59e0b; margin-bottom: 12px;"></i>
                        <h3 style="font-size: 1rem; font-weight: 700;">Curriculum Hub</h3>
                        <p style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">Control exam lifecycle</p>
                    </a>
                </div>
            </div>

            <footer style="margin-top: 60px; padding-top: 32px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 0.875rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <p>© <?php echo date('Y'); ?> ExamSystem Pro • Academic Intelligence Hub</p>
                    <div style="display: flex; gap: 20px;">
                        <span><i class="fas fa-server"></i> Node: <?php echo defined('SYSTEM_NODE_ID') ? SYSTEM_NODE_ID : 'CLUSTER'; ?></span>
                        <span><i class="fas fa-network-wired"></i> IP: <?php echo $_SERVER['SERVER_ADDR'] ?? '127.0.0.1'; ?></span>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</body>
</html>
