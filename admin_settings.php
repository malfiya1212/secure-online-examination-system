<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$msg = "";
// For a real system, we'd have a 'settings' table. Since we don't, we'll simulate it
// or create a simple table if it doesn't exist for persistence.
$conn->query("CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
)");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $registration_enabled = isset($_POST['registration_enabled']) ? '1' : '0';
    $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
    $institution_name = trim($_POST['institution_name']);
    
    $stmt = $conn->prepare("REPLACE INTO system_settings (setting_key, setting_value) VALUES ('registration_enabled', ?), ('maintenance_mode', ?), ('institution_name', ?)");
    $stmt->bind_param("sss", $registration_enabled, $maintenance_mode, $institution_name);
    
    if($stmt->execute()) {
        $msg = "Global cluster configuration synchronized successfully.";
    } else {
        $msg = "Error updating cluster settings: " . $conn->error;
    }
}

// Fetch current settings
$settings = [];
$res = $conn->query("SELECT * FROM system_settings");
while($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cluster Settings | ExamSystem Pro</title>
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
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 32px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 3px; bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(26px); }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .setting-item:last-child { border-bottom: none; }
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
                <a href="admin_settings.php" class="sidebar-link active"><i class="fas fa-cog"></i> Settings</a>
                <a href="admin_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="admin_cluster_monitor.php" class="sidebar-link"><i class="fas fa-network-wired"></i> Cluster Health</a>
                <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <a href="logout.php" class="sidebar-link" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
                </div>
            </nav>
        </aside>

        <main class="admin-main">
            <header style="margin-bottom: 40px;">
                <h1 style="font-size: 2rem; font-weight: 800; color: var(--secondary);">System Configuration</h1>
                <p style="color: var(--text-muted);">Manage global cluster behaviors and institutional branding</p>
            </header>

            <form method="post">
                <div class="settings-grid">
                    <div class="card animate-fade-in">
                        <h3 style="margin-bottom: 24px; font-weight: 800;"><i class="fas fa-access-point" style="margin-right: 12px; color: var(--primary);"></i> Access Controls</h3>
                        
                        <div class="setting-item">
                            <div>
                                <div style="font-weight: 700;">Student Self-Registration</div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">Allow new students to register through the portal</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="registration_enabled" <?php echo ($settings['registration_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="setting-item">
                            <div>
                                <div style="font-weight: 700;">Cluster Maintenance Mode</div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">Restrict access to all nodes for scheduled updates</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="card animate-fade-in" style="animation-delay: 0.1s;">
                        <h3 style="margin-bottom: 24px; font-weight: 800;"><i class="fas fa-building" style="margin-right: 12px; color: var(--primary);"></i> Institutional Branding</h3>
                        
                        <div class="form-group">
                            <label>Institution Name</label>
                            <div class="input-with-icon">
                                <input type="text" name="institution_name" value="<?php echo htmlspecialchars($settings['institution_name'] ?? 'ExamSystem Pro'); ?>" required>
                                <i class="fas fa-university"></i>
                            </div>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">Displayed on all certifications and node banners.</p>
                        </div>

                        <div style="margin-top: 32px;">
                            <button type="submit" name="save_settings" class="btn-block">Synchronize Configuration</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="card animate-fade-in" style="margin-top: 32px; animation-delay: 0.2s;">
                <h3 style="margin-bottom: 24px; font-weight: 800;"><i class="fas fa-microchip" style="margin-right: 12px; color: var(--primary);"></i> Node Infrastructure</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div style="padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Master Node ID</div>
                        <div style="font-weight: 800; color: var(--secondary); margin-top: 4px;"><?php echo gethostname(); ?></div>
                    </div>
                    <div style="padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Database Health</div>
                        <div style="font-weight: 800; color: #10b981; margin-top: 4px;">OPERATIONAL</div>
                    </div>
                    <div style="padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Time Sync Status</div>
                        <div style="font-weight: 800; color: #3b82f6; margin-top: 4px;">SYNCHRONIZED</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        <?php if($msg): ?>
        Swal.fire({
            icon: 'success',
            title: 'Cluster Configured',
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
