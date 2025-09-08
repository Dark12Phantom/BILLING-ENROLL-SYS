<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

protectPage();

// Check if fee ID is provided
if (!isset($_GET['id'])) {
    header("Location: fees.php");
    exit();
}

$feeId = $_GET['id'];

// Fetch fee details
$stmt = $pdo->prepare("SELECT * FROM fees WHERE id = ?");
$stmt->execute([$feeId]);
$fee = $stmt->fetch();

if (!$fee) {
    header("Location: fees.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
    $frequency = $isRecurring ? $_POST['frequency'] : 'One-time';

    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Fee name is required.";
    }

    if ($amount <= 0) {
        $errors[] = "Amount must be greater than 0.";
    }

    // Check if fee name already exists (excluding current fee)
    $stmt = $pdo->prepare("SELECT id FROM fees WHERE name = ? AND id != ?");
    $stmt->execute([$name, $feeId]);
    if ($stmt->fetch()) {
        $errors[] = "A fee with this name already exists.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE fees SET 
                                 name = ?, 
                                 description = ?, 
                                 amount = ?, 
                                 is_recurring = ?, 
                                 frequency = ?
                                 WHERE id = ?");
            $stmt->execute([$name, $description, $amount, $isRecurring, $frequency, $feeId]);

            $_SESSION['success'] = "Fee type updated successfully!";
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
        <h2>Edit Fee Type</h2>
        <hr>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Fee Name</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?= htmlspecialchars($fee['name']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" 
                          rows="3"><?= htmlspecialchars($fee['description']) ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="amount" class="form-label">Amount</label>
                <input type="number" class="form-control" id="amount" name="amount" 
                       step="0.01" min="0" value="<?= htmlspecialchars($fee['amount']) ?>" required>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_recurring" 
                           name="is_recurring" <?= $fee['is_recurring'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_recurring">Recurring Fee</label>
                </div>
            </div>
            
            <div class="mb-3" id="frequency-container" 
                 style="display: <?= $fee['is_recurring'] ? 'block' : 'none' ?>;">
                <label for="frequency" class="form-label">Frequency</label>
                <select class="form-select" id="frequency" name="frequency">
                    <option value="Monthly" <?= $fee['frequency'] === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="Quarterly" <?= $fee['frequency'] === 'Quarterly' ? 'selected' : '' ?>>Quarterly</option>
                    <option value="Annual" <?= $fee['frequency'] === 'Annual' ? 'selected' : '' ?>>Annual</option>
                </select>
            </div>
            
            <div class="text-end">
                <a href="fees.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Fee</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const recurringCheckbox = document.getElementById('is_recurring');
    const frequencyContainer = document.getElementById('frequency-container');
    
    recurringCheckbox.addEventListener('change', function() {
        frequencyContainer.style.display = this.checked ? 'block' : 'none';
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>