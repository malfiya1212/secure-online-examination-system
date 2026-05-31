<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html");
    exit();
}

$result_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$student_id = $_SESSION['user_id'];

// Fetch Result Summary & Verify Ownership
$sql = "SELECT r.*, e.title, e.subject 
        FROM results r 
        JOIN exams e ON r.exam_id = e.id 
        WHERE r.id = ? AND r.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $result_id, $student_id);
$stmt->execute();
$brief = $stmt->get_result()->fetch_assoc();

if (!$brief) {
    die("Access denied or result not found.");
}

// Fetch Detailed Answers
$query = "SELECT sa.*, q.question_text, q.correct_answer as model_answer, q.marks as max_q_marks 
          FROM student_answers sa 
          JOIN questions q ON sa.question_id = q.id 
          WHERE sa.student_id = ? AND sa.exam_id = ?";
$stmt_ans = $conn->prepare($query);
$stmt_ans->bind_param("ii", $student_id, $brief['exam_id']);
$stmt_ans->execute();
$answers = $stmt_ans->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deep Dive Analysis | ExamSystem Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .analysis-layout { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-weight: 600; margin-bottom: 24px; }
        .back-btn:hover { color: var(--primary); }
        
        .summary-header { background: #0f172a; color: white; padding: 40px; border-radius: 20px; margin-bottom: 32px; }
        
        .answer-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 32px; margin-bottom: 24px; position: relative; }
        .answer-card.correct { border-left: 6px solid #10b981; }
        .answer-card.incorrect { border-left: 6px solid #ef4444; }
        
        .q-text { font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 16px; }
        .choice-box { padding: 16px; border-radius: 8px; margin-top: 12px; font-size: 0.95rem; }
        .choice-user { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .choice-model { background: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
        
        .status-pill { position: absolute; top: 32px; right: 32px; padding: 4px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .pill-correct { background: #dcfce7; color: #166534; }
        .pill-incorrect { background: #fef2f2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="analysis-layout">
        <a href="student_results.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Results</a>
        
        <div class="summary-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: 1.75rem; font-weight: 800;"><?php echo htmlspecialchars($brief['title']); ?></h1>
                    <p style="color: #94a3b8; margin-top: 4px;"><?php echo htmlspecialchars($brief['subject']); ?> • Detailed Performance Review</p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 2.5rem; font-weight: 800;"><?php echo floatval($brief['score']); ?> <span style="font-size: 1.25rem; color: #64748b;">/ <?php echo floatval($brief['total_marks']); ?></span></div>
                    <p style="color: #94a3b8; font-weight: 600;">Overall Achievement</p>
                </div>
            </div>
        </div>

        <h2 style="margin-bottom: 24px; font-weight: 800; color: #1e293b;"><i class="fas fa-list-check" style="margin-right: 8px; color: var(--primary);"></i> Question Breakdown</h2>

        <?php foreach ($answers as $index => $ans): ?>
            <div class="answer-card <?php echo $ans['is_correct'] ? 'correct' : 'incorrect'; ?>">
                <span class="status-pill <?php echo $ans['is_correct'] ? 'pill-correct' : 'pill-incorrect'; ?>">
                    <?php echo $ans['is_correct'] ? 'Correct' : 'Incorrect'; ?>
                </span>
                
                <div style="color: #94a3b8; font-weight: 700; font-size: 0.85rem; margin-bottom: 8px;">QUESTION #<?php echo $index + 1; ?></div>
                <div class="q-text"><?php echo htmlspecialchars($ans['question_text']); ?></div>
                
                <div class="choice-box choice-user">
                    <div style="font-size: 0.75rem; opacity: 0.7; margin-bottom: 4px; font-weight: 800;">YOUR RESPONSE:</div>
                    <strong><?php echo htmlspecialchars($ans['answer'] ?: '(No answer provided)'); ?></strong>
                </div>

                <?php if (!$ans['is_correct']): ?>
                    <div class="choice-box choice-model">
                        <div style="font-size: 0.75rem; opacity: 0.7; margin-bottom: 4px; font-weight: 800;">EVALUATED ANSWER:</div>
                        <strong><?php echo htmlspecialchars($ans['model_answer']); ?></strong>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 20px; display: flex; justify-content: flex-end; font-size: 0.85rem; font-weight: 700; color: #64748b;">
                    Marks: <span style="color: <?php echo $ans['is_correct'] ? '#10b981' : '#ef4444'; ?>; margin-left: 4px;">
                        <?php echo floatval($ans['marks_earned']); ?> / <?php echo floatval($ans['max_q_marks']); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($answers)): ?>
            <div style="text-align: center; padding: 80px; background: white; border-radius: 20px; border: 1px dashed #e2e8f0;">
                <i class="fas fa-database" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 20px;"></i>
                <p style="color: #64748b;">Detailed response data for this assessment is unavailable (Old format result).</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
