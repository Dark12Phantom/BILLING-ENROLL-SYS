<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';

protectPage();

// Check if payment ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$paymentId = $_GET['id'];

// Fetch payment details with student info
$stmt = $pdo->prepare("SELECT p.*, s.first_name, s.last_name, s.student_id, s.grade_level, s.section, 
                       u.username as received_by_name
                       FROM payments p 
                       JOIN students s ON p.student_id = s.id
                       JOIN users u ON p.received_by = u.id
                       WHERE p.id = ?");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    header("Location: index.php");
    exit();
}

// Fetch payment items (fees paid)
$stmt = $pdo->prepare("SELECT pi.amount, f.name, sf.due_date
                      FROM payment_items pi
                      LEFT JOIN student_fees sf ON pi.student_fee_id = sf.id
                      LEFT JOIN fees f ON sf.fee_id = f.id
                      WHERE pi.payment_id = ?");
$stmt->execute([$paymentId]);
$paymentItems = $stmt->fetchAll();

// Calculate total
$itemsTotal = array_sum(array_column($paymentItems, 'amount'));
$total = is_numeric($payment['amount']) ? (float)$payment['amount'] : (float)$itemsTotal;

require_once '../../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Payment Details</h2>
        <hr>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5>Payment #<?= $payment['id'] ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Student Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Name:</th>
                                <td><?= htmlspecialchars($payment['last_name']) ?>, <?= htmlspecialchars($payment['first_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Student ID:</th>
                                <td><?= htmlspecialchars($payment['student_id']) ?></td>
                            </tr>
                            <tr>
                                <th>Grade Level:</th>
                                <td><?= htmlspecialchars($payment['grade_level']) ?></td>
                            </tr>
                            <tr>
                                <th>Section:</th>
                                <td><?= htmlspecialchars($payment['section']) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Payment Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Date:</th>
                                <td><?= date('F j, Y', strtotime($payment['payment_date'])) ?></td>
                            </tr>
                            <tr>
                                <th>Payment Method:</th>
                                <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                            </tr>
                            <tr>
                                <th>Reference Number:</th>
                                <td><?= !empty($payment['reference_number']) ? htmlspecialchars($payment['reference_number']) : 'N/A' ?></td>
                            </tr>
                            <tr>
                                <th>Received By:</th>
                                <td><?= htmlspecialchars($payment['received_by_name']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <h5 class="mb-3">Payment Breakdown</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fee Type</th>
                                <th>Original Due Date</th>
                                <th>Amount Paid</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($paymentItems)): ?>
                                <?php foreach ($paymentItems as $item): ?>
                                    <tr>
                                        <td><?= isset($item['name']) && $item['name'] ? htmlspecialchars($item['name']) : 'N/A' ?></td>
                                        <td><?= isset($item['due_date']) && $item['due_date'] ? date('M d, Y', strtotime($item['due_date'])) : 'N/A' ?></td>
                                        <td>₱<?= number_format($item['amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>N/A</td>
                                    <td>N/A</td>
                                    <td>₱<?= number_format($total, 2) ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th></th>
                                <th class="text-end">Total Payment:</th>
                                <th>₱<?= number_format($total, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Payments
                    </a>
                    <div>
                        <a href="receipt.php?id=<?= $paymentId ?>" class="btn btn-primary" target="_blank">
                            <i class="fas fa-receipt"></i> View Receipt
                        </a>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <!-- <button class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#voidModal">
                                <i class="fas fa-ban"></i> Void Payment
                            </button> -->
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Void Payment Modal -->
<?php if ($_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="voidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Void Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="void-payment.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="payment_id" value="<?= $paymentId ?>">
                    <p>Are you sure you want to void this payment?</p>
                    <div class="mb-3">
                        <label for="void_reason" class="form-label">Reason for voiding:</label>
                        <textarea class="form-control" id="void_reason" name="void_reason" rows="3" required></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> Voiding a payment cannot be undone. This will:
                        <ul class="mb-0 mt-2">
                            <li>Mark all paid fees as unpaid</li>
                            <li>Record this void transaction</li>
                            <li>Require administrator approval</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Void</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require '../../../includes/footer.php'; ?>