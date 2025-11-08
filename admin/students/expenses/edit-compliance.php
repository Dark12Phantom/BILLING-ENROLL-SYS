<?php
require_once '../../../includes/auth.php';
protectPage();

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No compliance record specified.";
    header("Location: compliance.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch compliance record with user info
$stmt = $pdo->prepare("SELECT c.*, u.username as paid_by_name 
                       FROM compliance_expenses c 
                       JOIN users u ON c.paid_by = u.id 
                       WHERE c.id = ?");
$stmt->execute([$id]);
$expense = $stmt->fetch();

if (!$expense) {
    $_SESSION['error'] = "Record not found.";
    header("Location: compliance.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type']);
    $amount = floatval($_POST['amount']);
    $payment_date = trim($_POST['payment_date']);
    $period_covered = trim($_POST['period_covered']);

    $errors = [];

    if (!in_array($type, ['Social Security System', 'Pag-IBIG', 'PhilHealth', 'Permit', 'Registration'])) {
        $errors[] = "Invalid expense type.";
    }

    if ($amount <= 0) {
        $errors[] = "Amount must be greater than 0.";
    }

    if (empty($payment_date)) {
        $errors[] = "Payment date is required.";
    }

    if (empty($errors)) {
        // Generate new reference number if type changed
        $oldType = $expense['type'];
        $referenceNumber = $expense['reference_number'];
        
        if ($type !== $oldType) {
            $shortType = match($type) {
                'Social Security System' => 'SSS',
                'Pag-IBIG' => 'PGB',
                'PhilHealth' => 'PHL',
                'Permit' => 'PMT',
                'Registration' => 'REG',
                default => 'GEN'
            };
            
            $dateForRef = date('Ymd', strtotime($payment_date));
            
            // Get last reference number for this type and date
            $stmt = $pdo->prepare("SELECT reference_number FROM compliance_expenses 
                                  WHERE reference_number LIKE ? 
                                  ORDER BY id DESC LIMIT 1");
            $stmt->execute(["REF-$shortType-$dateForRef-%"]);
            $lastRef = $stmt->fetchColumn();
            
            if ($lastRef && preg_match("/^REF-$shortType-$dateForRef-(\d{6})$/", $lastRef, $m)) {
                $nextNum = str_pad($m[1] + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $nextNum = '000001';
            }
            
            $referenceNumber = "REF-$shortType-$dateForRef-$nextNum";
        }

        $stmt = $pdo->prepare("UPDATE compliance_expenses 
                              SET type = ?, amount = ?, payment_date = ?, 
                                  period_covered = ?, reference_number = ? 
                              WHERE id = ?");
        $stmt->execute([$type, $amount, $payment_date, $period_covered, $referenceNumber, $id]);

        $_SESSION['success'] = "Compliance record updated successfully!";
        header("Location: compliance.php");
        exit();
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

require_once '../../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Edit Compliance Expense</h2>
        <hr>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5>Update Compliance Payment Details</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="Social Security System" <?= $expense['type'] === 'Social Security System' ? 'selected' : '' ?>>SSS</option>
                                    <option value="Pag-IBIG" <?= $expense['type'] === 'Pag-IBIG' ? 'selected' : '' ?>>Pag-IBIG</option>
                                    <option value="PhilHealth" <?= $expense['type'] === 'PhilHealth' ? 'selected' : '' ?>>PhilHealth</option>
                                    <option value="Permit" <?= $expense['type'] === 'Permit' ? 'selected' : '' ?>>Permit</option>
                                    <option value="Registration" <?= $expense['type'] === 'Registration' ? 'selected' : '' ?>>Registration</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0" value="<?= htmlspecialchars($expense['amount']) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                       value="<?= htmlspecialchars($expense['payment_date']) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="period_covered" class="form-label">Period Covered</label>
                                <input type="text" class="form-control" id="period_covered" name="period_covered" 
                                       value="<?= htmlspecialchars($expense['period_covered']) ?>" 
                                       placeholder="e.g., January 2024">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number"
                                       value="<?= htmlspecialchars($expense['reference_number']) ?>" readonly>
                                <small class="text-muted">Will be auto-generated if type changes</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Paid By</label>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($expense['paid_by_name']) ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <a href="compliance.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Compliance
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const paymentDateInput = document.getElementById('payment_date');
    const refInput = document.getElementById('reference_number');
    const originalType = '<?= htmlspecialchars($expense['type']) ?>';
    const originalRef = '<?= htmlspecialchars($expense['reference_number']) ?>';
    
    function updateReference() {
        const type = typeSelect.value;
        const dateValue = paymentDateInput.value;
        
        if (!dateValue) return;
        
        // Only update if type changed
        if (type === originalType) {
            refInput.value = originalRef;
            return;
        }
        
        const date = new Date(dateValue);
        const ymd = date.getFullYear().toString() +
            String(date.getMonth() + 1).padStart(2, '0') +
            String(date.getDate()).padStart(2, '0');
        
        let shortType;
        switch (type) {
            case 'Social Security System':
                shortType = 'SSS';
                break;
            case 'Pag-IBIG':
                shortType = 'PGB';
                break;
            case 'PhilHealth':
                shortType = 'PHL';
                break;
            case 'Permit':
                shortType = 'PMT';
                break;
            case 'Registration':
                shortType = 'REG';
                break;
            default:
                shortType = 'GEN';
                break;
        }
        
        refInput.value = `REF-${shortType}-${ymd}-XXXXXX`;
    }
    
    typeSelect.addEventListener('change', function() {
        updateReference();
        if (this.value !== originalType) {
            alert('Note: A new reference number will be generated when you save.');
        }
    });
    
    paymentDateInput.addEventListener('change', function() {
        if (typeSelect.value !== originalType) {
            updateReference();
        }
    });
});
</script>

<?php require '../../../includes/footer.php'; ?>