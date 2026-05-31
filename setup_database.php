<?php
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS online_exam_system";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    die("Error creating database: " . $conn->error);
}

$conn->select_db("online_exam_system");

// Users Table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') NOT NULL,
    education_level VARCHAR(50),
    grade_year VARCHAR(50),
    department VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created successfully<br>";
} else {
    echo "Error creating table users: " . $conn->error . "<br>";
}

// Admin User (Default)
$password = password_hash("admin123", PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (name, email, password, role, status) 
        VALUES ('System Admin', 'admin@example.com', '$password', 'admin', 'approved')";
if ($conn->query($sql) === TRUE) {
    echo "Default admin user created (admin@example.com / admin123)<br>";
}

// Exams Table
$sql = "CREATE TABLE IF NOT EXISTS exams (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    level VARCHAR(50) NOT NULL,
    subject VARCHAR(100),
    duration INT NOT NULL, -- in minutes
    total_marks INT NOT NULL,
    instructions TEXT,
    created_by INT(6) UNSIGNED,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'exams' created successfully<br>";
} else {
    echo "Error creating table exams: " . $conn->error . "<br>";
}

// Questions Table
$sql = "CREATE TABLE IF NOT EXISTS questions (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id INT(6) UNSIGNED,
    question_text TEXT NOT NULL,
    type ENUM('mcq', 'true_false', 'short_answer', 'essay') NOT NULL,
    options TEXT, -- JSON encoded options for MCQ
    correct_answer TEXT,
    marks INT NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'questions' created successfully<br>";
} else {
    echo "Error creating table questions: " . $conn->error . "<br>";
}

// Subjects Table
$sql = "CREATE TABLE IF NOT EXISTS subjects (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    level VARCHAR(50), -- Elementary, High School, etc.
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'subjects' created successfully<br>";
} else {
    echo "Error creating table subjects: " . $conn->error . "<br>";
}

// Results Table
$sql = "CREATE TABLE IF NOT EXISTS results (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id INT(6) UNSIGNED,
    student_id INT(6) UNSIGNED,
    score DECIMAL(5,2),
    total_marks INT,
    answers TEXT, -- JSON encoded student answers
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Table 'results' created successfully<br>";
} else {
    echo "Error creating table results: " . $conn->error . "<br>";
}

// Sessions Table (For secure distributed sessions)
$sql = "CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    access INT(10) UNSIGNED NOT NULL,
    data TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if ($conn->query($sql) === TRUE) {
    echo "Table 'sessions' created successfully<br>";
} else {
    echo "Error creating table sessions: " . $conn->error . "<br>";
}

// System Nodes (Distributed Monitoring)
$sql = "CREATE TABLE IF NOT EXISTS system_nodes (
    node_id VARCHAR(50) NOT NULL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Online',
    version VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if ($conn->query($sql) === TRUE) echo "Table 'system_nodes' created<br>";

// System Logs (Distributed Monitoring)
$sql = "CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    node_id VARCHAR(50) NOT NULL,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if ($conn->query($sql) === TRUE) echo "Table 'system_logs' created<br>";

// Submission Locks (Distributed Locking)
$sql = "CREATE TABLE IF NOT EXISTS submission_locks (
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    node_id VARCHAR(50) NOT NULL,
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (exam_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
if ($conn->query($sql) === TRUE) echo "Table 'submission_locks' created<br>";

$conn->close();
?>
