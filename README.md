# 🛡️ Secure Online Examination System with Integrated Cybersecurity Features

A comprehensive, production-grade online examination platform built with **PHP, MySQL, HTML/CSS, and JavaScript**, featuring **9 integrated cybersecurity modules** that demonstrate real-world security concepts in action.

---

## 📋 Table of Contents
- [Features](#features)
- [Cybersecurity Concepts Implemented](#cybersecurity-concepts-implemented)
- [Installation](#installation)
- [Default Credentials](#default-credentials)
- [Project Structure](#project-structure)
- [Screenshots](#screenshots)

---

## ✨ Features
- 👨‍🎓 **Multi-Role System:** Admin, Teacher, and Student dashboards
- 📝 **Exam Management:** Create, manage, and take exams with MCQ, True/False, Short Answer
- 📊 **Results & Analytics:** Automated grading and result tracking
- 🔐 **Security Dashboard:** Interactive cybersecurity education center
- 🌐 **Distributed Architecture:** Multi-node cluster monitoring and logging

---

## 🔒 Cybersecurity Concepts Implemented

### 1. Authentication
- Secure login/logout system with session-based authentication
- Role-based access control (RBAC) for Admin, Teacher, and Student
- Session validation on every protected page
- **File:** `login.php`, `logout.php`, `db_connect.php`

### 2. Password Security
- Passwords hashed using **Bcrypt** via `password_hash()` (never stored in plain text)
- Secure password verification using `password_verify()`
- Strong hashing with automatic salting
- **File:** `register.php`, `login.php`

### 3. SQL Injection Protection
- All database queries use **Prepared Statements** with parameterized binding
- User inputs are never concatenated directly into SQL strings
- Example: `$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");`
- **File:** `login.php`, `register.php`, all PHP files with database queries

### 4. XSS (Cross-Site Scripting) Protection
- Custom `xss_clean()` function sanitizes all user output
- Uses `htmlspecialchars()` with `ENT_QUOTES` and `UTF-8` encoding
- Prevents malicious JavaScript injection into web pages
- **File:** `config.php` (global function)

### 5. Cryptography
- **AES-256-CBC** encryption engine for sensitive data at rest
- Secure random IV generation using `openssl_random_pseudo_bytes()`
- `encryptData()` and `decryptData()` helper functions
- Live demonstration available on the Security Dashboard
- **File:** `crypto_helper.php`

### 6. Network Security
- **HTTP Strict Transport Security (HSTS):** Forces HTTPS connections
- **Content Security Policy (CSP):** Restricts script/resource sources
- **X-Frame-Options:** Prevents Clickjacking attacks
- **X-XSS-Protection:** Browser-level XSS filtering
- **X-Content-Type-Options:** Prevents MIME-type sniffing
- **Referrer-Policy:** Controls referrer information leakage
- **File:** `config.php`

### 7. Database Security
- Secure session storage in database (not file system)
- `HttpOnly` cookies to prevent JavaScript session theft
- `use_only_cookies` to prevent session fixation via URL
- Secure cookie flag for HTTPS environments
- Distributed locking to prevent race conditions
- **File:** `config.php`, `session_handler.php`, `distributed_lock.php`

### 8. Penetration Testing
- Security Dashboard shows real-time vulnerability scan results
- Demonstrates protection status against:
  - SQL Injection attacks
  - Cross-Site Scripting (XSS)
  - Brute Force / Dictionary attacks
  - Insecure Direct Object Reference (IDOR)
- **File:** `security_dashboard.php`

### 9. Secure Software Development (SSDLC)
- Follows **OWASP Top 10** mitigation guidelines
- Graceful error handling (no sensitive info leaked to users)
- Centralized cluster logging for anomaly detection
- Input validation on all forms
- Separation of concerns (config, logic, presentation)
- **File:** `config.php`, `distributed_logger.php`, `security_dashboard.php`

---

## 🚀 Installation

### Prerequisites
- **PHP 7.4+** (with OpenSSL extension enabled)
- **MySQL 5.7+** (via XAMPP, WAMP, or standalone)
- **Web Browser** (Chrome, Edge, Firefox)

### Setup Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/YOUR_USERNAME/secure-online-examination-system.git
   ```

2. **Start MySQL** (via XAMPP Control Panel or command line)

3. **Navigate to the project folder:**
   ```bash
   cd secure-online-examination-system
   ```

4. **Start the PHP development server:**
   ```bash
   php -S localhost:8000
   ```

5. **Initialize the database:**
   Open your browser and visit:
   ```
   http://localhost:8000/setup_database.php
   ```

6. **Login and explore:**
   ```
   http://localhost:8000/login.html
   ```

---

## 🔑 Default Credentials

| Role    | Email               | Password  |
|---------|---------------------|-----------|
| Admin   | admin@example.com   | admin123  |

---

## 📁 Project Structure

```
├── config.php                  # Global config + Network Security headers
├── crypto_helper.php           # AES-256-CBC Cryptography engine
├── db_connect.php              # Secure database connection
├── distributed_logger.php      # Cluster monitoring & audit logging
├── session_handler.php         # Database-backed secure sessions
├── security_dashboard.php      # Interactive Security Education Center
├── setup_database.php          # Database initialization script
│
├── login.html / login.php      # Authentication (SQL Injection protected)
├── register.html / register.php # Registration (Password hashing)
│
├── admin_dashboard.php         # Admin control panel
├── teacher_dashboard.php       # Teacher management panel
├── student_dashboard.php       # Student exam portal
│
├── manage_exams.php            # Exam CRUD operations
├── manage_students.php         # Student management
├── manage_teachers.php         # Teacher management
├── take_exam.php               # Exam-taking interface
├── submit_exam.php             # Secure exam submission
│
├── style.css                   # Global stylesheet
└── README.md                   # This file
```

---

## 🛠️ Technologies Used
- **Backend:** PHP 7.4+
- **Database:** MySQL (MySQLi with Prepared Statements)
- **Frontend:** HTML5, CSS3, JavaScript
- **Encryption:** OpenSSL (AES-256-CBC)
- **Hashing:** Bcrypt (PASSWORD_DEFAULT)
- **Icons:** Font Awesome 6

---

## 👨‍💻 Author
Developed as a Cybersecurity-focused academic project demonstrating secure software development practices.

## 📄 License
This project is for educational purposes.
