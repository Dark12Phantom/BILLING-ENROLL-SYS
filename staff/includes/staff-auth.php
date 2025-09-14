<?php
require_once 'database.php';

function isLoggedInStaff()
{
    return isset($_SESSION['user_id']);
}

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

function logout()
{
    session_unset();
    session_destroy();
}

function protectPage()
{
    if (!isLoggedInStaff()) {
        header("Location: ../login.php");
        exit();
    }
}
