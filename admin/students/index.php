<?php
require_once '../../includes/auth.php';
protectPage();

require_once '../../includes/header.php';

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM students WHERE 
          first_name LIKE ? OR 
          last_name LIKE ? OR 
          student_id LIKE ? OR
          grade_level LIKE ? OR
          section LIKE ?
          ORDER BY last_name, first_name";
$params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Student Record</h2>
        <hr>
        
        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Student List</h5>
                    </div>
                    <div class="col-md-6">
                        <form class="form-inline float-end">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                                <a href="add.php" class="btn btn-success ms-2"><i class="fas fa-plus"></i> Add Student</a>
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
                                <th>Grade Level</th>
                                <th>Section</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td><?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?></td>
                                    <td><?= htmlspecialchars($student['grade_level']) ?></td>
                                    <td><?= htmlspecialchars($student['section']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $student['status'] == 'Active' ? 'success' : 
                                            ($student['status'] == 'Inactive' ? 'warning' : 'secondary') 
                                        ?>">
                                            <?= htmlspecialchars($student['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="./view.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                        <a href="./edit.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        <a href="./delete.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>