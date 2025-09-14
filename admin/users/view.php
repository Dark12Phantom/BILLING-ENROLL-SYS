<?php
require_once '../../includes/auth.php';
protectPage();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM user_tables WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: index.php");
    exit();
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>User Overview</h2>
        <hr>

        <div class="row">
            <div class="col-md-offset-4 col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5>User Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($user['idPicturePath'])): ?>
                                <img src="../../uploads/profiles/<?= htmlspecialchars($user['idPicturePath']) ?>"
                                    alt="ID Picture"
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
                                <th>Staff ID:</th>
                                <td><?= htmlspecialchars($user['staff_id']) ?></td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td><?= htmlspecialchars($user['last_name']) ?>, <?= htmlspecialchars($user['first_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Date of Birth:</th>
                                <td><?= date('F j, Y', strtotime($user['date_of_birth'])) ?></td>
                            </tr>
                            <tr>
                                <th>Age:</th>
                                <td><?= floor((time() - strtotime($user['date_of_birth'])) / 31556926) ?> years</td>
                            </tr>
                            <tr>
                                <th>Gender:</th>
                                <td><?= htmlspecialchars($user['gender']) ?></td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td><?= htmlspecialchars($user['address']) ?></td>
                            </tr>
                            <tr>
                                <th>Mobile Number:</th>
                                <td><?= htmlspecialchars($user['mobile_number']) ?></td>
                            </tr>
                            <tr>
                                <th>Grade Level:</th>
                                <td><?= htmlspecialchars($user['user_type']) ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-<?=
                                                            $user['status'] == 'Active' ? 'success' : ($user['status'] == 'Inactive' ? 'warning' : 'secondary')
                                                            ?>">
                                        <?= htmlspecialchars($user['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="text-center mb-4">
                        <a href="index.php" class="btn btn-secondary">Back to List</a>
                        <a href="edit.php?id=<?= $userId ?>" class="btn btn-primary">Edit</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>