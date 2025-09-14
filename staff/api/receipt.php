<?php
require_once '../includes/staff-auth.php';
require_once '../includes/database.php';

protectPage();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$paymentId = $_GET['id'];
$debugMode = isset($_GET['debug']);

$stmt = $pdo->prepare("SELECT p.*, s.id as student_db_id, s.first_name, s.last_name, s.student_id 
                      FROM payments p 
                      JOIN students s ON p.student_id = s.id 
                      WHERE p.id = ?");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    header("Location: index.php");
    exit();
}

$studentDbId = $payment['student_db_id'];
$studentIdNumber = $payment['student_id'];

$stmt = $pdo->prepare("SELECT pi.amount, f.name, sf.status, sf.due_date, sf.id as student_fee_id
                        FROM payment_items pi
                        JOIN payments p ON pi.payment_id = p.id
                        JOIN student_fees sf ON pi.student_fee_id = sf.id
                        JOIN fees f ON sf.fee_id = f.id
                        WHERE pi.payment_id = ?
                        ORDER BY f.name");
$stmt->execute([$paymentId]);
$paymentItems = $stmt->fetchAll();

$totalPaid = array_sum(array_column($paymentItems, 'amount'));

$unpaidQuery = "SELECT sf.id, sf.amount, sf.due_date, f.name, sf.status
                FROM student_fees sf
                JOIN fees f ON sf.fee_id = f.id
                WHERE sf.student_id = ? 
                AND sf.id NOT IN (
                    SELECT student_fee_id 
                    FROM payment_items 
                    JOIN payments ON payment_items.payment_id = payments.id 
                    WHERE payments.student_id = ?
                )
                ORDER BY sf.due_date ASC";

$stmt = $pdo->prepare($unpaidQuery);
$stmt->execute([$studentDbId, $studentDbId]);
$unpaidFees = $stmt->fetchAll();

$remainingBalance = array_sum(array_column($unpaidFees, 'amount'));

$nextPayment = null;
$nextPaymentAmount = 0;
if (!empty($unpaidFees)) {
    $nextPayment = $unpaidFees[0];
    $nextPaymentAmount = $nextPayment['amount'];
}

$stmt = $pdo->prepare("SELECT COALESCE(SUM(pi.amount), 0) as total_paid_ever
                      FROM payment_items pi
                      JOIN payments p ON pi.payment_id = p.id
                      WHERE p.student_id = ?");
$stmt->execute([$studentDbId]);
$totalPaidEver = $stmt->fetch()['total_paid_ever'];

$schoolName = "Maranatha Christian Academy";
$schoolAddress = "1700 Ibarra st. corner Makiling st. Sampaloc, Manila, Manila, Philippines";
$schoolContact = "Phone: 287324098 | Email: admission@mca-manila.edu.ph";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - Maranatha Christian Academy</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .receipt-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .receipt-header {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .school-logo {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .receipt-body {
            padding: 30px;
        }

        .receipt-footer {
            background-color: #f5f5f5;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #ddd;
        }

        .divider {
            border-top: 2px dashed #ddd;
            margin: 25px 0;
        }

        .payment-details {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
        }

        .table th {
            border-top: none;
            border-bottom: 2px solid #dee2e6;
        }

        .badge-paid {
            background-color: #28a745;
            font-size: 0.9rem;
        }

        .badge-pending {
            background-color: #ffc107;
            font-size: 0.9rem;
            color: #212529;
        }

        .print-button {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            border: none;
            padding: 10px 20px;
            color: white;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .debug-section {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 14px;
        }

        .debug-toggle {
            cursor: pointer;
            color: #007bff;
            text-decoration: underline;
        }

        @media print {
            body {
                background-color: white;
            }

            .receipt-container {
                box-shadow: none;
                margin: 0;
                border-radius: 0;
            }

            .no-print {
                display: none !important;
            }

            .debug-section {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="school-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1><?= htmlspecialchars($schoolName) ?></h1>
            <p class="mb-0"><?= htmlspecialchars($schoolAddress) ?></p>
            <p class="mb-0"><?= htmlspecialchars($schoolContact) ?></p>
        </div>

        <div class="receipt-body">
            <div class="text-center mb-4">
                <h2 class="text-primary">OFFICIAL PAYMENT RECEIPT</h2>
                <p class="text-muted">Transaction #<?= $payment['id'] ?></p>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Student Information</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($payment['last_name']) ?>, <?= htmlspecialchars($payment['first_name']) ?></p>
                            <p class="mb-1"><strong>Student ID:</strong> <?= htmlspecialchars($studentIdNumber) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Payment Details</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Date:</strong> <?= date('F j, Y', strtotime($payment['payment_date'])) ?></p>
                            <p class="mb-1"><strong>Method:</strong> <?= htmlspecialchars($payment['payment_method']) ?></p>
                            <?php if (!empty($payment['reference_number'])): ?>
                                <p class="mb-0"><strong>Reference #:</strong> <?= htmlspecialchars($payment['reference_number']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <h4 class="mb-3">Payment Items</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Fee Description</th>
                            <th>Due Date</th>
                            <th class="text-end">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= date('M j, Y', strtotime($item['due_date'])) ?></td>
                                <td class="text-end">₱<?= number_format($item['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="2" class="text-end">Total Paid (This Payment):</th>
                            <th class="text-end">₱<?= number_format($totalPaid, 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="divider"></div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Payment Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Paid (This Payment):</span>
                                <strong class="text-success">₱<?= number_format($totalPaid, 2) ?></strong>
                            </div>
                            <?php if (!empty($unpaidFees)): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Next Payment Due:</span>
                                    <span><?= date('M j, Y', strtotime($nextPayment['due_date'])) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Next Payment Amount:</span>
                                    <span>₱<?= number_format($nextPaymentAmount, 2) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Next Payment:</span>
                                    <span class="text-success">No pending payments</span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Remaining Balance:</span>
                                <strong class="<?= $remainingBalance > 0 ? 'text-danger' : 'text-success' ?>">
                                    ₱<?= number_format($remainingBalance, 2) ?>
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total Paid to Date:</span>
                                <strong>₱<?= number_format($totalPaidEver, 2) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-md-6">
                    <?php if (!empty($unpaidFees)): ?>
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Unpaid Fees</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Fee</th>
                                                <th>Due Date</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($unpaidFees as $unpaid): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($unpaid['name']) ?></td>
                                                    <td><?= date('M j, Y', strtotime($unpaid['due_date'])) ?></td>
                                                    <td class="text-end">₱<?= number_format($unpaid['amount'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Unpaid Fees</h5>
                            </div>
                            <div class="card-body text-center">
                                <p class="text-success mb-0"><i class="fas fa-check-circle"></i> No unpaid fees</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <br>
                    <div class="col-md-12">
                        <p>Received By: ______________________________</p>
                        <p>Date: <span id="current-date" style="text-decoration: underline;"></span></p>
                        <script>
                            const dateDisplayElement = document.getElementById("current-date");

                            const today = new Date();

                            const options = {
                                year: 'numeric',
                                month: 'numeric',
                                day: 'numeric'
                            };
                            const formattedDate = today.toLocaleDateString('en-US', options);

                            dateDisplayElement.textContent = formattedDate;
                        </script>
                    </div>
                </div>
            </div>

            <div class="receipt-footer">
                <p class="mb-2">Thank you for your payment!</p>
                <p class="mb-3">This is an official receipt from <?= htmlspecialchars($schoolName) ?></p>

                <?php if (!empty($unpaidFees)): ?>
                    <div class="alert alert-warning mb-3">
                        <strong>Please note:</strong> You have unpaid fees amounting to ₱<?= number_format($remainingBalance, 2) ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-3">
                        <strong>All fees are fully paid. Thank you!</strong>
                    </div>
                <?php endif; ?>

                <div class="no-print">
                    <button onclick="window.print()" class="print-button me-2">
                        <i class="fas fa-print me-1"></i> Print Receipt
                    </button>
                    <a href="../htmls/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Payments
                    </a>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>