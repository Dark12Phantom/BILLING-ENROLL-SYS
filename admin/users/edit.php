<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

protectPage();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$staffId = $_GET['id'];

// Fetch staff data from user_tables
$stmt = $pdo->prepare("SELECT * FROM user_tables WHERE id = ?");
$stmt->execute([$staffId]);
$staff = $stmt->fetch();

if (!$staff) {
    header("Location: index.php");
    exit();
}

// Fetch user account data from users table
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$staff['userID']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffData = [
        'id' => $staffId,
        'staff_id' => trim($_POST['staff_id']),
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'date_of_birth' => $_POST['date_of_birth'],
        'gender' => $_POST['gender'],
        'address' => trim($_POST['address']),
        'mobile_number' => trim($_POST['mobile_number'])
    ];

    $userData = [
        'id' => $staff['userID'],
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email'])
    ];

    // Only update password if provided
    if (!empty($_POST['password'])) {
        $userData['password'] = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    }

    $errors = [];

    if (empty($staffData['first_name']) || empty($staffData['last_name'])) {
        $errors[] = "Staff name is required.";
    }

    if (empty($staffData['date_of_birth'])) {
        $errors[] = "Date of birth is required.";
    }

    if (empty($userData['username']) || empty($userData['email'])) {
        $errors[] = "Username and email are required.";
    }

    if (!empty($userData['email']) && !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Check if username or email already exists for another user
    if (!empty($userData['username'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$userData['username'], $userData['email'], $userData['id']]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already exists for another user.";
        }
    }

    $uploadDir = '../../uploads/profiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newImagePath = null;
    if (!empty($_FILES['idPicturePath']['name'])) {
        $file = $_FILES['idPicturePath'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Only JPG, PNG, and GIF files are allowed.";
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = "File size must be less than 2MB.";
        }

        if (empty($errors)) {
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newImagePath = 'STA_' . $staffData['staff_id'] . '_PFP.' . $fileExtension;
            $targetPath = $uploadDir . $newImagePath;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                if (!empty($staff['idPicturePath']) && $staff['idPicturePath'] !== $newImagePath) {
                    $oldImagePath = $uploadDir . $staff['idPicturePath'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            } else {
                $errors[] = "Failed to upload ID picture.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update user_tables
            if ($newImagePath) {
                $staffData['idPicturePath'] = $newImagePath;
                $stmt = $pdo->prepare("UPDATE user_tables SET 
                                      staff_id = :staff_id,
                                      first_name = :first_name,
                                      last_name = :last_name,
                                      date_of_birth = :date_of_birth,
                                      gender = :gender,
                                      address = :address,
                                      mobile_number = :mobile_number,
                                      idPicturePath = :idPicturePath
                                      WHERE id = :id");
            } else {
                $stmt = $pdo->prepare("UPDATE user_tables SET 
                                      staff_id = :staff_id,
                                      first_name = :first_name,
                                      last_name = :last_name,
                                      date_of_birth = :date_of_birth,
                                      gender = :gender,
                                      address = :address,
                                      mobile_number = :mobile_number
                                      WHERE id = :id");
            }

            $stmt->execute($staffData);

            // Update users table
            if (isset($userData['password'])) {
                $stmt = $pdo->prepare("UPDATE users SET 
                                      username = :username,
                                      email = :email,
                                      password = :password
                                      WHERE id = :id");
            } else {
                $stmt = $pdo->prepare("UPDATE users SET 
                                      username = :username,
                                      email = :email
                                      WHERE id = :id");
            }
            $stmt->execute($userData);

            $pdo->commit();

            $_SESSION['success'] = "Staff updated successfully!";
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
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
    }

    .upload-icon {
        font-size: 3rem;
        color: #6c757d;
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
        <h2 class="text-center mb-4">Edit Staff</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Staff Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="staffForm">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="section-title">Staff Information</h5>

                            <div class="text-center mb-4">
                                <div class="preview-container">
                                    <?php if (!empty($staff['idPicturePath'])): ?>
                                        <img src="../../uploads/profiles/<?= htmlspecialchars($staff['idPicturePath']) ?>"
                                            alt="Current ID Picture" class="img-thumbnail" id="currentImagePreview">
                                    <?php else: ?>
                                        <div class="upload-icon"><i class="fas fa-user"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="id_picture" class="form-label">Update ID Picture</label>
                                    <input type="file" class="form-control" id="id_picture" name="id_picture" accept="image/*">
                                    <div class="form-text">Upload a new passport-size photo (JPG, PNG, max 2MB)</div>
                                </div>
                                <div class="mb-3 d-none" id="newImageContainer">
                                    <img src="" id="newImagePreview" alt="New ID Picture" class="img-thumbnail mb-2" style="width: 200px; height: 200px; object-fit: cover;">
                                    <div class="form-text">New ID Picture Preview</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="staff_id" class="form-label required-field">Staff ID</label>
                                <input type="text" class="form-control" id="staff_id" name="staff_id"
                                    value="<?= htmlspecialchars($staff['staff_id'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="first_name" class="form-label required-field">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                    value="<?= htmlspecialchars($staff['first_name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label required-field">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                    value="<?= htmlspecialchars($staff['last_name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label required-field">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                    value="<?= htmlspecialchars($staff['date_of_birth']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="gender" class="form-label required-field">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= $staff['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $staff['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $staff['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="section-title">Account Information</h5>

                            <div class="mb-3">
                                <label for="username" class="form-label required-field">Username</label>
                                <input type="text" class="form-control" id="username" name="username"
                                    value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label required-field">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password">
                                    <span class="input-group-text password-toggle" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                                <div class="form-text">Leave blank to keep current password</div>
                            </div>

                            <h5 class="section-title mt-4">Contact Information</h5>

                            <div class="mb-3">
                                <label for="address" class="form-label required-field">Home Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required>
                                    <?= htmlspecialchars($staff['address'] ?? '') ?>
                                </textarea>
                            </div>
                            <div class="mb-3">
                                <label for="mobile_number" class="form-label">Mobile Number</label>
                                <input type="tel" class="form-control" id="mobile_number" name="mobile_number"
                                    value="<?= htmlspecialchars($staff['mobile_number'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary btn-lg btn-action"><i class="fas fa-save me-2"></i>Update Staff</button>
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
        const newImagePreview = document.getElementById('newImagePreview');
        const newImageContainer = document.getElementById('newImageContainer');
        const currentImagePreview = document.getElementById('currentImagePreview');
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');

        fileInput.addEventListener('change', function() {
            const file = this.files[0];

            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, PNG, and GIF files are allowed.');
                    this.value = '';
                    newImagePreview.src = '';
                    newImageContainer.classList.add('d-none');
                    return;
                }

                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB.');
                    this.value = '';
                    newImagePreview.src = '';
                    newImageContainer.classList.add('d-none');
                    return;
                }

                const reader = new FileReader();

                reader.addEventListener('load', function() {
                    newImagePreview.src = reader.result;
                    newImageContainer.classList.remove('d-none');
                    if (currentImagePreview) {
                        currentImagePreview.style.display = 'none';
                    }
                });

                reader.readAsDataURL(file);
            } else {
                newImagePreview.src = '';
                newImageContainer.classList.add('d-none');
                if (currentImagePreview) {
                    currentImagePreview.style.display = 'block';
                }
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

            // Validate email format
            const email = document.getElementById('email').value;
            if (email && !/\S+@\S+\.\S+/.test(email)) {
                valid = false;
                document.getElementById('email').classList.add('is-invalid');
                alert('Please enter a valid email address.');
            }

            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
            }
        });
    });
</script>

<?php require_once '../../includes/footer.php'; ?>