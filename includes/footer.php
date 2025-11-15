</div>
<?php
if (!isset($baseUrl)) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    
    $baseDir = '/BILLING-ENROLL-SYS';
    if (strpos($scriptName, 'BILLING-ENROLL-SYS') !== false) {
        $baseDir = substr($scriptName, 0, strpos($scriptName, 'BILLING-ENROLL-SYS') + strlen('BILLING-ENROLL-SYS'));
    }
    
    $baseUrl = $protocol . '://' . $host . $baseDir;
}
?>
    <footer class="py-3" style="background-color: var(--secondary-color); color: white;">
        <div class="container text-center">
            <small>&copy; <?= date('Y') ?> Enrollment and Billing System</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/js/script.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const activeTab = localStorage.getItem("activeTab");

            if (activeTab) {
                const tabTrigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
                if (tabTrigger) {
                    const tab = new bootstrap.Tab(tabTrigger);
                    tab.show();
                }
            }

            const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabLinks.forEach(tab => {
                tab.addEventListener("shown.bs.tab", e => {
                    localStorage.setItem("activeTab", e.target.getAttribute("data-bs-target"));
                });
            });
        })
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-expense')) {
                e.preventDefault();
                const expenseId = e.target.dataset.id;

                if (!confirm('Are you sure you want to delete this expense?')) return;

                fetch('delete-expense.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'expense_id=' + encodeURIComponent(expenseId)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const row = e.target.closest('tr');
                            row.style.transition = 'opacity 0.3s';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 300);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(() => alert('Error deleting expense.'));
            }
        });
    </script>
    </body>

    </html>