<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;


$stmt = $conn->prepare("SELECT title, subject, level, grade_year, total_marks FROM exams WHERE id = ? AND created_by = ?");
$stmt->bind_param("ii", $exam_id, $teacher_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) { die("Access denied or assessment not found."); }


function updateExamTotal($conn, $exam_id) {
    $stmt = $conn->prepare("SELECT SUM(marks) as total FROM questions WHERE exam_id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    $update = $conn->prepare("UPDATE exams SET total_marks = ? WHERE id = ?");
    $update->bind_param("ii", $total, $exam_id);
    $update->execute();
    return $total;
}

// Handle Add Question
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_text'])) {
    $text = trim($_POST['question_text']);
    $type = $_POST['type'];
    $marks = intval($_POST['marks']);
    $correct = $_POST['correct_answer_radio'] ?? ''; // Fixed for MCQ
    
    $options = [];
    if ($type === 'mcq' && isset($_POST['options'])) {
        $options = array_values(array_filter($_POST['options'], function($val) { return trim($val) !== ''; }));
        // If radio value exists and is within range, set correct answer to the text of that option
        if ($correct !== '' && isset($options[$correct])) {
            $correct = $options[$correct];
        }
    }
    $options_json = json_encode($options);
    
    $stmt = $conn->prepare("INSERT INTO questions (exam_id, created_by, question_text, type, options, correct_answer, marks, education_level, grade_year, subject) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssssss", $exam_id, $teacher_id, $text, $type, $options_json, $correct, $marks, $exam['level'], $exam['grade_year'], $exam['subject']);
    
    if ($stmt->execute()) {
        $msg = "Item successfully integrated.";
        updateExamTotal($conn, $exam_id);
        if (isset($_POST['save_to_bank'])) {
            $bank_stmt = $conn->prepare("INSERT INTO questions (exam_id, created_by, question_text, type, options, correct_answer, marks, education_level, grade_year, subject) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $bank_stmt->bind_param("issssssss", $teacher_id, $text, $type, $options_json, $correct, $marks, $exam['level'], $exam['grade_year'], $exam['subject']);
            $bank_stmt->execute();
        }
    }
}

// Handle Import from Repository
if (isset($_GET['import_id'])) {
    $import_id = intval($_GET['import_id']);
    $stmt = $conn->prepare("INSERT INTO questions (exam_id, created_by, question_text, type, options, correct_answer, marks, education_level, grade_year, subject) 
             SELECT ?, ?, question_text, type, options, correct_answer, marks, education_level, grade_year, subject FROM questions WHERE id = ? AND exam_id IS NULL AND created_by = ?");
    $stmt->bind_param("iiii", $exam_id, $teacher_id, $import_id, $teacher_id);
    if ($stmt->execute()) {
        updateExamTotal($conn, $exam_id);
        header("Location: add_question.php?exam_id=$exam_id&msg=Import Successful");
        exit();
    }
}

// Handle Delete Question
if (isset($_GET['delete'])) {
    $qid = intval($_GET['delete']);
    $conn->query("DELETE FROM questions WHERE id=$qid AND exam_id=$exam_id");
    updateExamTotal($conn, $exam_id);
    header("Location: add_question.php?exam_id=$exam_id");
    exit();
}

// Fetch Existing Questions
$q_stmt = $conn->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC");
$q_stmt->bind_param("i", $exam_id);
$q_stmt->execute();
$questions = $q_stmt->get_result();

// Fetch Repository Items
$r_stmt = $conn->prepare("SELECT * FROM questions WHERE exam_id IS NULL AND created_by = ? ORDER BY subject, id DESC");
$r_stmt->bind_param("i", $teacher_id);
$r_stmt->execute();
$repository = $r_stmt->get_result();

$current_total = updateExamTotal($conn, $exam_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Editor | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: #1e293b; color: white; position: fixed; height: 100vh; padding: 32px 0; }
        .admin-main { flex: 1; margin-left: 280px; padding: 40px; background: #f8fafc; }
        .sidebar-link { display: flex; align-items: center; gap: 16px; padding: 14px 32px; color: #94a3b8; text-decoration: none; transition: 0.2s; font-weight: 500; }
        .sidebar-link:hover, .sidebar-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        
        .editor-grid { display: grid; grid-template-columns: 1.2fr 1.8fr; gap: 32px; }
        .form-card { background: white; border-radius: 20px; padding: 32px; border: 1px solid #e2e8f0; position: sticky; top: 40px; }
        .question-preview { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 32px; }
        
        .q-item { padding: 20px; border-bottom: 1px solid #f1f5f9; position: relative; }
        .q-item:last-child { border-bottom: none; }

        .stats-bar { display: flex; gap: 24px; margin-bottom: 32px; background: white; padding: 24px 32px; border-radius: 20px; border: 1px solid #e2e8f0; align-items: center; }
        .stat-item { flex: 1; }
        .stat-item h4 { font-size: 0.75rem; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
        .stat-item div { font-size: 1.5rem; font-weight: 800; color: #1e293b; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .modal-content { background: white; width: 90%; max-width: 800px; border-radius: 24px; padding: 32px; max-height: 80vh; overflow-y: auto; }
        .repo-item { padding: 16px; border: 1px solid #f1f5f9; border-radius: 12px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; }
        .repo-item:hover { border-color: var(--primary); background: #f8fafc; }
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
            <header style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($exam['title']); ?></h1>
                    <p style="color: #64748b;">Engineering Subjective and Objective Assessment Items</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button class="btn-block" style="width: auto; padding: 12px 24px; background: #6366f1;" onclick="openRepo()"><i class="fas fa-folder-open"></i> Repository Browser</button>
                    <a href="manage_exams.php" class="btn-block" style="width: auto; padding: 12px 24px; background: #1e293b;"><i class="fas fa-check-double"></i> Commit Assessment</a>
                </div>
            </header>

            <div class="stats-bar">
                <div class="stat-item">
                    <h4>Total Marks</h4>
                    <div><?php echo $current_total; ?></div>
                </div>
                <div class="stat-item">
                    <h4>Items</h4>
                    <div><?php echo $questions->num_rows; ?></div>
                </div>
                <div class="stat-item">
                    <h4>Subject</h4>
                    <div style="font-size: 1rem; color: #2563eb;"><?php echo htmlspecialchars($exam['subject']); ?></div>
                </div>
                <div class="stat-item">
                    <h4>Target</h4>
                    <div style="font-size: 1rem;"><?php echo ucfirst($exam['level']); ?></div>
                </div>
            </div>

            <div class="editor-grid">
                <div class="form-card">
                    <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 24px;">Draft New Item</h3>
                    <form method="post">
                        <div class="form-group"><label>Question Prompt</label><textarea name="question_text" required placeholder="Outline the question..."></textarea></div>
                        <div class="form-group"><label>Category</label><select name="type" id="qType" onchange="toggleOptions(this.value)"><option value="mcq">Multiple Choice</option><option value="short_answer">Short Answer</option></select></div>
                        
                        <div id="optionsGroup" style="display: block; margin-bottom: 20px;">
                            <label>Response Choices (Check Correct)</label>
                            <?php for($i=0; $i<4; $i++): ?>
                                <div style="display: flex; gap: 12px; margin-bottom: 12px; align-items: center;">
                                    <input type="radio" name="correct_answer_radio" value="<?php echo $i; ?>" style="width: 20px; height: 20px;">
                                    <input type="text" name="options[]" placeholder="Choice <?php echo $i+1; ?>" style="flex: 1;">
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="form-group"><label>Mark Allocation</label><input type="number" name="marks" value="1" min="1"></div>
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding: 12px; background: #f8fafc; border-radius: 12px;">
                            <input type="checkbox" name="save_to_bank" id="stb" style="width: 18px; height: 18px;">
                            <label for="stb" style="margin: 0; cursor: pointer; font-size: 0.9rem;">Store in global repository for reuse?</label>
                        </div>
                        <button type="submit" class="btn-block">Integrate Item</button>
                    </form>
                </div>

                <div class="question-preview">
                    <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 24px;">Assessment Manifest</h3>
                    <?php if($questions->num_rows === 0): ?>
                        <div style="text-align: center; padding: 48px; color: #94a3b8;"><i class="fas fa-file-pen" style="font-size: 3rem; margin-bottom: 16px;"></i><p>No items detected. Add new items or browse the repository.</p></div>
                    <?php else: ?>
                        <?php while($q = $questions->fetch_assoc()): ?>
                            <div class="q-item">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($q['question_text']); ?></div>
                                    <div style="display: flex; gap: 12px; align-items: center;">
                                        <div style="font-size: 0.75rem; color: #2563eb; font-weight: 800; background: #eff6ff; padding: 4px 8px; border-radius: 6px;"><?php echo $q['marks']; ?> PTS</div>
                                        <a href="?exam_id=<?php echo $exam_id; ?>&delete=<?php echo $q['id']; ?>" style="color: #ef4444;" onclick="return confirm('Archive item?')"><i class="fas fa-trash-can"></i></a>
                                    </div>
                                </div>
                                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 8px; font-weight: 700; text-transform: uppercase;"><?php echo $q['type']; ?> Item</div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="repoModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a;">Repository Browser</h2>
                <button onclick="closeRepo()" style="background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer;"><i class="fas fa-times"></i></button>
            </div>
            <p style="color: #64748b; margin-bottom: 24px;">Select items from your global repository to clone into this assessment.</p>
            
            <div id="repoList">
                <?php if($repository->num_rows === 0): ?>
                    <p style="text-align: center; color: #94a3b8;">Repository is empty. Save items to bank when creating them.</p>
                <?php else: ?>
                    <?php while($r = $repository->fetch_assoc()): ?>
                        <div class="repo-item">
                            <div>
                                <div style="font-weight: 700;"><?php echo htmlspecialchars($r['question_text']); ?></div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;"><?php echo htmlspecialchars($r['subject']); ?> • <?php echo $r['marks']; ?> Marks</div>
                            </div>
                            <a href="?exam_id=<?php echo $exam_id; ?>&import_id=<?php echo $r['id']; ?>" class="btn-block" style="width: auto; padding: 8px 16px; font-size: 0.8rem; background: #f1f5f9; color: #1e293b;"><i class="fas fa-clone"></i> Import</a>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleOptions(type) {
            document.getElementById('optionsGroup').style.display = type === 'mcq' ? 'block' : 'none';
        }
        function openRepo() { document.getElementById('repoModal').style.display = 'flex'; }
        function closeRepo() { document.getElementById('repoModal').style.display = 'none'; }
        window.onclick = e => { if (e.target.id === 'repoModal') closeRepo(); }
    </script>
</body>
</html>