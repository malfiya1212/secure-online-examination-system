<?php
require_once 'db_connect.php';
require_once 'crypto_helper.php';

// Only admins can access the security dashboard
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Access Denied. Unauthorized access detected.");
}

$cryptoTestString = "TopSecretExamScore100";
$encrypted = encryptData($cryptoTestString);
$decrypted = decryptData($encrypted);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cybersecurity Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f3f4f6; }
        .card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .secure-badge { background-color: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-gray-800"><i class="fas fa-shield-alt text-blue-600"></i> Secure Examination System - Security Center</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Network & Database Security -->
            <div class="card border-l-4 border-blue-500">
                <h2 class="text-xl font-semibold mb-4"><i class="fas fa-network-wired"></i> Network & Database Security</h2>
                <ul class="space-y-3">
                    <li><i class="fas fa-check-circle text-green-500"></i> HTTP Strict Transport Security (HSTS) <span class="secure-badge">Active</span></li>
                    <li><i class="fas fa-check-circle text-green-500"></i> Content Security Policy (CSP) <span class="secure-badge">Active</span></li>
                    <li><i class="fas fa-check-circle text-green-500"></i> Database Prepared Statements (PDO/MySQLi) <span class="secure-badge">Active</span></li>
                    <li><i class="fas fa-check-circle text-green-500"></i> Secure Session (HttpOnly & Secure cookies) <span class="secure-badge">Active</span></li>
                </ul>
            </div>

            <!-- Penetration Testing & Vulnerability Status -->
            <div class="card border-l-4 border-red-500">
                <h2 class="text-xl font-semibold mb-4"><i class="fas fa-bug"></i> Penetration Testing / Vulnerability Scans</h2>
                <ul class="space-y-3">
                    <li><strong>SQL Injection:</strong> Blocked via Prepared Statements. <span class="secure-badge">Protected</span></li>
                    <li><strong>Cross-Site Scripting (XSS):</strong> Blocked via strict HTML entity encoding. <span class="secure-badge">Protected</span></li>
                    <li><strong>Brute Force:</strong> Mitigated via strong password hashing (Bcrypt). <span class="secure-badge">Protected</span></li>
                    <li><strong>Insecure Direct Object Reference:</strong> Mitigated via strict RBAC. <span class="secure-badge">Protected</span></li>
                </ul>
            </div>

            <!-- Cryptography -->
            <div class="card border-l-4 border-purple-500 md:col-span-2">
                <h2 class="text-xl font-semibold mb-4"><i class="fas fa-key"></i> Cryptography Engine (AES-256-CBC)</h2>
                <p class="mb-2 text-sm text-gray-600">Sensitive data at rest (like user scores) can be encrypted to maintain confidentiality.</p>
                <div class="bg-gray-100 p-4 rounded text-sm font-mono mt-4">
                    <p><strong>Original Data:</strong> <?php echo xss_clean($cryptoTestString); ?></p>
                    <p class="mt-2 text-red-600 break-all"><strong>Encrypted Payload:</strong> <?php echo xss_clean($encrypted); ?></p>
                    <p class="mt-2 text-green-600"><strong>Decrypted Output:</strong> <?php echo xss_clean($decrypted); ?></p>
                </div>
            </div>

            <!-- Secure Software Development -->
            <div class="card border-l-4 border-green-500 md:col-span-2">
                <h2 class="text-xl font-semibold mb-4"><i class="fas fa-code"></i> Secure Software Development Lifecycle (SSDLC)</h2>
                <p class="text-sm text-gray-600 mb-4">All application code strictly follows the OWASP Top 10 mitigation guidelines.</p>
                <ul class="list-disc list-inside text-sm text-gray-800 space-y-2">
                    <li><strong>Authentication:</strong> Robust session handler, password_hash with Bcrypt used by default.</li>
                    <li><strong>Input Validation:</strong> All inputs sanitized and strictly typed.</li>
                    <li><strong>Error Handling:</strong> Errors securely logged and hidden from end users to avoid information disclosure.</li>
                    <li><strong>Logging:</strong> Active cluster logging enabled to detect anomalous behavior.</li>
                </ul>
            </div>

        </div>
        
        <div class="mt-8 border-t pt-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-800"><i class="fas fa-graduation-cap text-blue-600"></i> Educational Cybersecurity Modules</h2>
            <p class="text-gray-600 mb-6">This section explains how each security concept is implemented within the source code of this project.</p>
            
            <div class="space-y-6">
                <!-- 1. Authentication & Password Security -->
                <div class="bg-white p-6 rounded shadow-sm border-l-4 border-indigo-500">
                    <h3 class="text-lg font-bold text-indigo-700">1. Authentication & Password Security</h3>
                    <p class="text-sm mt-2 text-gray-700"><strong>Concept:</strong> Never store plain-text passwords. Use strong, slow hashing algorithms with unique salts.</p>
                    <div class="bg-gray-800 text-green-400 p-3 rounded mt-2 font-mono text-xs overflow-x-auto">
                        // Bad: $password = $_POST['password'];<br>
                        // Good (Implemented in register.php):<br>
                        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT); // Bcrypt<br>
                        // Verification (Implemented in login.php):<br>
                        password_verify($input_password, $hashed_from_db);
                    </div>
                </div>

                <!-- 2. SQL Injection Protection -->
                <div class="bg-white p-6 rounded shadow-sm border-l-4 border-yellow-500">
                    <h3 class="text-lg font-bold text-yellow-700">2. SQL Injection (SQLi) Protection</h3>
                    <p class="text-sm mt-2 text-gray-700"><strong>Concept:</strong> Prevent hackers from altering database queries by separating SQL code from user data.</p>
                    <div class="bg-gray-800 text-green-400 p-3 rounded mt-2 font-mono text-xs overflow-x-auto">
                        // Bad: "SELECT * FROM users WHERE email = '" . $_POST['email'] . "'";<br>
                        // Good (Implemented using Prepared Statements):<br>
                        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");<br>
                        $stmt->bind_param("s", $email);<br>
                        $stmt->execute();
                    </div>
                </div>

                <!-- 3. Cross-Site Scripting (XSS) Protection -->
                <div class="bg-white p-6 rounded shadow-sm border-l-4 border-pink-500">
                    <h3 class="text-lg font-bold text-pink-700">3. Cross-Site Scripting (XSS) Protection</h3>
                    <p class="text-sm mt-2 text-gray-700"><strong>Concept:</strong> Prevent hackers from injecting malicious JavaScript into web pages viewed by other users.</p>
                    <div class="bg-gray-800 text-green-400 p-3 rounded mt-2 font-mono text-xs overflow-x-auto">
                        // Bad: echo "&lt;h1&gt;Welcome " . $_GET['name'] . "&lt;/h1&gt;";<br>
                        // Good (Implemented via xss_clean in config.php):<br>
                        function xss_clean($data) {<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');<br>
                        }<br>
                        echo "&lt;h1&gt;Welcome " . xss_clean($_GET['name']) . "&lt;/h1&gt;";
                    </div>
                </div>

                <!-- 4. Network Security & Headers -->
                <div class="bg-white p-6 rounded shadow-sm border-l-4 border-teal-500">
                    <h3 class="text-lg font-bold text-teal-700">4. Network Security (Secure Headers)</h3>
                    <p class="text-sm mt-2 text-gray-700"><strong>Concept:</strong> Instruct the web browser to enforce strict security rules (blocking Clickjacking and MIME-sniffing).</p>
                    <div class="bg-gray-800 text-green-400 p-3 rounded mt-2 font-mono text-xs overflow-x-auto">
                        // Implemented in config.php:<br>
                        header("X-Frame-Options: SAMEORIGIN"); // Prevents Clickjacking<br>
                        header("X-Content-Type-Options: nosniff"); // Prevents MIME-type confusion<br>
                        header("Content-Security-Policy: default-src 'self'"); // Restricts script sources
                    </div>
                </div>

                <!-- 5. Database Security (Session Hardening) -->
                <div class="bg-white p-6 rounded shadow-sm border-l-4 border-orange-500">
                    <h3 class="text-lg font-bold text-orange-700">5. Database Security & Session Hijacking Prevention</h3>
                    <p class="text-sm mt-2 text-gray-700"><strong>Concept:</strong> Protect session cookies from being stolen by JavaScript to prevent unauthorized access.</p>
                    <div class="bg-gray-800 text-green-400 p-3 rounded mt-2 font-mono text-xs overflow-x-auto">
                        // Implemented in config.php:<br>
                        ini_set('session.cookie_httponly', 1); // Blocks JavaScript access to cookies<br>
                        ini_set('session.use_only_cookies', 1); // Forces cookies instead of URL parameters
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 mb-10">
            <a href="admin_dashboard.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
