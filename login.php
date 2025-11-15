<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'altcha-verify.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'staff') {
        header("Location: staff/dashboard.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $altcha_payload = $_POST['altcha'] ?? '';

    if (!verifyAltcha($altcha_payload)) {
        $error = "Invalid security verification. Please try again.";
    } else {
        $stmt = $pdo->prepare("
            SELECT u.*, ut.user_type 
            FROM users u
            LEFT JOIN user_tables ut ON ut.userID = u.id
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_type'] = $user['user_type'] ?? null;

            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
                exit();
            } elseif ($user['role'] === 'staff') {
                header("Location: staff/dashboard.php");
                exit();
            } else {
                $error = "Role not recognized.";
            }
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="./logo.png" type="image/png">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            padding: 1.5rem;
            text-align: center;
        }

        .logo-container {
            margin: -50px auto 20px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 5px solid white;
        }

        .logo-container img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 50%;
        }

        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        .input-group-text {
            background-color: transparent;
            border-right: none;
        }

        .input-with-icon {
            border-left: none;
        }

        .btn-login {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .forgot-password {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card login-card animate__animated animate__fadeIn">
                    <div class="card-header">
                        <h4 class="text-white mb-0"><i class="fas fa-lock me-2"></i> LOGIN AUTHENTICATION</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="logo-container animate__animated animate__bounceIn">
                            <img src="logo.jpg" alt="School Logo">
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger animate__animated animate__shakeX">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="loginForm" class="animate__animated animate__fadeIn animate__delay-1s">
                            <div class="mb-4">
                                <label for="username" class="form-label fw-bold">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="fas fa-user text-muted"></i></span>
                                    <input type="text" class="form-control input-with-icon" id="username" name="username" placeholder="Enter your username" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="fas fa-key text-muted"></i></span>
                                    <input type="password" class="form-control input-with-icon" id="password" name="password" placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Altcha Widget -->
                            <!-- <div class="mb-4">
                                <altcha-widget 
                                    challengeurl="challenge.php"
                                    hidefooter
                                    name="altcha"
                                ></altcha-widget>
                            </div> -->

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-login btn-lg text-white" id="submitBtn">
                                    <i class="fas fa-sign-in-alt me-2"></i> LOGIN
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center bg-light">
                        <p class="mb-0 text-muted">Forgot Password? || <a href="./forgot-password.php" class="text-primary">Change Password</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Altcha Script -->
    <script async defer src="https://cdn.jsdelivr.net/gh/altcha-org/altcha@main/dist/altcha.min.js" type="module"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Disable submit until Altcha is verified
        const submitBtn = document.getElementById('submitBtn');
        const altchaWidget = document.querySelector('altcha-widget');
        
        if (altchaWidget) {
            submitBtn.disabled = true;
            
            altchaWidget.addEventListener('statechange', (event) => {
                if (event.detail.state === 'verified') {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            });
        }

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.classList.add('animate__animated', 'animate__pulse');
            });

            input.addEventListener('blur', function() {
                this.parentElement.parentElement.classList.remove('animate__animated', 'animate__pulse');
            });
        });

        window.addEventListener("pageshow", function(event) {
            if (event.persisted || performance.getEntriesByType("navigation")[0].type === "back_forward") {
                window.location.reload(true);
            }
        });
    </script>
</body>

</html>