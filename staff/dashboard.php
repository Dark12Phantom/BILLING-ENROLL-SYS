<?php
require_once 'includes/staff-auth.php';
protectPage();

require_once 'includes/staff-header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Dashboard</h2>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <h5 class="card-title">Total Students</h5>
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM students");
                $count = $stmt->fetchColumn();
                ?>
                <h2 class="card-text"><?php echo $count; ?></h2>
                <a href="./htmls/index.php" class="text-white">View Students</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <h5 class="card-title">Total Payments</h5>
                <?php
                $stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())");
                $amount = $stmt->fetchColumn() ?? 0;
                ?>
                <h2 class="card-text">₱<?php echo number_format($amount, 2); ?></h2>
                <a href="./api/history.php" class="text-white">View Payments</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <h5 class="card-title">Pending Fees</h5>
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM student_fees WHERE status = 'Pending'");
                $count = $stmt->fetchColumn();
                ?>
                <h2 class="card-text"><?php echo $count; ?></h2>
                <a href="./api/fees.php" class="text-white">View Pending Fees</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/staff-footer.php'; ?>