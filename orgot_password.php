<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: login.html?error=Valid email is required");
        exit();
    }

    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate secure token
        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Save token to database
        $sql_update = "UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sss", $token, $expiry, $email);
        $stmt_update->execute();

        // Send email
        $reset_link = BASE_URL . "reset_password.php?token=" . $token;
        $subject = "Reset Your Password - Online Exam System";
        $message = "
        <html>
        <body>
            <h2>Password Reset Request</h2>
            <p>Hello,</p>
            <p>Click the link below to reset your password:</p>
            <p><a href='$reset_link' style='background:#1e3a8a;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;'>Reset Password</a></p>
            <p>This link expires in 1 hour.</p>
            <p>If you didn't request this, ignore this email.</p>
            <br>
            <p>Online Exam System Team</p>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: no-reply@yourdomain.com' . "\r\n";

        if (mail($email, $subject, $message, $headers)) {
            header("Location: login.html?success=Reset link sent to your email!");
        } else {
            header("Location: login.html?error=Failed to send email. Try again.");
        }
    } else {
        // Don't reveal if email exists (security best practice)
        header("Location: login.html?success=If email exists, reset link has been sent.");
    }
    exit();
}
?>