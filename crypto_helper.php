<?php
/**
 * Cryptography Module for Secure Online Examination System
 * Provides AES-256-CBC encryption for sensitive data (e.g., test scores, PII)
 */

// A secure encryption key should ideally be stored in an environment variable
// For the sake of this implementation, we define it here securely.
define('ENCRYPTION_KEY', 'xS3cur3!Ex4m@K3y#2026_StrongPass');

function encryptData($data) {
    if (empty($data)) return $data;
    
    $cipher = "AES-256-CBC";
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivLength);
    
    // Encrypt the data
    $encrypted = openssl_encrypt($data, $cipher, ENCRYPTION_KEY, 0, $iv);
    
    // Prepend the IV so we can use it during decryption
    // Encode to base64 so it can be safely stored in the database
    return base64_encode($iv . $encrypted);
}

function decryptData($data) {
    if (empty($data)) return $data;
    
    $cipher = "AES-256-CBC";
    $ivLength = openssl_cipher_iv_length($cipher);
    
    // Decode from base64
    $data = base64_decode($data);
    
    // Extract the IV and the encrypted text
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    
    if (strlen($iv) !== $ivLength) {
        return false; // Invalid IV
    }
    
    // Decrypt the data
    return openssl_decrypt($encrypted, $cipher, ENCRYPTION_KEY, 0, $iv);
}
?>
