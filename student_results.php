<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['user_name'] ?? 'Candidate';

// Fetch Results
$sql = "SELECT r.*, e.title, e.subject, e.total_marks as max_score 
        FROM results r 
        JOIN exams e ON r.exam_id = e.id 
        WHERE r.student_id = ? 
        ORDER BY r.submitted_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// Calculate Stats
$results_data = [];
$total_percent = 0;
$passed_count = 0;

while($row = $result->fetch_assoc()) {
    $percentage = ($row['max_score'] > 0) ? ($row['score'] / $row['max_score']) * 100 : 0;
    $row['percentage'] = $percentage;
    $total_percent += $percentage;
    
    if ($percentage >= 50) { 
        $row['status'] = 'Passed';
        $passed_count++;
    } else {
        $row['status'] = 'Failed';
    }
    
    $results_data[] = $row;
}

$total_exams = count($results_data);
$failed_count = $total_exams - $passed_count;
$avg_score = ($total_exams > 0) ? round($total_percent / $total_exams) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Transcript | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .dashboard-layout { display: flex; min-height: 100vh; background: #f8fafc; }
        .sidebar { width: 280px; background: #0f172a; color: white; position: fixed; height: 100vh; padding: 32px 0; }
        .main-content { flex: 1; margin-left: 280px; padding: 40px; }
        .nav-link { 
            display: flex; align-items: center; gap: 12px; padding: 12px 32px; 
            color: #94a3b8; text-decoration: none; transition: 0.2s; 
        }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        
        .stat-card { background: white; padding: 24px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #64748b; font-size: 0.875rem; text-transform: uppercase; margin-bottom: 8px; }
        .stat-card .value { font-size: 1.875rem; font-weight: 800; color: #1e293b; }
        
        .results-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        .results-table th { text-align: left; padding: 16px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 0.75rem; text-transform: uppercase; }
        .results-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; }
        
        .badge { padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-passed { background: #dcfce7; color: #166534; }
        .badge-failed { background: #fee2e2; color: #991b1b; }
        
        .view-details { color: var(--primary); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .view-details:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div style="padding: 0 32px 32px;">
                <div class="brand" style="font-size: 1.5rem; font-weight: 800; color: white;">ExamSystem Pro</div>
                <p style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">Student Intelligence Portal</p>
            </div>
            <nav>
                <a href="student_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                <a href="student_results.php" class="nav-link active"><i class="fas fa-file-invoice"></i> My Results</a>
                <a href="logout.php" class="nav-link" style="margin-top: 40px; color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header style="margin-bottom: 40px;">
                <h1 style="font-size: 2rem; font-weight: 800; color: #1e293b;">Performance Analytics</h1>
                <p style="color: #64748b;">Detailed history of your assessment performance</p>
            </header>

            <div class="stat-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 40px;">
                <div class="stat-card">
                    <h3>Total Exams</h3>
                    <div class="value"><?php echo $total_exams; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Average Score</h3>
                    <div class="value"><?php echo $avg_score; ?>%</div>
                </div>
                <div class="stat-card">
                    <h3>Successful</h3>
                    <div class="value" style="color: #10b981;"><?php echo $passed_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Needs Improvement</h3>
                    <div class="value" style="color: #ef4444;"><?php echo $failed_count; ?></div>
                </div>
            </div>

            <div class="card" style="background: white; border-radius: 20px; padding: 32px; border: 1px solid #e2e8f0;">
                <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 24px;">Assessment History</h2>
                <div style="overflow-x: auto;">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Assessment Name</th>
                                <th>Subject</th>
                                <th>Completion Date</th>
                                <th>Score (Real/Max)</th>
                                <th>Performance</th>
                                <th>Status</th>
                                <th>Analysis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($results_data) > 0): ?>
                                <?php foreach($results_data as $row): ?>
                                    <tr>
                                        <td style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['subject'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                                        <td><strong><?php echo floatval($row['score']); ?></strong> <span style="color: #94a3b8;">/ <?php echo floatval($row['max_score']); ?></span></td>
                                        <td>
                                            <div style="width: 100px; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                                <div style="width: <?php echo $row['percentage']; ?>%; height: 100%; background: <?php echo $row['percentage'] >= 50 ? '#10b981' : '#ef4444'; ?>;"></div>
                                            </div>
                                            <span style="font-size: 0.7rem; color: #64748b;"><?php echo round($row['percentage'], 1); ?>%</span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $row['status'] == 'Passed' ? 'badge-passed' : 'badge-failed'; ?>">
                                                <?php echo strtoupper($row['status'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_result_details.php?id=<?php echo $row['id']; ?>" class="view-details">
                                                <i class="fas fa-chart-line"></i> Deep Dive
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">No assessment history found for this profile.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer style="margin-top: 60px; padding-top: 32px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 0.875rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <p>© <?php echo date('Y'); ?> ExamSystem Pro • Global Academic Network</p>
                    <div style="display: flex; gap: 20px;">
                        <span><i class="fas fa-server"></i> Node: <?php echo defined('SYSTEM_NODE_ID') ? SYSTEM_NODE_ID : 'LOCAL'; ?></span>
                        <span><i class="fas fa-network-wired"></i> IP: <?php echo $_SERVER['SERVER_ADDR'] ?? '127.0.0.1'; ?></span>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</body>
</html>
