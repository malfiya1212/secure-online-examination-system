<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['user_name'];

// Fetch Profile
$profile_sql = "SELECT education_level, grade_year, section, stream, department, semester FROM users WHERE id = ?";
$p_stmt = $conn->prepare($profile_sql);
$p_stmt->bind_param("i", $student_id);
$p_stmt->execute();
$profile = $p_stmt->get_result()->fetch_assoc();

$profile_incomplete = !$profile['education_level'];

// Fetch Recent Results (Activity)
$recent_stmt = $conn->prepare("SELECT r.score, e.title, e.total_marks 
                               FROM results r 
                               JOIN exams e ON r.exam_id = e.id 
                               WHERE r.student_id = ? 
                               ORDER BY r.submitted_at DESC LIMIT 3");
$recent_stmt->bind_param("i", $student_id);
$recent_stmt->execute();
$recent_activity = $recent_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!$profile_incomplete) {
    $level = $profile['education_level'];
    $grade = $profile['grade_year'];
    $section = $profile['section'];
    $stream = $profile['stream'];
    $dept = $profile['department'];
    $sem = $profile['semester'];

    $sql = "SELECT e.*, u.name as teacher_name,
            (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) as q_count,
            (SELECT COUNT(*) FROM results r WHERE r.exam_id = e.id AND r.student_id = ?) as taken
            FROM exams e 
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.status = 'active'
            AND e.level = ?
            AND (e.grade_year IS NULL OR e.grade_year = '' OR e.grade_year = ?)
            AND (e.section IS NULL OR e.section = '' OR e.section = ?)
            AND (e.stream IS NULL OR e.stream = '' OR e.stream = ?)
            AND (e.department IS NULL OR e.department = '' OR e.department = ?)
            AND (e.semester IS NULL OR e.semester = '' OR e.semester = ?)
            HAVING q_count > 0
            ORDER BY e.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $student_id, $level, $grade, $section, $stream, $dept, $sem);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #f8fafc; }
        .dashboard-grid { display: grid; grid-template-columns: 320px 1fr; gap: 32px; padding: 40px 0; }
        .sidebar { position: sticky; top: 40px; }
        .sidebar-card { background: white; border-radius: 20px; padding: 32px; border: 1px solid #e2e8f0; margin-bottom: 24px; }
        
        .avatar-circle { width: 64px; height: 64px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 16px; border: 2px solid #dbeafe; }
        .activity-item { padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .activity-item:last-child { border-bottom: none; }
        
        .exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
        .card-pro { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; transition: 0.2s; display: flex; flex-direction: column; }
        .card-pro:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: #2563eb; }
        .tag { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; display: inline-block; margin-bottom: 12px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="student_dashboard.php" class="brand"><i class="fas fa-graduation-cap"></i> ExamSystem Pro</a>
            <div class="nav-links">
                <a href="student_dashboard.php" class="active">Dashboard</a>
                <a href="available_exams.php">Take Exam</a>
                <a href="student_results.php">My Insights</a>
                <a href="logout.php" class="btn-nav-primary">Sign Out</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($profile_incomplete): ?>
            <div style="margin: 40px 0; padding: 60px; background: white; border: 1px solid #fed7aa; border-radius: 24px; text-align: center;">
                <div style="width: 80px; height: 80px; background: #fff7ed; color: #f97316; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2rem;"><i class="fas fa-id-card"></i></div>
                <h2 style="font-weight: 800; color: #1e293b; margin-bottom: 12px;">Profile Alignment Required</h2>
                <p style="color: #64748b; max-width: 500px; margin: 0 auto 32px;">Configure your academic credentials to receive targeted assessments from the faculty node.</p>
                <a href="complete_profile.php" class="btn-block" style="width: auto; padding: 12px 32px;">Complete Profile Alignment</a>
            </div>
        <?php else: ?>
            <div class="dashboard-grid">
                <aside class="sidebar">
                    <div class="sidebar-card">
                        <div class="avatar-circle"><?php echo strtoupper(substr((string)($student_name ?? 'S'), 0, 1)); ?></div>
                        <h3 style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars((string)($student_name ?? 'Student')); ?></h3>
                        <p style="font-size: 0.8rem; color: #94a3b8; font-weight: 700; margin-bottom: 20px;">@STUDENT_ID_<?php echo (int)$student_id; ?></p>
                        
                        <div style="font-size: 0.9rem; color: #475569;">
                            <div style="margin-bottom: 8px;"><i class="fas fa-school" style="width: 20px; color: #2563eb;"></i> <?php echo ucfirst((string)($level ?? 'N/A')); ?></div>
                            <div style="margin-bottom: 8px;"><i class="fas fa-layer-group" style="width: 20px; color: #2563eb;"></i> <?php echo ($level === 'university' || $level === 'master') ? 'Year' : 'Grade'; ?> <?php echo htmlspecialchars((string)($grade ?? 'N/A')); ?></div>
                        </div>
                    </div>

                    <div class="sidebar-card">
                        <h4 style="font-size: 0.8rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 16px;">Recent Performance</h4>
                        <?php if(empty($recent_activity)): ?>
                            <p style="font-size: 0.85rem; color: #94a3b8;">No assessment data yet.</p>
                        <?php else: ?>
                            <?php foreach($recent_activity as $act): 
                                $pct = ($act['total_marks'] > 0) ? ($act['score'] / $act['total_marks'] * 100) : 0;
                            ?>
                                <div class="activity-item">
                                    <div style="font-weight: 700; font-size: 0.85rem; color: #1e293b;"><?php echo htmlspecialchars($act['title']); ?></div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                        <div style="font-size: 0.75rem; color: #64748b;"><?php echo round($pct, 1); ?>% Achievement</div>
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $pct >= 50 ? '#10b981' : '#ef4444'; ?>;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </aside>

                <main>
                    <header style="margin-bottom: 40px;">
                        <h1 style="font-size: 2.25rem; font-weight: 800; color: #0f172a;">Welcome Back, <?php echo htmlspecialchars((string)explode(' ', $student_name)[0]); ?>!</h1>
                        <p style="color: #64748b; font-size: 1.1rem;">Ready for your next academic milestone?</p>
                    </header>

                    <!-- Hero Section -->
                    <div style="background: linear-gradient(135deg, #2563eb, #1e40af); padding: 48px; border-radius: 32px; color: white; margin-bottom: 40px; box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.2);">
                        <div style="max-width: 500px;">
                            <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 16px;">Live Assessments are Ready</h2>
                            <p style="opacity: 0.9; margin-bottom: 32px; line-height: 1.6;">Our faculty has synchronized new examination modules tailored for the <strong><?php echo ($level === 'university' || $level === 'master') ? 'Institutional' : 'Educational'; ?> <?php echo ucfirst($level); ?></strong> tier.</p>
                            <a href="available_exams.php" class="btn-block" style="background: white; color: #2563eb; width: auto; padding: 16px 40px; border: none; font-size: 1.1rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                                <i class="fas fa-satellite-dish" style="margin-right: 10px;"></i> Browse Available Exams
                            </a>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div class="sidebar-card" style="margin: 0;">
                            <h4 style="font-size: 0.8rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 16px;">System Health</h4>
                            <div style="display: flex; align-items: center; gap: 12px; color: #10b981; font-weight: 700;">
                                <i class="fas fa-circle-check"></i> Connected to Faculty Node
                            </div>
                            <p style="font-size: 0.85rem; color: #94a3b8; margin-top: 8px;">Synchronized: <?php echo SYNC_TIME; ?></p>
                        </div>
                        <div class="sidebar-card" style="margin: 0;">
                            <h4 style="font-size: 0.8rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 16px;">Academic Credentials</h4>
                            <div style="font-size: 0.95rem; color: #1e293b; font-weight: 700;">
                                <?php echo htmlspecialchars((string)($dept ? $dept . " Dept." : ($stream ?? 'General'))); ?>
                            </div>
                            <?php if($sem): ?><div style="font-size: 0.85rem; color: #64748b;">Semester <?php echo $sem; ?></div><?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        <?php endif; ?>

        <footer style="margin-top: 80px; padding: 40px 0; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 0.85rem;">
            <div style="display: flex; justify-content: space-between;">
                <p>© <?php echo date('Y'); ?> ExamSystem Pro • Global Instance</p>
                <div style="display: flex; gap: 24px;">
                    <span><i class="fas fa-server"></i> Node: <?php echo defined('SYSTEM_NODE_ID') ? SYSTEM_NODE_ID : 'LOCAL'; ?></span>
                    <span><i class="fas fa-network-wired"></i> IP: <?php echo $_SERVER['SERVER_ADDR'] ?? '127.0.0.1'; ?></span>
                    <span><i class="fas fa-clock"></i> Sync: <?php echo date('H:i'); ?></span>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Dashboard specific interactions
    </script>
</body>
</html>
