<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

protectPage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
    $frequency = $isRecurring ? $_POST['frequency'] : 'One-time';

    // Basic validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Fee name is required.";
    }

    if ($amount <= 0) {
        $errors[] = "Amount must be greater than 0.";
    }

    if ($isRecurring && empty($frequency)) {
        $errors[] = "Frequency is required for recurring fees.";
    }

    // Check if fee name already exists
    $stmt = $pdo->prepare("SELECT id FROM fees WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        $errors[] = "A fee with this name already exists.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO fees (name, description, amount, is_recurring, frequency) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $amount, $isRecurring, $frequency]);

            $_SESSION['success'] = "Fee type added successfully!";
            header("Location: fees.php");
            exit();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// If not POST or if there were errors, redirect back to fees page
$_SESSION['error'] = $error ?? "Invalid request.";
header("Location: fees.php");
exit();
?>