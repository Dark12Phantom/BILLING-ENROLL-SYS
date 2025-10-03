<?php
require_once '../../includes/auth.php';
protectPage();

require_once '../../includes/header.php';

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
        'section' => $_POST['section']
    ];

    $parentData = [
        'first_name' => $_POST['parent_first_name'],
        'last_name' => $_POST['parent_last_name'],
        'relationship' => $_POST['relationship'],
        'mobile_number' => $_POST['parent_mobile_number'],
        'email' => $_POST['parent_email'],
        'address' => $_POST['parent_address']
    ];

    $uploadDir = 'uploads/studentProfiles/';
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

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, last_name, date_of_birth, gender, address, mobile_number, grade_level, section, idPicturePath) 
                              VALUES (:student_id, :first_name, :last_name, :date_of_birth, :gender, :address, :mobile_number, :grade_level, :section, :idPicturePath)");
        $stmt->execute($studentData);
        $studentId = $pdo->lastInsertId();

        $parentData['student_id'] = $studentId;
        $stmt = $pdo->prepare("INSERT INTO parents (student_id, first_name, last_name, relationship, mobile_number, email, address) 
                              VALUES (:student_id, :first_name, :last_name, :relationship, :mobile_number, :email, :address)");
        $stmt->execute($parentData);

        $pdo->commit();

        $_SESSION['success'] = "Student added successfully!";
        header("Location: index.php");
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
                                        <input type="file" class="form-control" id="id_picture" name="idPicturePath" accept="image/*" required hidden>
                                        <div class="form-text">Upload a passport-size photo (JPG, PNG, max 2MB)</div>
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
                            </div>

                            <div class="col-md-6">
                                <h5 class="section-title">Additional Information</h5>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Home Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="mobile_number" class="form-label">Mobile Number</label>
                                    <input type="tel" class="form-control" id="mobile_number" name="mobile_number">
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

                                <h5 class="section-title mt-4">Parent/Guardian Information</h5>

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
                                    <input type="tel" class="form-control" id="parent_mobile_number" name="parent_mobile_number" required>
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
    
</body>

<?php require_once '../../includes/footer.php'; ?>