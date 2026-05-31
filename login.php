<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: login.html?error=All fields are required");
        exit();
    }

    // Prepare SQL to find user by email only
    $sql = "SELECT id, name, password, role, status FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify Password
        if (password_verify($password, $user['password'])) {
            
            // Validate Role Selection if provided
            if (isset($_POST['role'])) {
                $selected_role = $_POST['role'];
                if ($selected_role !== $user['role']) {
                    header("Location: login.html?error=Incorrect role selected. You are registered as a " . ucfirst($user['role']));
                    exit();
                }
            }

            // Check if account is approved
            if ($user['status'] !== 'approved') {
                header("Location: login.html?error=Your account is " . $user['status']);
                exit();
            }

            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $user['role'];

            // Log this across the cluster
            log_cluster_event($conn, "User Login", "User " . $user['name'] . " logged in as " . $user['role']);

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'teacher':
                    header("Location: teacher_dashboard.php");
                    break;
                case 'student':
                    header("Location: student_dashboard.php");
                    break;
                default:
                    header("Location: login.html?error=Unknown role");
            }
            exit();
        } else {
            header("Location: login.html?error=Invalid password");
            exit();
        }
    } else {
        header("Location: login.html?error=No account found with this email");
        exit();
    }
}
?>