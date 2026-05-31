<?php
include 'db_connect.php';

// Function to create user
function createUser($conn, $name, $email, $password, $role) {
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $status = 'approved';

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Update existing user
        $sql = "UPDATE users SET password = ?, role = ?, status = ?, name = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $hashed_password, $role, $status, $name, $email);
        echo "Updated existing user: $email (Password: $password)<br>";
    } else {
        // Insert new user
        $sql = "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $status);
        echo "Created new user: $email (Password: $password)<br>";
    }
    
    if ($stmt->execute()) {
        echo "Success!<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

echo "<h2>Creating Test Users...</h2>";

// 1. Admin
createUser($conn, "System Admin", "admin@email.com", "admin123", "admin");

// 2. Teacher
createUser($conn, "John Teacher", "teacher@email.com", "teacher123", "teacher");

// 3. Student
createUser($conn, "Jane Student", "student@email.com", "student123", "student");

echo "<h3>Done! You can now login with:</h3>";
echo "<ul>";
echo "<li>Admin: admin@email.com / admin123</li>";
echo "<li>Teacher: teacher@email.com / teacher123</li>";
echo "<li>Student: student@email.com / student123</li>";
echo "</ul>";
echo "<a href='login.html'>Go to Login Page</a>";
?>
