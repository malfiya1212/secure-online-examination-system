<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$teacher_name = $_SESSION['user_name'] ?? 'Faculty';

// Handle Add/Edit Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $text = trim($_POST['text']);
    $type = $_POST['type'];
    $subject = trim($_POST['subject']); 
    $level = $_POST['level'];
    $difficulty = $_POST['difficulty'] ?? 'medium';
    $topic = trim($_POST['topic'] ?? '');
    $marks = intval($_POST['marks'] ?? 1);
    
    if ($action === 'add') {
        $options = '[]';      
        $correct = '';        
        $grade_year = $_POST['grade_year'] ?? '';

        $stmt = $conn->prepare("
            INSERT INTO questions 
            (created_by, exam_id, question_text, type, options, correct_answer, marks, education_level, grade_year, subject, difficulty, topic)
            VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssisssss", $teacher_id, $text, $type, $options, $correct, $marks, $level, $grade_year, $subject, $difficulty, $topic);
        $stmt->execute();

    } elseif ($action === 'bulk_add') {
        $bulk_text = trim($_POST['bulk_text']);
        $lines = explode("\n", $bulk_text);
        foreach ($lines as $line) {
            $parts = explode("|", $line);
            if (count($parts) >= 2) {
                $q_text = trim($parts[0]);
                $q_subj = trim($parts[1]);
                $q_grade = isset($parts[2]) ? trim($parts[2]) : '';
                $q_marks = isset($parts[3]) ? intval($parts[3]) : 1;
                $q_diff = isset($parts[4]) ? trim(strtolower($parts[4])) : 'medium';
                
                $stmt = $conn->prepare("INSERT INTO questions (created_by, question_text, type, subject, education_level, grade_year, marks, difficulty) VALUES (?, ?, 'short_answer', ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssis", $teacher_id, $q_text, $q_subj, $level, $q_grade, $q_marks, $q_diff);
                $stmt->execute();
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['question_id']);
        $grade_year = $_POST['grade_year'] ?? '';
        $stmt = $conn->prepare("
            UPDATE questions 
            SET question_text=?, type=?, education_level=?, grade_year=?, subject=?, marks=?, difficulty=?, topic=? 
            WHERE id=? AND created_by=?
        ");
        $stmt->bind_param("sssssiisii", $text, $type, $level, $grade_year, $subject, $marks, $difficulty, $topic, $id, $teacher_id);
        $stmt->execute();
    }

    header("Location: teacher_question_bank.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM questions WHERE id=? AND created_by=?");
    $stmt->bind_param("ii", $id, $teacher_id);
    $stmt->execute();
    header("Location: teacher_question_bank.php");
    exit();
}

// Fetch Questions
$stmt = $conn->prepare("SELECT * FROM questions WHERE created_by=? ORDER BY id DESC");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$questions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Repository | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: #1e293b; color: white; position: fixed; height: 100vh; padding: 32px 0; }
        .admin-main { flex: 1; margin-left: 280px; padding: 40px; background: #f8fafc; }
        .sidebar-link { display: flex; align-items: center; gap: 16px; padding: 14px 32px; color: #94a3b8; text-decoration: none; transition: 0.2s; font-weight: 500; }
        .sidebar-link:hover, .sidebar-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        
        .bank-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .question-card { background: white; padding: 24px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px; transition: 0.2s; }
        .question-card:hover { border-color: var(--primary); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .tag { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; margin-right: 8px; }
        .tag-blue { background: #eff6ff; color: #1e40af; }
        .tag-pink { background: #fdf2f7; color: #9d174d; }
        .difficulty { border-radius: 4px; padding: 2px 8px; font-size: 0.65rem; font-weight: 800; }
        .easy { background: #dcfce7; color: #15803d; }
        .medium { background: #fef9c3; color: #854d0e; }
        .hard { background: #fee2e2; color: #b91c1c; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .modal-content { background: white; width: 90%; max-width: 650px; border-radius: 20px; padding: 32px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
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
                <a href="teacher_question_bank.php" class="sidebar-link active"><i class="fas fa-database"></i> Question Bank</a>
                <a href="manage_exams.php" class="sidebar-link"><i class="fas fa-file-pen"></i> Assessments</a>
                <a href="teacher_results.php" class="sidebar-link"><i class="fas fa-square-poll-vertical"></i> Insights</a>
                <a href="my_students.php" class="sidebar-link"><i class="fas fa-user-group"></i> My Students</a>
                <a href="teacher_profile.php" class="sidebar-link"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="logout.php" class="sidebar-link" style="margin-top: 40px; color: #f87171;"><i class="fas fa-sign-out-alt"></i> De-authenticate</a>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="bank-header">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 800; color: #0f172a;">Question Repository</h1>
                    <p style="color: #64748b;">Centralized management of global assessment items</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button class="btn-block" style="width: auto; padding: 12px 24px; background: #6366f1;" onclick="openBulkModal()"><i class="fas fa-file-import"></i> Bulk Import</button>
                    <button class="btn-block" style="width: auto; padding: 12px 24px;" onclick="openModal('add')"><i class="fas fa-plus"></i> New Item</button>
                </div>
            </div>

            <div id="questionsGrid">
                <?php while($q = $questions->fetch_assoc()): ?>
                    <div class="question-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-bottom: 12px;"><?php echo htmlspecialchars($q['question_text']); ?></div>
                            <div style="display: flex; gap: 8px;">
                                <button class="btn-block" style="width:36px; height:36px; padding:0; background:#f1f5f9; color:#64748b;" onclick='openModal("edit", <?php echo json_encode($q); ?>)'><i class="fas fa-edit"></i></button>
                                <a href="?delete=<?php echo $q['id']; ?>" class="btn-block" style="width:36px; height:36px; padding:0; background:#fef2f2; color:#ef4444;" onclick="return confirm('Archive item from repository?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span class="tag tag-blue"><?php echo ucfirst($q['education_level']); ?><?php 
                                $is_uni = ($q['education_level'] === 'university' || $q['education_level'] === 'master');
                                echo $q['grade_year'] ? ($is_uni ? " Y".$q['grade_year'] : " G".$q['grade_year']) : ""; 
                            ?></span>
                            <span class="tag tag-pink"><?php echo htmlspecialchars($q['subject']); ?></span>
                            <span class="difficulty <?php echo $q['difficulty'] ?? 'medium'; ?>"><?php echo strtoupper((string)($q['difficulty'] ?? 'MEDIUM')); ?></span>
                            <span style="margin-left: auto; font-size: 0.8rem; font-weight: 700; color: #94a3b8;"><?php echo $q['marks']; ?> MARKS</span>
                        </div>
                    </div>
<?php endwhile; ?>
            </div>
        </main>
    </div>

    <!-- Multi-purpose Modal -->
    <div id="qModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="margin-bottom: 24px;">Add Assessment Item</h2>
            <form method="post">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="question_id" id="qId">
                <div class="form-group"><label>Prompt</label><textarea name="text" id="qText" required style="min-height: 80px;"></textarea></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group"><label>Type</label><select name="type" id="qType"><option value="mcq">MCQ</option><option value="short_answer">Short Answer</option></select></div>
                    <div class="form-group"><label>Difficulty</label><select name="difficulty" id="qDifficulty"><option value="easy">Easy</option><option value="medium">Medium</option><option value="hard">Hard</option></select></div>
                    <div class="form-group"><label>Education Level</label><select name="level" id="qLevel" onchange="updateGrades(this.value)"><option value="elementary">Elementary</option><option value="highschool">High School</option><option value="university">University</option></select></div>
                    <div class="form-group"><label>Grade/Year</label><select name="grade_year" id="qGradeYear"></select></div>
                    <div class="form-group"><label>Subject</label><input type="text" name="subject" id="qSubject" required></div>
                    <div class="form-group"><label>Topic</label><input type="text" name="topic" id="qTopic"></div>
                    <div class="form-group"><label>Marks</label><input type="number" name="marks" id="qMarks" value="1"></div>
                </div>
                <div style="margin-top: 32px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="btn-block" style="width:auto; padding:12px 24px; background:#f1f5f9; color:#64748b;">Cancel</button>
                    <button type="submit" class="btn-block" style="width:auto; padding:12px 32px;">Commit Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="bulkModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 12px;">Institutional Bulk Import</h2>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 24px;">Format: <code>Question | Subject | Grade | Marks | Difficulty</code> (One per line)</p>
            <form method="post">
                <input type="hidden" name="action" value="bulk_add">
                <div class="form-group">
                    <label>Paste Questions</label>
                    <textarea name="bulk_text" placeholder="What is PHP? | Web Dev | 3 | 5 | Easy&#10;Explain MVC | Software | 4 | 10 | Hard" style="min-height: 200px; font-family: monospace;"></textarea>
                </div>
                <div class="form-group"><label>Common Education Level</label><select name="level"><option value="elementary">Elementary</option><option value="highschool">High School</option><option value="university">University</option></select></div>
                <div style="margin-top: 32px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeBulkModal()" class="btn-block" style="width:auto; padding:12px 24px; background:#f1f5f9; color:#64748b;">Cancel</button>
                    <button type="submit" class="btn-block" style="width:auto; padding:12px 32px;">Import Sequence</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openBulkModal() { document.getElementById('bulkModal').style.display = 'flex'; }
        function closeBulkModal() { document.getElementById('bulkModal').style.display = 'none'; }
        function openModal(mode, data = null) {
            document.getElementById('qModal').style.display = 'flex';
            if (mode === 'add') {
                document.getElementById('modalTitle').textContent = 'Add Assessment Item';
                document.getElementById('formAction').value = 'add';
                document.getElementById('qId').value = '';
                document.getElementById('qLevel').value = 'elementary';
                updateGrades('elementary');
            } else {
                document.getElementById('modalTitle').textContent = 'Modify Item';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('qId').value = data.id;
                document.getElementById('qText').value = data.question_text;
                document.getElementById('qLevel').value = data.education_level;
                updateGrades(data.education_level);
                document.getElementById('qGradeYear').value = data.grade_year;
                document.getElementById('qSubject').value = data.subject;
                document.getElementById('qMarks').value = data.marks;
                document.getElementById('qDifficulty').value = data.difficulty;
                document.getElementById('qType').value = data.type;
            }
        }
        function closeModal() { document.getElementById('qModal').style.display = 'none'; }
        function updateGrades(level) {
            const select = document.getElementById('qGradeYear');
            select.innerHTML = '';
            if (level === 'elementary') for(let i=1;i<=8;i++) select.innerHTML += `<option value="${i}">Grade ${i}</option>`;
            else if (level === 'highschool') for(let i=9;i<=12;i++) select.innerHTML += `<option value="${i}">Grade ${i}</option>`;
            else for(let i=1;i<=4;i++) select.innerHTML += `<option value="${i}">Year ${i}</option>`;
        }
        window.onclick = e => { 
            if (e.target == document.getElementById('qModal')) closeModal(); 
            if (e.target == document.getElementById('bulkModal')) closeBulkModal(); 
        }
    </script>
</body>
</html>
