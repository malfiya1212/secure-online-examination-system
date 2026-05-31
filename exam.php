<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get exam ID from URL
if (!isset($_GET['exam_id'])) {
    die("No exam selected.");
}
$exam_id = (int)$_GET['exam_id'];

// Fetch exam details
$sql_exam = "SELECT title, duration, total_marks FROM exams WHERE id = ? AND status = 'active'";
$stmt_exam = $conn->prepare($sql_exam);
$stmt_exam->bind_param("i", $exam_id);
$stmt_exam->execute();
$result_exam = $stmt_exam->get_result();

if ($result_exam->num_rows == 0) {
    die("Exam not found or inactive.");
}
$exam = $result_exam->fetch_assoc();
$exam_title = $exam['title'];
$duration_minutes = $exam['duration'];
$total_marks = $exam['total_marks'];

// Fetch all questions for this exam
$sql_questions = "SELECT id, question_text, type, options, correct_answer, marks 
                  FROM questions WHERE exam_id = ? ORDER BY id";
$stmt_q = $conn->prepare($sql_questions);
$stmt_q->bind_param("i", $exam_id);
$stmt_q->execute();
$result_q = $stmt_q->get_result();
$questions = $result_q->fetch_all(MYSQLI_ASSOC);

$total_questions = count($questions);
if ($total_questions == 0) {
    die("No questions in this exam.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taking Exam | Online Exam System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: #f8f9fc; min-height: 100vh; }
        .exam-container { max-width: 1000px; margin: 40px auto; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .exam-header { background: #1e3a8a; color: white; padding: 25px; display: flex; justify-content: space-between; align-items: center; }
        .exam-title { font-size: 1.8rem; font-weight: bold; }
        .timer { font-size: 1.6rem; font-weight: bold; background: rgba(0,0,0,0.2); padding: 10px 20px; border-radius: 50px; }
        .question-section { padding: 50px; }
        .question-number { font-size: 1.3rem; color: #1e3a8a; margin-bottom: 20px; font-weight: bold; }
        .question-text { font-size: 1.4rem; margin-bottom: 30px; line-height: 1.6; }
        .options { display: flex; flex-direction: column; gap: 15px; }
        .option { display: flex; align-items: center; background: #f1f5f9; padding: 20px; border-radius: 12px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
        .option:hover { background: #e2e8f0; }
        .option input { margin-right: 15px; transform: scale(1.3); }
        .option.selected { border-color: #3b82f6; background: #dbeafe; }
        .navigation { padding: 30px; background: #f8f9fc; display: flex; justify-content: space-between; align-items: center; }
        .nav-btn { background: #1e3a8a; color: white; padding: 14px 30px; border: none; border-radius: 50px; font-size: 1.1rem; cursor: pointer; }
        .nav-btn:hover { background: #2563eb; }
        .submit-btn { background: #16a34a; }
        .submit-btn:hover { background: #15803d; }
        .question-counter { font-size: 1.1rem; color: #666; }
        @media (max-width: 768px) { .exam-header { flex-direction: column; gap: 15px; text-align: center; } .question-section { padding: 30px; } }
    </style>
</head>
<body>
    <div class="exam-container">
        <div class="exam-header">
            <div class="exam-title"><?php echo htmlspecialchars($exam_title); ?></div>
            <div class="timer" id="timer">00:00:00</div>
        </div>

        <form id="examForm" action="submit_exam.php" method="post">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">

            <div class="question-section" id="questionSection">
                <!-- Questions loaded by JS -->
            </div>

            <div class="navigation">
                <div class="question-counter">
                    <span id="answeredCount">0</span> answered • <?php echo $total_questions; ?> total
                </div>
                <div>
                    <button type="button" class="nav-btn" onclick="previousQuestion()">Previous</button>
                    <button type="button" class="nav-btn" onclick="nextQuestion()">Next</button>
                    <button type="submit" class="nav-btn submit-btn">Submit Exam</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const questions = <?php echo json_encode($questions); ?>;
        const durationSeconds = <?php echo $duration_minutes * 60; ?>;
        const totalQuestions = questions.length;

        let currentQuestion = 0;
        let timeLeft = durationSeconds;
        let answers = {}; // Store student answers: {question_id: answer}
        let timerInterval;

        function startTimer() {
            timerInterval = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    alert("Time's up! Submitting exam automatically...");
                    document.getElementById("examForm").submit();
                    return;
                }
                timeLeft--;
                const h = String(Math.floor(timeLeft / 3600)).padStart(2, '0');
                const m = String(Math.floor((timeLeft % 3600) / 60)).padStart(2, '0');
                const s = String(timeLeft % 60).padStart(2, '0');
                document.getElementById('timer').textContent = `${h}:${m}:${s}`;
            }, 1000);
        }

        function loadQuestion() {
            const q = questions[currentQuestion];
            document.querySelector('.question-number').innerHTML = `Question <span id="currentQ">${currentQuestion + 1}</span> of ${totalQuestions}`;
            document.querySelector('.question-text').textContent = q.question_text;

            const optionsDiv = document.querySelector('.options');
            optionsDiv.innerHTML = '';

            if (q.type === 'mcq' || q.type === 'true_false') {
                const opts = JSON.parse(q.options);
                Object.keys(opts).forEach(key => {
                    const label = document.createElement('label');
                    label.className = 'option';
                    if (answers[q.id] === key) label.classList.add('selected');

                    label.innerHTML = `
                        <input type="radio" name="q${q.id}" value="${key}" ${answers[q.id] === key ? 'checked' : ''}>
                        <span>${key}) ${opts[key]}</span>
                    `;
                    label.onclick = () => selectOption(q.id, key);
                    optionsDiv.appendChild(label);
                });
            } else if (q.type === 'short_answer' || q.type === 'essay') {
                const textarea = document.createElement('textarea');
                textarea.name = `q${q.id}`;
                textarea.placeholder = "Type your answer here...";
                textarea.style.width = "100%";
                textarea.style.minHeight = "150px";
                textarea.style.padding = "15px";
                textarea.style.borderRadius = "12px";
                textarea.style.border = "2px solid #e2e8f0";
                textarea.value = answers[q.id] || '';
                textarea.oninput = () => answers[q.id] = textarea.value;
                optionsDiv.appendChild(textarea);
            }

            updateAnsweredCount();
        }

        function selectOption(qid, value) {
            answers[qid] = value;
            updateAnsweredCount();
            loadQuestion(); // refresh to show selection
        }

        function updateAnsweredCount() {
            const answered = Object.keys(answers).length;
            document.getElementById('answeredCount').textContent = answered;
        }

        function nextQuestion() {
            if (currentQuestion < totalQuestions - 1) {
                currentQuestion++;
                loadQuestion();
            }
        }

        function previousQuestion() {
            if (currentQuestion > 0) {
                currentQuestion--;
                loadQuestion();
            }
        }

        // Save answers before leaving
        window.onbeforeunload = () => "Your progress will be lost if you leave!";

        // Start exam
        window.onload = () => {
            loadQuestion();
            startTimer();
        };
    </script>
</body>
</html>