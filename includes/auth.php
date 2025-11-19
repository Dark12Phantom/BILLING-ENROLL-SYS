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
    try {
        global $pdo;
        $today = new DateTime('now');
        $june = new DateTime($today->format('Y') . '-06-01');
        if ($today >= $june) {
            $year = (int)$today->format('Y');
            $syCurrent = $year . ' - ' . ($year + 1);
            $syNext = ($year + 1) . ' - ' . ($year + 2);
            $updPast = $pdo->prepare("UPDATE enrollment_history SET status = 'past' WHERE school_year = ? AND status = 'current'");
            $updPast->execute([$syCurrent]);
            $insCurrFromPre = $pdo->prepare("INSERT INTO enrollment_history (student_id, school_year, status)
                SELECT eh.student_id, eh.school_year, 'current'
                FROM enrollment_history eh
                WHERE eh.school_year = ? AND eh.status = 'pre-enrollment'
                  AND NOT EXISTS (
                    SELECT 1 FROM enrollment_history eh2
                    WHERE eh2.student_id = eh.student_id AND eh2.school_year = eh.school_year AND eh2.status = 'current'
                )");
            $insCurrFromPre->execute([$syNext]);
            $insMissing = $pdo->prepare("INSERT INTO enrollment_history (student_id, school_year, status)
                SELECT s.id, ?, 'pre-enrollment'
                FROM students s
                WHERE (s.status IS NULL OR s.status <> 'Inactive')
                  AND NOT EXISTS (
                    SELECT 1 FROM enrollment_history eh
                    WHERE eh.student_id = s.id AND eh.school_year = ? AND eh.status = 'pre-enrollment'
                )");
            $insMissing->execute([$syNext, $syNext]);
        }
    } catch (Exception $e) {}
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
