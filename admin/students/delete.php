<?php
require_once '../../includes/auth.php';
protectPage();

require_once '../../includes/header.php';

$error = '';
$success = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $studentId = (int) $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT idPicturePath FROM students WHERE id = ? AND isDeleted = 0");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();

            if ($student) {
                $stmt = $pdo->prepare("
                    UPDATE parents 
                    SET isDeleted = 1 
                    WHERE student_id = ?
                ");
                $stmt->execute([$studentId]);

                $stmt = $pdo->prepare("
                    UPDATE students 
                    SET isDeleted = 1 
                    WHERE id = ?
                ");
                $stmt->execute([$studentId]);

                if (!empty($student['idPicturePath'])) {
                    $filePath = 'uploads/studentProfiles/' . $student['idPicturePath'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                $pdo->commit();
                $success = "Student moved to deleted records successfully!";
            } else {
                $error = "Student not found or already archived.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error deleting student: " . $e->getMessage();
        }
    }
} else {
    $error = "Invalid student ID.";
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <h2 class="text-center mb-4">Archive Student</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <a href="index.php" class="btn btn-primary">Back to List</a>
        <?php else: ?>
            <div class="card shadow">
                <div class="card-body text-center">
                    <p>Archive student and parent record?</p>
                    <form method="POST">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Confirm</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
