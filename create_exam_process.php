<?php
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $type = trim($_POST['type']);
    $level = trim($_POST['level']);
    $duration = (int)$_POST['duration'];
    
    // Capture Targeting Fields
    $grade_year = !empty($_POST['grade_year']) ? trim($_POST['grade_year']) : null;
    $section = !empty($_POST['section']) ? trim($_POST['section']) : null;
    $stream = !empty($_POST['stream']) ? trim($_POST['stream']) : null;
    $semester = !empty($_POST['semester']) ? trim($_POST['semester']) : null;
    $department = !empty($_POST['department']) ? trim($_POST['department']) : null;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : 'General';
    $instructions = trim($_POST['instructions']);
    $teacher_id = $_SESSION['user_id'];
    $shuffle = isset($_POST['shuffle_questions']) ? 1 : 0;
    $total_marks = 0;

    $sql = "INSERT INTO exams (title, type, level, grade_year, section, stream, semester, department, subject, duration, total_marks, instructions, created_by, status, shuffle_questions) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssiisii", $title, $type, $level, $grade_year, $section, $stream, $semester, $department, $subject, $duration, $total_marks, $instructions, $teacher_id, $shuffle);
    
    if ($stmt->execute()) {
        $exam_id = $conn->insert_id;
        // Redirect to Add Questions page for this exam
        header("Location: add_question.php?exam_id=" . $exam_id);
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    header("Location: create_exam.php");
}
?>
