<?php
require_once '../../includes/auth.php';
protectPage();

require_once '../../includes/header.php';

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT ut.*, u.last_login 
          FROM user_tables ut
          LEFT JOIN users u ON ut.id = u.id
          WHERE ut.first_name LIKE ? 
             OR ut.last_name LIKE ? 
             OR ut.staff_id LIKE ? 
             OR ut.user_type LIKE ?
          ORDER BY ut.last_name, ut.first_name";
$params = ["%$search%", "%$search%", "%$search%", "%$search%"];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Staffs</h2>
        <hr>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5>User List</h5>
                    </div>
                    <div class="col-md-6">
                        <form class="form-inline float-end">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                                <a href="add.php" class="btn btn-success ms-2"><i class="fas fa-plus"></i>Add User</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Staff ID</th>
                                <th>Account Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                                <th>Last Login</th>
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
                                if ($user['user_type'] == 'admin') {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($user['staff_id']) . '</td>';
                                    echo '<td>' . htmlspecialchars($user['last_name']) . ', ' . htmlspecialchars($user['first_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($user['staff_id']) . '</td>';
                                    echo '<td>' . htmlspecialchars($user['user_type']) . '</td>';
                                    echo '<td><span class="badge bg-' . $badge . '">' . htmlspecialchars($status) . '</span></td>';
                                    echo '<td>
                                    <a href="view.php?id=' . $user['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    <a href="edit.php?id=' . $user['id'] . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                </td>';
                                    echo '<td>' . htmlspecialchars($lastLogin) . '</td>';
                                    echo '</tr>';
                                } elseif ($user['user_type'] == 'staff') {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($user['staff_id']) . '</td>';
                                    echo '<td>' . htmlspecialchars($user['last_name']) . ', ' . htmlspecialchars($user['first_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($user['staff_id']) . '</td>';
                                    echo '<td>' . htmlspecialchars($user['user_type']) . '</td>';
                                    echo '<td><span class="badge bg-'
                                        . ($user['status'] == 'Active' ? 'success' : ($user['status'] == 'Inactive' ? 'warning' : 'secondary'))
                                        . '">' . htmlspecialchars($user['status'] ?? 'Unknown') . '</span></td>';
                                    echo '<td>
                                    <a href="view.php?id=' . $user['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    <a href="edit.php?id=' . $user['id'] . '" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <a href="delete.php?id=' . $user['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-trash"></i></a>
                                    </td>';
                                    echo '<td>' . htmlspecialchars($lastLogin) . '</td>';
                                    echo '</tr>';
                                }
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