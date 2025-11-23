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
$isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
$frequency = isset($_POST['frequency']) ? trim($_POST['frequency']) : '';
$nextDueDate = isset($_POST['next_due_date']) ? trim($_POST['next_due_date']) : '';

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

if (!in_array($type, ['Social Security System', 'Pag-IBIG', 'PhilHealth', 'Permit', 'Registration'])) {
    $errors[] = "Invalid expense type.";
}

if ($amount <= 0) {
    $errors[] = "Amount must be greater than 0.";
}

if (empty($paymentDate) || !strtotime($paymentDate)) {
    $errors[] = "Valid payment date is required.";
}

// Validate recurring fields if recurring is checked
if ($isRecurring) {
    if (!in_array($frequency, ['monthly', 'quarterly', 'semi-annual', 'annual', 'weekly', 'yearly'])) {
        $errors[] = "Invalid frequency selected.";
    }
    
    if (empty($nextDueDate) || !strtotime($nextDueDate)) {
        $errors[] = "Valid next due date is required for recurring payments.";
    }
}

if (empty($errors)) {
    try {
        $pdo->beginTransaction();

        // Insert into compliance_expenses
        $stmt = $pdo->prepare("
            INSERT INTO compliance_expenses 
            (type, amount, payment_date, reference_number, period_covered, paid_by, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
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

        // Create receipt
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

        // If recurring, add to billing_schedule
        if ($isRecurring) {
            $expenseName = $type; // Use the type directly as expense_name
            
            $stmt = $pdo->prepare("
                INSERT INTO billing_schedule 
                (expense_name, amount, category, frequency, next_due_date, last_run, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $expenseName,
                $amount,
                'Compliance',
                $frequency,
                $nextDueDate,
                $paymentDate,
                'active'
            ]);
            
            $_SESSION['success'] = "Compliance payment added successfully and scheduled for recurring payments.";
        } else {
            $_SESSION['success'] = "Compliance payment added successfully.";
        }

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