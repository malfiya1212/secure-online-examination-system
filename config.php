<?php
/**
 * Global Configuration File
 * This file centralizes all system settings to support a distributed architecture.
 */

// --- Database Configuration ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'online_exam_system');

// --- Network & Distributed Settings ---
// Dynamically detect the Base URL to support access from different PCs via IP
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_path = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
$base_url = $protocol . $host . rtrim($script_path, '/') . '/';

define('BASE_URL', $base_url);

// --- Advanced Network Security & Cryptography Integration ---
// 1. Network Security: Secure HTTP Headers against XSS, Clickjacking, and Sniffing
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval';");
header("Referrer-Policy: strict-origin-when-cross-origin");

// 2. Database Security & Session Hardening
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1); // Prevent XSS theft of cookies
    ini_set('session.use_only_cookies', 1); // Prevent session fixation
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0); // HTTPS only
}

// 3. XSS Protection Helper (Secure Software Development)
function xss_clean($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = xss_clean($value);
        }
        return $data;
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}


// --- System Metadata (For Distributed Visibility) ---
define('SYSTEM_NODE_ID', gethostname()); // Unique ID for this server node
define('SYSTEM_VERSION', '2.0.0-distributed');

// --- Error Reporting (Adjust for production if needed) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Utility function to get absolute paths for redirects/includes
 */
function site_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}
?>
