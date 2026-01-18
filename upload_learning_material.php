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
        $message = '<div class="alert alert-danger">You do not own this subject.</div>';
    } elseif (empty($title)) {
        $message = '<div class="alert alert-warning">Title is required.</div>';
    } elseif (empty($_FILES['material_file']['name'])) {
        $message = '<div class="alert alert-warning">Please upload a file.</div>';
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
            $message = '<div class="alert alert-danger">Invalid file type. Allowed: PDF, Word, PowerPoint, ZIP, JPG, PNG.</div>';
        } elseif ($_FILES['material_file']['size'] > 10000000) { // 10MB limit
            $message = '<div class="alert alert-danger">File is too large (max 10MB).</div>';
        } elseif (move_uploaded_file($_FILES['material_file']['tmp_name'], $target_file)) {
            // Save to database
            $sql = "INSERT INTO learning_materials (subject_id, title, file_path, uploaded_at) 
                    VALUES ($subject_id, '$title', '$target_file', NOW())";
            
            if (mysqli_query($connection, $sql)) {
                $message = '<div class="alert alert-success">Material uploaded successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Database error: ' . mysqli_error($connection) . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">File upload failed. Check folder permissions.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Learning Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 700px; }
        .card { border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-normal">Upload Learning Material</h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label fw-medium">Select Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">-- Choose your subject --</option>
                        <?php while ($sub = mysqli_fetch_assoc($subjects)): ?>
                            <option value="<?php echo $sub['id']; ?>">
                                <?php echo htmlspecialchars($sub['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Title</label>
                    <input type="text" name="title" class="form-control" required 
                           placeholder="e.g. Week 1 Lecture Slides">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">Upload File</label>
                    <input type="file" name="material_file" class="form-control" required 
                           accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.jpg,.jpeg,.png">
                    <small class="text-muted d-block mt-1">
                        Allowed: PDF, Word, PowerPoint, ZIP, JPG/PNG (max 10MB)
                    </small>
                </div>

                <button type="submit" name="upload_material" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i> Upload Material
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>s