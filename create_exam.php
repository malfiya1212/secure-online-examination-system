<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['user_name'] ?? 'teacher;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assessment | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: #1e293b; color: white; position: fixed; height: 100vh; padding: 32px 0; }
        .admin-main { flex: 1; margin-left: 280px; padding: 40px; background: #f8fafc; }
        .sidebar-link { display: flex; align-items: center; gap: 16px; padding: 14px 32px; color: #94a3b8; text-decoration: none; transition: 0.2s; font-weight: 500; }
        .sidebar-link:hover, .sidebar-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        
        .form-card { background: white; border-radius: 20px; padding: 40px; border: 1px solid #e2e8f0; }
        .section-title { font-size: 1rem; font-weight: 800; color: #1e293b; margin-bottom: 24px; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
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
                <a href="manage_exams.php" class="sidebar-link active"><i class="fas fa-file-pen"></i> Assessments</a>
                <a href="teacher_results.php" class="sidebar-link"><i class="fas fa-square-poll-vertical"></i> Insights</a>
                <a href="my_students.php" class="sidebar-link"><i class="fas fa-user-group"></i> My Students</a>
                <a href="teacher_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="logout.php" class="sidebar-link" style="margin-top: 40px; color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header style="margin-bottom: 40px;">
                <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a;">Draft New Assessment</h1>
                <p style="color: #64748b;">Configure exam parameters and audience targeting metrics</p>
            </header>

            <form action="create_exam_process.php" method="post" id="examForm" class="form-card">
                <div style="margin-bottom: 40px;">
                    <h3 class="section-title">Fundamental Configuration</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Assessment Title</label>
                            <input type="text" name="title" placeholder="e.g. Molecular Biology Q1" required>
                        </div>
                        <div class="form-group">
                            <label>Assessment Category</label>
                            <select name="type" required>
                                <option value="quiz">Quiz</option>
                                <option value="midterm">Midterm</option>
                                <option value="final">Final Exam</option>
                                <option value="test">Subjective Test</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subject / Discipline</label>
                            <input type="text" name="subject" placeholder="e.g. Physics, Law" required>
                        </div>
                        <div class="form-group">
                            <label>Temporal Duration (Minutes)</label>
                            <input type="number" name="duration" min="1" value="60" required>
                        </div>
                        <div class="form-group" style="display: flex; align-items: center; gap: 12px; height: 100%;">
                            <input type="checkbox" name="shuffle_questions" id="shuffle" value="1" style="width: 20px; height: 20px;">
                            <label for="shuffle" style="margin: 0; cursor: pointer;">Randomize Question Order for Students</label>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 40px;">
                    <h3 class="section-title">Scholar Targeting Metrics</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Education Level</label>
                            <select name="level" required>
                                <option value="elementary">Elementary</option>
                                <option value="highschool">High School</option>
                                <option value="university">University</option>
                                <option value="master">Master's</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Grade / Year Allocation</label>
                            <input type="text" name="grade_year" placeholder="e.g. 10, 1, Master-1">
                        </div>
                        <div class="form-group">
                            <label>Department / Stream (Optional)</label>
                            <input type="text" name="department" placeholder="e.g. CS, Arts, Science">
                        </div>
                        <div class="form-group">
                            <label>Section / Group (Optional)</label>
                            <input type="text" name="section" placeholder="e.g. A, Morning, B1">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Operational Instructions</label>
                    <textarea name="instructions" style="min-height: 100px;" placeholder="Outline the rules for candidates..."></textarea>
                </div>

                <button type="submit" class="btn-block" style="margin-top: 32px; padding: 16px;">
                    <i class="fas fa-network-wired"></i> Commit to Cluster Repository
                </button>
            </form>

            <footer style="margin-top: 60px; padding-top: 32px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 0.875rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <p>© <?php echo date('Y'); ?> ExamSystem Pro • Global Node Configuration</p>
                    <div style="display: flex; gap: 20px;">
                        <span><i class="fas fa-server"></i> Node: <?php echo defined('SYSTEM_NODE_ID') ? SYSTEM_NODE_ID : 'LOCAL'; ?></span>
                        <span><i class="fas fa-network-wired"></i> IP: <?php echo $_SERVER['SERVER_ADDR'] ?? '127.0.0.1'; ?></span>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</body>
</html>
