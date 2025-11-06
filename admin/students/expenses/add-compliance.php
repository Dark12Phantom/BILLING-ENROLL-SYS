<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

protectPage();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: compliance.php");
    exit();
}

$paidBy = $_SESSION['user_id'];

$type = isset($_POST['type']) ? trim((string)$_POST['type']) : '';
$amount = isset($_POST['amount']) && is_numeric($_POST['amount']) ? floatval($_POST['amount']) : 0;
$paymentDate = $_POST['payment_date'];
$referenceNumber = isset($_POST['reference_number']) ? trim($_POST['reference_number']) : '';
$periodCovered = isset($_POST['period_covered']) ? trim($_POST['period_covered']) : '';

$errors = [];

if (empty($periodCovered)) {
    $errors[] = "Period covered is required.";
} elseif (!preg_match('/^[\w\s\-\/]+$/', $periodCovered)) {
    $errors[] = "Period covered contains invalid characters.";
}

if (strlen($referenceNumber) > 50) {
    $errors[] = "Reference number must not exceed 50 characters.";
} elseif (!preg_match('/^[A-Za-z0-9\-]+$/', $referenceNumber)) {
    $errors[] = "Reference number contains invalid characters.";
}
$periodCovered = trim($_POST['period_covered']);

$errors = [];

if (!in_array($type, ['Social Security System', 'Pag-IBIG', 'PhilHealth', 'Permit', 'Registration'])) {
    $errors[] = "Invalid expense type.";
}

if ($amount <= 0) {
    $errors[] = "Amount must be greater than 0.";
}

if (empty($paymentDate) || !strtotime($paymentDate)) {
    $errors[] = "Valid payment date is required.";
}

if (empty($errors)) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO compliance_expenses 
            (type, amount, payment_date, reference_number, period_covered, paid_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $type,
            $amount,
            $paymentDate,
            $referenceNumber,
            $periodCovered,
            $paidBy
        ]);

        $complianceId = $pdo->lastInsertId();

        $receiptNo = 'RCPT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $stmt = $pdo->prepare("
            INSERT INTO receipts (expense_id, receipt_no, date_issued, amount, description, compliance_id)
            VALUES (NULL, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $receiptNo,
            date('Y-m-d'),
            $amount,
            "$type Payment for Period: $periodCovered",
            $complianceId
        ]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = "Unexpected error: " . $e->getMessage();
    }
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
}

header("Location: compliance.php");
exit();
?>
