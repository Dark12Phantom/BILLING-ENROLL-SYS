<?php
require_once 'database.php';

function isLoggedInStaff()
{
    return isset($_SESSION['user_id']);
}

function login($username, $password)
{
    global $pdo;

    // Fetch user and user_type together
    $stmt = $pdo->prepare("
        SELECT u.*, ut.user_type 
        FROM users u
        LEFT JOIN user_tables ut ON ut.userID = u.id
        WHERE u.username = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Store all session variables
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['user_type'] = $user['user_type'] ?? null; // Make sure this is set

        // Update last login
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        // Debug logging
        error_log("Login successful - User: {$user['username']}, Type: {$user['user_type']}");
        
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

function requireRole($allowedRoles = [])
{
    if (!isLoggedInStaff()) {
        header("Location: ../login.php");
        exit();
    }

    $userType = $_SESSION['user_type'] ?? null;

    if (!$userType || !in_array($userType, $allowedRoles)) {
        header("HTTP/1.1 403 Forbidden");
        echo "Access Denied. Required role: " . implode(', ', $allowedRoles);
        exit();
    }
}
?>