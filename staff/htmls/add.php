<?php
require_once '../includes/staff-auth.php';
protectPage();

require_once '../includes/staff-header.php';

$userId = $_SESSION['user_id'];
$stmtUser = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM user_tables WHERE id = ?");
$stmtUser->execute([$userId]);
$createdBy = $stmtUser->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentData = [
        'idPicturePath' => null,
        'student_id' => $_POST['student_id'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'date_of_birth' => $_POST['date_of_birth'],
        'gender' => $_POST['gender'],
        'address' => $_POST['address'],
        'mobile_number' => $_POST['mobile_number'],
        'grade_level' => $_POST['grade_level'],
        'section' => $_POST['section'],
        'createdBy' => $createdBy
    ];

    $studentEnrollmentData = [
        'student_id' => $_POST['student_id'],
        'school_year' => $_POST['school_year'],
        'status' => 'current'
    ];

    $parentData = [
        'first_name' => $_POST['parent_first_name'],
        'last_name' => $_POST['parent_last_name'],
        'relationship' => $_POST['relationship'],
        'mobile_number' => $_POST['parent_mobile_number'],
        'email' => $_POST['parent_email'],
        'address' => $_POST['parent_address']
    ];

    $uploadDir = '../../admin/students/uploads/studentProfiles';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $studentData['idPicturePath'] = null;

    if (!empty($_FILES['idPicturePath']['name'])) {
        $fileName = 'STU_' . $_POST['student_id'] . 'PFP';
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['idPicturePath']['tmp_name'], $targetPath)) {
            $studentData['idPicturePath'] = $fileName;
        } else {
            $error = "Failed to upload ID picture.";
        }
    }


    $check = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
    $check->execute([$studentData['student_id']]);

    if ($check->fetch()) {
        $_SESSION['error'] = "This student already exists. Update their record instead.";
        echo '
                <div class="container mt-5">
                    <div class="alert alert-danger text-center shadow-sm" role="alert" style="max-width:600px;margin:auto;">
                        <h4 class="alert-heading">Student Already Exists</h4>
                        <p class="mb-0">This pupil has been recorded before.</strong></p>
                    </div>
                </div>
            ';
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, last_name, date_of_birth, gender, address, mobile_number, grade_level, section, idPicturePath, createdBy) 
                              VALUES (:student_id, :first_name, :last_name, :date_of_birth, :gender, :address, :mobile_number, :grade_level, :section, :idPicturePath, :createdBy)");
        $stmt->execute($studentData);
        $studentId = $pdo->lastInsertId();

        $studentEnrollmentData['student_id'] = $studentId;
        $stmt = $pdo->prepare("INSERT INTO enrollment_history (student_id, school_year, status)
                                VALUES (:student_id, :school_year, :status)");
        $stmt->execute($studentEnrollmentData);

        $parentData['student_id'] = $studentId;
        $stmt = $pdo->prepare("INSERT INTO parents (student_id, first_name, last_name, relationship, mobile_number, email, address) 
                              VALUES (:student_id, :first_name, :last_name, :relationship, :mobile_number, :email, :address)");
        $stmt->execute($parentData);

        $pdo->commit();

        $_SESSION['success'] = "Student added successfully!";
        echo '
            <div class="container mt-5">
                <div class="alert alert-success text-center shadow-sm" role="alert" style="max-width:600px;margin:auto;">
                    <h4 class="alert-heading">Student Added Successfully!</h4>
                    <p class="mb-0">You will be redirected to the student list in <strong>3 seconds...</strong></p>
                </div>
            </div>
            <meta http-equiv="refresh" content="3;url=index.php">
            ';
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error adding student: " . $e->getMessage();
    }
}
?>

<head>
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
    </style>
</head>


<body>
    <div class="container py-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-center mb-4">Add New Student</h2>

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Student Information Form</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="section-title">Student Information</h5>

                                    <div class="text-center mb-4">
                                        <div class="preview-container" id="uploadArea">
                                            <div class="upload-icon">📷</div>
                                            <img id="imagePreview" alt="ID Picture Preview">
                                        </div>
                                        <div class="mb-3">
                                            <label for="id_picture" class="form-label">Student ID Picture</label>

                                            <!-- Button group for choosing input method -->
                                            <div class="btn-group w-100 mb-2" role="group">
                                                <button type="button" class="btn btn-outline-primary" id="useCameraBtn">
                                                    <i class="bi bi-camera"></i> Use Camera
                                                </button>
                                                <button type="button" class="btn btn-outline-primary" id="useFileBtn">
                                                    <i class="bi bi-upload"></i> Upload File
                                                </button>
                                            </div>

                                            <!-- Camera preview container (hidden by default) -->
                                            <div id="cameraContainer" style="display: none;">
                                                <div style="position: relative; display: inline-block;">
                                                    <video id="cameraPreview" autoplay playsinline style="width: 100%; max-width: 400px; background: #000; border: 2px solid #dee2e6; border-radius: 4px;"></video>
                                                    <div id="cameraStatus" style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px;">
                                                        Initializing camera...
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-success" id="captureBtn" disabled>
                                                        <i class="bi bi-camera-fill"></i> Capture Photo
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="stopCameraBtn">
                                                        <i class="bi bi-x-circle"></i> Cancel
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- File input -->
                                            <input type="file" class="form-control" id="id_picture" name="idPicturePath" accept="image/*" required hidden>

                                            <!-- Hidden canvas for capturing photo -->
                                            <canvas id="captureCanvas" style="display: none;"></canvas>

                                            <!-- Crop editor (hidden by default) -->
                                            <div id="cropContainer" style="display: none;">
                                                <div style="position: relative; max-width: 500px; margin: 0 auto;">
                                                    <canvas id="cropCanvas" style="max-width: 100%; border: 2px solid #dee2e6; border-radius: 4px; cursor: crosshair;"></canvas>
                                                    <div id="cropOverlay" style="position: absolute; border: 2px dashed #fff; box-shadow: 0 0 0 9999px rgba(0,0,0,0.5); pointer-events: none;"></div>
                                                </div>
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-primary" id="applyCropBtn">
                                                        <i class="bi bi-check-circle"></i> Apply Crop
                                                    </button>
                                                    <button type="button" class="btn btn-secondary" id="cancelCropBtn">
                                                        <i class="bi bi-x-circle"></i> Cancel
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" id="resetCropBtn">
                                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                                    </button>
                                                </div>
                                                <div class="form-text mt-2">Click and drag to select the area to crop</div>
                                            </div>

                                            <!-- Preview of final image -->
                                            <div id="imagePreview" class="mt-2" style="display: none;">
                                                <img id="previewImg" src="" alt="Preview" style="max-width: 100%; max-height: 300px; border: 2px solid #dee2e6; border-radius: 4px;">
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-warning btn-sm" id="editImageBtn">
                                                        <i class="bi bi-crop"></i> Edit/Crop Again
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" id="removeImageBtn">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="form-text">Take a photo or upload a passport-size photo (JPG, PNG, max 2MB)</div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="student_id" class="form-label">Student ID</label>
                                        <input type="text" class="form-control" id="student_id" name="student_id" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="schoolYear" class="form-label">School Year</label>
                                        <input type="text" class="form-control" id="schoolYear" name="school_year" readonly>
                                    </div>
                                </div>

                                <script>
                                    const currentYear = new Date().getFullYear();
                                    const nextYear = currentYear + 1;
                                    document.getElementById("schoolYear").value = `${currentYear} - ${nextYear}`;
                                </script>

                                <div class="col-md-6">
                                    <h5 class="section-title">Additional Information</h5>

                                    <div class="mb-3">
                                        <label for="address" class="form-label">Home Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="mobile_number" class="form-label">Mobile Number</label>
                                        <input type="tel" class="form-control" maxlength="11" pattern="[0-9]{11}" id="mobile_number" name="mobile_number">
                                    </div>
                                    <div class="mb-3">
                                        <label for="grade_level" class="form-label">Grade Level</label>
                                        <select class="form-select" id="grade_level" name="grade_level" required>
                                            <option value="" disabled selected> Select Grade Level </option>
                                            <option value="Kindergarten">Kindergarten</option>
                                            <option value="Grade 1">Grade 1</option>
                                            <option value="Grade 2">Grade 2</option>
                                            <option value="Grade 3">Grade 3</option>
                                            <option value="Grade 4">Grade 4</option>
                                            <option value="Grade 5">Grade 5</option>
                                            <option value="Grade 6">Grade 6</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="section" class="form-label">Section</label>
                                        <input type="text" class="form-control" id="section" name="section" required>
                                    </div>

                                    <h5 class="section-title mt-4">Emergency Contact Information</h5>

                                    <div class="mb-3">
                                        <label for="parent_first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="parent_first_name" name="parent_first_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="parent_last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="parent_last_name" name="parent_last_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="relationship" class="form-label">Relationship</label>
                                        <input type="text" class="form-control" id="relationship" name="relationship" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="parent_mobile_number" class="form-label">Mobile Number</label>
                                        <input type="tel" class="form-control" maxlength="11" pattern="[0-9]{11}" id="parent_mobile_number" name="parent_mobile_number" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="parent_email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="parent_email" name="parent_email">
                                    </div>
                                    <div class="mb-3">
                                        <label for="parent_address" class="form-label">Address</label>
                                        <textarea class="form-control" id="parent_address" name="parent_address" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 text-center">
                                <button type="submit" class="btn btn-primary btn-lg">Save Student</button>
                                <a href="#" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                            </div>
                        </form>
                    </div>
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
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let stream = null;
            let originalImage = null;
            let originalBlob = null;
            let croppedBlob = null;
            let cropData = {
                startX: 0,
                startY: 0,
                endX: 0,
                endY: 0,
                isDragging: false
            };

            const video = document.getElementById('cameraPreview');
            const captureCanvas = document.getElementById('captureCanvas');
            const cropCanvas = document.getElementById('cropCanvas');
            const cropOverlay = document.getElementById('cropOverlay');
            const fileInput = document.getElementById('id_picture');
            const previewImg = document.getElementById('previewImg');
            const captureBtn = document.getElementById('captureBtn');
            const cameraStatus = document.getElementById('cameraStatus');

            // Show camera interface
            document.getElementById('useCameraBtn').addEventListener('click', async function() {
                document.getElementById('cameraContainer').style.display = 'block';
                document.getElementById('cropContainer').style.display = 'none';
                document.getElementById('imagePreview').style.display = 'none';
                cameraStatus.textContent = 'Requesting camera access...';
                cameraStatus.style.background = 'rgba(255, 165, 0, 0.8)';

                try {
                    const constraints = {
                        video: {
                            width: {
                                ideal: 640
                            },
                            height: {
                                ideal: 480
                            },
                            facingMode: 'user'
                        },
                        audio: false
                    };

                    stream = await navigator.mediaDevices.getUserMedia(constraints);
                    video.srcObject = stream;

                    video.onloadedmetadata = function() {
                        video.play().then(() => {
                            cameraStatus.textContent = 'Camera ready!';
                            cameraStatus.style.background = 'rgba(0, 128, 0, 0.8)';
                            captureBtn.disabled = false;

                            setTimeout(() => {
                                cameraStatus.style.display = 'none';
                            }, 2000);
                        }).catch(err => {
                            console.error('Error playing video:', err);
                            showCameraError('Failed to start video playback');
                        });
                    };

                } catch (err) {
                    console.error('Camera error:', err);
                    let errorMsg = 'Unable to access camera. ';

                    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                        errorMsg += 'Please allow camera permission and try again.';
                    } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                        errorMsg += 'No camera found on this device.';
                    } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                        errorMsg += 'Camera is already in use by another application.';
                    } else {
                        errorMsg += err.message;
                    }

                    showCameraError(errorMsg);
                }
            });

            function showCameraError(message) {
                cameraStatus.textContent = message;
                cameraStatus.style.background = 'rgba(220, 53, 69, 0.8)';
                cameraStatus.style.display = 'block';
                captureBtn.disabled = true;

                setTimeout(() => {
                    if (confirm(message + '\n\nWould you like to upload a file instead?')) {
                        document.getElementById('useFileBtn').click();
                    }
                }, 500);
            }

            // Show file upload interface
            document.getElementById('useFileBtn').addEventListener('click', function() {
                fileInput.click();
                stopCamera();
                document.getElementById('cameraContainer').style.display = 'none';
            });

            // Capture photo from camera
            captureBtn.addEventListener('click', function() {
                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    const context = captureCanvas.getContext('2d');
                    captureCanvas.width = video.videoWidth;
                    captureCanvas.height = video.videoHeight;
                    context.drawImage(video, 0, 0);

                    captureCanvas.toBlob(function(blob) {
                        if (blob) {
                            originalBlob = blob;
                            loadImageForCropping(blob);
                            stopCamera();
                            document.getElementById('cameraContainer').style.display = 'none';
                        } else {
                            alert('Failed to capture image. Please try again.');
                        }
                    }, 'image/jpeg', 0.9);
                } else {
                    alert('Camera not ready. Please wait a moment and try again.');
                }
            });

            // Handle file upload
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 2 * 1024 * 1024) {
                        alert('File size must be less than 2MB');
                        fileInput.value = '';
                        return;
                    }

                    originalBlob = file;
                    loadImageForCropping(file);
                }
            });

            // Load image into crop editor
            function loadImageForCropping(imageBlob) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        originalImage = img;
                        showCropEditor(img);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(imageBlob);
            }

            // Show crop editor
            function showCropEditor(img) {
                document.getElementById('cropContainer').style.display = 'block';
                document.getElementById('imagePreview').style.display = 'none';

                // Set canvas size
                const maxWidth = 500;
                const scale = Math.min(1, maxWidth / img.width);
                cropCanvas.width = img.width * scale;
                cropCanvas.height = img.height * scale;

                // Draw image
                const ctx = cropCanvas.getContext('2d');
                ctx.drawImage(img, 0, 0, cropCanvas.width, cropCanvas.height);

                // Initialize crop area (full image)
                cropData = {
                    startX: 0,
                    startY: 0,
                    endX: cropCanvas.width,
                    endY: cropCanvas.height,
                    isDragging: false,
                    scale: scale
                };

                updateCropOverlay();
            }

            // Crop canvas mouse events
            cropCanvas.addEventListener('mousedown', function(e) {
                const rect = cropCanvas.getBoundingClientRect();
                cropData.startX = e.clientX - rect.left;
                cropData.startY = e.clientY - rect.top;
                cropData.isDragging = true;
            });

            cropCanvas.addEventListener('mousemove', function(e) {
                if (cropData.isDragging) {
                    const rect = cropCanvas.getBoundingClientRect();
                    cropData.endX = e.clientX - rect.left;
                    cropData.endY = e.clientY - rect.top;
                    updateCropOverlay();
                }
            });

            cropCanvas.addEventListener('mouseup', function() {
                cropData.isDragging = false;
            });

            cropCanvas.addEventListener('mouseleave', function() {
                cropData.isDragging = false;
            });

            // Touch events for mobile
            cropCanvas.addEventListener('touchstart', function(e) {
                e.preventDefault();
                const rect = cropCanvas.getBoundingClientRect();
                const touch = e.touches[0];
                cropData.startX = touch.clientX - rect.left;
                cropData.startY = touch.clientY - rect.top;
                cropData.isDragging = true;
            });

            cropCanvas.addEventListener('touchmove', function(e) {
                e.preventDefault();
                if (cropData.isDragging) {
                    const rect = cropCanvas.getBoundingClientRect();
                    const touch = e.touches[0];
                    cropData.endX = touch.clientX - rect.left;
                    cropData.endY = touch.clientY - rect.top;
                    updateCropOverlay();
                }
            });

            cropCanvas.addEventListener('touchend', function() {
                cropData.isDragging = false;
            });

            // Update crop overlay
            function updateCropOverlay() {
                const x = Math.min(cropData.startX, cropData.endX);
                const y = Math.min(cropData.startY, cropData.endY);
                const width = Math.abs(cropData.endX - cropData.startX);
                const height = Math.abs(cropData.endY - cropData.startY);

                cropOverlay.style.left = x + 'px';
                cropOverlay.style.top = y + 'px';
                cropOverlay.style.width = width + 'px';
                cropOverlay.style.height = height + 'px';
            }

            // Apply crop
            document.getElementById('applyCropBtn').addEventListener('click', function() {
                if (!originalImage) return;

                const x = Math.min(cropData.startX, cropData.endX) / cropData.scale;
                const y = Math.min(cropData.startY, cropData.endY) / cropData.scale;
                const width = Math.abs(cropData.endX - cropData.startX) / cropData.scale;
                const height = Math.abs(cropData.endY - cropData.startY) / cropData.scale;

                if (width < 10 || height < 10) {
                    alert('Crop area is too small. Please select a larger area.');
                    return;
                }

                // Create cropped image
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = width;
                tempCanvas.height = height;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.drawImage(originalImage, x, y, width, height, 0, 0, width, height);

                // Convert to blob and save
                tempCanvas.toBlob(function(blob) {
                    croppedBlob = blob;

                    // Create file and update input
                    const file = new File([blob], 'cropped_image.jpg', {
                        type: 'image/jpeg'
                    });
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;

                    // Show preview
                    const previewUrl = URL.createObjectURL(blob);
                    previewImg.src = previewUrl;
                    document.getElementById('imagePreview').style.display = 'block';
                    document.getElementById('cropContainer').style.display = 'none';

                    // Update original image with cropped version for re-editing
                    const croppedImg = new Image();
                    croppedImg.onload = function() {
                        originalImage = croppedImg;
                        originalBlob = blob;
                    };
                    croppedImg.src = previewUrl;
                }, 'image/jpeg', 0.9);
            });

            // Cancel crop
            document.getElementById('cancelCropBtn').addEventListener('click', function() {
                document.getElementById('cropContainer').style.display = 'none';

                // If we had a previous cropped image, restore it
                if (croppedBlob) {
                    const previewUrl = URL.createObjectURL(croppedBlob);
                    previewImg.src = previewUrl;
                    document.getElementById('imagePreview').style.display = 'block';
                } else {
                    // No previous image, clear everything
                    fileInput.value = '';
                    originalImage = null;
                    originalBlob = null;
                }
            });

            // Reset crop
            document.getElementById('resetCropBtn').addEventListener('click', function() {
                if (originalImage) {
                    cropData.startX = 0;
                    cropData.startY = 0;
                    cropData.endX = cropCanvas.width;
                    cropData.endY = cropCanvas.height;
                    updateCropOverlay();
                }
            });

            // Edit image again
            document.getElementById('editImageBtn').addEventListener('click', function() {
                if (originalImage) {
                    showCropEditor(originalImage);
                }
            });

            // Remove image
            document.getElementById('removeImageBtn').addEventListener('click', function() {
                fileInput.value = '';
                previewImg.src = '';
                originalImage = null;
                originalBlob = null;
                croppedBlob = null;
                document.getElementById('imagePreview').style.display = 'none';
            });

            // Stop camera
            document.getElementById('stopCameraBtn').addEventListener('click', function() {
                stopCamera();
                document.getElementById('cameraContainer').style.display = 'none';
            });

            function stopCamera() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    video.srcObject = null;
                    stream = null;
                }
                cameraStatus.style.display = 'block';
                cameraStatus.textContent = 'Initializing camera...';
                captureBtn.disabled = true;
            }
        });
    </script>

</body>

<?php require_once '../includes/staff-footer.php'; ?>