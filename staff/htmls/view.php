<?php
require_once '../includes/staff-auth.php';
protectPage();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$studentId = $_GET['id'];

$stmt = $pdo->prepare("SELECT s.*, eh.grade_level FROM students s 
                       LEFT JOIN enrollment_history eh ON eh.student_id = s.id AND eh.status = 'current'
                       WHERE s.id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM parents WHERE student_id = ?");
$stmt->execute([$studentId]);
$parent = $stmt->fetch();

$stmt = $pdo->prepare("SELECT sf.*, f.name FROM student_fees sf JOIN fees f ON sf.fee_id = f.id WHERE sf.student_id = ?");
$stmt->execute([$studentId]);
$fees = $stmt->fetchAll();

require_once '../includes/staff-header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Student Overview</h2>
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5>Student Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($student['idPicturePath'])): ?>
                                <img src="../../uploads/studentProfiles/<?= htmlspecialchars($student['idPicturePath']) ?>" 
                                     alt="Student ID Picture" 
                                     class="img-thumbnail" 
                                     style="width: 200px; height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 200px; height: 200px; margin: 0 auto;">
                                    <span class="text-muted">No ID Picture</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <table class="table">
                            <tr>
                                <th>Student ID:</th>
                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td><?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Date of Birth:</th>
                                <td><?= date('F j, Y', strtotime($student['date_of_birth'])) ?></td>
                            </tr>
                            <tr>
                                <th>Age:</th>
                                <td><?= floor((time() - strtotime($student['date_of_birth'])) / 31556926) ?> years</td>
                            </tr>
                            <tr>
                                <th>Gender:</th>
                                <td><?= htmlspecialchars($student['gender']) ?></td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td><?= htmlspecialchars($student['address']) ?></td>
                            </tr>
                            <tr>
                                <th>Mobile Number:</th>
                                <td><?= htmlspecialchars($student['mobile_number']) ?></td>
                            </tr>
                            <tr>
                                <th>Grade Level:</th>
                                <td><?= htmlspecialchars($student['grade_level'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <th>Section:</th>
                                <td><?= htmlspecialchars($student['section']) ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-<?= 
                                        $student['status'] == 'Active' ? 'success' : 
                                        ($student['status'] == 'Inactive' ? 'warning' : 'secondary') 
                                    ?>">
                                        <?= htmlspecialchars($student['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5>Emergency Contact</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($parent): ?>
                            <table class="table">
                                <tr>
                                    <th>Name:</th>
                                    <td><?= htmlspecialchars($parent['last_name']) ?>, <?= htmlspecialchars($parent['first_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Relationship:</th>
                                    <td><?= htmlspecialchars($parent['relationship']) ?></td>
                                </tr>
                                <tr>
                                    <th>Mobile Number:</th>
                                    <td><?= htmlspecialchars($parent['mobile_number']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?= htmlspecialchars($parent['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td><?= htmlspecialchars($parent['address']) ?></td>
                                </tr>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-warning">No emergency contact information found.</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Fee Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($fees): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Fee</th>
                                            <th>Amount</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fees as $fee): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($fee['name']) ?></td>
                                                <td>₱<?= number_format($fee['amount'], 2) ?></td>
                                                <td><?= date('M d, Y', strtotime($fee['due_date'])) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $fee['status'] == 'Paid' ? 'success' : 
                                                        ($fee['status'] == 'Overdue' ? 'danger' : 'warning') 
                                                    ?>">
                                                        <?= htmlspecialchars($fee['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="../api/pay-bill.php?student_id=<?= $studentId ?>" class="btn btn-primary">Pay Bill</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No fees assigned to this student.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-end">
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</div>
<?php require_once '../includes/staff-footer.php'; ?>
