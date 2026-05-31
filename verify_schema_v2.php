<?php
include 'db_connect.php';

echo "<h2>Starting Database Schema Verification...</h2>";

// 1. Ensure 'users' table has all required columns
$required_columns = [
    'education_level' => "ALTER TABLE users ADD COLUMN education_level VARCHAR(50) AFTER role",
    'grade_year'      => "ALTER TABLE users ADD COLUMN grade_year VARCHAR(50) AFTER education_level",
    'department'      => "ALTER TABLE users ADD COLUMN department VARCHAR(100) AFTER grade_year",
    'semester'        => "ALTER TABLE users ADD COLUMN semester VARCHAR(20) AFTER department",
    'section'         => "ALTER TABLE users ADD COLUMN section VARCHAR(20) AFTER semester",
    'stream'          => "ALTER TABLE users ADD COLUMN stream VARCHAR(50) AFTER section",
    'status'          => "ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER stream"
];

foreach ($required_columns as $col => $alter_sql) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
    if ($check->num_rows == 0) {
        if ($conn->query($alter_sql)) {
            echo "✅ Column '$col' added to 'users' table successfully.<br>";
        } else {
            echo "❌ Error adding column '$col' to 'users': " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ️ Column '$col' already exists in 'users' table.<br>";
    }
}

// 1.1 Ensure 'exams' table has targeting columns
$exam_columns = [
    'level' => "ALTER TABLE exams ADD COLUMN level VARCHAR(50) AFTER type",
    'grade_year' => "ALTER TABLE exams ADD COLUMN grade_year VARCHAR(50) AFTER level",
    'section' => "ALTER TABLE exams ADD COLUMN section VARCHAR(20) AFTER grade_year",
    'stream' => "ALTER TABLE exams ADD COLUMN stream VARCHAR(50) AFTER section",
    'semester' => "ALTER TABLE exams ADD COLUMN semester VARCHAR(20) AFTER stream",
    'department' => "ALTER TABLE exams ADD COLUMN department VARCHAR(100) AFTER semester"
];

foreach ($exam_columns as $col => $alter_sql) {
    $check = $conn->query("SHOW COLUMNS FROM exams LIKE '$col'");
    if ($check->num_rows == 0) {
        if ($conn->query($alter_sql)) {
            echo "✅ Column '$col' added to 'exams' table successfully.<br>";
        } else {
            // Level might already exist from previous setup but check anyway
            if (strpos($conn->error, "Duplicate column name") === false) {
                 echo "❌ Error adding column '$col' to 'exams': " . $conn->error . "<br>";
            } else {
                 echo "ℹ️ Column '$col' already exists in 'exams' table.<br>";
            }
        }
    } else {
        echo "ℹ️ Column '$col' already exists in 'exams' table.<br>";
    }
}

// 2. Ensure Admin User exists with constant credentials
$admin_email = "admin@example.com";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$admin_name = "System Admin";

$check_admin = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check_admin->bind_param("s", $admin_email);
$check_admin->execute();
$result = $check_admin->get_result();

if ($result->num_rows == 0) {
    $insert_admin = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'approved')");
    $insert_admin->bind_param("sss", $admin_name, $admin_email, $admin_password);
    if ($insert_admin->execute()) {
        echo "✅ Default admin user created (admin@example.com / admin123).<br>";
    } else {
        echo "❌ Error creating admin user: " . $conn->error . "<br>";
    }
} else {
    // Update existing admin password to be constant as requested
    $update_admin = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'admin'");
    $update_admin->bind_param("ss", $admin_password, $admin_email);
    if ($update_admin->execute()) {
        echo "✅ Admin credentials updated to constant values.<br>";
    } else {
        echo "❌ Error updating admin credentials: " . $conn->error . "<br>";
    }
}

// 3. Check for 'status' in 'exams' table (Common issue mentioned in conversation history)
$check_exam_status = $conn->query("SHOW COLUMNS FROM exams LIKE 'status'");
if ($check_exam_status->num_rows == 0) {
    if ($conn->query("ALTER TABLE exams ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'")) {
        echo "✅ Column 'status' added to 'exams' table.<br>";
    }
}

echo "<h3>Schema verification complete!</h3>";
$conn->close();
?>
