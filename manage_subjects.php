<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Handle Actions
$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $name = trim($_POST['name']);
    $level = $_POST['level'];
    $dept = trim($_POST['department']);
    
    if ($action == 'add') {
        $stmt = $conn->prepare("INSERT INTO subjects (name, level, department) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $level, $dept);
        if ($stmt->execute()) $msg = "Subject created successfully.";
        else $msg = "Error: " . $conn->error;
    } elseif ($action == 'edit') {
        $id = $_POST['subject_id'];
        $stmt = $conn->prepare("UPDATE subjects SET name=?, level=?, department=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $level, $dept, $id);
        if ($stmt->execute()) $msg = "Subject updated successfully.";
        else $msg = "Error: " . $conn->error;
    }
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM subjects WHERE id=$id");
    header("Location: manage_subjects.php");
    exit();
}

$result = $conn->query("SELECT * FROM subjects ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curriculum Management | ExamSystem Pro</title>
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
                <a href="manage_subjects.php" class="sidebar-link active"><i class="fas fa-book"></i> Curriculum</a>
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
                    <h1 style="font-size: 2rem; font-weight: 800; color: var(--secondary);">Curriculum Repository</h1>
                    <p style="color: var(--text-muted);">Configure academic subjects and departmental taxonomy</p>
                </div>
                <button class="btn-nav-primary" onclick="openModal('add')"><i class="fas fa-plus"></i> Add Subject</button>
            </header>

            <div class="card animate-fade-in">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div class="input-with-icon" style="flex: 1; max-width: 400px;">
                        <input type="text" id="searchInput" placeholder="Search curriculum..." onkeyup="searchTable()">
                        <i class="fas fa-search"></i>
                    </div>
                    <span class="badge badge-blue">Catalog Items: <?php echo $result->num_rows; ?></span>
                </div>

                <div class="table-container">
                    <table id="subjectsTable">
                        <thead>
                            <tr>
                                <th>SUBJECT MODULE</th>
                                <th>ACADEMIC TIER</th>
                                <th>DEPARTMENT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="padding: 20px 24px;">
                                    <div style="font-weight: 700; color: var(--secondary);"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Module ID: #<?php echo $row['id']; ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-success"><?php echo strtoupper($row['level'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--secondary);"><?php echo htmlspecialchars($row['department'] ?? 'GENERAL'); ?></div>
                                </td>
                                <td>
                                    <button class="btn-nav-primary" style="padding: 6px 12px; font-size: 0.8rem;" onclick='openModal("edit", <?php echo json_encode($row); ?>)'><i class="fas fa-edit"></i></button>
                                    <button class="btn-nav-primary" style="padding: 6px 12px; font-size: 0.8rem; background: #ef4444;" onclick="confirmDelete(<?php echo $row['id']; ?>)"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Enhanced Modal -->
    <div id="subjectModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
        <div class="card" style="width:100%; max-width:500px; margin:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h2 id="modalTitle" style="font-weight:800;">Define Subject</h2>
                <i class="fas fa-times" style="cursor:pointer; color:var(--text-muted);" onclick="closeModal()"></i>
            </div>

            <form method="post">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="subject_id" id="subjectId">
                
                <div class="form-group">
                    <label>Module Name</label>
                    <div class="input-with-icon">
                        <input type="text" name="name" id="subName" required placeholder="e.g. Advanced Mathematics">
                        <i class="fas fa-book"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Academic Tier</label>
                    <div class="input-with-icon">
                        <select name="level" id="subLevel" required>
                            <option value="elementary">Grade School</option>
                            <option value="highschool">High School</option>
                            <option value="university">Undergraduate</option>
                            <option value="master">Post-Graduate</option>
                        </select>
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Department Placement</label>
                    <div class="input-with-icon">
                        <input type="text" name="department" id="subDept" placeholder="e.g. Science & Logic">
                        <i class="fas fa-building"></i>
                    </div>
                </div>

                <div style="display:flex; gap:16px; margin-top:32px;">
                    <button type="submit" class="btn-block">Publish Module</button>
                    <button type="button" class="btn-block" style="background:#64748b;" onclick="closeModal()">Dismiss</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(mode, data = null) {
            document.getElementById('subjectModal').style.display = 'flex';
            if (mode === 'add') {
                document.getElementById('modalTitle').textContent = 'Define Subject';
                document.getElementById('formAction').value = 'add';
                document.forms[0].reset();
            } else {
                document.getElementById('modalTitle').textContent = 'Update Subject Module';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('subjectId').value = data.id;
                document.getElementById('subName').value = data.name;
                document.getElementById('subLevel').value = data.level;
                document.getElementById('subDept').value = data.department;
            }
        }
        function closeModal() { document.getElementById('subjectModal').style.display = 'none'; }
        
        function confirmDelete(id) {
            Swal.fire({
                title: 'Purge Module?',
                text: "The subject will be removed from the institutional catalog.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Purge Data'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?action=delete&id=' + id;
                }
            })
        }

        function searchTable() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#subjectsTable tbody tr');
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(input) ? '' : 'none';
            });
        }

        <?php if($msg): ?>
        Swal.fire({
            icon: 'success',
            title: 'Catalog Updated',
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
