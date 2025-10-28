<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

protectPage();

if (!isset($_GET['id'])) {
    die("No compliance record specified.");
}

$id = intval($_GET['id']);

// Fetch compliance record
$stmt = $pdo->prepare("SELECT * FROM compliance_expenses WHERE id = ?");
$stmt->execute([$id]);
$expense = $stmt->fetch();

if (!$expense) {
    die("Record not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type']);
    $amount = floatval($_POST['amount']);

    $errors = [];

    if (!in_array($type, ['SSS', 'Pag-IBIG', 'PhilHealth', 'Permit', 'Registration'])) {
        $errors[] = "Invalid expense type.";
    }

    if ($amount <= 0) {
        $errors[] = "Amount must be greater than 0.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE compliance_expenses SET type = ?, amount = ? WHERE id = ?");
        $stmt->execute([$type, $amount, $id]);

        $_SESSION['success'] = "Compliance record updated successfully!";
        header("Location: compliance.php");
        exit();
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>

<?php require_once '../../../includes/header.php'; ?>

<div class="container mt-4">
    <h4>Edit Compliance Record</h4>
    <form method="POST" class="mt-3">

        <!-- TYPE (Editable) -->
        <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <select class="form-select" id="type" name="type" required>
                <?php
                $types = ['SSS', 'Pag-IBIG', 'PhilHealth', 'Permit', 'Registration'];
                foreach ($types as $t) {
                    $selected = ($expense['type'] === $t) ? 'selected' : '';
                    echo "<option value='$t' $selected>$t</option>";
                }
                ?>
            </select>
        </div>

        <!-- AMOUNT (Editable) -->
        <div class="mb-3">
            <label for="amount" class="form-label">Amount</label>
            <input type="number" step="0.01" class="form-control" id="amount" name="amount"
                   value="<?= htmlspecialchars($expense['amount']) ?>" required>
        </div>

        <!-- PAYMENT DATE (Read-only) -->
        <div class="mb-3">
            <label class="form-label">Payment Date</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($expense['payment_date']) ?>" readonly>
        </div>

        <!-- REFERENCE NUMBER (Read-only) -->
        <div class="mb-3">
            <label class="form-label">Reference Number</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($expense['reference_number']) ?>" readonly>
        </div>

        <!-- PERIOD COVERED (Read-only) -->
        <div class="mb-3">
            <label class="form-label">Period Covered</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($expense['period_covered']) ?>" readonly>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="compliance.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once '../../../includes/footer.php'; ?>
