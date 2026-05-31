<?php
function sendResetEmail($email, $token) {
    $reset_link = "http://localhost/online_exam_system/reset_password.php?token=" . $token;
    
    $subject = "Password Reset Request - Online Exam System";
    $message = "
    <html>
    <body>
        <h2>Password Reset Request</h2>
        <p>Hello,</p>
        <p>You requested a password reset. Click the link below to reset your password:</p>
        <p><a href='$reset_link' style='background:#1e3a8a;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;display:inline-block;'>Reset Password</a></p>
        <p>This link expires in 1 hour.</p>
        <p>If you didn't request this, ignore this email.</p>
        <br>
        <p>Online Exam System Team</p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: no-reply@onlineexam.com' . "\r\n";

    return mail($email, $subject, $message, $headers);
}
?>