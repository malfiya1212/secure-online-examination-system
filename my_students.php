<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Fetch Students who have taken exams created by this teacher
$sql = "SELECT DISTINCT u.id, u.name, u.email, u.grade_year, u.education_level 
        FROM users u 
        JOIN results r ON u.id = r.student_id 
        JOIN exams e ON r.exam_id = e.id 
        WHERE e.created_by = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholars Directory | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: #1e293b; color: white; position: fixed; height: 100vh; padding: 32px 0; }
        .admin-main { flex: 1; margin-left: 280px; padding: 40px; }
        .sidebar-link { display: flex; align-items: center; gap: 16px; padding: 14px 32px; color: #94a3b8; text-decoration: none; transition: 0.2s; font-weight: 500; }
        .sidebar-link:hover, .sidebar-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        
        .student-table th { background: #f8fafc; text-align: left; padding: 16px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #f1f5f9; }
        .student-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; color: #1e293b; }
        
        .avatar-spot { width: 36px; height: 36px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; }
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
                <a href="teacher_results.php" class="sidebar-link"><i class="fas fa-square-poll-vertical"></i> Insights</a>
                <a href="my_students.php" class="sidebar-link active"><i class="fas fa-user-group"></i> My Students</a>
                <a href="teacher_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="logout.php" class="sidebar-link" style="margin-top: 40px; color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header style="margin-bottom: 40px;">
                <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a;">Scholars Directory</h1>
                <p style="color: #64748b;">Profiles of students who have participated in your active assessments</p>
            </header>

            <div class="card" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 32px;">
                <table style="width: 100%; border-collapse: collapse;" class="student-table">
                    <thead>
                        <tr>
                            <th>Student Identity</th>
                            <th>Email Address</th>
                            <th>Grade/Year</th>
                            <th>Academic Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="display: flex; align-items: center; gap: 12px;">
                                    <div class="avatar-spot"><?php echo strtoupper(substr((string)($row['name'] ?? 'S'), 0, 1)); ?></div>
                                    <span style="font-weight: 700;"><?php echo htmlspecialchars($row['name']); ?></span>
                                </td>
                                <td style="color: #64748b;"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><span style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-weight: 600;"><?php echo htmlspecialchars($row['grade_year'] ?? 'N/A'); ?></span></td>
                                <td><span style="text-transform: capitalize; color: #2563eb; font-weight: 600;"><?php echo htmlspecialchars($row['education_level'] ?? 'N/A'); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;">No students discovered yet. Data populates as assessments are completed.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
