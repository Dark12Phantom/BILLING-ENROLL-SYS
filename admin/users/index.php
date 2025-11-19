<?php
require_once '../../includes/auth.php';
protectPage();

require_once '../../includes/header.php';

// Search and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT ut.*, u.last_login, u.role 
          FROM user_tables ut
          LEFT JOIN users u ON ut.userID = u.id
          WHERE (
              ut.first_name LIKE ? 
              OR ut.last_name LIKE ? 
              OR ut.staff_id LIKE ? 
              OR ut.user_type LIKE ?
          )
          AND COALESCE(ut.user_type, u.role) <> 'system'
          ORDER BY ut.last_name, ut.first_name";
$params = ["%$search%", "%$search%", "%$search%", "%$search%"];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Staff Record</h2>
        <hr>

        <!-- Search + Filter Bar -->
        <div class="mb-3">
            <form class="form-inline d-flex justify-content-between align-items-center" method="get">
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    <a href="add.php" class="btn btn-success ms-2"><i class="fas fa-plus"></i> Add Staff</a>
                </div>
            </form>
        </div>

        <!-- Staff Table -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Staff ID</th>
                                <th>Account Type</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($users as $user) {
                                $status = $user['status'] ?? 'Unknown';
                                $badge = $status === 'Active' ? 'success' : ($status === 'Inactive' ? 'warning' : 'secondary');
                                $lastLogin = $user['last_login']
                                    ? date("M d, Y h:i A", strtotime($user['last_login']))
                                    : 'Never';
                                $accountType = !empty($user['user_type']) ? $user['user_type'] : ($user['role'] ?? 'Unknown');
                                $accountTypeLabel = ucfirst($accountType);

                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($user['staff_id'] ?? '') . '</td>';
                                echo '<td>' . htmlspecialchars($user['last_name'] ?? '') . ', ' . htmlspecialchars($user['first_name'] ?? '') . '</td>';
                                echo '<td>' . htmlspecialchars($user['staff_id'] ?? '') . '</td>';
                                echo '<td>' . htmlspecialchars($accountTypeLabel) . '</td>';
                                echo '<td><span class="badge bg-' . $badge . '">' . htmlspecialchars($status) . '</span></td>';
                                echo '<td>' . htmlspecialchars($lastLogin ?? '') . '</td>';
                                echo '<td>';
                                echo '<a href="view.php?id=' . $user['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a> ';
                                echo '<a href="edit.php?id=' . $user['id'] . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a> ';
                                if (($user['role'] ?? '') !== 'admin') {
                                    echo '<a href="delete.php?id=' . $user['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-trash"></i></a>';
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
