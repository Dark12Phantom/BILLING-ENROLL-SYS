<?php
// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session start (only if not already started)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>