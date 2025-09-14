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

            // First fetch student to know if he hath a profile picture
            $stmt = $pdo->prepare("SELECT idPicturePath FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();

            if ($student) {
                // Delete parent first (FK constraint may require this)
                $stmt = $pdo->prepare("DELETE FROM parents WHERE student_id = ?");
                $stmt->execute([$studentId]);

                // Delete student
                $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
                $stmt->execute([$studentId]);

                // Remove profile picture if it existeth
                if (!empty($student['idPicturePath'])) {
                    $filePath = 'uploads/studentProfiles/' . $student['idPicturePath'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                $pdo->commit();

                $success = "Student deleted successfully!";
            } else {
                $error = "Student not found.";
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
        <h2 class="text-center mb-4">Delete Student</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <a href="index.php" class="btn btn-primary">Back to List</a>
        <?php else: ?>
            <div class="card shadow">
                <div class="card-body text-center">
                    <p>Are you sure you want to delete this student and all related parent information?</p>
                    <form method="POST">
                        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
