<?php
// Mock session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1; 
$_SESSION['user_role'] = 'teacher';

// Mock POST data
$_POST = [
    'title' => 'Validation Test Exam 2',
    'type' => 'quiz',
    'level' => 'university',
    'duration' => 60,
    'grade_year' => '4',
    'section' => 'A',
    'stream' => 'CS',
    'semester' => '1',
    'department' => 'Science',
    'subject' => 'Unit Testing',
    'instructions' => 'This is a test.',
    'shuffle_questions' => '1'
];

$_SERVER["REQUEST_METHOD"] = "POST";

// Capture output
ob_start();
require_once 'create_exam_process.php';
$output = ob_get_clean();

// db_connect.php is already included by create_exam_process.php
if (isset($conn)) {
    $res = $conn->query("SELECT * FROM exams WHERE title = 'Validation Test Exam 2' ORDER BY id DESC LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        echo "SUCCESS: Exam created with ID " . $row['id'] . "\n";
        echo "Type: " . $row['type'] . "\n";
        echo "Level: " . $row['level'] . "\n";
        echo "Duration: " . $row['duration'] . "\n";
    } else {
        echo "FAILURE: Exam not found in database.\n";
        echo "Output: " . $output . "\n";
    }
} else {
    echo "FAILURE: Database connection not found.\n";
}
?>
