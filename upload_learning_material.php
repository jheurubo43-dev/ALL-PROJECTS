<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Fetch teacher's subjects
$subjects = mysqli_query($connection, "
    SELECT id, name 
    FROM subjects 
    WHERE teacher_id = $user_id 
    ORDER BY name
");

// Handle upload
if (isset($_POST['upload_material'])) {
    $subject_id = (int)$_POST['subject_id'];
    $title      = mysqli_real_escape_string($connection, trim($_POST['title']));

    // Security: verify teacher owns this subject
    $check = mysqli_query($connection, "SELECT id FROM subjects WHERE id = $subject_id AND teacher_id = $user_id");
    if (mysqli_num_rows($check) === 0) {
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>You do not own this subject.</div>';
    } elseif (empty($title)) {
        $message = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Title is required.</div>';
    } elseif (empty($_FILES['material_file']['name'])) {
        $message = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Please upload a file.</div>';
    } else {
        $target_dir = "uploads/materials/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['material_file']['name']);
        $target_file = $target_dir . $file_name;

        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $message = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Invalid file type. Allowed: PDF, Word, PowerPoint, ZIP, JPG, PNG.</div>';
        } elseif ($_FILES['material_file']['size'] > 10000000) { // 10MB limit
            $message = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>File is too large (max 10MB).</div>';
        } elseif (move_uploaded_file($_FILES['material_file']['tmp_name'], $target_file)) {
            // Save to database
            $sql = "INSERT INTO learning_materials (subject_id, title, file_path, uploaded_at) 
                    VALUES ($subject_id, '$title', '$file_name', NOW())";
            
            if (mysqli_query($connection, $sql)) {
                $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Material uploaded successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Database error: ' . mysqli_error($connection) . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>File upload failed. Check folder permissions.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Learning Material | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        .container { max-width: 700px; }
        .card { 
            border: 1px solid #e0e0e0; 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        [data-bs-theme="dark"] .card {
            background: #1e293b;
            border-color: #334155;
        }
        .upload-icon {
            font-size: 3rem;
            color: #3b82f6;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Upload Learning Material</h2>
            <p class="text-muted mb-0">Share resources with your students</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <i class="fas fa-cloud-upload-alt upload-icon"></i>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-book me-2 text-primary"></i>Select Subject
                    </label>
                    <select name="subject_id" class="form-select form-select-lg" required>
                        <option value="">-- Choose your subject --</option>
                        <?php 
                        mysqli_data_seek($subjects, 0); // Reset pointer
                        while ($sub = mysqli_fetch_assoc($subjects)): 
                        ?>
                            <option value="<?php echo $sub['id']; ?>">
                                <?php echo htmlspecialchars($sub['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-heading me-2 text-primary"></i>Title
                    </label>
                    <input type="text" name="title" class="form-control form-control-lg" required 
                           placeholder="e.g. Week 1 Lecture Slides">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-file me-2 text-primary"></i>Upload File
                    </label>
                    <input type="file" name="material_file" class="form-control form-control-lg" required 
                           accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.jpg,.jpeg,.png">
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Allowed: PDF, Word, PowerPoint, ZIP, JPG/PNG (max 10MB)
                    </small>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="upload_material" class="btn btn-primary btn-lg">
                        <i class="fas fa-upload me-2"></i> Upload Material
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Theme persistence
    const savedTheme = localStorage.getItem('theme');
    if(savedTheme) {
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>