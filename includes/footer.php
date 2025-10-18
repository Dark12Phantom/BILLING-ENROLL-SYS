    </div> <!-- Close container div -->
    <br><br><br><br>
    <!-- Add a subtle footer with theme colors -->
    <footer class="mt-5 py-3" style="background-color: var(--secondary-color); color: white;">
        <div class="container text-center">
            <small>&copy; <?= date('Y') ?> Enrollment and Billing System</small>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../../../assets/js/script.js"></script>
    <script src="../../assets/js/script.js"></script>
    <script src="../assets/js/script.js"></script>
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
    </script>
    </body>

    </html>