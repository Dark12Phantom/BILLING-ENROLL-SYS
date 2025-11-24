<?php
require_once '../includes/staff-auth.php';
require_once '../includes/database.php';

protectPage();

$search = $_GET['search'] ?? '';
$studentId = $_GET['student_id'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Base query
$query = "SELECT p.*, s.first_name, s.last_name, s.student_id 
          FROM payments p 
          JOIN students s ON p.student_id = s.id 
          WHERE 1=1";
$params = [];

// Filters
if (!empty($search)) {
    $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR p.reference_number LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if (!empty($studentId)) {
    $query .= " AND p.student_id = ?";
    $params[] = $studentId;
}
if (!empty($startDate)) {
    $query .= " AND p.payment_date >= ?";
    $params[] = $startDate;
}
if (!empty($endDate)) {
    $query .= " AND p.payment_date <= ?";
    $params[] = $endDate;
}

$query .= " ORDER BY p.payment_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Supporting queries
$students = $pdo->query("SELECT id, first_name, last_name, student_id FROM students ORDER BY last_name")->fetchAll();
$totalQuery = "SELECT SUM(amount) FROM payments";
$totalAmount = $pdo->query($totalQuery)->fetchColumn();

require_once '../includes/staff-header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Payment Records</h2>
        <hr>

        <!-- Filter & Search Bar -->
        <div class="mb-3">
            <form class="form-inline d-flex justify-content-between align-items-center" method="get">
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <!-- Student dropdown -->
                    <select class="form-select" name="student_id" style="width: 300px;" onchange="this.form.submit()">
                        <option value="">All Students</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>" <?= $studentId == $student['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Date range -->
                    <div class="d-flex gap-2" style="position: relative;">
                        <p style="position: absolute;  top: -20px; left: 40%; font-size: 0.8em;">Date Range</p>
                        <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($startDate) ?>" onchange="this.form.submit()">
                        <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($endDate) ?>" onchange="this.form.submit()">
                    </div>

                    <!-- Reset button -->
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                </div>

            </form>
        </div>

        <!-- Payment Table -->
        <div class="card mb-4">
            <div class="card-body">
                <?php if (!empty($startDate) || !empty($endDate)): ?>
                    <div class="alert alert-info mb-3">
                        Showing payments from
                        <strong><?= $startDate ? date('M d, Y', strtotime($startDate)) : 'the beginning' ?></strong>
                        to
                        <strong><?= $endDate ? date('M d, Y', strtotime($endDate)) : 'now' ?></strong>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No payments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payment['id']) ?></td>
                                        <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                        <td>
                                            <?= htmlspecialchars($payment['last_name']) ?>, <?= htmlspecialchars($payment['first_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($payment['student_id']) ?></small>
                                        </td>
                                        <td>₱<?= number_format($payment['amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                                        <td><?= !empty($payment['reference_number']) ? htmlspecialchars($payment['reference_number']) : 'N/A' ?></td>
                                        <td>
                                            <a href="view-payment.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="receipt.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-secondary" title="Print Receipt">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Total (Displayed):</td>
                                <td><strong>₱<?= number_format(array_sum(array_column($payments, 'amount')), 2) ?></strong></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <span><strong>System Total:</strong> ₱<?= number_format($totalAmount ?? 0, 2) ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/staff-footer.php'; ?>