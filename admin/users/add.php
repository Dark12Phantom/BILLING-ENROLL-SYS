<?php
require_once '../../includes/auth.php';
protectPage();

// Initialize variables
$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $requiredFields = [
        'staff_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'address',
        'username',
        'email',
        'password'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $error = "Please fill in all required fields.";
            break;
        }
    }

    // Validate email format
    if (empty($error) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }

    // Check if username or email already exists
    if (empty($error)) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$_POST['username'], $_POST['email']]);
        if ($checkStmt->fetch()) {
            $error = "Username or email already exists.";
        }
    }

    if (empty($error)) {
        $userData = [
            'idPicturePath' => null,
            'staff_id' => $_POST['staff_id'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'date_of_birth' => $_POST['date_of_birth'],
            'gender' => $_POST['gender'],
            'status' => 'Active',
            'address' => $_POST['address'],
            'mobile_number' => $_POST['mobile_number'],
            'user_type' => 'staff'
        ];

        $uploadDir = '../../uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Handle file upload
        if (!empty($_FILES['idPicturePath']['name'])) {
            $fileExtension = pathinfo($_FILES['idPicturePath']['name'], PATHINFO_EXTENSION);
            $fileName = 'STA_' . $_POST['staff_id'] . 'PFP.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['idPicturePath']['tmp_name'], $targetPath)) {
                $userData['idPicturePath'] = $fileName;
            } else {
                $error = "Failed to upload ID picture.";
            }
        }

        if (empty($error)) {
            try {
                $pdo->beginTransaction();

                $userAccountData = [
                    'username' => $_POST['username'],
                    'email' => $_POST['email'],
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'role' => 'staff',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $userStmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) 
                                          VALUES (:username, :email, :password, :role, :created_at)");
                $userStmt->execute($userAccountData);
                $userId = $pdo->lastInsertId();

                $userData['userID'] = $userId;
                $stmt = $pdo->prepare("INSERT INTO user_tables (userID, staff_id, first_name, last_name, date_of_birth, gender, address, mobile_number, user_type, status,idPicturePath) 
                                      VALUES (:userID, :staff_id, :first_name, :last_name, :date_of_birth, :gender, :address, :mobile_number, :user_type, :status,:idPicturePath)");
                $stmt->execute($userData);

                $pdo->commit();

                $success = "Staff added successfully!";
                echo '
                    <div class="container mt-5">
                        <div class="alert alert-success text-center shadow-sm" role="alert" style="max-width:600px;margin:auto;">
                            <h4 class="alert-heading">Adding Staff Success!</h4>
                            <p>Staff added successfully. Redirecting to staff list in <strong>3 seconds...</strong></p>
                        </div>
                    </div>
                    <meta http-equiv="refresh" content="3;url=index.php">
                    ';
                exit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Error adding staff: " . $e->getMessage();
            }
        }
    }
}


require_once '../../includes/header.php';
?>

<style>
    .preview-container {
        width: 200px;
        height: 200px;
        border: 2px dashed #ccc;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin: 15px auto;
        background-color: #f8f9fa;
        transition: all 0.3s ease;
    }

    .preview-container img {
        max-width: 100%;
        max-height: 100%;
        display: none;
    }

    .upload-icon {
        font-size: 3rem;
        color: #6c757d;
    }

    #uploadArea {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    #uploadArea:hover {
        border-color: #0d6efd;
        background-color: #e9ecef;
    }

    .form-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .section-title {
        border-bottom: 2px solid #0d6efd;
        padding-bottom: 10px;
        margin-bottom: 20px;
        color: #0d6efd;
    }

    .required-field::after {
        content: "*";
        color: red;
        margin-left: 4px;
    }

    .btn-action {
        min-width: 120px;
    }

    .password-toggle {
        cursor: pointer;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <h2 class="text-center mb-4">Add New Staff</h2>

        <!-- Display error or success messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Staff Information Form</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="staffForm">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="section-title">Staff Information</h5>

                            <div class="text-center mb-4">
                                <div class="preview-container" id="uploadArea">
                                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <img id="imagePreview" alt="ID Picture Preview">
                                </div>
                                <div class="mb-3">
                                    <label for="id_picture" class="form-label required-field">Staff ID Picture</label>
                                    <input type="file" class="form-control" id="id_picture" name="idPicturePath" accept="image/*" required>
                                    <div class="form-text">Upload a passport-size photo (JPG, PNG, max 2MB)</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="staff_id" class="form-label required-field">Staff ID</label>
                                <input type="text" class="form-control" id="staff_id" name="staff_id" value="<?php echo isset($_POST['staff_id']) ? htmlspecialchars($_POST['staff_id']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="first_name" class="form-label required-field">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label required-field">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label required-field">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="gender" class="form-label required-field">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="section-title">Account Information</h5>

                            <div class="mb-3">
                                <label for="username" class="form-label required-field">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label required-field">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label required-field">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <span class="input-group-text password-toggle" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                                <div class="form-text">Password must be at least 8 characters long</div>
                            </div>

                            <h5 class="section-title mt-4">Additional Information</h5>

                            <div class="mb-3">
                                <label for="address" class="form-label required-field">Home Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="mobile_number" class="form-label">Mobile Number</label>
                                <input type="tel" maxlength="11" pattern="[0-9]{11}" class="form-control" id="mobile_number" name="mobile_number" value="<?php echo isset($_POST['mobile_number']) ? htmlspecialchars($_POST['mobile_number']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary btn-lg btn-action"><i class="fas fa-save me-2"></i>Save</button>
                        <a href="index.php" class="btn btn-secondary btn-lg ms-2 btn-action"><i class="fas fa-times me-2"></i>Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('id_picture');
        const imagePreview = document.getElementById('imagePreview');
        const uploadArea = document.getElementById('uploadArea');
        const uploadIcon = uploadArea.querySelector('.upload-icon');
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');

        (function() {
                const today = new Date();
                const y = today.getFullYear();
                const m = String(today.getMonth() + 1).padStart(2, '0');
                const d = String(today.getDate()).padStart(2, '0');
                const maxDate = `${y}-${m}-${d}`;
                const el = document.getElementById('date_of_birth');
                if (el) el.max = maxDate;

                document.addEventListener('submit', function(e) {
                    const input = document.getElementById('date_of_birth');
                    if (!input) return;
                    if (input.value && input.value > maxDate) {
                        e.preventDefault();
                        input.setCustomValidity('Please choose today or a past date.');
                        input.reportValidity();
                    } else {
                        input.setCustomValidity('');
                    }
                }, true);
            })();

        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            const file = this.files[0];

            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size exceeds 2MB. Please choose a smaller file.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();

                reader.addEventListener('load', function() {
                    imagePreview.src = reader.result;
                    imagePreview.style.display = 'block';
                    uploadIcon.style.display = 'none';
                });

                reader.readAsDataURL(file);
            }
        });

        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#0d6efd';
            uploadArea.style.backgroundColor = '#e9ecef';
        });

        uploadArea.addEventListener('dragleave', function() {
            uploadArea.style.borderColor = '#ccc';
            uploadArea.style.backgroundColor = '#f8f9fa';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#ccc';
            uploadArea.style.backgroundColor = '#f8f9fa';

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        });

        // Password toggle functionality
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Form validation
        document.getElementById('staffForm').addEventListener('submit', function(e) {
            let valid = true;
            const requiredFields = this.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            const password = document.getElementById('password').value;
            if (password.length < 8) {
                valid = false;
                document.getElementById('password').classList.add('is-invalid');
                alert('Password must be at least 8 characters long.');
            }

            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
            }
        });
    });
</script>

<?php require_once '../../includes/footer.php'; ?>