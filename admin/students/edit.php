<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

protectPage();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$studentId = $_GET['id'];

$stmt = $pdo->prepare("SELECT s.*, eh.grade_level FROM students s 
                       LEFT JOIN enrollment_history eh ON eh.student_id = s.id AND eh.status = 'current'
                       WHERE s.id = ?");
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

    $gradeLevelPosted = trim($_POST['grade_level'] ?? '');
    if (empty($gradeLevelPosted)) {
        $errors[] = "Grade level is required.";
    }

    if (!empty($studentData['student_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? AND id != ?");
        $stmt->execute([$studentData['student_id'], $studentId]);
        if ($stmt->fetch()) {
            $errors[] = "Student ID already exists for another student.";
        }
    }

    $uploadDir = '../../uploads/studentProfiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newImagePath = null;
    
    if (!empty($_POST['croppedImage'])) {
        $imageData = $_POST['croppedImage'];
        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $decodedImage = base64_decode($imageData);
        
        $newImagePath = 'STU_' . $studentData['student_id'] . '_PFP.jpg';
        $targetPath = $uploadDir . $newImagePath;
        
        if (file_put_contents($targetPath, $decodedImage)) {
            if (!empty($student['idPicturePath']) && $student['idPicturePath'] !== $newImagePath) {
                $oldImagePath = $uploadDir . $student['idPicturePath'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        } else {
            $errors[] = "Failed to save ID picture.";
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
                                      section = :section,
                                      status = :status,
                                      lastUpdated = :lastUpdated,
                                      last_updatedBy = :last_updatedBy
                                      WHERE id = :id");
            }

            $stmt->execute($studentData);

            $year = (int)date('Y');
            $syPast = ($year - 1) . ' - ' . $year;
            $syCurrent = $year . ' - ' . ($year + 1);
            $syNext = ($year + 1) . ' - ' . ($year + 2);
            $postedSY = $studentEnrollmentData['schoolYear'] ?? null;

            $upsert = function($sy, $status, $gradeLevel = null) use ($pdo, $studentId) {
                $stmt = $pdo->prepare("SELECT id, status FROM enrollment_history WHERE student_id = ? AND school_year = ? LIMIT 1");
                $stmt->execute([$studentId, $sy]);
                $row = $stmt->fetch();
                if ($row && isset($row['id'])) {
                    if ($gradeLevel === null) {
                        if ($row['status'] !== $status) {
                            $upd = $pdo->prepare("UPDATE enrollment_history SET status = ? WHERE id = ?");
                            $upd->execute([$status, $row['id']]);
                        }
                    } else {
                        $upd = $pdo->prepare("UPDATE enrollment_history SET status = ?, grade_level = ? WHERE id = ?");
                        $upd->execute([$status, $gradeLevel, $row['id']]);
                    }
                } else {
                    if ($gradeLevel === null) {
                        $ins = $pdo->prepare("INSERT INTO enrollment_history (student_id, school_year, status) VALUES (?, ?, ?)");
                        $ins->execute([$studentId, $sy, $status]);
                    } else {
                        $ins = $pdo->prepare("INSERT INTO enrollment_history (student_id, school_year, status, grade_level) VALUES (?, ?, ?, ?)");
                        $ins->execute([$studentId, $sy, $status, $gradeLevel]);
                    }
                }
            };

            // Update statuses for timeline; set grade level only on posted school year
            $upsert($syPast, 'past');
            $upsert($syCurrent, 'current', ($postedSY === $syCurrent) ? $gradeLevelPosted : null);
            if ($postedSY === $syNext) {
                $upsert($syNext, 'pre-enrollment', $gradeLevelPosted);
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

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <style>
        .crop-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
        }

        .crop-modal-content {
            background-color: #fff;
            margin: 2% auto;
            padding: 20px;
            width: 90%;
            max-width: 800px;
            border-radius: 8px;
        }

        .crop-container {
            max-height: 500px;
            overflow: hidden;
            margin: 20px 0;
        }

        .crop-container img {
            max-width: 100%;
        }

        .crop-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        #cameraContainer {
            text-align: center;
            margin-top: 15px;
        }

        #cameraPreview {
            max-width: 100%;
            width: 400px;
            background: #000;
            border: 2px solid #dee2e6;
            border-radius: 4px;
            margin: 10px auto;
            display: block;
        }

        .camera-controls {
            margin-top: 10px;
        }

        .camera-controls button {
            margin: 0 5px;
        }
    </style>
</head>

<div class="row">
    <div class="col-md-12">
        <h2>Edit Student</h2>
        <hr>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="studentForm">
            <div class="row">
                <div class="col-md-6">
                    <h4>Student Information</h4>

                    <div class="row g-3">
                        <div class="col-md-12 text-center">
                            <div class="mb-3">
                                <?php if (!empty($student['idPicturePath'])): ?>
                                    <img src="../../uploads/studentProfiles/<?= htmlspecialchars($student['idPicturePath']) ?>" 
                                         id="currentIdPicture" 
                                         alt="Current ID Picture" 
                                         class="img-thumbnail mb-2" 
                                         style="width: 200px; height: 200px; object-fit: cover;">
                                    <div class="form-text">Current ID Picture</div>
                                <?php else: ?>
                                    <div class="alert alert-info">No ID picture uploaded</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <img src="" id="newIdPicturePreview" alt="" class="img-thumbnail mb-2" style="width: 200px; height: 200px; object-fit: cover; display: none;">
                                <div class="form-text" id="newPictureLabel" style="display: none;">New ID Picture (Preview)</div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Update ID Picture</label>
                            
                            <div class="btn-group w-100 mb-2" role="group">
                                <button type="button" class="btn btn-outline-primary" id="useCameraBtn">
                                    <i class="bi bi-camera"></i> Use Camera
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="useFileBtn">
                                    <i class="bi bi-upload"></i> Upload File
                                </button>
                            </div>

                            <div id="cameraContainer" style="display:none;">
                                <video id="cameraPreview" autoplay playsinline></video>
                                <div class="camera-controls">
                                    <button type="button" class="btn btn-success" id="captureBtn">
                                        <i class="bi bi-camera-fill"></i> Capture Photo
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="stopCameraBtn">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </button>
                                </div>
                            </div>

                            <input type="file" id="fileInput" accept="image/*" style="display:none;">
                            <input type="hidden" name="croppedImage" id="croppedImageData">
                            
                            <div class="form-text">Take a photo or upload a passport-size photo (JPG, PNG, max 2MB)</div>
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
                        <div class="input-group">
                            <span class="input-group-text">+63</span>
                            <input type="tel" class="form-control" id="mobile_number" name="mobile_number"
                                value="<?= htmlspecialchars(preg_replace('/^\+63/', '', $student['mobile_number'] ?? '')) ?>" 
                                placeholder="9XXXXXXXXX" maxlength="10" pattern="9[0-9]{9}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="grade_level" class="form-label">Grade Level</label>
                        <input type="text" class="form-control" id="grade_level" name="grade_level"
                            value="<?= htmlspecialchars($student['grade_level'] ?? '') ?>" required>
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
                        <div class="input-group">
                            <span class="input-group-text">+63</span>
                            <input type="tel" class="form-control" id="parent_mobile_number" name="parent_mobile_number"
                                value="<?= htmlspecialchars(preg_replace('/^\+63/', '', $parent['mobile_number'] ?? '')) ?>" 
                                placeholder="9XXXXXXXXX" maxlength="10" pattern="9[0-9]{9}">
                        </div>
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

<div id="cropModal" class="crop-modal">
    <div class="crop-modal-content">
        <h4 class="text-center mb-3">Crop Your Photo</h4>
        <div class="crop-container">
            <img id="cropImage" src="">
        </div>
        <div class="crop-buttons">
            <button type="button" class="btn btn-success" id="cropConfirm">
                <i class="bi bi-check-circle"></i> Crop & Use
            </button>
            <button type="button" class="btn btn-secondary" id="cropCancel">
                <i class="bi bi-x-circle"></i> Cancel
            </button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
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

    let cropper = null;
    let stream = null;

    const fileInput = document.getElementById('fileInput');
    const newImagePreview = document.getElementById('newIdPicturePreview');
    const newPictureLabel = document.getElementById('newPictureLabel');
    const cropModal = document.getElementById('cropModal');
    const cropImage = document.getElementById('cropImage');
    const cameraContainer = document.getElementById('cameraContainer');
    const video = document.getElementById('cameraPreview');

    document.getElementById('useFileBtn').addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            fileInput.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (event) => {
            cropImage.src = event.target.result;
            cropModal.style.display = 'block';
            initCropper();
        };
        reader.readAsDataURL(file);
    });

    document.getElementById('useCameraBtn').addEventListener('click', async () => {
        cameraContainer.style.display = 'block';
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user' },
                audio: false
            });
            video.srcObject = stream;
        } catch (err) {
            alert('Cannot access camera: ' + err.message);
            cameraContainer.style.display = 'none';
        }
    });

    document.getElementById('captureBtn').addEventListener('click', () => {
        if (!stream) return;
        
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        cropImage.src = canvas.toDataURL('image/jpeg');
        cropModal.style.display = 'block';
        initCropper();
        stopCamera();
    });

    document.getElementById('stopCameraBtn').addEventListener('click', stopCamera);

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        cameraContainer.style.display = 'none';
    }

    function initCropper() {
        if (cropper) {
            cropper.destroy();
        }
        
        cropper = new Cropper(cropImage, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.8,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
        });
    }

    document.getElementById('cropConfirm').addEventListener('click', () => {
        if (!cropper) return;
        
        const canvas = cropper.getCroppedCanvas({
            width: 400,
            height: 400,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });
        
        const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);
        
        newImagePreview.src = croppedDataUrl;
        newImagePreview.style.display = 'block';
        newPictureLabel.style.display = 'block';
        
        document.getElementById('croppedImageData').value = croppedDataUrl;
        
        cropModal.style.display = 'none';
        cropper.destroy();
        cropper = null;
    });

    document.getElementById('cropCancel').addEventListener('click', () => {
        cropModal.style.display = 'none';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        fileInput.value = '';
    });

    document.getElementById('studentForm').addEventListener('submit', function(e) {
        const dobInput = document.getElementById('date_of_birth');
        if (dobInput.value) {
            const dob = new Date(dobInput.value);
            const today = new Date();
            if (dob > today) {
                e.preventDefault();
                alert('Date of birth cannot be in the future.');
                return false;
            }
        }
        
        const studentMobile = document.getElementById('mobile_number');
        const parentMobile = document.getElementById('parent_mobile_number');
        
        if (studentMobile.value && !studentMobile.value.startsWith('+63')) {
            studentMobile.value = '+63' + studentMobile.value;
        }
        
        if (parentMobile.value && !parentMobile.value.startsWith('+63')) {
            parentMobile.value = '+63' + parentMobile.value;
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>
