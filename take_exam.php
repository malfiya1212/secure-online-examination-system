<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html");
    exit();
}

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$student_id = $_SESSION['user_id'];

// Check if already taken
$check = $conn->query("SELECT id FROM results WHERE exam_id = $exam_id AND student_id = $student_id");
if ($check->num_rows > 0) {
    header("Location: student_dashboard.php?error=Exam already completed.");
    exit();
}

// Fetch Exam Details
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

if (!$exam) {
    header("Location: student_dashboard.php?error=Assessment not found.");
    exit();
}

// Security Check: Only active exams can be taken
if ($exam['status'] !== 'active') {
    header("Location: student_dashboard.php?error=This assessment is currently inactive and cannot be accessed.");
    exit();
}

// Fetch Questions
$order_sql = ($exam['shuffle_questions'] == 1) ? " ORDER BY RAND()" : " ORDER BY id ASC";
$q_sql = "SELECT id, question_text, type, options, marks FROM questions WHERE exam_id = ?" . $order_sql;
$q_stmt = $conn->prepare($q_sql);
$q_stmt->bind_param("i", $exam_id);
$q_stmt->execute();
$questions = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($questions)) {
    header("Location: student_dashboard.php?error=Exam has no questions yet.");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['title']); ?> | Examination</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --emerald: #10b981;
            --slate-800: #1e293b;
            --slate-700: #334155;
            --slate-100: #f1f5f9;
            --text-main: #0f172a;
        }

        body { background: #f8fafc; color: var(--text-main); height: 100vh; display: flex; flex-direction: column; overflow: hidden; }

        /* Exam Header */
        .exam-header { background: white; border-bottom: 2px solid var(--slate-100); padding: 16px 40px; display: flex; justify-content: space-between; align-items: center; z-index: 100; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .exam-title-box { display: flex; align-items: center; gap: 16px; }
        .exam-badge { background: #eff6ff; color: var(--primary); padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; }
        
        .timer-container { display: flex; align-items: center; gap: 12px; background: var(--slate-800); color: white; padding: 10px 24px; border-radius: 12px; font-weight: 700; font-size: 1.25rem; font-family: 'Courier New', Courier, monospace; }
        .timer-warning { background: #ef4444; animation: pulse 1s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }

        /* Layout */
        .exam-body { flex: 1; display: grid; grid-template-columns: 1fr 320px; overflow: hidden; }
        
        /* Sidebar */
        .exam-sidebar { background: white; border-left: 2px solid var(--slate-100); display: flex; flex-direction: column; }
        .sidebar-scroll { flex: 1; overflow-y: auto; padding: 24px; }
        .nav-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .nav-dot { aspect-ratio: 1; border: 1px solid var(--border); background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; position: relative; }
        .nav-dot:hover { border-color: var(--primary); color: var(--primary); }
        .nav-dot.active { border-color: var(--primary); background: #eff6ff; color: var(--primary); box-shadow: 0 0 0 2px var(--primary); }
        .nav-dot.answered { background: var(--emerald); color: white; border-color: var(--emerald); }
        .nav-dot.flagged::after { content: '\f024'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; top: -5px; right: -5px; color: #f59e0b; font-size: 0.7rem; background: white; border-radius: 50%; padding: 2px; }

        /* Content */
        .exam-content { padding: 40px; overflow-y: auto; display: flex; justify-content: center; scroll-behavior: smooth; }
        .q-container { max-width: 800px; width: 100%; display: none; }
        .q-container.active { display: block; animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .question-meta { margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .q-text { font-size: 1.5rem; font-weight: 700; line-height: 1.4; color: #0f172a; margin-bottom: 32px; }

        .option-group { display: flex; flex-direction: column; gap: 16px; }
        .option-item { position: relative; cursor: pointer; }
        .option-item input { position: absolute; opacity: 0; cursor: pointer; }
        .option-label { display: block; padding: 20px 24px 20px 60px; background: white; border: 2px solid var(--slate-100); border-radius: 16px; transition: all 0.2s; position: relative; font-weight: 500; }
        .option-item:hover .option-label { border-color: var(--primary); background: #f8fafc; }
        .option-item input:checked + .option-label { border-color: var(--primary); background: #eff6ff; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1); }
        
        .option-marker { position: absolute; left: 24px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; border: 2px solid var(--border); border-radius: 50%; transition: all 0.2s; background: white; }
        .option-item input:checked + .option-label .option-marker { border-color: var(--primary); background: var(--primary); box-shadow: inset 0 0 0 4px white; }

        /* Footer */
        .exam-footer { background: white; border-top: 2px solid var(--slate-100); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        .footer-btns { display: flex; gap: 16px; }
        .btn-exam { padding: 12px 28px; border-radius: 12px; font-weight: 700; cursor: pointer; border: none; transition: all 0.2s; display: flex; align-items: center; gap: 8px; font-size: 1rem; }
        .btn-prev { background: var(--slate-100); color: var(--slate-700); }
        .btn-prev:hover { background: #e2e8f0; }
        .btn-next { background: var(--primary); color: white; }
        .btn-next:hover { background: var(--primary-dark); }
        .btn-flag { background: #fffbeb; color: #92400e; border: 1px solid #fef3c7; }
        .btn-flag.active { background: #fef3c7; }
        .btn-submit { background: var(--emerald); color: white; padding: 12px 36px; border-radius: 12px; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3); }

        textarea.essay-field { width: 100%; border-radius: 16px; border: 2px solid var(--slate-100); padding: 24px; font-size: 1.1rem; min-height: 250px; transition: all 0.2s; resize: vertical; outline: none; }
        textarea.essay-field:focus { border-color: var(--primary); background: white; }
    </style>
</head>
<body onbeforeunload="return 'Are you sure you want to leave? Your progress will only be saved when you submit.'">
    
    <header class="exam-header">
        <div class="exam-title-box">
            <span class="exam-badge">Assessment in Progress</span>
            <h1 style="font-size: 1.25rem; font-weight: 800;"><?php echo htmlspecialchars($exam['title']); ?></h1>
        </div>
        <div class="timer-container" id="timerContainer">
            <i class="fas fa-clock"></i> <span id="timerDisplay">--:--:--</span>
        </div>
    </header>

    <div class="exam-body">
        <main class="exam-content">
            <form id="examForm" action="submit_exam.php" method="post" style="width: 100%; max-width: 800px;">
                <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                
                <?php foreach($questions as $idx => $q): ?>
                <div class="q-container" id="q-container-<?php echo $idx; ?>">
                    <div class="question-meta">
                        <span style="font-weight: 800; color: var(--primary);">Question <?php echo $idx + 1; ?> of <?php echo count($questions); ?></span>
                        <span class="exam-badge" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">Worth <?php echo $q['marks']; ?> Marks</span>
                    </div>

                    <div class="q-text"><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></div>

                    <?php if($q['type'] === 'mcq' || $q['type'] === 'true_false'): ?>
                        <div class="option-group">
                            <?php 
                                $opts = json_decode($q['options']);
                                if ($q['type'] === 'true_false') $opts = ['True', 'False']; 
                                if($opts): foreach($opts as $optIdx => $opt): 
                            ?>
                            <label class="option-item">
                                <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo htmlspecialchars($opt); ?>" onchange="markAnswered(<?php echo $idx; ?>)">
                                <span class="option-label">
                                    <span class="option-marker"></span>
                                    <?php echo htmlspecialchars($opt); ?>
                                </span>
                            </label>
                            <?php endforeach; endif; ?>
                        </div>
                    <?php else: ?>
                        <textarea class="essay-field" name="answers[<?php echo $q['id']; ?>]" placeholder="Provide your detailed response here..." oninput="markAnswered(<?php echo $idx; ?>)"></textarea>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </form>
        </main>

        <aside class="exam-sidebar">
            <div class="sidebar-scroll">
                <h3 style="font-size: 0.9rem; font-weight: 800; text-transform: uppercase; color: var(--slate-700); margin-bottom: 20px; letter-spacing: 0.05em;">Question Overview</h3>
                <div class="nav-grid">
                    <?php for($i=0; $i < count($questions); $i++): ?>
                        <div class="nav-dot" id="nav-dot-<?php echo $i; ?>" onclick="goToQuestion(<?php echo $i; ?>)"><?php echo $i + 1; ?></div>
                    <?php endfor; ?>
                </div>

                <div style="margin-top: 40px; padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <h4 style="font-size: 0.8rem; font-weight: 800; margin-bottom: 12px; color: var(--slate-700);">LEGEND</h4>
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.8rem;">
                        <div style="display: flex; align-items: center; gap: 8px;"><div style="width:12px; height:12px; background:white; border:1px solid #cbd5e1; border-radius: 3px;"></div> Unseen/Unanswered</div>
                        <div style="display: flex; align-items: center; gap: 8px;"><div style="width:12px; height:12px; background:var(--emerald); border-radius: 3px;"></div> Answered</div>
                        <div style="display: flex; align-items: center; gap: 8px;"><div style="width:12px; height:12px; border:1px solid #f59e0b; position:relative;"><i class="fas fa-flag" style="color:#f59e0b; font-size: 8px; position:absolute; top:2px; left:2px;"></i></div> Flagged for Review</div>
                    </div>
                </div>
            </div>

            <div style="padding: 24px; border-top: 2px solid var(--slate-100);">
                <button class="btn-exam btn-submit" style="width: 100%;" onclick="finalizeSubmission()">Submit Final Exam</button>
            </div>
        </aside>
    </div>

    <footer class="exam-footer">
        <div class="footer-btns">
            <button class="btn-exam btn-prev" id="btnPrev" onclick="prevQ()"><i class="fas fa-chevron-left"></i> Previous</button>
            <button class="btn-exam btn-next" id="btnNext" onclick="nextQ()">Next Question <i class="fas fa-chevron-right"></i></button>
        </div>
        <button class="btn-exam btn-flag" id="btnFlag" onclick="toggleFlag()"><i class="fas fa-flag"></i> Flag for Review</button>
    </footer>

    <script>
        let currentIdx = 0;
        const totalQuestions = <?php echo count($questions); ?>;
        const durationSeconds = <?php echo $exam['duration'] * 60; ?>;
        let timeLeft = durationSeconds;
        const flaggingStatus = new Array(totalQuestions).fill(false);

        // Timer Execution
        function updateTimer() {
            if (timeLeft < 0) {
                window.onbeforeunload = null; // Remove warning
                alert("TIME EXPIRED: Your assessment is being submitted automatically.");
                document.getElementById('examForm').submit();
                return;
            }

            const h = Math.floor(timeLeft / 3600).toString().padStart(2, '0');
            const m = Math.floor((timeLeft % 3600) / 60).toString().padStart(2, '0');
            const s = (timeLeft % 60).toString().padStart(2, '0');
            
            const display = document.getElementById('timerDisplay');
            display.textContent = `${h}:${m}:${s}`;

            if (timeLeft <= 300) { // Red at 5 minutes
                document.getElementById('timerContainer').classList.add('timer-warning');
            }

            timeLeft--;
            setTimeout(updateTimer, 1000);
        }

        function goToQuestion(idx) {
            // Hide current
            document.querySelectorAll('.q-container').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-dot').forEach(el => el.classList.remove('active'));

            // Show new
            document.getElementById(`q-container-${idx}`).classList.add('active');
            document.getElementById(`nav-dot-${idx}`).classList.add('active');

            currentIdx = idx;
            updateFooterNavs();
        }

        function updateFooterNavs() {
            document.getElementById('btnPrev').style.visibility = (currentIdx === 0) ? 'hidden' : 'visible';
            document.getElementById('btnNext').style.visibility = (currentIdx === totalQuestions - 1) ? 'hidden' : 'visible';
            
            // Flag state
            const flagBtn = document.getElementById('btnFlag');
            if (flaggingStatus[currentIdx]) {
                flagBtn.classList.add('active');
            } else {
                flagBtn.classList.remove('active');
            }
        }

        function nextQ() { if(currentIdx < totalQuestions-1) goToQuestion(currentIdx + 1); }
        function prevQ() { if(currentIdx > 0) goToQuestion(currentIdx - 1); }

        function toggleFlag() {
            flaggingStatus[currentIdx] = !flaggingStatus[currentIdx];
            const dot = document.getElementById(`nav-dot-${currentIdx}`);
            if(flaggingStatus[currentIdx]) {
                dot.classList.add('flagged');
            } else {
                dot.classList.remove('flagged');
            }
            updateFooterNavs();
        }

        function markAnswered(idx) {
            document.getElementById(`nav-dot-${idx}`).classList.add('answered');
        }

        function finalizeSubmission() {
            const unanswered = document.querySelectorAll('.nav-dot:not(.answered)').length;
            let msg = "Are you sure you want to finish your assessment?";
            if(unanswered > 0) {
                msg += `\n\nWARNING: You have ${unanswered} unanswered questions.`;
            }
            
            if (confirm(msg)) {
                window.onbeforeunload = null;
                document.getElementById('examForm').submit();
            }
        }

        // Initialize
        goToQuestion(0);
        updateTimer();
    </script>
</body>
</html>
