<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$sql = "SELECT r.*, u.name as student_name, e.title as exam_title 
        FROM results r 
        JOIN users u ON r.student_id = u.id 
        JOIN exams e ON r.exam_id = e.id 
        ORDER BY r.submitted_at DESC LIMIT 100";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Results | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Shared Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: #f4f6f9; display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #1e3a8a; color: white; position: fixed; height: 100%; overflow-y: auto; transition: all 0.3s; z-index: 1000; }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-header h2 { font-size: 1.6rem; }
        .sidebar-menu a { display: flex; align-items: center; padding: 15px 25px; color: white; text-decoration: none; }
        .sidebar-menu a i { margin-right: 15px; font-size: 1.2rem; width: 25px; text-align: center; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #2563eb; }
        .main-content { flex: 1; margin-left: 260px; padding: 40px; }
        .page-title { font-size: 2.2rem; color: #1e3a8a; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); }
        th, td { padding: 16px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #1e3a8a; color: white; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header"><h2>Admin Panel</h2></div>
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="manage_students.php"><i class="fas fa-user-graduate"></i><span>Students</span></a>
            <a href="view_results.php" class="active"><i class="fas fa-chart-bar"></i><span>Results</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <h1 class="page-title">All Results</h1>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Exam</th>
                    <th>Score</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['exam_title']); ?></td>
                    <td><?php echo $row['score'] . ' / ' . $row['total_marks']; ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($row['submitted_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
