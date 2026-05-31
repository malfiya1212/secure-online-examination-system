<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Simulation</title>
    <style>
        body { font-family: sans-serif; background: #f0f0f0; padding: 50px; text-align: center; }
        .card { background: white; padding: 40px; max-width: 600px; margin: 0 auto; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        code { background: #eee; padding: 5px 10px; border-radius: 4px; display: block; margin: 20px 0; word-break: break-all; }
        .btn { background: #6366f1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Check your Email</h1>
        <p>A password reset link has been sent to <strong><?php echo htmlspecialchars($_GET['email']); ?></strong>.</p>
        <p>(Since this is a local environment, here is the link for testing:)</p>
        
        <code>http://localhost/oline%20exam/reset_password.php?token=<?php echo htmlspecialchars($_GET['token']); ?>&email=<?php echo htmlspecialchars($_GET['email']); ?></code>
        
        <br><br>
        <a href="reset_password.php?token=<?php echo htmlspecialchars($_GET['token']); ?>&email=<?php echo htmlspecialchars($_GET['email']); ?>" class="btn">Reset Password Now</a>
    </div>
</body>
</html>
