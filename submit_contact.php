<?php
include 'db_connect.php';

// Processing form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($message)) {
        $missing = [];
        if (empty($first_name)) $missing[] = "First Name";
        if (empty($last_name)) $missing[] = "Last Name";
        if (empty($email)) $missing[] = "Email";
        if (empty($message)) $missing[] = "Message";
        
        $error_msg = 'Please fill in required fields: ' . implode(', ', $missing);
        echo "<script>alert('$error_msg'); window.history.back();</script>";
        exit;
    }

    // Combine name
    $full_name = $first_name . ' ' . $last_name;

    // Insert into database
    $sql = "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $full_name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            // Success: Redirect back to contact.html with a success message (using JS for simplicity)
            echo "<script>alert('Thank you! Your message has been sent successfully.'); window.location.href='Contact.html';</script>";
        } else {
            echo "<script>alert('Error: Could not save message. Please try again.'); window.history.back();</script>";
        }
        $stmt->close();
    } else {
        echo "Database error: " . $conn->error;
    }
    
    $conn->close();
} else {
    // If accessed directly
    header("Location: Contact.html");
    exit();
}
?>
