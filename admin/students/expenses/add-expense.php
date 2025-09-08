<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

protectPage();

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: operational.php");
    exit();
}

// Get current user ID (approver)
$approvedBy = $_SESSION['user_id'];

// Validate and sanitize input data
$category = trim($_POST['category']);
$particular = trim($_POST['particular']);
$amount = floatval($_POST['amount']);
$dateIncurred = $_POST['date_incurred'];
$evidence = '';

// Initialize error array
$errors = [];

// Validation
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

// Handle file upload for evidence
if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/expenses/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExt = strtolower(pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION));
    $fileName = uniqid('expense_') . '.' . $fileExt;
    $targetPath = $uploadDir . $fileName;
    
    // Validate file type and size
    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
    $maxFileSize = 2 * 1024 * 1024; // 2MB
    
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

// If no errors, proceed with database insertion
if (empty($errors)) {
    try {
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
        
        $_SESSION['success'] = "Expense added successfully!";
    } catch (PDOException $e) {
        // Delete uploaded file if database insertion fails
        if (!empty($evidence) && file_exists('../uploads/' . $evidence)) {
            unlink('../uploads/' . $evidence);
        }
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// Handle errors or redirect on success
if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    $_SESSION['form_data'] = $_POST; // Save form data for repopulation
}

header("Location: operational.php");
exit();
?>