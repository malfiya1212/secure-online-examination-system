<?php
include 'db_connect.php';

// Security: Only Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Handle Actions
$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action == 'add' || $action == 'edit') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $level = $_POST['education_level'];
        $grade = trim($_POST['grade_year']);
        
        if ($action == 'add') {
            $password = password_hash("student123", PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, education_level, grade_year, status) VALUES (?, ?, ?, 'student', ?, ?, 'approved')");
            $stmt->bind_param("sssss", $name, $email, $password, $level, $grade);
            if ($stmt->execute()) $msg = "Student added successfully";
            else $msg = "Error: " . $conn->error;
        } elseif ($action == 'edit') {
            $id = $_POST['user_id'];
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, education_level=?, grade_year=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $level, $grade, $id);
            if ($stmt->execute()) $msg = "Student updated successfully";
            else $msg = "Error: " . $conn->error;
        }
    }
}

// Handle GET Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM users WHERE id=$id AND role='student'");
        header("Location: manage_students.php");
        exit();
    } elseif ($_GET['action'] == 'toggle') {
        $current = $conn->query("SELECT status FROM users WHERE id=$id")->fetch_assoc()['status'];
        $new_status = ($current == 'approved') ? 'rejected' : 'approved';
        $conn->query("UPDATE users SET status='$new_status' WHERE id=$id");
        header("Location: manage_students.php");
        exit();
    }
}

// Fetch Students
$result = $conn->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholars Management | ExamSystem Pro</title>
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
        
        .modal-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
                <a href="manage_students.php" class="sidebar-link active"><i class="fas fa-user-graduate"></i> Scholars</a>
                <a href="manage_teachers.php" class="sidebar-link"><i class="fas fa-chalkboard-teacher"></i> Faculty</a>
                <a href="manage_exams.php" class="sidebar-link"><i class="fas fa-file-invoice"></i> Assessments</a>
                <a href="manage_subjects.php" class="sidebar-link"><i class="fas fa-book"></i> Curriculum</a>
                <a href="user_approvals.php" class="sidebar-link"><i class="fas fa-user-check"></i> Approvals</a>
                <a href="admin_settings.php" class="sidebar-link"><i class="fas fa-cog"></i> Settings</a>
                <a href="admin_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="admin_cluster_monitor.php" class="sidebar-link"><i class="fas fa-network-wired"></i> Cluster Health</a>
                <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                    <a href="logout.php" class="sidebar-link" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
                </div>
            </nav>
        </aside>

        <main class="admin-main">
            <header style="margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 800; color: var(--secondary);">Scholars Directory</h1>
                    <p style="color: var(--text-muted);">Manage institutional candidates and academic status</p>
                </div>
                <button class="btn-nav-primary" onclick="openModal('add')"><i class="fas fa-plus"></i> Enroll Student</button>
            </header>

            <div class="card animate-fade-in">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div class="input-with-icon" style="flex: 1; max-width: 400px;">
                        <input type="text" id="searchInput" placeholder="Search by name, email or grade..." onkeyup="searchTable()">
                        <i class="fas fa-search"></i>
                    </div>
                    <span class="badge badge-blue">Total Scholars: <?php echo $result->num_rows; ?></span>
                </div>

                <div class="table-container">
                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>SCHOLAR INFO</th>
                                <th>TIER / GRADE</th>
                                <th>ENROLLMENT STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="padding: 20px 24px;">
                                    <div style="font-weight: 700; color: var(--secondary);"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-blue"><?php echo strtoupper((string)($row['education_level'] ?? 'PENDING')); ?></span>
                                    <div style="font-size: 0.8rem; margin-top: 4px; color: var(--text-muted);"><?php echo htmlspecialchars($row['grade_year'] ?? 'N/A'); ?> Year</div>
                                </td>
                                <td>
                                    <span class="badge <?php echo (($row['status'] ?? 'inactive') == 'approved') ? 'badge-success' : 'badge-error'; ?>">
                                        <?php echo strtoupper((string)($row['status'] ?? 'INACTIVE')); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-nav-primary" style="padding: 6px 12px; font-size: 0.8rem;" onclick='openModal("edit", <?php echo json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                    <a href="?action=toggle&id=<?php echo $row['id']; ?>" class="btn-nav-primary" style="padding: 6px 12px; font-size: 0.8rem; background: #f59e0b;" onclick="return confirm('Toggle scholar access status?')"><i class="fas fa-shield-alt"></i></a>
                                    <button class="btn-nav-primary" style="padding: 6px 12px; font-size: 0.8rem; background: #ef4444;" onclick="confirmDelete(<?php echo $row['id']; ?>)"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer class="dist-footer" style="margin-top: 60px; background: transparent; border: none; padding-bottom: 0;">
                <div class="node-info-pills">
                    <span><i class="fas fa-server"></i> Node: <strong><?php echo gethostname(); ?></strong></span>
                    <span><i class="fas fa-user-shield"></i> Role: <strong>MASTER_ADMIN</strong></span>
                </div>
            </footer>
        </main>
    </div>

    <!-- Enhanced Modal -->
    <div id="studentModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
        <div class="card" style="width:100%; max-width:600px; margin:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h2 id="modalTitle" style="font-weight:800;">Enroll New Scholar</h2>
                <i class="fas fa-times" style="cursor:pointer; color:var(--text-muted);" onclick="closeModal()"></i>
            </div>

            <form method="post">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="user_id" id="userId">
                
                <div class="form-group">
                    <label>Scholar Full Name</label>
                    <div class="input-with-icon">
                        <input type="text" name="name" id="studentName" required placeholder="e.g. John Doe">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email ID / Credentials</label>
                    <div class="input-with-icon">
                        <input type="email" name="email" id="studentEmail" required placeholder="name@institution.com">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>

                <div class="modal-form-grid">
                    <div class="form-group">
                        <label>Academic Tier</label>
                        <div class="input-with-icon">
                            <select name="education_level" id="studentLevel" required>
                                <option value="elementary">Grade School</option>
                                <option value="highschool">High School</option>
                                <option value="university">Undergraduate</option>
                                <option value="master">Post-Graduate</option>
                            </select>
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Year / Level</label>
                        <div class="input-with-icon">
                            <input type="text" name="grade_year" id="studentGrade" placeholder="e.g. 10">
                            <i class="fas fa-layer-group"></i>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:16px; margin-top:32px;">
                    <button type="submit" class="btn-block">Synchronize Record</button>
                    <button type="button" class="btn-block" style="background:#64748b;" onclick="closeModal()">Dismiss</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(mode, data = null) {
            document.getElementById('studentModal').style.display = 'flex';
            if (mode === 'add') {
                document.getElementById('modalTitle').textContent = 'Enroll New Scholar';
                document.getElementById('formAction').value = 'add';
                document.forms[0].reset();
            } else {
                document.getElementById('modalTitle').textContent = 'Modify Scholar Record';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('userId').value = data.id;
                document.getElementById('studentName').value = data.name;
                document.getElementById('studentEmail').value = data.email;
                document.getElementById('studentLevel').value = data.education_level;
                document.getElementById('studentGrade').value = data.grade_year;
            }
        }
        function closeModal() { document.getElementById('studentModal').style.display = 'none'; }
        
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Scholar record will be permanently purged from the cluster.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Purge Record'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?action=delete&id=' + id;
                }
            })
        }

        function searchTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#studentsTable tbody tr');
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(input) ? '' : 'none';
            });
        }

        <?php if($msg): ?>
        Swal.fire({
            icon: '<?php echo strpos($msg, "Error") !== false ? "error" : "success"; ?>',
            title: 'System Notification',
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
