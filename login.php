<?php
session_start();
require_once 'config.php';

// Handle login form submission
if (isset($_POST['login'])) {
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both email and password.";
        header("Location: index.php");
        exit();
    }

    // Use prepared statement to avoid SQL injection
    $stmt = $conn->prepare("
        SELECT member_id, fname, lname, email, password_hash, role
        FROM Member
        WHERE email = ?
    ");
    if (!$stmt) {
        $_SESSION['login_error'] = "Database error. Please try again later.";
        header("Location: index.php");
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    $stmt->close();

    // Check if user exists AND password is correct
    if ($member && password_verify($password, $member['password_hash'])) {

        // Normalize role (if somehow NULL, treat as 'member')
        $role = $member['role'] ?: 'member';

        // Set session values
        $_SESSION['member_id'] = $member['member_id'];  
        $_SESSION['fname']     = $member['fname'];
        $_SESSION['lname']     = $member['lname'];
        $_SESSION['email']     = $member['email'];
        $_SESSION['role']      = $role;

        // Redirect based on role
        if ($role === 'admin') {
            header("Location: admin_portal.php");
        } else {
            header("Location: member_dashboard.php");
        }
        exit();
    }
    
    // Invalid login
    $_SESSION['login_error'] = "Incorrect email or password";
    header("Location: index.php");
    exit();
}
?>
