<?php
include 'db_connect.php';

$subjects = [
    ['Mathematics', 'elementary'], ['English', 'elementary'], ['Science', 'elementary'], ['History', 'elementary'],
    ['Mathematics', 'highschool'], ['Physics', 'highschool'], ['Chemistry', 'highschool'], ['Biology', 'highschool'], ['History', 'highschool'], ['Geography', 'highschool'], ['English', 'highschool'],
    ['Computer Science', 'university'], ['Data Science', 'university'], ['Mechanical Engineering', 'university'], ['Electrical Engineering', 'university'], ['Business Administration', 'university'], ['Economics', 'university'],
    ['Advanced Artificial Intelligence', 'master'], ['Software Architecture', 'master'], ['Strategic Management', 'master'], ['Quantum Computing', 'master']
];

$stmt = $conn->prepare("INSERT IGNORE INTO subjects (name, level) VALUES (?, ?)");

foreach ($subjects as $s) {
    $stmt->bind_param("ss", $s[0], $s[1]);
    $stmt->execute();
}

echo "Subjects seeded successfully!";
?>
