<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM questions WHERE id=$id");
    header("Location: question_bank.php");
    exit();
}

$sql = "SELECT q.*, e.title as exam_title 
        FROM questions q 
        LEFT JOIN exams e ON q.exam_id = e.id 
        ORDER BY q.id DESC LIMIT 100";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Question Bank | Admin Panel</title>
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
        .delete { color: #ef4444; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header"><h2>Admin Panel</h2></div>
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="manage_students.php"><i class="fas fa-user-graduate"></i><span>Students</span></a>
            <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i><span>Teachers</span></a>
            <a href="manage_exams.php"><i class="fas fa-file-alt"></i><span>Exams</span></a>
            <a href="question_bank.php" class="active"><i class="fas fa-question-circle"></i><span>Questions</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <h1 class="page-title">Question Bank (All)</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Question</th>
                    <th>Type</th>
                    <th>Exam</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars(substr($row['question_text'], 0, 50)) . '...'; ?></td>
                    <td><?php echo ucfirst($row['type']); ?></td>
                    <td><?php echo htmlspecialchars($row['exam_title'] ?? 'N/A'); ?></td>
                    <td><a href="?delete=<?php echo $row['id']; ?>" class="delete" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
