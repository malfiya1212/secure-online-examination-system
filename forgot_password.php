<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email)) {
        header("Location: forgot_password.html?error=Email is required");
        exit();
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate Token
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        // Save to DB (Assuming a password_resets table or update users table with reset_token)
        // For now, let's create a password_resets table automatically if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
            email VARCHAR(100) NOT NULL,
            token VARCHAR(100) NOT NULL,
            expiry DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Delete old tokens for this email
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // Insert new token
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expiry);
        $stmt->execute();

        // In a real app, send email here. For simulation:
        // Redirect to a page that pretends to send email or shows the link for testing
        header("Location: reset_simulation.php?token=$token&email=$email");
        exit();
    } else {
        header("Location: forgot_password.html?error=Email not found");
        exit();
    }
}
?>