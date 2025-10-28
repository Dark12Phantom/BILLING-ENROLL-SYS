<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

protectPage();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: operational.php");
    exit();
}

$approvedBy = $_SESSION['user_id'];

$category = trim($_POST['category']);
$particular = trim($_POST['particular']);
$amount = floatval($_POST['amount']);
$dateIncurred = $_POST['date_incurred'];
$evidence = '';

$errors = [];

if (empty($category)) {
    $errors[] = "Category is required.";
}

if (empty($particular)) {
    $errors[] = "Particular is required.";
}

if ($amount <= 0) {
    $errors[] = "Amount must be greater than 0.";
}

if (empty($dateIncurred) || !strtotime($dateIncurred)) {
    $errors[] = "Valid date is required.";
}

if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/expenses/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExt = strtolower(pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION));
    $fileName = uniqid('expense_') . '.' . $fileExt;
    $targetPath = $uploadDir . $fileName;
    
    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
    $maxFileSize = 2 * 1024 * 1024;
    
    if (in_array($fileExt, $allowedTypes)) {
        if ($_FILES['evidence']['size'] <= $maxFileSize) {
            if (move_uploaded_file($_FILES['evidence']['tmp_name'], $targetPath)) {
                $evidence = 'expenses/' . $fileName;
            } else {
                $errors[] = "Failed to upload evidence file.";
            }
        } else {
            $errors[] = "File size exceeds maximum limit of 2MB.";
        }
    } else {
        $errors[] = "Only JPG, PNG, and PDF files are allowed.";
    }
}

if (empty($errors)) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO operational_expenses 
                             (category, particular, amount, evidence, date_incurred, approved_by) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $category,
            $particular,
            $amount,
            $evidence,
            $dateIncurred,
            $approvedBy
        ]);

        $expenseId = $pdo->lastInsertId();

        $receiptNo = 'RCPT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $stmt = $pdo->prepare("
            INSERT INTO receipts (expense_id, receipt_no, date_issued, amount, description, compliance_id)
            VALUES (?, ?, ?, ?, ?, NULL)
        ");
        $stmt->execute([
            $expenseId,
            $receiptNo,
            date('Y-m-d'),
            $amount,
            "Operational Expense - $category: $particular"
        ]);

        $pdo->commit();
        $_SESSION['success'] = "Operational expense and receipt recorded successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();

        if (!empty($evidence) && file_exists('../uploads/' . $evidence)) {
            unlink('../uploads/' . $evidence);
        }
        $errors[] = "Database error: " . $e->getMessage();
    }
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    $_SESSION['form_data'] = $_POST;
}

header("Location: operational.php");
exit();
?>
