<?php
include 'db_connect.php';

$msg = "";
$error = "";

if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    
    // Validate Token
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expiry > NOW()");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $error = "Invalid or expired token.";
    }
} else {
    // If posting form
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $token = $_POST['token'];
        $email = $_POST['email'];
        $pass = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        
        if ($pass !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            // Update Password
            $new_hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $new_hash, $email);
            
            if ($stmt->execute()) {
                // Delete token
                $conn->query("DELETE FROM password_resets WHERE email = '$email'");
                header("Location: login.html?msg=Password reset successful! Please login.");
                exit();
            } else {
                $error = "Database error.";
            }
        }
    } else {
        $error = "No token provided.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        /* Reusing Login Styles for Consistency */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: white; padding: 40px; border-radius: 16px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        h2 { color: #1e3a8a; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; }
        button { width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php elseif(isset($_POST['token'])): ?>
            <!-- Should have redirected -->
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <input type="password" name="password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                
                <button type="submit">Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>