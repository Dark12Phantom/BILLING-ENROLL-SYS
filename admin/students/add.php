<?php
require_once '../../includes/auth.php';
protectPage();

require_once '../../includes/header.php';

$userId = $_SESSION['user_id'];
$stmtUser = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM user_tables WHERE id = ?");
$stmtUser->execute([$userId]);
$createdBy = $stmtUser->fetchColumn();

function normalizeMobile($num)
{
    $num = preg_replace('/\D/', '', $num);

    if (strlen($num) === 9 && $num[0] === '9') {
        return '+63' . $num;
    }

    if (strlen($num) === 10 && $num[0] === '0' && $num[1] === '9') {
        return '+63' . substr($num, 1);
    }

    if (strlen($num) === 11 && $num[0] === '0' && $num[1] === '9') {
        return '+63' . substr($num, 1);
    }

    if (strlen($num) === 11 && substr($num, 0, 2) === '63') {
        return '+' . $num;
    }

    if (strlen($num) === 12 && substr($num, 0, 2) === '63') {
        return '+' . $num;
    }

    return null;
}

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

    $uploadDir = '../../uploads/studentProfiles/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $studentData['idPicturePath'] = null;

    // Handle base64 cropped image
    if (!empty($_POST['croppedImage'])) {
        $imageData = $_POST['croppedImage'];
        $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $decodedImage = base64_decode($imageData);

        $fileName = 'STU_' . $_POST['student_id'] . 'PFP.jpg';
        $targetPath = $uploadDir . $fileName;

        if (file_put_contents($targetPath, $decodedImage)) {
            $studentData['idPicturePath'] = $fileName;
        } else {
            $error = "Failed to save ID picture.";
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
                        <p class="mb-0">This pupil has been recorded before.</p>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
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
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .preview-container:hover {
            border-color: #0d6efd;
            background-color: #e9ecef;
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

        /* Crop Modal Styles */
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
                        <form method="POST" enctype="multipart/form-data" id="studentForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="section-title">Student Information</h5>

                                    <div class="text-center mb-4">
                                        <div class="preview-container" id="uploadArea">
                                            <div class="upload-icon">📷</div>
                                            <img id="previewImg" alt="ID Picture Preview">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Student ID Picture</label>

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
                                        <input type="text" class="form-control" id="student_id" name="student_id" minlength="12" maxlength="12" required>
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

                                <div class="col-md-6">
                                    <h5 class="section-title">Additional Information</h5>

                                    <div class="mb-3">
                                        <label for="address" class="form-label">Home Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="mobile_number" class="form-label">Mobile Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text">+63</span>
                                            <input type="tel" class="form-control" id="mobile_number" name="mobile_number" placeholder="9XXXXXXXXX" maxlength="10" pattern="9[0-9]{9}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="grade_level" class="form-label">Grade Level</label>
                                        <select class="form-select" id="grade_level" name="grade_level" required>
                                            <option value="" disabled selected>Select Grade Level</option>
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
                                        <select class="form-select" id="section" name="section" required disabled>
                                            <option value="" disabled selected>Select Grade Level First</option>
                                        </select>
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
                                        <div class="input-group">
                                            <span class="input-group-text">+63</span>
                                            <input type="tel" class="form-control" id="parent_mobile_number" name="parent_mobile_number" placeholder="9XXXXXXXXX" maxlength="10" pattern="9[0-9]{9}" required>
                                        </div>
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
                                <a href="index.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Crop Modal -->
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
        // Set school year
        const currentYear = new Date().getFullYear();
        const nextYear = currentYear + 1;
        document.getElementById("schoolYear").value = `${currentYear} - ${nextYear}`;

        // Set max date for date of birth
        const today = new Date();
        const y = today.getFullYear();
        const m = String(today.getMonth() + 1).padStart(2, '0');
        const d = String(today.getDate()).padStart(2, '0');
        const maxDate = `${y}-${m}-${d}`;
        document.getElementById('date_of_birth').max = maxDate;

        // Image handling with cropping
        let cropper = null;
        let stream = null;

        const fileInput = document.getElementById('fileInput');
        const previewImg = document.getElementById('previewImg');
        const uploadArea = document.getElementById('uploadArea');
        const cropModal = document.getElementById('cropModal');
        const cropImage = document.getElementById('cropImage');
        const cameraContainer = document.getElementById('cameraContainer');
        const video = document.getElementById('cameraPreview');

        // Upload file button
        document.getElementById('useFileBtn').addEventListener('click', () => {
            fileInput.click();
        });

        // Click upload area to select file
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        // File selected
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

        // Camera
        document.getElementById('useCameraBtn').addEventListener('click', async () => {
            cameraContainer.style.display = 'block';
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user'
                    },
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

        // Initialize cropper
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

        // Confirm crop
        document.getElementById('cropConfirm').addEventListener('click', () => {
            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);

            previewImg.src = croppedDataUrl;
            previewImg.style.display = 'block';
            uploadArea.querySelector('.upload-icon').style.display = 'none';

            document.getElementById('croppedImageData').value = croppedDataUrl;

            cropModal.style.display = 'none';
            cropper.destroy();
            cropper = null;
        });

        // Cancel crop
        document.getElementById('cropCancel').addEventListener('click', () => {
            cropModal.style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            fileInput.value = '';
        });

        // Form validation and mobile number formatting
        document.getElementById('studentForm').addEventListener('submit', function(e) {
            const dobInput = document.getElementById('date_of_birth');
            if (dobInput.value && dobInput.value > maxDate) {
                e.preventDefault();
                alert('Please choose today or a past date for date of birth.');
                return false;
            }

            if (!document.getElementById('croppedImageData').value) {
                e.preventDefault();
                alert('Please upload and crop a student photo.');
                return false;
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

    <script>
        const sectionsByGrade = {
            'Kindergarten': [{
                    value: 'Apple',
                    label: 'Apple'
                },
                {
                    value: 'Apricot',
                    label: 'Apricot'
                },
                {
                    value: 'Avocado',
                    label: 'Avocado'
                }
            ],
            'Grade 1': [{
                    value: 'Birch',
                    label: 'Birch'
                },
                {
                    value: 'Banyan',
                    label: 'Banyan'
                },
                {
                    value: 'Bamboo',
                    label: 'Bamboo'
                }
            ],
            'Grade 2': [{
                    value: 'Cranberry',
                    label: 'Cranberry'
                },
                {
                    value: 'Cherry',
                    label: 'Cherry'
                },
                {
                    value: 'Currant',
                    label: 'Currant'
                }
            ],
            'Grade 3': [{
                    value: 'Dolphin',
                    label: 'Dolphin'
                },
                {
                    value: 'Damselfish',
                    label: 'Damselfish'
                },
                {
                    value: 'Dory',
                    label: 'Dory'
                }
            ],
            'Grade 4': [{
                    value: 'Elephant',
                    label: 'Elephant'
                },
                {
                    value: 'Elk',
                    label: 'Elk'
                },
                {
                    value: 'Echidna',
                    label: 'Echidna'
                }
            ],
            'Grade 5': [{
                    value: 'Falcon',
                    label: 'Falcon'
                },
                {
                    value: 'Ferret',
                    label: 'Ferret'
                },
                {
                    value: 'Fox',
                    label: 'Fox'
                }
            ],
            'Grade 6': [{
                    value: 'Gardenia',
                    label: 'Gardenia'
                },
                {
                    value: 'Geranium',
                    label: 'Geranium'
                },
                {
                    value: 'Gerbera',
                    label: 'Gerbera'
                }
            ]
        };

        const gradeLevelSelect = document.getElementById('grade_level');
        const sectionSelect = document.getElementById('section');

        gradeLevelSelect.addEventListener('change', function() {
            const selectedGrade = this.value;

            sectionSelect.innerHTML = '<option value="" disabled selected>Select Section</option>';

            sectionSelect.disabled = false;

            if (sectionsByGrade[selectedGrade]) {
                sectionsByGrade[selectedGrade].forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.value;
                    option.textContent = section.label;
                    sectionSelect.appendChild(option);
                });
            }
        });
    </script>
</body>

<?php require_once '../../includes/footer.php'; ?>