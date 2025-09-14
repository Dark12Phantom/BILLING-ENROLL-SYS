<?php
require_once 'db.php';

// Check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Login function
function login($username, $password)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        return true;
    }

    return false;
}

// Logout function
function logout()
{
    session_unset();
    session_destroy();
}

// Protect page
function protectPage()
{
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}
