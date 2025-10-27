<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

protectPage();

header('Content-Type: application/json');

// Only process POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

if (!isset($_POST['expense_id']) || !is_numeric($_POST['expense_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid expense ID.']);
    exit();
}

$expenseId = intval($_POST['expense_id']);

try {
    $stmt = $pdo->prepare("SELECT evidence FROM operational_expenses WHERE id = ?");
    $stmt->execute([$expenseId]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$expense) {
        echo json_encode(['status' => 'error', 'message' => 'Expense record not found.']);
        exit();
    }

    if (!empty($expense['evidence'])) {
        $filePath = '../uploads/' . $expense['evidence'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $deleteStmt = $pdo->prepare("DELETE FROM operational_expenses WHERE id = ?");
    $deleteStmt->execute([$expenseId]);

    if ($deleteStmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Expense deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete expense.']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
exit();
?>