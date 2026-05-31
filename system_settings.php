<?php
include 'db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$msg = "";
$error = "";
$admin_id = $_SESSION['user_id'];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (!empty($password)) {
        // Update with password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $hashed_password, $admin_id);
    } else {
        // Update without password
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $admin_id);
    }
    
    if ($stmt->execute()) {
        $msg = "Profile updated successfully! If you changed your credentials, please use them next time.";
        // Update session name if changed
        $_SESSION['user_name'] = $name;
        // Re-fetch details to show updated values
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
}

// Fetch Current Admin Details
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings</title>
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
        .card { background: white; padding: 30px; border-radius: 16px; width: 100%; max-width: 600px; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; }
        button { background: #1e3a8a; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header"><h2>Admin Panel</h2></div>
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="system_settings.php" class="active"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <h1 class="page-title">Profile & System Settings</h1>
        <?php if($msg): ?>
            <div style="background: #ecfdf5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #d1fae5;">
                <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #fee2e2;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Update My Profile</h3>
            <p style="color:#666; margin-bottom:20px; font-size:0.9rem;">Change your login credentials here.</p>
            
            <form method="post">
                <input type="hidden" name="action" value="update_profile">
                
                <label>Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                
                <label>New Password <small>(Leave blank to keep current)</small></label>
                <input type="password" name="password" placeholder="Enter new password">
                
                <button type="submit">Update Profile</button>
            </form>
        </div>
    </main>
</body>
</html>
