<?php
require_once '../includes/staff-auth.php';
protectPage();

$stmt = $pdo->query("SELECT * FROM fees ORDER BY name");
$feeTypes = $stmt->fetchAll();

$query = "SELECT sf.*, f.name as fee_name, s.first_name, s.last_name, s.student_id 
          FROM student_fees sf 
          JOIN fees f ON sf.fee_id = f.id 
          JOIN students s ON sf.student_id = s.id 
          ORDER BY sf.due_date DESC";
$stmt = $pdo->query($query);
$studentFees = $stmt->fetchAll();

require_once '../includes/staff-header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>View Assigned Fees</h2>
        <hr>
        <div class="tab-content" id="feeTabsContent">
            <div class="tab-pane fade show active" id="assigned" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Assigned Fees</h5>
                            </div>

                            <div class="col-md-6 text-end">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignFeeModal">
                                    <i class="fas fa-plus"></i> Assign Fee
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Fee</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($studentFees as $fee): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($fee['last_name']) ?>, <?= htmlspecialchars($fee['first_name']) ?> (<?= htmlspecialchars($fee['student_id']) ?>)</td>
                                            <td><?= htmlspecialchars($fee['fee_name']) ?></td>
                                            <td>₱<?= number_format($fee['amount'], 2) ?></td>
                                            <td><?= date('M d, Y', strtotime($fee['due_date'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?=
                                                                        $fee['status'] == 'Paid' ? 'success' : ($fee['status'] == 'Overdue' ? 'danger' : 'warning')
                                                                        ?>">
                                                    <?= htmlspecialchars($fee['status']) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <a href="edit-assigned-fee.php?id=<?= $fee['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                                <a href="delete-assigned-fee.php?id=<?= $fee['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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
    </div>
</div>

<!-- Assign Fee Modal -->
<div class="modal fade" id="assignFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Fee to Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="assign-fee.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="student" class="form-label">Student</label>
                        <select class="form-select" id="student" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php
                            $stmt = $pdo->query("SELECT id, first_name, last_name, student_id FROM students ORDER BY last_name, first_name");
                            while ($student = $stmt->fetch()): ?>
                                <option value="<?= $student['id'] ?>">
                                    <?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?> (<?= htmlspecialchars($student['student_id']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="fee_id" class="form-label">Fee Type</label>
                        <select class="form-select" id="fee_id" name="fee_id" required>
                            <option value="">Select Fee</option>
                            <?php foreach ($feeTypes as $fee): ?>
                                <option value="<?= $fee['id'] ?>">
                                    <?= htmlspecialchars($fee['name']) ?> (₱<?= number_format($fee['amount'], 2) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" required>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Discount(s)</label>
                            <div class="form-check">
                                <input class="form-check-input discount-checkbox" name="discounts[]" type="checkbox" value="referral" id="discount_referral">
                                <label class="form-check-label" for="discount_referral">Referral Discount (₱500.00)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input discount-checkbox" name="discounts[]" type="checkbox" value="earlybird" id="discount_earlybird">
                                <label class="form-check-label" for="discount_earlybird">Earlybird Discount (₱500.00)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input discount-checkbox" name="discounts[]" type="checkbox" value="sibling" id="discount_sibling">
                                <label class="form-check-label" for="discount_sibling">Sibling Discount (₱500.00)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input discount-checkbox" name="discounts[]" type="checkbox" value="fullpayment" id="discount_fullpayment">
                                <label class="form-check-label" for="discount_fullpayment">Full Payment Discount (₱1,000.00)</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Original Amount:</small><br>
                                        <span id="original_amount" class="fw-bold">₱0.00</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Discount:</small><br>
                                        <span id="discount_amount" class="text-success">-₱0.00</span>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="text-center">
                                    <small class="text-muted">Final Amount:</small><br>
                                    <span id="final_amount" class="h5 text-primary">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign Fee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const feeSelect = document.getElementById('fee_id');
        const discountCheckboxes = document.querySelectorAll('.discount-checkbox');
        const originalAmountSpan = document.getElementById('original_amount');
        const discountAmountSpan = document.getElementById('discount_amount');
        const finalAmountSpan = document.getElementById('final_amount');

        let originalAmount = 0;

        const discountMap = {
            referral: 500,
            earlybird: 500,
            sibling: 500,
            fullpayment: 1000
        };

        feeSelect.addEventListener('change', function() {
            if (this.value) {
                const selectedOption = this.options[this.selectedIndex];
                const amountMatch = selectedOption.text.match(/₱([\d,]+\.\d{2})/);
                if (amountMatch) {
                    originalAmount = parseFloat(amountMatch[1].replace(/,/g, ''));
                    originalAmountSpan.textContent = '₱' + originalAmount.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    calculateDiscount();
                }
            } else {
                originalAmount = 0;
                originalAmountSpan.textContent = '₱0.00';
                calculateDiscount();
            }
        });

        discountCheckboxes.forEach(cb => {
            cb.addEventListener('change', calculateDiscount);
        });

        function calculateDiscount() {
            let discountAmount = 0;

            discountCheckboxes.forEach(cb => {
                if (cb.checked && discountMap[cb.value]) {
                    discountAmount += discountMap[cb.value];
                }
            });

            if (discountAmount > originalAmount) {
                discountAmount = originalAmount;
            }

            const finalAmount = originalAmount - discountAmount;

            discountAmountSpan.textContent = '-₱' + discountAmount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            finalAmountSpan.textContent = '₱' + finalAmount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            if (discountAmount > 0) {
                discountAmountSpan.className = 'text-success fw-bold';
                finalAmountSpan.className = 'h5 text-success';
            } else {
                discountAmountSpan.className = 'text-muted';
                finalAmountSpan.className = 'h5 text-primary';
            }
        }

        calculateDiscount();
    });
</script>

<?php require_once '../includes/staff-footer.php'; ?>