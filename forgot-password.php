<?php
require_once './includes/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $new_username = trim($_POST['new_username']);
    
    if (empty($email) || empty($new_password) || empty($confirm_password) || empty($new_username)) {
        $error = "All fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (strlen($new_username) < 3) {
        $error = "Username must be at least 3 characters long";
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $new_username)) {
        $error = "Username can only contain letters, numbers, underscore, and hyphen";
    } else {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $username_check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $username_check->execute([$new_username, $user['id']]);
            
            if ($username_check->fetch()) {
                $error = "Username already exists. Please choose a different username.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_stmt = $pdo->prepare("UPDATE users SET password = ?, username = ? WHERE email = ?");
                if ($update_stmt->execute([$hashed_password, $new_username, $email])) {
                    $success = "Password and username have been successfully updated! You can now login with your new credentials.";
                } else {
                    $error = "Failed to update credentials. Please try again.";
                }
            }
        } else {
            $error = "No account found with this email address";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Credentials - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .btn-reset {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-reset:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
        }

        .back-to-login {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .back-to-login:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .password-strength {
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #198754; }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card login-card animate__animated animate__fadeIn">
                    <div class="card-header">
                        <h4 class="text-white mb-0"><i class="fas fa-key me-2"></i> RESET CREDENTIALS</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="logo-container animate__animated animate__bounceIn">
                            <img src="./logo.jpg" alt="School Logo">
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger animate__animated animate__shakeX">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success animate__animated animate__bounceIn">
                                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="animate__animated animate__fadeIn animate__delay-1s">
                            <div class="mb-4">
                                <label for="email" class="form-label fw-bold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control input-with-icon" id="email" name="email" 
                                           placeholder="Enter your email address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Enter the email linked to your account.</small>
                            </div>

                            <div class="mb-4">
                                <label for="new_username" class="form-label fw-bold">New Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="fas fa-user text-muted"></i></span>
                                    <input type="text" class="form-control input-with-icon" id="new_username" name="new_username" 
                                           placeholder="Enter new username" required value="<?php echo isset($_POST['new_username']) ? htmlspecialchars($_POST['new_username']) : ''; ?>">
                                </div>
                                <div id="usernameAvailability" class="password-strength"></div>
                            </div>

                            <div class="mb-4">
                                <label for="new_password" class="form-label fw-bold">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control input-with-icon" id="new_password" name="new_password" 
                                           placeholder="Enter new password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordStrength" class="password-strength"></div>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label fw-bold">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control input-with-icon" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm new password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordMatch" class="password-strength"></div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-reset btn-lg text-white">
                                    <i class="fas fa-sync-alt me-2"></i> RESET LOGIN INFORMATION
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center bg-light">
                        <p class="mb-0 text-muted">Remember your login information? <a href="./login.php" class="back-to-login">Back to Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('toggleNewPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('new_password');
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

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
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

        document.getElementById('new_username').addEventListener('input', function() {
            const username = this.value;
            const availabilityDiv = document.getElementById('usernameAvailability');
            
            if (username.length === 0) {
                availabilityDiv.innerHTML = '';
                return;
            }
            
            if (username.length < 3) {
                availabilityDiv.innerHTML = '<i class="fas fa-times me-1"></i>Username must be at least 3 characters';
                availabilityDiv.className = 'password-strength strength-weak';
                return;
            }
            
            if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
                availabilityDiv.innerHTML = '<i class="fas fa-times me-1"></i>Only letters, numbers, underscore, and hyphen allowed';
                availabilityDiv.className = 'password-strength strength-weak';
                return;
            }
            
            availabilityDiv.innerHTML = '<i class="fas fa-check me-1"></i>Username format is valid';
            availabilityDiv.className = 'password-strength strength-strong';
        });

        document.addEventListener('DOMContentLoaded', function() {
            const availabilityDiv = document.getElementById('usernameAvailability');
            availabilityDiv.innerHTML = '<i class="fas fa-info-circle me-1"></i>Changing the username is optional, leave it blank if no change is necessary';
            availabilityDiv.className = 'password-strength text-muted';
        });

        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 8) strength++;
            else feedback.push('at least 8 characters');
            
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('uppercase letter');
            
            if (/[a-z]/.test(password)) strength++;
            else feedback.push('lowercase letter');
            
            if (/\d/.test(password)) strength++;
            else feedback.push('number');
            
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push('special character');
            
            let strengthText = '';
            let strengthClass = '';
            
            if (strength < 3) {
                strengthText = 'Weak - Add: ' + feedback.slice(0, 2).join(', ');
                strengthClass = 'strength-weak';
            } else if (strength < 4) {
                strengthText = 'Medium - Consider adding: ' + feedback.slice(0, 1).join(', ');
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Strong password!';
                strengthClass = 'strength-strong';
            }
            
            strengthDiv.innerHTML = '<i class="fas fa-info-circle me-1"></i>' + strengthText;
            strengthDiv.className = 'password-strength ' + strengthClass;
        });

        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchDiv.innerHTML = '<i class="fas fa-check me-1"></i>Passwords match!';
                matchDiv.className = 'password-strength strength-strong';
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times me-1"></i>Passwords do not match';
                matchDiv.className = 'password-strength strength-weak';
            }
        }

        document.getElementById('new_password').addEventListener('input', checkPasswordMatch);
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.classList.add('animate__animated', 'animate__pulse');
            });

            input.addEventListener('blur', function() {
                this.parentElement.parentElement.classList.remove('animate__animated', 'animate__pulse');
            });
        });

        <?php if (!empty($success)): ?>
        setTimeout(function() {
            window.location.href = '../login.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>

</html>