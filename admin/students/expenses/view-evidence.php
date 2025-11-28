<?php
require_once '../../../includes/auth.php';
require_once '../../../includes/db.php';
protectPage();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: operational.php');
    exit();
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT evidence FROM operational_expenses WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    header('Location: operational.php');
    exit();
}

$evidence = $row['evidence'] ?? '';
if (empty($evidence)) {
    header('Location: view-receipt.php?id=' . $id . '&type=operational');
    exit();
}

$uploadsBase = realpath(__DIR__ . '/../uploads');
$filePath = realpath($uploadsBase . DIRECTORY_SEPARATOR . $evidence);

// Security: ensure the file is within the uploads directory
if ($filePath === false || strpos($filePath, $uploadsBase) !== 0 || !file_exists($filePath)) {
    header('Location: operational.php');
    exit();
}

$fileUrl = '/BILLING-ENROLL-SYS/admin/students/uploads/' . htmlspecialchars($evidence, ENT_QUOTES, 'UTF-8');
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

require_once '../../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Evidence Viewer</h2>
            <hr>
            <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?= $fileUrl ?>" alt="Evidence" class="img-fluid" style="max-height:75vh;">
                    </div>
                </div>
            <?php elseif ($ext === 'pdf'): ?>
                <div class="card">
                    <div class="card-body">
                        <object data="<?= $fileUrl ?>" type="application/pdf" width="100%" height="800px">
                            <p>PDF cannot be displayed. <a href="<?= $fileUrl ?>" target="_blank">Download</a></p>
                        </object>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">Unsupported file type. <a href="<?= $fileUrl ?>" target="_blank">Download</a></div>
            <?php endif; ?>
            <div class="mt-3 text-end">
                <a href="<?= $fileUrl ?>" class="btn btn-primary" target="_blank">Download Evidence</a>
                <a href="operational.php" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>

