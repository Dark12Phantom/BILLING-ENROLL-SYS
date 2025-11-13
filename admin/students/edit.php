<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

protectPage();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$studentId = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM parents WHERE student_id = ?");
$stmt->execute([$studentId]);
$parent = $stmt->fetch();

$userId = $_SESSION['user_id'];
$stmtUser = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM user_tables WHERE id = ?");
$stmtUser->execute([$userId]);
$lastUpdatedBy = $stmtUser->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $today = date('Y-m-d H:i:s');

    $studentData = [
        'id' => $studentId,
        'student_id' => trim($_POST['student_id']),
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'date_of_birth' => $_POST['date_of_birth'],
        'gender' => $_POST['gender'],
        'address' => trim($_POST['address']),
        'mobile_number' => trim($_POST['mobile_number']),
        'grade_level' => trim($_POST['grade_level']),
        'section' => trim($_POST['section']),
        'status' => $_POST['status'],
        'lastUpdated' => $today,
        'last_updatedBy' => $lastUpdatedBy
    ];

    $studentEnrollmentData = [
        'student_id' => trim($_POST['student_id']),
        'schoolYear' => !empty(trim($_POST['school_year'])) ? trim($_POST['school_year']) : null,
        'status' => 'current'
    ];

    $parentData = [
        'first_name' => trim($_POST['parent_first_name']),
        'last_name' => trim($_POST['parent_last_name']),
        'relationship' => trim($_POST['relationship']),
        'mobile_number' => trim($_POST['parent_mobile_number']),
        'email' => trim($_POST['parent_email']),
        'address' => trim($_POST['parent_address'])
    ];

    $errors = [];

    if (empty($studentData['first_name']) || empty($studentData['last_name'])) {
        $errors[] = "Student name is required.";
    }

    if (empty($studentData['date_of_birth'])) {
        $errors[] = "Date of birth is required.";
    }

    if (empty($studentData['grade_level'])) {
        $errors[] = "Grade level is required.";
    }

    if (!empty($studentData['student_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? AND id != ?");
        $stmt->execute([$studentData['student_id'], $studentId]);
        if ($stmt->fetch()) {
            $errors[] = "Student ID already exists for another student.";
        }
    }

    $uploadDir = 'uploads/studentProfiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newImagePath = null;
    if (!empty($_FILES['id_picture']['name'])) {
        $file = $_FILES['id_picture'];

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
            $newImagePath = 'STU_' . $studentData['student_id'] . '_PFP.' . $fileExtension;
            $targetPath = $uploadDir . $newImagePath;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                if (!empty($student['idPicturePath']) && $student['idPicturePath'] !== $newImagePath) {
                    $oldImagePath = $uploadDir . $student['idPicturePath'];
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

            if ($newImagePath) {
                $studentData['idPicturePath'] = $newImagePath;
                $stmt = $pdo->prepare("UPDATE students SET 
                                      student_id = :student_id,
                                      first_name = :first_name,
                                      last_name = :last_name,
                                      date_of_birth = :date_of_birth,
                                      gender = :gender,
                                      address = :address,
                                      mobile_number = :mobile_number,
                                      grade_level = :grade_level,
                                      section = :section,
                                      status = :status,
                                      idPicturePath = :idPicturePath,
                                      lastUpdated = :lastUpdated,
                                      last_updatedBy = :last_updatedBy
                                      WHERE id = :id");
            } else {
                $stmt = $pdo->prepare("UPDATE students SET 
                                      student_id = :student_id,
                                      first_name = :first_name,
                                      last_name = :last_name,
                                      date_of_birth = :date_of_birth,
                                      gender = :gender,
                                      address = :address,
                                      mobile_number = :mobile_number,
                                      grade_level = :grade_level,
                                      section = :section,
                                      status = :status,
                                      lastUpdated = :lastUpdated,
                                      last_updatedBy = :last_updatedBy
                                      WHERE id = :id");
            }

            $stmt->execute($studentData);

            $stmt = $pdo->prepare("
                SELECT school_year 
                FROM enrollment_history
                WHERE student_id = ? AND status = 'current'
                LIMIT 1
            ");
            $stmt->execute([$studentId]);
            $existingCurrentSY = $stmt->fetchColumn();

            $currentSY = $studentEnrollmentData['schoolYear'];
            if ($currentSY === $existingCurrentSY) {
            } else if ($currentSY !== null && $currentSY !== '') {
                $stmt = $pdo->prepare("
                    UPDATE enrollment_history
                    SET status = 'past'
                    WHERE student_id = ?
                ");
                $stmt->execute([$studentId]);
                $stmt = $pdo->prepare("
                    SELECT id FROM enrollment_history
                    WHERE student_id = ? AND school_year = ?
                    LIMIT 1
                ");
                $stmt->execute([$studentId, $currentSY]);
                $exists = $stmt->fetchColumn();

                if ($exists) {
                    $stmt = $pdo->prepare("
                                        UPDATE enrollment_history
                                        SET 
                                            status = 'current',
                                            created_at = CONVERT_TZ(NOW(), '+00:00', '+08:00')
                                        WHERE id = ?
                                    ");
                    $stmt->execute([$exists]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO enrollment_history (student_id, school_year, status)
                        VALUES (?, ?, 'current')
                    ");
                    $stmt->execute([$studentId, $currentSY]);
                }
            }

            if ($parent) {
                $parentData['id'] = $parent['id'];
                $stmt = $pdo->prepare("UPDATE parents SET 
                                      first_name = :first_name,
                                      last_name = :last_name,
                                      relationship = :relationship,
                                      mobile_number = :mobile_number,
                                      email = :email,
                                      address = :address
                                      WHERE id = :id");
            } else {
                $parentData['student_id'] = $studentId;
                $stmt = $pdo->prepare("INSERT INTO parents 
                                      (student_id, first_name, last_name, relationship, mobile_number, email, address) 
                                      VALUES (:student_id, :first_name, :last_name, :relationship, :mobile_number, :email, :address)");
            }
            $stmt->execute($parentData);

            $pdo->commit();

            $_SESSION['success'] = "Student updated successfully!";
            header("Location: view.php?id=$studentId");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

$stmt = $pdo->prepare("
    SELECT school_year 
    FROM enrollment_history
    WHERE student_id = ? AND status = 'current'
    LIMIT 1
");
$stmt->execute([$student['id']]);
$currentSY = $stmt->fetchColumn();

$currentSY = $currentSY ?? '';

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Edit Student</h2>
        <hr>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <h4>Student Information</h4>

                    <div class="row g-3">
                        <div class="col-md-6 text-center">
                            <div class="mb-3">
                                <img src="" id="newIdPicturePreview" alt="" class="img-thumbnail mb-2" style="width: 200px; height: 200px; object-fit: cover;">
                                <div class="form-text">New ID Picture</div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label for="id_picture" class="form-label">Update ID Picture</label>
                            <input type="file" class="form-control" id="id_picture" name="id_picture" accept="image/*">
                            <div class="form-text">Upload a new passport-size photo (JPG, PNG, max 2MB)</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="student_id" name="student_id"
                            value="<?= htmlspecialchars($student['student_id'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                            value="<?= htmlspecialchars($student['first_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                            value="<?= htmlspecialchars($student['last_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                            value="<?= htmlspecialchars($student['date_of_birth']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= $student['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Home Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required><?= htmlspecialchars($student['address']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="mobile_number" class="form-label">Mobile Number</label>
                        <input type="tel" class="form-control" id="mobile_number" name="mobile_number"
                            value="<?= htmlspecialchars($student['mobile_number'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="grade_level" class="form-label">Grade Level</label>
                        <input type="text" class="form-control" id="grade_level" name="grade_level"
                            value="<?= htmlspecialchars($student['grade_level']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="section" class="form-label">Section</label>
                        <input type="text" class="form-control" id="section" name="section"
                            value="<?= htmlspecialchars($student['section']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="school_year" class="form-label">School Year</label>
                        <input type="text" class="form-control" id="school_year" name="school_year"
                            value="<?= htmlspecialchars($currentSY) ?>">
                    </div>

                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const schoolYearInput = document.getElementById("school_year");
                            const statusSelect = document.getElementById("status");

                            function computeSchoolYear() {
                                const year = new Date().getFullYear();
                                return `${year} - ${year + 1}`;
                            }

                            if (statusSelect.value === "Active" && !schoolYearInput.value) {
                                schoolYearInput.value = computeSchoolYear();
                            }

                            statusSelect.addEventListener("change", () => {
                                const status = statusSelect.value;
                                if (status === "Active") {
                                    schoolYearInput.value = computeSchoolYear();
                                } else {
                                    schoolYearInput.value = "";
                                }
                            });
                        });
                    </script>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Active" <?= $student['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $student['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="Graduated" <?= $student['status'] === 'Graduated' ? 'selected' : '' ?>>Graduated</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <h4>Emergency Contact Information</h4>
                    <div class="mb-3">
                        <label for="parent_first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="parent_first_name" name="parent_first_name"
                            value="<?= htmlspecialchars($parent['first_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="parent_last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="parent_last_name" name="parent_last_name"
                            value="<?= htmlspecialchars($parent['last_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="relationship" class="form-label">Relationship</label>
                        <input type="text" class="form-control" id="relationship" name="relationship"
                            value="<?= htmlspecialchars($parent['relationship'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="parent_mobile_number" class="form-label">Mobile Number</label>
                        <input type="tel" class="form-control" id="parent_mobile_number" name="parent_mobile_number"
                            value="<?= htmlspecialchars($parent['mobile_number'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="parent_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="parent_email" name="parent_email"
                            value="<?= htmlspecialchars($parent['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="parent_address" class="form-label">Address</label>
                        <textarea class="form-control" id="parent_address" name="parent_address" rows="3"><?= htmlspecialchars($parent['address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Update Student</button>
                <a href="view.php?id=<?= $studentId ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('id_picture');
        const newImagePreview = document.getElementById('newIdPicturePreview');

        fileInput.addEventListener('change', function() {
            const file = this.files[0];

            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, PNG, and GIF files are allowed.');
                    this.value = '';
                    newImagePreview.src = '';
                    newImagePreview.style.display = 'none';
                    return;
                }

                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB.');
                    this.value = '';
                    newImagePreview.src = '';
                    newImagePreview.style.display = 'none';
                    return;
                }

                const reader = new FileReader();

                reader.addEventListener('load', function() {
                    newImagePreview.src = reader.result;
                    newImagePreview.style.display = 'block';
                });

                reader.readAsDataURL(file);
            } else {
                newImagePreview.src = '';
                newImagePreview.style.display = 'none';
            }
        });

        const dobInput = document.getElementById('date_of_birth');
        dobInput.addEventListener('change', function() {
            if (this.value) {
                const dob = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }

                console.log('Age:', age);
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dobInput = document.getElementById('date_of_birth');
        dobInput.addEventListener('change', function() {
            if (this.value) {
                const dob = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }

                console.log('Age:', age);
            }
        });

        const fileInput = document.getElementById('id_picture');
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    console.log('File selected:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    });
</script>

<?php require_once '../../includes/footer.php'; ?>