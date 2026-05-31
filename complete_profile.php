<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $level = $_POST['level'];
    $grade_year = $_POST['grade_year'];
    $section = $_POST['section'] ?? null;
    $stream = $_POST['stream'] ?? null;
    $department = $_POST['department'] ?? null;
    $semester = $_POST['semester'] ?? null;

    $sql = "UPDATE users SET education_level = ?, grade_year = ?, section = ?, stream = ?, department = ?, semester = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $level, $grade_year, $section, $stream, $department, $semester, $user_id);

    if ($stmt->execute()) {
        header("Location: student_dashboard.php?msg=Profile updated successfully!");
        exit();
    } else {
        $error = "Failed to update profile. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .profile-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }
        .dynamic-section {
            padding: 24px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="student_dashboard.php" class="brand"><i class="fas fa-graduation-cap"></i> ExamSystem <span style="font-weight:300;">Pro</span></a>
            <div class="nav-links">
                <a href="logout.php" class="btn-nav-primary">Sign Out</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="profile-container animate-fade-in">
            <div style="text-align: center; margin-bottom: 32px;">
                <div style="width: 64px; height: 64px; background: #eff6ff; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 16px;">
                    <i class="fas fa-id-card"></i>
                </div>
                <h2 style="font-weight: 800; color: #0f172a;">Complete Your Profile</h2>
                <p style="color: var(--text-muted);">Configure your institutional tier to access live assessments.</p>
            </div>

            <form action="complete_profile.php" method="post">
                <div class="form-group">
                    <label>Educational Tier</label>
                    <div class="input-with-icon">
                        <select name="level" id="eduLevel" required onchange="toggleLevelFields(this.value)">
                            <option value="">Select Tier</option>
                            <option value="elementary">Grade School (1-8)</option>
                            <option value="highschool">Secondary (9-12)</option>
                            <option value="university">Undergraduate</option>
                            <option value="master">Post-Graduate</option>
                        </select>
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>

                <div id="dynamicFields"></div>

                <button type="submit" class="btn-block" style="margin-top: 32px;">Synchronize Credentials</button>
            </form>
        </div>
    </div>

    <script>
        function toggleLevelFields(level) {
            const container = document.getElementById('dynamicFields');
            container.innerHTML = '';
            if (!level) return;
            
            container.className = 'dynamic-section';
            let html = '';

            if (level === 'elementary') {
                html = `<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group"><label>Grade</label><select name="grade_year" required style="width: 100%; border: 1px solid var(--border); padding: 10px; border-radius: 8px;">
                        ${Array.from({length:8}, (_,i)=>`<option value="${i+1}">Grade ${i+1}</option>`).join('')}
                    </select></div>
                    <div class="form-group"><label>Section</label><input type="text" name="section" placeholder="e.g. A" required></div>
                </div>`;
            } else if (level === 'highschool') {
                html = `<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group"><label>Grade</label><select name="grade_year" required style="width: 100%; border: 1px solid var(--border); padding: 10px; border-radius: 8px;">
                        ${Array.from({length:4}, (_,i)=>`<option value="${i+9}">Grade ${i+9}</option>`).join('')}
                    </select></div>
                    <div class="form-group"><label>Track</label><input type="text" name="stream" placeholder="Science/Social" required></div>
                </div>`;
            } else if (level === 'university' || level === 'master') {
                html = `<div class="form-group"><label>Major / Clinical Department</label><input type="text" name="department" placeholder="e.g. CS" required></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group"><label>Academic Year</label><select name="grade_year" required style="width: 100%; border: 1px solid var(--border); padding: 10px; border-radius: 8px;">
                        ${[1,2,3,4,5,6].map(y => `<option value="${y}">${y} Year</option>`).join('')}
                    </select></div>
                    <div class="form-group"><label>Semester</label><select name="semester" required style="width: 100%; border: 1px solid var(--border); padding: 10px; border-radius: 8px;">
                        <option value="1">1st Sem</option><option value="2">2nd Sem</option>
                    </select></div>
                </div>`;
            }
            container.innerHTML = html;
        }

        <?php if($error): ?>
        Swal.fire({ icon: 'error', title: 'Update Failed', text: '<?php echo $error; ?>' });
        <?php endif; ?>
    </script>
</body>
</html>
