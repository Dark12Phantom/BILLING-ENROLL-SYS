<?php
require_once '../../includes/auth.php';
protectPage();

require_once '../../includes/header.php';

// Search and grade filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filterGrade = isset($_GET['grade_level']) ? $_GET['grade_level'] : '';

$gradeQuery = $pdo->query("SELECT DISTINCT grade_level 
                           FROM students 
                           WHERE isDeleted = 0
                           ORDER BY grade_level ASC");
$gradeLevels = $gradeQuery->fetchAll(PDO::FETCH_COLUMN);

if ($filterGrade === "__ARCHIVED__") {

    $query = "SELECT * FROM students 
              WHERE isDeleted = 1
              ORDER BY grade_level, last_name, first_name";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $students = $stmt->fetchAll();
} else {

    // NORMAL QUERY: only active students
    $query = "SELECT * FROM students 
              WHERE isDeleted = 0 
              AND (
                   first_name LIKE ? OR
                   last_name LIKE ? OR
                   student_id LIKE ? OR
                   grade_level LIKE ? OR
                   section LIKE ?
              )";

    $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];

    if (!empty($filterGrade)) {
        $query .= " AND grade_level = ?";
        $params[] = $filterGrade;
    }

    $query .= " ORDER BY grade_level, last_name, first_name";

    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 15;
    $offset = ($page - 1) * $limit;

    // Count total
    $countSql = "SELECT COUNT(*) FROM students WHERE isDeleted = 0 AND (
        first_name LIKE ? OR last_name LIKE ? OR student_id LIKE ? OR grade_level LIKE ? OR section LIKE ?
    )";
    $countParams = $params;
    if (!empty($filterGrade)) {
        $countSql .= " AND grade_level = ?";
        $countParams[] = $filterGrade;
    }
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRows = intval($countStmt->fetchColumn());
    $totalPages = max(1, (int)ceil($totalRows / $limit));

    $query .= " LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
}

if ($filterGrade) {
    $query .= " AND grade_level = ?";
    $params[] = $filterGrade;
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Student Record</h2>
        <hr>

        <div class="mb-3">
            <form class="form-inline d-flex justify-content-between align-items-center" method="get">
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    <a href="add.php" class="btn btn-success ms-2"><i class="fas fa-plus"></i> Add Student</a>
                </div>
                <div class="ms-3">
                    <select name="grade_level" class="form-select" style="width: 200px;" onchange="this.form.submit()">
                        <option value="">All Grade Levels</option>

                        <option value="__ARCHIVED__" <?= $filterGrade == '__ARCHIVED__' ? 'selected' : '' ?>>
                            Archived
                        </option>

                        <?php foreach ($gradeLevels as $grade): ?>
                            <option value="<?= htmlspecialchars($grade) ?>" <?= $filterGrade == $grade ? 'selected' : '' ?>>
                                <?= htmlspecialchars($grade) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
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
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No students found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                                        <td><?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?></td>
                                        <td><?= htmlspecialchars($student['grade_level']) ?></td>
                                        <td><?= htmlspecialchars($student['section']) ?></td>
                                        <td>
                                            <span class="badge bg-<?=
                                                                    $student['status'] == 'Active' ? 'success' : ($student['status'] == 'Inactive' ? 'warning' : 'secondary')
                                                                    ?>">
                                                <?= htmlspecialchars($student['status']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <a href="./view.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <?php if ($filterGrade !== "__ARCHIVED__"): ?>
                                                <a href="./edit.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="./delete.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page-1)])) ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page+1)])) ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
