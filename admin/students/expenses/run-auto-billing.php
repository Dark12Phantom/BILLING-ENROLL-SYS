<?php
require_once '../../../includes/db.php';
header('Content-Type: application/json');

$today = date('Y-m-d');

try {
    // --- OPERATIONAL BILLING ---
    $opbills = $pdo->query("
        SELECT * FROM billing_schedule
        WHERE category='Operational' AND status='active' AND next_due_date <= '$today'
    ")->fetchAll();

    foreach ($opbills as $bill) {
        // Insert into operational_expenses
        $stmt = $pdo->prepare("
            INSERT INTO operational_expenses (category, particular, amount, evidence, date_incurred, approved_by)
            VALUES (:category, :particular, :amount, :evidence, :date_incurred, :approved_by)
        ");
        $stmt->execute([
            ':category' => $bill['category'],
            ':particular' => $bill['expense_name'],
            ':amount' => $bill['amount'],
            ':evidence' => 'Auto-Billed',
            ':date_incurred' => $today,
            ':approved_by' => 0 // ensure foreign key constraint allows 0 or make nullable
        ]);

        $expense_id = $pdo->lastInsertId();
        $receipt_no = sprintf("REF-%s-%04d", date('Ymd'), $expense_id);

        // Insert receipt
        $receipt_stmt = $pdo->prepare("
            INSERT INTO receipts (expense_id, receipt_no, date_issued, amount, description)
            VALUES (:expense_id, :receipt_no, :date_issued, :amount, :description)
        ");
        $receipt_stmt->execute([
            ':expense_id' => $expense_id,
            ':receipt_no' => $receipt_no,
            ':date_issued' => $today,
            ':amount' => $bill['amount'],
            ':description' => $bill['expense_name']
        ]);

        // Update next due date
        $next_due = new DateTime($bill['next_due_date']);
        switch (strtolower($bill['frequency'])) {
            case 'monthly':
                $next_due->modify('+1 month');
                break;
            case 'weekly':
                $next_due->modify('+1 week');
                break;
            case 'yearly':
                $next_due->modify('+1 year');
                break;
        }

        $update = $pdo->prepare("
            UPDATE billing_schedule 
            SET last_run = :last_run, next_due_date = :next_due_date 
            WHERE id = :id
        ");
        $update->execute([
            ':last_run' => $today,
            ':next_due_date' => $next_due->format('Y-m-d'),
            ':id' => $bill['id']
        ]);
    }

    // --- COMPLIANCE BILLING ---
    $cmbills = $pdo->query("
        SELECT * FROM billing_schedule
        WHERE category='Compliance' AND status='active' AND next_due_date <= '$today'
    ")->fetchAll();

    foreach ($cmbills as $cmbill) {
        // Step 1: Insert compliance expense
        $stmt = $pdo->prepare("
        INSERT INTO compliance_expenses (type, amount, payment_date, reference_number, period_covered, paid_by)
        VALUES (:type, :amount, :payment_date, :reference_number, :period_covered, :paid_by)
    ");

        // Generate a temporary reference number first
        $temp_ref = sprintf("TEMP-%s", uniqid());

        $stmt->execute([
            ':type' => $cmbill['expense_name'],
            ':amount' => $cmbill['amount'],
            ':payment_date' => $today,
            ':reference_number' => $temp_ref,
            ':period_covered' => '1 Month',
            ':paid_by' => 0
        ]);

        // Step 2: Now get the inserted expense ID
        $expense_id = $pdo->lastInsertId();

        // Step 3: Generate proper receipt number using that ID
        $receipt_no = sprintf("REF-%s-%04d", date('Ymd'), $expense_id);

        // Step 4: Update the compliance record with correct reference number
        $update_ref = $pdo->prepare("UPDATE compliance_expenses SET reference_number = :ref WHERE id = :id");
        $update_ref->execute([':ref' => $receipt_no, ':id' => $expense_id]);

        // Step 5: Create receipt
        $receipt_stmt = $pdo->prepare("
        INSERT INTO receipts (receipt_no, date_issued, amount, description, compliance_id)
        VALUES (:receipt_no, :date_issued, :amount, :description, :compliance_id)
    ");
        $receipt_stmt->execute([
            ':receipt_no' => $receipt_no,
            ':date_issued' => $today,
            ':amount' => $cmbill['amount'],
            ':description' => $cmbill['expense_name'],
            ':compliance_id' => $expense_id
        ]);

        // Step 6: Update billing schedule
        $next_due = new DateTime($cmbill['next_due_date']);
        switch (strtolower($cmbill['frequency'])) {
            case 'monthly':
                $next_due->modify('+1 month');
                break;
            case 'weekly':
                $next_due->modify('+1 week');
                break;
            case 'yearly':
                $next_due->modify('+1 year');
                break;
        }

        $update = $pdo->prepare("
        UPDATE billing_schedule 
        SET last_run = :last_run, next_due_date = :next_due_date 
        WHERE id = :id
    ");
        $update->execute([
            ':last_run' => $today,
            ':next_due_date' => $next_due->format('Y-m-d'),
            ':id' => $cmbill['id']
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Auto-billing executed successfully.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
