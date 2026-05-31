<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_input = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password_input !== $confirm_password) {
        header("Location: register.html?error=Passwords do not match");
        exit();
    }

    $password = password_hash($password_input, PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $status = 'approved'; 
    
    // Capture additional fields
    $level = $_POST['level'] ?? null;
    $grade_year = $_POST['grade_year'] ?? null;
    $department = $_POST['department'] ?? null;
    $semester = $_POST['semester'] ?? null;
    $section = $_POST['section'] ?? null;
    $stream = $_POST['stream'] ?? null;

    $sql = "INSERT INTO users (name, email, password, role, status, education_level, grade_year, department, semester, section, stream) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssss", $name, $email, $password, $role, $status, $level, $grade_year, $department, $semester, $section, $stream);

    if ($stmt->execute()) {
        // Redirect all users to login page (no auto-login)
        header("Location: login.html?msg=Registration successful! Please login to continue.");
        exit();
    } else {
        // Check for duplicate email error (Error code 1062)
        if ($conn->errno === 1062) {
            header("Location: register.html?error=Email already exists. Please login.");
        } else {
            header("Location: register.html?error=Registration failed. Please try again.");
        }
    }
}
?>