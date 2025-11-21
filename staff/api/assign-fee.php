<?php
require_once '../includes/staff-auth.php';
require_once '../includes/database.php';

protectPage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'] ?? null;
    $feeId = $_POST['fee_id'] ?? null;
    $dueDate = $_POST['due_date'] ?? '';
    $discounts = $_POST['discounts'] ?? [];

    $errors = [];

    if (empty($studentId)) {
        $errors[] = "Student is required.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        if (!$stmt->fetch()) {
            $errors[] = "Invalid student selected.";
        }
    }

    $feeInfo = null;
    if (empty($feeId)) {
        $errors[] = "Fee type is required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM fees WHERE id = ?");
        $stmt->execute([$feeId]);
        $feeInfo = $stmt->fetch();
        if (!$feeInfo) {
            $errors[] = "Invalid fee type selected.";
        }
    }

    if (empty($dueDate)) {
        $errors[] = "Due date is required.";
    } elseif (strtotime($dueDate) === false) {
        $errors[] = "Invalid due date format.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM student_fees WHERE student_id = ? AND fee_id = ? AND status = 'Pending'");
        $stmt->execute([$studentId, $feeId]);
        if ($stmt->fetch()) {
            $errors[] = "This student already has a pending fee of this type.";
        }
    }

    if (empty($errors) && $feeInfo) {
        try {
            $pdo->beginTransaction();

            $originalAmount = $feeInfo['amount'];
            $discountAmount = 0;

            $discountRules = [
                'referral'    => 500,
                'earlybird'   => 500,
                'sibling'     => 500,
                'fullpayment' => 1000,
            ];

            foreach ($discounts as $d) {
                if (isset($discountRules[$d])) {
                    $discountAmount += $discountRules[$d];
                }
            }

            $discountAmount = min($discountAmount, $originalAmount);
            $finalAmount = $originalAmount - $discountAmount;

            if (!empty($discounts)) {
                $stmt = $pdo->prepare("INSERT INTO discounts (discount_types, total_amount, student_id, fees_id) 
                                        VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    json_encode($discounts),
                    $discountAmount,
                    $studentId,
                    $feeId
                ]);
            }

            $totalAmount = $finalAmount;
            $frequency = $feeInfo['frequency'];
            $installments = 1;
            $intervalMonths = 1;

            switch ($frequency) {
                case 'Monthly':
                    $installments = 12;
                    $intervalMonths = 1;
                    break;
                case 'Quarterly':
                    $installments = 4;
                    $intervalMonths = 3;
                    break;
                case 'Annual':
                    $installments = 2;
                    $intervalMonths = 6;
                    break;
                case 'One-time':
                default:
                    $installments = 1;
                    break;
            }

            $installmentAmount = $totalAmount / $installments;
            $regularInstallmentAmount = floor($installmentAmount * 100) / 100;
            $lastInstallmentAmount = $totalAmount - ($regularInstallmentAmount * ($installments - 1));

            $dueDateObj = new DateTime($dueDate);

            for ($i = 1; $i <= $installments; $i++) {
                $currentDueDate = clone $dueDateObj;
                if ($i > 1) {
                    $monthsToAdd = ($i - 1) * $intervalMonths;
                    $currentDueDate->add(new DateInterval('P' . $monthsToAdd . 'M'));
                }

                $currentAmount = ($i === $installments) ? $lastInstallmentAmount : $regularInstallmentAmount;

                $stmt = $pdo->prepare("INSERT INTO student_fees 
                    (student_id, fee_id, amount, due_date, status) 
                    VALUES (?, ?, ?, ?, 'Pending')");
                $stmt->execute([
                    $studentId,
                    $feeId,
                    $currentAmount,
                    $currentDueDate->format('Y-m-d')
                ]);
            }

            $pdo->commit();

            $installmentMsg = $installments > 1 
                ? "Fee assigned successfully with {$installments} installments." 
                : "Fee assigned successfully as full payment.";

            if ($discountAmount > 0) {
                $installmentMsg .= " Discounts applied: " . implode(', ', $discounts);
                $installmentMsg .= ". Total discount: ₱" . number_format($discountAmount, 2);
                $installmentMsg .= ". Final amount: ₱" . number_format($finalAmount, 2);
            }

            $_SESSION['success'] = $installmentMsg;
            header("Location: fees.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: fees.php");
        exit();
    }
} else {
    header("Location: fees.php");
    exit();
}
?>
