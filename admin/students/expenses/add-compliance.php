<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

protectPage();

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: compliance.php");
    exit();
}

// Get current user ID
$paidBy = $_SESSION['user_id'];

// Validate and sanitize input data
$type = $_POST['type'];
$amount = floatval($_POST['amount']);
$paymentDate = $_POST['payment_date'];
$referenceNumber = trim($_POST['reference_number']);
$periodCovered = trim($_POST['period_covered']);

// Initialize error array
$errors = [];

// Validation
if (!in_array($type, ['SSS', 'Pag-IBIG', 'PhilHealth', 'Permit', 'Registration'])) {
    $errors[] = "Invalid expense type.";
}

if ($amount <= 0) {
    $errors[] = "Amount must be greater than 0.";
}

if (empty($paymentDate) || !strtotime($paymentDate)) {
    $errors[] = "Valid payment date is required.";
}

// If no errors, proceed with database insertion
if (empty($errors)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO compliance_expenses 
                             (type, amount, payment_date, reference_number, period_covered, paid_by) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $type,
            $amount,
            $paymentDate,
            $referenceNumber,
            $periodCovered,
            $paidBy
        ]);
        
        $_SESSION['success'] = "Compliance payment recorded successfully!";
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// Handle errors or redirect on success
if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
}

header("Location: compliance.php");
exit();
?>