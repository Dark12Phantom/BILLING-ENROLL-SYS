<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

protectPage();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$feeId = $_GET['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM student_fees WHERE id = ?");
    $stmt->execute([$feeId]);

    header("Location: index.php?msg=Fee+assignment+deleted+successfully");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=Unable+to+delete+fee+assignment");
    exit();
}
