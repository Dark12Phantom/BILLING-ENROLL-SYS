<?php
require_once '../../../includes/auth.php';
protectPage();

if (!isset($_GET['student_id'])) {
    header("Location: index.php");
    exit();
}

$studentId = (int)$_GET['student_id'];

unset($_SESSION['ref_no']);
if (!isset($_SESSION['ref_no'])) {
    $_SESSION['ref_no'] = "REF-" . date("YmdHis") . rand(100, 999);
}

$referenceNumber = $_SESSION['ref_no'];

if (isset($_GET['cancel']) && $_GET['cancel'] == 1) {
    unset($_SESSION['ref_no']);
}

$stmt = $pdo->prepare("SELECT id, first_name, last_name FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT sf.id, f.name, sf.amount, sf.due_date 
                      FROM student_fees sf 
                      JOIN fees f ON sf.fee_id = f.id 
                      WHERE sf.student_id = ? AND sf.status = 'Pending'");
$stmt->execute([$studentId]);
$pendingFees = $stmt->fetchAll();

$receivedBy = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentData = [
        'student_id' => $studentId,
        'amount' => $_POST['total_amount'],
        'payment_date' => date('Y-m-d'),
        'payment_method' => $_POST['payment_method'],
        'reference_number' => $referenceNumber,
        'received_by' => $receivedBy
    ];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO payments (student_id, amount, payment_date, payment_method, reference_number, received_by) 
                              VALUES (:student_id, :amount, :payment_date, :payment_method, :reference_number, :received_by)");
        $stmt->execute($paymentData);
        $paymentId = $pdo->lastInsertId();

        foreach ($_POST['fee_ids'] as $feeId) {
            if (isset($_POST['pay_fee_' . $feeId])) {
                $amount = $_POST['amount_' . $feeId];

                $stmt = $pdo->prepare("INSERT INTO payment_items (payment_id, student_fee_id, amount) 
                                      VALUES (?, ?, ?)");
                $stmt->execute([$paymentId, $feeId, $amount]);

                $stmt = $pdo->prepare("UPDATE student_fees SET status = 'Paid' WHERE id = ?");
                $stmt->execute([$feeId]);
            }
        }

        $pdo->commit();

        $_SESSION['success'] = "Payment processed successfully!";
        header("Location: receipt.php?id=$paymentId");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error processing payment: " . $e->getMessage();
    }
}

require_once '../../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Pay Bill</h2>
        <hr>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Student: <?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="student_id" value="<?= $studentId ?>">

                    <h5 class="mb-3">Select Fees to Pay:</h5>

                    <?php if ($pendingFees): ?>
                        <div class="table-responsive mb-4">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="50px">Pay</th>
                                        <th>Fee</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingFees as $fee): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="pay_fee_<?= $fee['id'] ?>" id="fee_<?= $fee['id'] ?>"
                                                    class="form-check-input fee-checkbox" value="1"
                                                    data-amount="<?= $fee['amount'] ?>">
                                            </td>
                                            <td>
                                                <label for="fee_<?= $fee['id'] ?>"><?= htmlspecialchars($fee['name']) ?></label>
                                                <input type="hidden" name="fee_ids[]" value="<?= $fee['id'] ?>">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control fee-amount"
                                                    name="amount_<?= $fee['id'] ?>" value="<?= $fee['amount'] ?>" step="0.01" min="0">
                                            </td>
                                            <td><?= date('M d, Y', strtotime($fee['due_date'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Check">Check</option>
                                        <option value="Credit Card">Credit Card</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reference_number" class="form-label">Reference Number</label>
                                    <input type="text" class="form-control"
                                        id="reference_number_display"
                                        value="<?= htmlspecialchars($referenceNumber) ?>"
                                        disabled>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-light p-3 mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Total Amount Due: <span id="total-due">0.00</span></h5>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h5>Amount to Pay: ₱<span id="total-pay">0.00</span></h5>
                                    <input type="hidden" name="total_amount" id="total-amount" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="../view.php?id=<?= $studentId ?>>cancel=1" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Process Payment</button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No pending fees for this student.</div>
                        <div class="text-end">
                            <a href="../view.php?id=<?= $studentId ?>" class="btn btn-secondary">Back</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const feeCheckboxes = document.querySelectorAll('.fee-checkbox');
        const feeAmounts = document.querySelectorAll('.fee-amount');

        function calculateTotal() {
            let total = 0;

            feeCheckboxes.forEach((checkbox, index) => {
                if (checkbox.checked) {
                    const amount = parseFloat(feeAmounts[index].value) || 0;
                    total += amount;
                }
            });

            document.getElementById('total-pay').textContent = total.toFixed(2);
            document.getElementById('total-amount').value = total;

            let totalDue = 0;
            feeAmounts.forEach(input => {
                totalDue += parseFloat(input.value) || 0;
            });
            document.getElementById('total-due').textContent = '₱' + totalDue.toFixed(2);
        }

        feeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', calculateTotal);
        });

        feeAmounts.forEach(input => {
            input.addEventListener('input', calculateTotal);
        });

        calculateTotal();
    });
</script>

<?php require '../../../includes/footer.php'; ?>