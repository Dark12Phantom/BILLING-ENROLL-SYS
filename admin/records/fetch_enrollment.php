<?php

require_once '../../includes/db.php';

$year = isset($_GET['year']) ? (int) $_GET['year'] : 0;
if ($year === 0) {
    echo "<p class='text-center text-muted'>Invalid school year.</p>";
    exit;
}

$sql = "
    SELECT 
        s.id,
        s.student_id,
        s.first_name,
        s.last_name,
        s.grade_level,
        s.section,
        s.status,
        eh.school_year AS schoolYear,
        eh.status AS enrollmentStatus,
        eh.created_at AS enrollment_created_at
    FROM enrollment_history eh
    INNER JOIN students s 
            ON s.id = eh.student_id
    WHERE YEAR(eh.created_at) = ?
    ORDER BY s.last_name, s.first_name
";

$stmt = $pdo->prepare($sql);
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