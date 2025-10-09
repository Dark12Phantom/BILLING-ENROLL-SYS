<?php
require_once '../../../includes/auth.php';
protectPage();

// Fetch compliance expenses
$stmt = $pdo->query("SELECT c.*, u.username as paid_by_name 
                     FROM compliance_expenses c 
                     JOIN users u ON c.paid_by = u.id 
                     ORDER BY c.payment_date DESC");
$expenses = $stmt->fetchAll();

// Calculate totals by type
$stmt = $pdo->query("SELECT type, SUM(amount) as total 
                     FROM compliance_expenses 
                     GROUP BY type");
$totals = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../../../includes/header.php';
?>

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
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($expense['payment_date'])) ?></td>
                                    <td><?= htmlspecialchars($expense['type']) ?></td>
                                    <td>₱<?= number_format($expense['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($expense['reference_number']) ?></td>
                                    <td><?= htmlspecialchars($expense['period_covered']) ?></td>
                                    <td><?= htmlspecialchars($expense['paid_by_name']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-compliance" data-id="<?= $expense['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="delete-compliance.php?id=<?= $expense['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
                            <option value="SSS">SSS</option>
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
                        <input type="text" class="form-control" id="reference_number" name="reference_number">
                    </div>
                    <div class="mb-3">
                        <label for="period_covered" class="form-label">Period Covered</label>
                        <input type="text" class="form-control" id="period_covered" name="period_covered">
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
    // Set payment date to today by default
    document.getElementById('payment_date').valueAsDate = new Date();
});
</script>

<?php require '../../../includes/footer.php'; ?>