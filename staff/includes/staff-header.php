<?php
// Calculate base URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);

// Extract base directory
$baseDir = '/BILLING-ENROLL-SYS'; // Or detect it dynamically
if (strpos($scriptName, 'BILLING-ENROLL-SYS') !== false) {
    $baseDir = substr($scriptName, 0, strpos($scriptName, 'BILLING-ENROLL-SYS') + strlen('BILLING-ENROLL-SYS'));
}

$baseUrl = $protocol . '://' . $host . $baseDir;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment and Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo $baseUrl; ?>/staff/assets/css/style.css" rel="stylesheet">
    <link rel="icon" href="<?php echo $baseUrl; ?>/logo.png" type="image/png">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }

        body {
            background-color: var(--light-bg);
            background-image: linear-gradient(to bottom,
                    rgba(52, 152, 219, 0.1),
                    rgba(52, 152, 219, 0.05));
            min-height: 100vh;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .navbar {
            background-color: var(--secondary-color) !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>

<body>
    <?php
    require_once 'staff-auth.php';
    protectPage();
    
    // Get user type from session
    $userType = $_SESSION['user_type'] ?? '';
    $username = $_SESSION['username'] ?? '';
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $baseUrl; ?>/staff/dashboard.php">Enrollment System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>/staff/dashboard.php">Home</a>
                    </li>

                    <?php if ($userType === 'registrar'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/staff/htmls/index.php">Student List</a>
                        </li>
                    <?php endif; ?>

                    <?php if ($userType === 'cashier'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="billingDropdown" role="button" data-bs-toggle="dropdown">
                                Billing & Payment
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/staff/api/history.php">Payment History</a></li>
                                <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/staff/api/fees.php">Fee Management</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($username); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $baseUrl; ?>/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">