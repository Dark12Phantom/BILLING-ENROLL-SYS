<?php
require_once 'db.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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
    $_SESSION = [];
    session_unset();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

// Protect page
function protectPage()
{
    if (!isLoggedIn()) {
        $root = 'http://' . $_SERVER['HTTP_HOST'] . '/BILLING-ENROLL-SYS/login.php';
        header("Location: $root");
        exit();
    }
}
function protectLoginPage()
{
    if (isLoggedIn()) {
        if ($_SESSION['role'] === 'admin') {
            header("Location: admin/dashboard.php");
            exit();
        } elseif ($_SESSION['role'] === 'staff') {
            header("Location: staff/dashboard.php");
            exit();
        }
    }
}
