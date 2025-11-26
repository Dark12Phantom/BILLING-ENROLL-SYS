<?php
// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session start
session_start();

// Base URL
define('BASE_URL', 'http://localhost/enrollment-system');
// reCAPTCHA keys (set via environment variables for security)
define('RECAPTCHA_SITE_KEY', '6Lc9ChksAAAAAC43Bn69Vq2aYRF0hTYGJvk2J0h9');
define('RECAPTCHA_SECRET', '6Lc9ChksAAAAADaAla84sy-niSlbDmEkZ8foeP4S');
?>
