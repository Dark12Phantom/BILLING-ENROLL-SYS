<?php
require_once '../../includes/auth.php';
protectPage();

require_once '../../includes/header.php';

// Get student ID from query string if exists
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// Fetch payments
$query = "SELECT p.*, s.first_name, s.last_name FROM payments p 
          JOIN students s ON p.student_id = s.id";
$params = [];

if ($studentId > 0) {
    $query .= " WHERE p.student_id = ?";
    $params[] = $studentId;
}

$query .= " ORDER BY p.payment_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Fetch students for filter dropdown
$stmt = $pdo->query("SELECT id, first_name, last_name FROM students ORDER BY last_name");
$students = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Payment History</h2>
        <hr>
        
        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5>All Payments</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <form class="form-inline">
                            <div class="input-group">
                                <select class="form-select" name="student_id" onchange="this.form.submit()">
                                    <option value="">All Students</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>" <?php echo $studentId == $student['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($student['last_name']) . ', ' . htmlspecialchars($student['first_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="pay-bill.php" class="btn btn-primary ms-2">New Payment</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Student</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['last_name'] . ', ' . $payment['first_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                                    <td>
                                        <a href="view-payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-info">View</a>
                                        <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-secondary">Receipt</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>