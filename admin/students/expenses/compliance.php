<?php
require_once '../../../includes/auth.php';
protectPage();

$stmt = $pdo->query("SELECT c.*, 
                     CASE 
                         WHEN c.paid_by = 0 THEN 'System'
                         ELSE u.username 
                     END as paid_by_name 
                     FROM compliance_expenses c 
                     LEFT JOIN users u ON c.paid_by = u.id 
                     ORDER BY c.payment_date DESC");
$expenses = $stmt->fetchAll();

$stmt = $pdo->query("SELECT type, SUM(amount) as total 
                     FROM compliance_expenses 
                     GROUP BY type");
$totals = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->query("SELECT reference_number FROM payments ORDER BY id DESC LIMIT 1");
$lastRef = $stmt->fetchColumn();

$today = date('Ymd');

if ($lastRef && preg_match("/^REF-$today-(\d{6})$/", $lastRef, $m)) {
    $nextNum = str_pad($m[1] + 1, 6, '0', STR_PAD_LEFT);
} else {
    $nextNum = '000001';
}

$newRef = "REF-$today-$nextNum";

require_once '../../../includes/header.php';
?>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle"></i> Success
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="successMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Error
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="errorMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h2>Compliance Expenses</h2>
        <hr>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Government Contributions</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addComplianceModal">
                            <i class="fas fa-plus"></i> Add Compliance Payment
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Reference Number</th>
                                <th>Period Covered</th>
                                <th>Paid By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">No compliance expenses recorded yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($expense['payment_date'])) ?></td>
                                        <td><?= htmlspecialchars($expense['type']) ?></td>
                                        <td>₱<?= number_format($expense['amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($expense['reference_number']) ?></td>
                                        <td><?= htmlspecialchars($expense['period_covered']) ?></td>
                                        <td><?= htmlspecialchars($expense['paid_by_name']) ?></td>
                                        <td>
                                            <?php if (!empty($expense['id'])): ?>
                                                <a href="./view-receipt.php?id=<?= htmlspecialchars($expense['id']) ?>&type=compliance" target="_blank" class="btn btn-sm btn-info">
                                                    View
                                                </a>
                                                <a href="./edit-compliance.php?id=<?= htmlspecialchars($expense['id']) ?>&type=compliance" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <h5>Totals by Type:</h5>
                    <div class="row">
                        <?php foreach ($totals as $type => $total): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6><?= htmlspecialchars($type) ?></h6>
                                        <h4>₱<?= number_format($total, 2) ?></h4>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Compliance Modal -->
<div class="modal fade" id="addComplianceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Compliance Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="add-compliance.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="Social Security System">SSS</option>
                            <option value="Pag-IBIG">Pag-IBIG</option>
                            <option value="PhilHealth">PhilHealth</option>
                            <option value="Permit">Permit</option>
                            <option value="Registration">Registration</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number"
                            value="<?= htmlspecialchars($newRef) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="period_covered" class="form-label">Period Covered</label>
                        <input type="text" class="form-control" id="period_covered" name="period_covered">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" value="1">
                            <label class="form-check-label" for="is_recurring">
                                Recurring Payment
                            </label>
                        </div>
                    </div>
                    <div id="recurring_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="frequency" class="form-label">Frequency</label>
                            <select class="form-select" id="frequency" name="frequency">
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="semi-annual">Semi-Annual</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="next_due_date" class="form-label">Next Due Date</label>
                            <input type="date" class="form-control" id="next_due_date" name="next_due_date">
                        </div>
                    </div>
                    <input type="hidden" name="paid_by" value="<?= $_SESSION['user_id'] ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show success or error modal if session messages exist
        <?php if (isset($_SESSION['success'])): ?>
            document.getElementById('successMessage').textContent = <?= json_encode($_SESSION['success']) ?>;
            new bootstrap.Modal(document.getElementById('successModal')).show();
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            document.getElementById('errorMessage').innerHTML = <?= json_encode($_SESSION['error']) ?>;
            new bootstrap.Modal(document.getElementById('errorModal')).show();
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        document.getElementById('payment_date').valueAsDate = new Date();

        const typeSelect = document.getElementById('type');
        const refInput = document.getElementById('reference_number');
        const isRecurringCheckbox = document.getElementById('is_recurring');
        const recurringFields = document.getElementById('recurring_fields');
        const frequencySelect = document.getElementById('frequency');
        const nextDueDateInput = document.getElementById('next_due_date');
        const paymentDateInput = document.getElementById('payment_date');

        function updateReference() {
            const today = new Date();
            const ymd = today.getFullYear().toString() +
                String(today.getMonth() + 1).padStart(2, '0') +
                String(today.getDate()).padStart(2, '0');

            const type = typeSelect.value;
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

            refInput.value = `REF-${shortType}-${ymd}-000001`;
        }

        function calculateNextDueDate() {
            const paymentDate = new Date(paymentDateInput.value);
            if (!paymentDate || isNaN(paymentDate)) return;

            const frequency = frequencySelect.value;
            let nextDate = new Date(paymentDate);

            switch (frequency) {
                case 'weekly':
                    nextDate.setDate(nextDate.getDate() + 7);
                    break;
                case 'monthly':
                    nextDate.setMonth(nextDate.getMonth() + 1);
                    break;
                case 'quarterly':
                    nextDate.setMonth(nextDate.getMonth() + 3);
                    break;
                case 'semi-annual':
                    nextDate.setMonth(nextDate.getMonth() + 6);
                    break;
                case 'annual':
                case 'yearly':
                    nextDate.setFullYear(nextDate.getFullYear() + 1);
                    break;
            }

            const year = nextDate.getFullYear();
            const month = String(nextDate.getMonth() + 1).padStart(2, '0');
            const day = String(nextDate.getDate()).padStart(2, '0');
            nextDueDateInput.value = `${year}-${month}-${day}`;
        }

        isRecurringCheckbox.addEventListener('change', function() {
            if (this.checked) {
                recurringFields.style.display = 'block';
                frequencySelect.required = true;
                nextDueDateInput.required = true;
                calculateNextDueDate();
            } else {
                recurringFields.style.display = 'none';
                frequencySelect.required = false;
                nextDueDateInput.required = false;
            }
        });

        typeSelect.addEventListener('change', updateReference);
        frequencySelect.addEventListener('change', calculateNextDueDate);
        paymentDateInput.addEventListener('change', calculateNextDueDate);

        updateReference();
    });
</script>

<?php require '../../../includes/footer.php'; ?>