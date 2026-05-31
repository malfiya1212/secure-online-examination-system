<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['user_name'] ?? 'Faculty';

// Fetch Exams created by this teacher
$sql = "SELECT e.id, e.title, e.subject, e.created_at, e.total_marks,
        (SELECT COUNT(*) FROM results r WHERE r.exam_id = e.id) as submission_count,
        (SELECT AVG(r.score / r.total_marks * 100) FROM results r WHERE r.exam_id = e.id) as avg_score,
        (SELECT COUNT(*) FROM results r WHERE r.exam_id = e.id AND (r.score / r.total_marks * 100) >= 50) as pass_count
        FROM exams e 
        WHERE e.created_by = ? 
        ORDER BY e.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$selected_exam = null;
$results = [];
if (isset($_GET['exam_id'])) {
    $exam_id = intval($_GET['exam_id']);
    $check = $conn->query("SELECT * FROM exams WHERE id=$exam_id AND created_by=$teacher_id");
    if ($check->num_rows > 0) {
        $selected_exam = $check->fetch_assoc();
        $res_sql = "SELECT r.*, u.name as student_name, u.email 
                    FROM results r 
                    JOIN users u ON r.student_id = u.id 
                    WHERE r.exam_id = ? 
                    ORDER BY r.score DESC";
        $stmt_res = $conn->prepare($res_sql);
        $stmt_res->bind_param("i", $exam_id);
        $stmt_res->execute();
        $results = $stmt_res->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Analytics | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: #1e293b; color: white; position: fixed; height: 100vh; padding: 32px 0; }
        .admin-main { flex: 1; margin-left: 280px; padding: 40px; }
        .sidebar-link { display: flex; align-items: center; gap: 16px; padding: 14px 32px; color: #94a3b8; text-decoration: none; transition: 0.2s; font-weight: 500; }
        .sidebar-link:hover, .sidebar-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        
        .exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
        .exam-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 24px; transition: 0.3s; cursor: pointer; }
        .exam-card:hover { border-color: var(--primary); transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.05); }
        
        .progress-circle { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800; }
        
        .res-table th { background: #f8fafc; text-align: left; padding: 16px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; }
        .res-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; }
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
                <a href="teacher_dashboard.php" class="sidebar-link"><i class="fas fa-chart-pie"></i> Overview</a>
                <a href="teacher_question_bank.php" class="sidebar-link"><i class="fas fa-database"></i> Question Bank</a>
                <a href="manage_exams.php" class="sidebar-link"><i class="fas fa-file-pen"></i> Assessments</a>
                <a href="teacher_results.php" class="sidebar-link active"><i class="fas fa-square-poll-vertical"></i> Insights</a>
                <a href="my_students.php" class="sidebar-link"><i class="fas fa-user-group"></i> My Students</a>
                <a href="teacher_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="logout.php" class="sidebar-link" style="margin-top: 40px; color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
            </nav>
        </aside>

        <main class="admin-main">
            <?php if (!$selected_exam): ?>
                <header style="margin-bottom: 40px;">
                    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a;">Performance Insights</h1>
                    <p style="color: #64748b;">Select an assessment to view detailed behavioral analytics and scores</p>
                </header>

                <div class="exam-grid">
                    <?php foreach ($exams as $exam): 
                        $pass_rate = $exam['submission_count'] > 0 ? round(($exam['pass_count'] / $exam['submission_count']) * 100) : 0;
                    ?>
                        <div class="exam-card" onclick="location.href='?exam_id=<?php echo $exam['id']; ?>'">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                                <div class="progress-circle" style="background: <?php echo $pass_rate >= 70 ? '#ecfdf5' : ($pass_rate >= 40 ? '#fff7ed' : '#fef2f2'); ?>; color: <?php echo $pass_rate >= 70 ? '#059669' : ($pass_rate >= 40 ? '#ea580c' : '#dc2626'); ?>;">
                                    <?php echo $pass_rate; ?>%
                                </div>
                                <span style="font-size: 0.7rem; color: #94a3b8; font-weight: 700;"><?php echo strtoupper($exam['subject'] ?? 'GENERAL'); ?></span>
                            </div>
                            <h3 style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 12px;"><?php echo htmlspecialchars($exam['title']); ?></h3>
                            <div style="display: flex; gap: 16px; font-size: 0.85rem; color: #64748b;">
                                <span><i class="fas fa-users-viewfinder"></i> <?php echo $exam['submission_count']; ?> Responses</span>
                                <span><i class="fas fa-bullseye"></i> Avg: <?php echo round($exam['avg_score'], 1); ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 40px;">
                    <a href="teacher_results.php" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:12px; border:1px solid #e2e8f0; color:#64748b; text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
                    <div>
                        <h1 style="font-size: 1.75rem; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($selected_exam['title']); ?></h1>
                        <p style="color: #64748b;">Detailed student response breakdown and score distribution</p>
                    </div>
                </div>

                <div class="card" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;" class="res-table">
                        <thead>
                            <tr>
                                <th>Student Identity</th>
                                <th>Email</th>
                                <th>Achievement</th>
                                <th>Score (Real/Max)</th>
                                <th>Submission Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $res): 
                                $pct = ($selected_exam['total_marks'] > 0) ? ($res['score'] / $selected_exam['total_marks'] * 100) : 0;
                            ?>
                            <tr>
                                <td style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($res['student_name']); ?></td>
                                <td style="color: #64748b;"><?php echo htmlspecialchars($res['email']); ?></td>
                                <td>
                                    <div style="width: 120px; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden; margin-bottom: 4px;">
                                        <div style="width: <?php echo $pct; ?>%; height: 100%; background: <?php echo $pct >= 50 ? '#10b981' : '#ef4444'; ?>;"></div>
                                    </div>
                                    <span style="font-size: 0.7rem; font-weight: 700; color: #64748b;"><?php echo round($pct, 1); ?>%</span>
                                </td>
                                <td><strong><?php echo floatval($res['score']); ?></strong> / <?php echo floatval($selected_exam['total_marks']); ?></td>
                                <td><?php echo date('M d, H:i', strtotime($res['submitted_at'])); ?></td>
                                <td>
                                    <span style="font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; <?php echo $pct >= 50 ? 'background:#dcfce7; color:#166534;' : 'background:#fef2f2; color:#991b1b;'; ?>">
                                        <?php echo $pct >= 50 ? 'PASSED' : 'FAILED'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

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
