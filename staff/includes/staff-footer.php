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
    </div>
        
    <footer class="py-3" style="background-color: var(--secondary-color); color: white;">
        <div class="container text-center">
            <small>&copy; <?php echo date('Y'); ?> Enrollment and Billing System</small>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/js/script.js"></script>
</body>
</html>