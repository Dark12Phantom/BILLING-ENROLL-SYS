<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

protectPage();

// Check if assigned fee ID is provided
if (!isset($_GET['id'])) {
    header("Location: fees.php");
    exit();
}

$assignedFeeId = $_GET['id'];

// Fetch assigned fee details with student and fee info
$stmt = $pdo->prepare("SELECT sf.*, s.first_name, s.last_name, s.student_id, f.name as fee_name 
                       FROM student_fees sf
                       JOIN students s ON sf.student_id = s.id
                       JOIN fees f ON sf.fee_id = f.id
                       WHERE sf.id = ?");
$stmt->execute([$assignedFeeId]);
$assignedFee = $stmt->fetch();

if (!$assignedFee) {
    header("Location: fees.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $dueDate = $_POST['due_date'];
    $status = $_POST['status'];

    // Validation
    $errors = [];
    
    if ($amount <= 0) {
        $errors[] = "Amount must be greater than 0.";
    }

    if (empty($dueDate) || !strtotime($dueDate)) {
        $errors[] = "Valid due date is required.";
    }

    if (!in_array($status, ['Pending', 'Paid', 'Overdue'])) {
        $errors[] = "Invalid status selected.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE student_fees SET 
                                 amount = ?, 
                                 due_date = ?, 
                                 status = ?
                                 WHERE id = ?");
            $stmt->execute([$amount, $dueDate, $status, $assignedFeeId]);

            $_SESSION['success'] = "Assigned fee updated successfully!";
            header("Location: fees.php");
            exit();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Edit Assigned Fee</h2>
        <hr>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Student</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($assignedFee['last_name']) ?>, <?= htmlspecialchars($assignedFee['first_name']) ?> (<?= htmlspecialchars($assignedFee['student_id']) ?>)" 
                                   readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fee Type</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($assignedFee['fee_name']) ?>" 
                                   readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   step="0.01" min="0" value="<?= htmlspecialchars($assignedFee['amount']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                   value="<?= htmlspecialchars($assignedFee['due_date']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Pending" <?= $assignedFee['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Paid" <?= $assignedFee['status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="Overdue" <?= $assignedFee['status'] === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="fees.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>