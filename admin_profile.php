<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "success";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Update basic info
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name;
        $msg = "Profile updated successfully.";
    } else {
        $msg = "Error updating profile: " . $conn->error;
        $msg_type = "error";
    }
    
    // Handle password update
    if (!empty($new_password)) {
        if ($new_password === $confirm_password) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            if ($stmt->execute()) {
                $msg .= " Password updated.";
            }
        } else {
            $msg = "Passwords do not match.";
            $msg_type = "error";
        }
    }
}

// Fetch current info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Profile | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .avatar-section {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid #f1f5f9;
        }
        .avatar-circle {
            width: 100px;
            height: 100px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 800;
        }
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
                <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_students.php" class="sidebar-link"><i class="fas fa-user-graduate"></i> Scholars</a>
                <a href="manage_teachers.php" class="sidebar-link"><i class="fas fa-chalkboard-teacher"></i> Faculty</a>
                <a href="manage_exams.php" class="sidebar-link"><i class="fas fa-file-invoice"></i> Assessments</a>
                <a href="manage_subjects.php" class="sidebar-link"><i class="fas fa-book"></i> Curriculum</a>
                <a href="user_approvals.php" class="sidebar-link"><i class="fas fa-user-check"></i> Approvals</a>
                <a href="admin_settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
                <a href="admin_profile.php" class="sidebar-link active"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="admin_cluster_monitor.php" class="sidebar-link"><i class="fas fa-network-wired"></i> Cluster Health</a>
                <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <a href="logout.php" class="sidebar-link" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
                </div>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="profile-container">
                <header style="margin-bottom: 40px;">
                    <h1 style="font-size: 2rem; font-weight: 800; color: var(--secondary);">Master Profile</h1>
                    <p style="color: var(--text-muted);">Configure your administrative credentials and identify traits</p>
                </header>

                <div class="card animate-fade-in">
                    <div class="avatar-section">
                        <div class="avatar-circle">
                            <?php echo strtoupper(substr((string)($admin['name'] ?? 'A'), 0, 1)); ?>
                        </div>
                        <div>
                            <h2 style="font-weight: 800; color: var(--secondary);"><?php echo htmlspecialchars($admin['name']); ?></h2>
                            <p style="color: var(--text-muted);"><?php echo htmlspecialchars($admin['email']); ?></p>
                            <span class="badge badge-blue" style="margin-top: 8px;">SYSTEM MASTER</span>
                        </div>
                    </div>

                    <form method="post">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                            <div class="form-group">
                                <label>Legal Name</label>
                                <div class="input-with-icon">
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email Identifier</label>
                                <div class="input-with-icon">
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                    <i class="fas fa-envelope"></i>
                                </div>
                            </div>
                        </div>

                        <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 32px 0;">
                        <h3 style="margin-bottom: 24px; font-weight: 800;"><i class="fas fa-key" style="margin-right: 12px; color: var(--primary);"></i> Security Authorization</h3>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                            <div class="form-group">
                                <label>New Password (Keep empty to preserve)</label>
                                <div class="input-with-icon">
                                    <input type="password" name="new_password" placeholder="••••••••">
                                    <i class="fas fa-lock"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <div class="input-with-icon">
                                    <input type="password" name="confirm_password" placeholder="••••••••">
                                    <i class="fas fa-lock-open"></i>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn-block" style="max-width: 250px;">Synchronize Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        <?php if($msg): ?>
        Swal.fire({
            icon: '<?php echo $msg_type; ?>',
            title: '<?php echo $msg_type == "success" ? "Authorized" : "Error"; ?>',
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
