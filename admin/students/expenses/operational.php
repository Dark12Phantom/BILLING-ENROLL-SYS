<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

protectPage();

// Fetch operational expenses
$stmt = $pdo->query("SELECT e.*, u.username as approved_by_name 
                     FROM operational_expenses e 
                     JOIN users u ON e.approved_by = u.id 
                     ORDER BY e.date_incurred DESC");
$expenses = $stmt->fetchAll();

// Calculate total
$stmt = $pdo->query("SELECT SUM(amount) FROM operational_expenses");
$total = $stmt->fetchColumn();

$incomeStmt = $pdo->query("SELECT SUM(amount) AS total_income FROM payment_items");
$totalIncome = $incomeStmt -> fetchColumn() ?: 0;

$compStmt = $pdo->query("SELECT SUM(amount) AS total_compliance FROM compliance_expenses");
$totalComp = $compStmt -> fetchColumn() ?: 0;

$opStmt = $pdo->query("SELECT SUM(amount) AS total_operational FROM operational_expenses");
$totalOp = $opStmt -> fetchColumn() ?: 0;

$netProfit = $totalIncome - ($totalComp + $totalOp);

require_once '../../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Operational Expenses</h2>
            <hr>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h5>Expense Tracking</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="fas fa-plus"></i> Add Expense
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Approved By</th>
                            <th>Evidence</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No expenses recorded yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($expense['date_incurred'])) ?></td>
                                    <td><?= htmlspecialchars($expense['category']) ?></td>
                                    <td><?= htmlspecialchars($expense['particular']) ?></td>
                                    <td>₱<?= number_format($expense['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($expense['approved_by_name']) ?></td>
                                    <td>
                                        <?php if (!empty($expense['id'])): ?>
                                            <a href="./view-receipt.php?id=<?= htmlspecialchars($expense['id']) ?>&type=operational" target="_blank" class="btn btn-sm btn-info">
                                                View
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="delete-expense.php?id=<?= $expense['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th colspan="3">Total Operational Expenses:</th>
                            <th>₱<?= number_format($total ?? 0, 2) ?></th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="p-4 row">
            <div class="col-sm-4 mb-3 mb-sm-0">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Income From Students</h5>
                        <p class="card-text">₱<?= number_format($totalIncome, 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Compliance Expenses</h5>
                        <p class="card-text">₱<?= number_format($totalComp, 2) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Operational Expenses</h5>
                        <p class="card-text">₱<?= number_format($totalOp, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 row">
            <div class="col-sm-4 mb-3 mb-sm-0">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Net Profit</h5>
                        <p class="card-text">₱<?= number_format($netProfit, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add New Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="add-expense.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Electricity">Electricity</option>
                                <option value="Water Bill">Water Bill</option>
                                <option value="Transportation">Transportation</option>
                                <option value="Internet Bill">Internet Bill</option>
                                <option value="Office Supplies">Office Supplies</option>
                                <option value="Faculty Training">Faculty Training</option>
                                <option value="Salaries">Salaries</option>
                                <option value="Repairs and Maintenance">Repairs and Maintenance</option>
                                <option value="Incentive">Incentive</option>
                                <option value="Medical">Medical</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_incurred" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date_incurred" name="date_incurred" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="particular" class="form-label">Description</label>
                        <input type="text" class="form-control" id="particular" name="particular" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="evidence" class="form-label">Evidence (Receipt/Invoice)</label>
                            <input type="file" class="form-control" id="evidence" name="evidence" accept=".jpg,.jpeg,.png,.pdf">
                            <small class="text-muted">Accepted formats: JPG, PNG, PDF (Max 2MB)</small>
                        </div>
                    </div>
                    <input type="hidden" name="approved_by" value="<?= $_SESSION['user_id'] ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('date_incurred').valueAsDate = new Date();
    });
</script>

<?php require '../../../includes/footer.php'; ?>