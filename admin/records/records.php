<?php
require_once '../../includes/auth.php';
protectPage();
require_once '../../includes/db.php';

$stmt = $pdo->query("SELECT DISTINCT YEAR(created_at) AS year FROM enrollment_history
                        UNION
                        SELECT DISTINCT YEAR(date_incurred) AS year FROM operational_expenses
                        UNION
                        SELECT DISTINCT YEAR(payment_date)AS year FROM compliance_expenses
                        UNION
                        SELECT DISTINCT YEAR(payment_date)AS year FROM payments
                        ");
$yearsAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$totalRows = count($yearsAll);
$totalPages = max(1, (int)ceil($totalRows / $limit));
$offset = ($page - 1) * $limit;
$years = array_slice($yearsAll, $offset, $limit);

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Records</h2>
        <hr>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-12">
                        <h5>Year List</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 60%;">Year</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($years)): ?>
                            <?php foreach ($years as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['year']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success me-1 view-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#recordsModal"
                                            data-type="enrollment"
                                            data-year="<?= htmlspecialchars($row['year']) ?>"
                                            style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;">
                                            View Students
                                        </button>

                                        <button class="btn btn-sm btn-warning me-1 view-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#recordsModal"
                                            data-type="transactions"
                                            data-year="<?= htmlspecialchars($row['year']) ?>"
                                            style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;">
                                            View Transactions
                                        </button>

                                        <button class="btn btn-sm btn-info view-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#recordsModal"
                                            data-type="logs"
                                            data-year="<?= htmlspecialchars($row['year']) ?>"
                                            style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem;">
                                            View Logs
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-muted">No records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
</div>

<div class="modal fade" id="recordsModal" tabindex="-1" aria-labelledby="recordsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="recordsModalLabel">View Records</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalContent">
        <div class="text-center text-muted">Loading records...</div>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.view-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const year = btn.getAttribute('data-year');
    const type = btn.getAttribute('data-type');
    const modalContent = document.getElementById('modalContent');
    const modalTitle = document.getElementById('recordsModalLabel');
    
    modalTitle.textContent = `Viewing ${type.charAt(0).toUpperCase() + type.slice(1)} for ${year}`;
    modalContent.innerHTML = '<div class="text-center text-muted py-5">Loading...</div>';

    try {
      const response = await fetch(`fetch_${type}.php?year=${encodeURIComponent(year)}`);
      const html = await response.text();
      modalContent.innerHTML = html;
    } catch (err) {
      modalContent.innerHTML = '<div class="text-danger text-center py-5">Failed to load records.</div>';
    }
  });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
