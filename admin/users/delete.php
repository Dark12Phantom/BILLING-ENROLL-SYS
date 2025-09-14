<?php
require_once '../includes/auth.php';
protectPage();

$error = '';
$success = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = (int) $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo->beginTransaction();

            // First delete from user_tables
            $stmt1 = $pdo->prepare("DELETE FROM user_tables WHERE userID = ?");
            $stmt1->execute([$userId]);

            // Then delete from users
            $stmt2 = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt2->execute([$userId]);

            $pdo->commit();

            $success = "Account deleted successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error deleting account: " . $e->getMessage();
        }
    }
} else {
    $error = "Invalid account ID.";
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <h2 class="text-center mb-4">Delete Account</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <a href="index.php" class="btn btn-primary">Back to List</a>
        <?php else: ?>
            <div class="card shadow">
                <div class="card-body text-center">
                    <p>Are you sure you want to delete this account?</p>
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
