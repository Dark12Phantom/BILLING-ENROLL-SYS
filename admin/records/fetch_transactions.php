<?php

require_once '../../includes/db.php';

$year = isset($_GET['year']) ? (int) $_GET['year'] : 0;

$stmt1 = $pdo->prepare("
    SELECT 
        p.id,
        'payments' AS source_table,
        p.student_id,
        p.amount,
        p.payment_date AS record_date,
        p.payment_method,
        p.reference_number,
        CONCAT(u.first_name, ' ', u.last_name) AS handled_by
    FROM payments p
    LEFT JOIN user_tables u ON p.received_by = u.id
    WHERE YEAR(p.payment_date) = ?
");
$stmt1->execute([$year]);
$payments = $stmt1->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("
    SELECT
        o.id,
        'operational_expenses' AS source_table,
        o.category,
        o.particular,
        o.amount,
        o.evidence,
        o.date_incurred AS record_date,
        CONCAT(u.first_name, ' ', u.last_name) AS handled_by
    FROM operational_expenses o
    LEFT JOIN user_tables u ON o.approved_by = u.id
    WHERE YEAR(o.date_incurred) = ?
");
$stmt2->execute([$year]);
$ops = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$stmt3 = $pdo->prepare("
    SELECT
        c.id,
        'compliance_expenses' AS source_table,
        c.type,
        c.amount,
        c.payment_date AS record_date,
        c.reference_number,
        c.period_covered,
        CONCAT(u.first_name, ' ', u.last_name) AS handled_by
    FROM compliance_expenses c
    LEFT JOIN user_tables u ON c.paid_by = u.id
    WHERE YEAR(c.payment_date) = ?
");
$stmt3->execute([$year]);
$compliance = $stmt3->fetchAll(PDO::FETCH_ASSOC);

$records = array_merge($payments, $ops, $compliance);

usort($records, fn($a, $b) => strtotime($b['record_date']) - strtotime($a['record_date']));

if (empty($records)) {
    echo "<p class='text-center text-muted'>No records found for {$year}.</p>";
    exit;
}
?>

<table class='table table-hover'>
    <thead class='table-dark'>
        <tr>
            <th>From</th>
            <th>Amount</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $rec): ?>
            <tr>
                <td><?= htmlspecialchars($rec['source_table']) ?></td>
                <td><?= htmlspecialchars($rec['amount']) ?></td>
                <td><?= htmlspecialchars($rec['record_date']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>