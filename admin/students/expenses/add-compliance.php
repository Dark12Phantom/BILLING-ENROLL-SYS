<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

protectPage();

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: compliance.php");
    exit();
}

$paidBy = $_SESSION['user_id'];

$type = $_POST['type'];
$amount = floatval($_POST['amount']);
$paymentDate = $_POST['payment_date'];
$referenceNumber = trim($_POST['reference_number']);
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

        // Insert into compliance_expenses
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

        // Generate receipt number
        $receiptNo = 'RCPT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        // Insert into receipts
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

        $_SESSION['success'] = "Compliance payment and receipt recorded successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = "Database error: " . $e->getMessage();
    }
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
}

header("Location: compliance.php");
exit();
?>
