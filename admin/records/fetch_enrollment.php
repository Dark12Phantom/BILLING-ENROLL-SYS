<?php

require_once '../../includes/db.php';

$year = isset($_GET['year']) ? (int) $_GET['year'] : 0;

$stmt = $pdo->prepare("
    SELECT 
        *,
        CASE 
            WHEN YEAR(lastUpdated) > YEAR(created_at)
                THEN YEAR(lastUpdated)
            ELSE YEAR(created_at)
        END AS enrollment_year
    FROM students
    HAVING enrollment_year = ?
");
$stmt->execute([$year]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($students)) {
    echo "<p class='text-center text-muted'>No records found for {$year}.</p>";
    exit;
}
?>

<table class='table table-hover'>
  <thead class='table-dark'>
    <tr>
      <th>ID</th>
      <th>Student ID</th>
      <th>Name</th>
      <th>Grade Level</th>
      <th>Section</th>
      <th>Status</th>
      <th>School Year</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($students as $stu): ?>
      <tr>
        <td><?= htmlspecialchars($stu['id']) ?></td>
        <td><?= htmlspecialchars($stu['student_id']) ?></td>
        <td><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
        <td><?= htmlspecialchars($stu['grade_level']) ?></td>
        <td><?= htmlspecialchars($stu['section']) ?></td>
        <td><?= htmlspecialchars($stu['status']) ?></td>
        <td><?= htmlspecialchars($stu['schoolYear']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
