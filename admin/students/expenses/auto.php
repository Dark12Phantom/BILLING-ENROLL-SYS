<?php
require_once '../../../includes/auth.php';
protectPage();
require_once '../../../includes/db.php';
require_once '../../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Auto-Billing Scheduler</h2>
        <hr>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Pending Bills for Auto-Billing</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <button id="runAutoBilling" class="btn btn-success">
                            <i class="fas fa-sync-alt"></i> Run Auto-Billing
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div id="alertArea"></div>

                <div class="table-responsive">
                    <table class="table table-striped" id="pendingBills">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Expense Name</th>
                                <th>Amount</th>
                                <th>Next Due Date</th>
                                <th>Frequency</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $today = date('Y-m-d');
                            $bills = $pdo->query("
                SELECT * FROM billing_schedule
                WHERE status='active' AND next_due_date <= '$today'
                ORDER BY category, next_due_date ASC
              ")->fetchAll();

                            if (!$bills):
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No bills due for auto-billing today.</td>
                                </tr>
                                <?php else:
                                foreach ($bills as $bill): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($bill['category']) ?></td>
                                        <td><?= htmlspecialchars($bill['expense_name']) ?></td>
                                        <td>₱<?= number_format($bill['amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($bill['next_due_date']) ?></td>
                                        <td><?= ucfirst($bill['frequency']) ?></td>
                                    </tr>
                            <?php endforeach;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>

                <div id="progressSection" class="mt-4" style="display:none;">
                    <h6>Processing...</h6>
                    <div class="progress">
                        <div id="billingProgress" class="progress-bar progress-bar-striped progress-bar-animated"
                            role="progressbar" style="width: 0%"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const runBtn = document.getElementById('runAutoBilling');
    const alertArea = document.getElementById('alertArea');
    const progressSection = document.getElementById('progressSection');
    const progressBar = document.getElementById('billingProgress');

    runBtn.addEventListener('click', async () => {
        alertArea.innerHTML = '';
        progressSection.style.display = 'block';
        progressBar.style.width = '20%';

        try {
            const response = await fetch('run-auto-billing.php', { method: 'POST' });

            if (!response.ok) {
                const text = await response.text();
                alertArea.innerHTML = `
                    <div class="alert alert-danger mt-3">
                        <strong>Server Error:</strong> ${response.status} ${response.statusText}<br>
                        <pre class="mt-2 p-2 bg-light text-danger" style="max-height:200px;overflow:auto;">${text}</pre>
                    </div>`;
                progressBar.style.width = '100%';
                progressBar.classList.remove('progress-bar-animated');
                return;
            }

            progressBar.style.width = '80%';
            let resultText = await response.text();
            let result;

            try {
                result = JSON.parse(resultText);
            } catch (e) {
                alertArea.innerHTML = `
                    <div class="alert alert-danger mt-3">
                        <strong>Invalid JSON Response</strong><br>
                        The server did not return valid JSON.<br><br>
                        <pre class="p-2 bg-light text-danger" style="max-height:200px;overflow:auto;">${resultText || "(empty response)"}</pre>
                    </div>`;
                progressBar.style.width = '100%';
                progressBar.classList.remove('progress-bar-animated');
                return;
            }

            progressBar.style.width = '100%';

            if (result.success) {
                alertArea.innerHTML = `
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-check-circle"></i> ${result.message}<br>
                        <small class="text-muted">Page will reload shortly...</small>
                    </div>`;
                // Auto reload after short delay
                setTimeout(() => location.reload(), 3000);
            } else {
                alertArea.innerHTML = `
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-circle"></i> ${result.message}
                    </div>`;
            }

        } catch (err) {
            alertArea.innerHTML = `
                <div class="alert alert-danger mt-3">
                    <i class="fas fa-bug"></i> Error occurred while running auto-billing.<br>
                    <pre class="mt-2 p-2 bg-light text-danger" style="max-height:200px;overflow:auto;">${err.message}</pre>
                </div>`;
        } finally {
            progressBar.classList.remove('progress-bar-animated');
        }
    });
});
</script>


<?php require '../../../includes/footer.php'; ?>