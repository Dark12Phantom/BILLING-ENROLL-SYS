<?php
require_once '../../../includes/auth.php';
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

require_once '../../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Fee Management</h2>
        <hr>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="feeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="types-tab" data-bs-toggle="tab" data-bs-target="#types" type="button">Fee Types</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assigned-tab" data-bs-toggle="tab" data-bs-target="#assigned" type="button">Assigned Fees</button>
            </li>
        </ul>

        <div class="tab-content" id="feeTabsContent">

            <!-- Fee Types Tab -->
            <div class="tab-pane fade show active" id="types" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Fee Types</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeModal">
                            <i class="fas fa-plus"></i> Add Fee Type
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Recurring</th>
                                        <th>Frequency</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($feeTypes as $fee): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($fee['name']) ?></td>
                                            <td><?= htmlspecialchars($fee['description']) ?></td>
                                            <td>₱<?= number_format($fee['amount'], 2) ?></td>
                                            <td><?= $fee['is_recurring'] ? 'Yes' : 'No' ?></td>
                                            <td><?= htmlspecialchars($fee['frequency']) ?></td>
                                            <td class="text-nowrap">
                                                <a href="edit-fee.php?id=<?= $fee['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                                <a href="delete-fee.php?id=<?= $fee['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (empty($feeTypes)): ?>
                                <p class="text-center text-muted mt-3">No fee types found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Fees Tab -->
            <div class="tab-pane fade" id="assigned" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Assigned Student Fees</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignFeeModal">
                            <i class="fas fa-plus"></i> Assign Fee
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-light">
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
                                            <td><?= htmlspecialchars($fee['last_name']) ?>, <?= htmlspecialchars($fee['first_name']) ?> <small class="text-muted">(<?= htmlspecialchars($fee['student_id']) ?>)</small></td>
                                            <td><?= htmlspecialchars($fee['fee_name']) ?></td>
                                            <td>₱<?= number_format($fee['amount'], 2) ?></td>
                                            <td><?= date('M d, Y', strtotime($fee['due_date'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $fee['status'] == 'Paid' ? 'success' : ($fee['status'] == 'Overdue' ? 'danger' : 'warning') ?>">
                                                    <?= htmlspecialchars($fee['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <a href="edit-assigned-fee.php?id=<?= $fee['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                                <a href="delete-assigned-fee.php?id=<?= $fee['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if (empty($studentFees)): ?>
                                    <p class="text-center text-muted mt-3">No assigned fees found.</p>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

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
        const checkbox = document.getElementById("is_recurring");
        const frequencyContainer = document.getElementById("frequency-container");

        checkbox.addEventListener("change", function() {
            frequencyContainer.style.display = this.checked ? "block" : "none";
        });

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

<?php require '../../../includes/footer.php'; ?>