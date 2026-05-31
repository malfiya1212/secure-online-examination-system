<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "success";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    
    if (!empty($name)) {
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $msg = "Profile name updated successfully.";
        } else {
            $msg = "Error updating profile: " . $conn->error;
            $msg_type = "error";
        }
    } else {
        $msg = "Name field cannot be left empty.";
        $msg_type = "error";
    }
}

// Fetch current info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Profile | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 280px;
            background: #1e293b;
            color: white;
            padding: 32px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .admin-main { flex: 1; margin-left: 280px; padding: 40px; background: #f8fafc; }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 32px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(255,255,255,0.05);
            color: white;
            border-left: 4px solid var(--primary);
        }
        
        .profile-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .avatar-section {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid #e2e8f0;
        }
        .avatar-circle {
            width: 80px;
            height: 80px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
        }
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            border: 1px solid #e2e8f0;
        }
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
                <a href="my_students.php" class="sidebar-link"><i class="fas fa-user-group"></i> My Students</a>
                <a href="teacher_profile.php" class="sidebar-link active"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="logout.php" class="sidebar-link" style="margin-top: 40px; color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="profile-container">
                <header style="margin-bottom: 40px;">
                    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a;">Faculty Profile</h1>
                    <p style="color: #64748b;">Manage your institutional identity details</p>
                </header>

                <div class="form-card">
                    <div class="avatar-section">
                        <div class="avatar-circle">
                            <?php echo strtoupper(substr((string)($teacher['name'] ?? 'F'), 0, 1)); ?>
                        </div>
                        <div>
                            <h2 style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($teacher['name']); ?></h2>
                            <p style="color: #64748b;"><?php echo htmlspecialchars($teacher['email']); ?></p>
                            <span style="background: #eff6ff; color: #2563eb; padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; margin-top: 8px; display: inline-block;">FACULTY MEMBER</span>
                        </div>
                    </div>

                    <form method="post">
                        <div class="form-group">
                            <label>Display Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($teacher['name']); ?>" required placeholder="e.g. Professor Smith">
                            <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 8px;"><i class="fas fa-circle-info"></i> Your email and password are managed by the System Administrator.</p>
                        </div>

                        <div style="margin-top: 32px;">
                            <button type="submit" class="btn-block">Update Identifier</button>
                        </div>
                    </form>
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

    <script>
        <?php if($msg): ?>
        Swal.fire({
            icon: '<?php echo $msg_type; ?>',
            title: 'Profile Updated',
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
