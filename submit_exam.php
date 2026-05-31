<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $exam_id = intval($_POST['exam_id']);
    $student_id = $_SESSION['user_id'];
    $node_id = SYSTEM_NODE_ID;

    /**
     * Advanced Distributed Concept: Distributed Mutual Exclusion (Locking)
     * We attempt to acquire a "Lock" in the database. If it fails (Unique constraint),
     * it means another node (or the same node) is already processing this submission.
     */
    try {
        $lock_stmt = $conn->prepare("INSERT INTO submission_locks (exam_id, student_id, node_id) VALUES (?, ?, ?)");
        $lock_stmt->bind_param("iis", $exam_id, $student_id, $node_id);
        $lock_stmt->execute();
    } catch (Exception $e) {
        // Mutual Exclusion in action: Reject concurrent or duplicate submission
        log_cluster_event($conn, "Submission Rejected", "Mutual Exclusion: Node $node_id prevented duplicate submission for Exam $exam_id");
        header("Location: student_dashboard.php?error=Submission already in progress or completed.");
        exit();
    }

    $answers = $_POST['answers'] ?? []; // Array of question_id => answer
    
    // Fetch Correct Answers
    $stmt = $conn->prepare("SELECT id, type, correct_answer, marks FROM questions WHERE exam_id = ?");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_score = 0;
    $total_marks = 0;
    
    while ($q = $result->fetch_assoc()) {
        $qid = $q['id'];
        $correct = trim($q['correct_answer']);
        $marks = $q['marks'];
        $total_marks += $marks;
        
        $user_ans = isset($answers[$qid]) ? trim($answers[$qid]) : '';
        
        // Auto-grading for MCQ and True/False
        if ($q['type'] === 'mcq' || $q['type'] === 'true_false') {
            if (strcasecmp($user_ans, $correct) === 0) {
                $total_score += $marks;
            }
        } else {
            // For Short Answer / Essay, we might need manual grading.
            // For now, let's just leave it as 0 or implement keyword matching if desired.
            // Current simple logic: If not empty, give partial marks? No, that's bad.
            // Let's assume manual grading is needed later, or 0 for now.
            // Or if strict, match string.
            if ($correct !== '' && strcasecmp($user_ans, $correct) === 0) {
                $total_score += $marks;
            }
        }
    }
    
    $ans_json = json_encode($answers);
    
    // Save Result
    $stmt = $conn->prepare("INSERT INTO results (exam_id, student_id, score, total_marks, answers) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiids", $exam_id, $student_id, $total_score, $total_marks, $ans_json);
    
    if ($stmt->execute()) {
        $score_display = (int)$total_score;
        $marks_display = (int)$total_marks;
        header("Location: student_dashboard.php?msg=Assessment submitted successfully! Node Result: $score_display/$marks_display points acknowledged.");
    } else {
        echo "Error saving results: " . $conn->error;
    }
}
?>
