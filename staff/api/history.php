<?php
require_once '../includes/staff-auth.php';
require_once '../includes/database.php';

protectPage();

$search = $_GET['search'] ?? '';
$studentId = $_GET['student_id'] ?? null;
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$query = "SELECT p.*, s.first_name, s.last_name, s.student_id 
          FROM payments p 
          JOIN students s ON p.student_id = s.id 
          WHERE 1=1";
$params = [];

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

$students = $pdo->query("SELECT id, first_name, last_name, student_id FROM students ORDER BY last_name")->fetchAll();

$totalQuery = "SELECT SUM(amount) FROM payments";
$totalAmount = $pdo->query($totalQuery)->fetchColumn();

require_once '../includes/staff-header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Payment History</h2>
            <hr>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="m-0 font-weight-bold text-primary">All Payments</h6>
                </div>
                <div class="col-md-6">
                    <form class="form-inline float-end">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search..." 
                                   value="<?= htmlspecialchars($search) ?>">
                            <select class="form-select ms-2" name="student_id" style="width: 180px;">
                                <option value="">All Students</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>" 
                                        <?= $studentId == $student['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" class="form-control ms-2" name="start_date" 
                                   value="<?= htmlspecialchars($startDate) ?>" placeholder="From Date">
                            <button type="submit" class="btn btn-primary ms-2">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <?php if (!empty($startDate) || !empty($endDate)): ?>
                <div class="alert alert-info mb-3">
                    Showing payments from <strong><?= !empty($startDate) ? date('M d, Y', strtotime($startDate)) : 'the beginning' ?></strong>
                    to <strong><?= !empty($endDate) ? date('M d, Y', strtotime($endDate)) : 'now' ?></strong>
                    <a href="index.php" class="float-end">Clear filters</a>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead class="thead-light">
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
                                <td colspan="7" class="text-center py-4">No payments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= $payment['id'] ?></td>
                                    <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td>
                                        <?= htmlspecialchars($payment['last_name']) ?>, <?= htmlspecialchars($payment['first_name']) ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($payment['student_id']) ?></small>
                                    </td>
                                    <td>₱<?= number_format($payment['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                                    <td><?= !empty($payment['reference_number']) ? htmlspecialchars($payment['reference_number']) : 'N/A' ?></td>
                                    <td>
                                        <a href="view-payment.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-info" title="View Details">
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
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong>₱<?= number_format(array_sum(array_column($payments, 'amount')), 2) ?></strong></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <strong>System Total:</strong> ₱<?= number_format($totalAmount ?? 0, 2) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/staff-footer.php'; ?>