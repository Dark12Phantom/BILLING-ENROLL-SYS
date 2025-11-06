<?php

require_once '../../includes/db.php';

$year = isset($_GET['year']) ? (int) $_GET['year'] : 0;

$stmt1 = $pdo->prepare("
    SELECT 
        u.id,
        CONCAT(ut.first_name, ' ', ut.last_name) AS fullname,
        u.last_login
    FROM users u
    LEFT JOIN user_tables ut ON ut.userID = u.id
    WHERE YEAR(u.last_login) = ?
");
$stmt1->execute([$year]);
$records = $stmt1->fetchAll(PDO::FETCH_ASSOC);

if (empty($records)) {
    echo "<p class='text-center text-muted'>No records found for {$year}.</p>";
    exit;
}
?>

<table class='table table-hover'>
    <thead class='table-dark'>
        <tr>
            <th>User</th>
            <th>Last Login</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $rec): ?>
            <tr>
                <td><?= htmlspecialchars($rec['fullname']) ?></td>
                <td><?= htmlspecialchars($rec['last_login']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
