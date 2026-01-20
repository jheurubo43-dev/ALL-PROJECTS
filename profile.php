<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$alert_type = "info";

// Fetch current user data
$user_query = mysqli_query($connection, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

if (!$user) {
    header("Location: logout.php");
    exit();
}

// Handle Upload
if (isset($_POST['upload'])) {
    $target_dir = "uploads/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file = $_FILES["profile_img"];
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $message = "Upload error.";
        $alert_type = "danger";
    } else {
        $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        $check = getimagesize($file["tmp_name"]);
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if ($check === false) {
            $message = "Not a valid image.";
            $alert_type = "danger";
        } elseif (!in_array($file_extension, $allowed_types)) {
            $message = "Only JPG, JPEG, PNG & GIF allowed.";
            $alert_type = "danger";
        } elseif ($file["size"] > 2000000) {
            $message = "File too large (max 2MB).";
            $alert_type = "danger";
        } else {
            if (move_uploaded_file($file["tmp_name"], $target_file)) {
                // Delete old pic if exists
                if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default.png' && file_exists("uploads/" . $user['profile_pic'])) {
                    unlink("uploads/" . $user['profile_pic']);
                }

                // Save to database
                mysqli_query($connection, "UPDATE users SET profile_pic = '$new_filename' WHERE id = $user_id");

                $message = "Profile picture updated successfully!";
                $alert_type = "success";

                // Refresh user data
                $user_query = mysqli_query($connection, "SELECT * FROM users WHERE id = $user_id");
                $user = mysqli_fetch_assoc($user_query);
            } else {
                $message = "Failed to save file.";
                $alert_type = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profile | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem;
        }
        [data-bs-theme="dark"] body {
            background: linear-gradient(135deg, #1e3a8a 0%, #581c87 100%);
        }
        .container { max-width: 600px; }
        .card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        [data-bs-theme="dark"] .card {
            background: #1e293b;
        }
        .preview-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .upload-zone {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        [data-bs-theme="dark"] .upload-zone {
            background: #334155;
            border-color: #475569;
        }
        .upload-zone:hover {
            border-color: #667eea;
            background: #f0f1ff;
        }
        [data-bs-theme="dark"] .upload-zone:hover {
            background: #475569;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h3 class="fw-bold mb-2">
                    <i class="fas fa-user-circle me-2 text-primary"></i>Edit Profile
                </h3>
                <p class="text-muted">Update your profile picture</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show">
                    <i class="fas fa-<?php echo $alert_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="text-center mb-4">
                <img src="uploads/<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default.png'; ?>" 
                     class="preview-img mb-3"
                     id="profilePreview"
                     alt="Profile"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&size=150&background=667eea&color=fff';">
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                <span class="badge bg-primary px-3 py-2"><?php echo ucfirst($user['role']); ?></span>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-image me-2 text-primary"></i>Choose New Photo
                    </label>
                    <div class="upload-zone" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <p class="mb-2"><strong>Click to upload</strong> or drag and drop</p>
                        <small class="text-muted">JPG, PNG or GIF • Max 2MB</small>
                        <input type="file" 
                               id="fileInput"
                               name="profile_img" 
                               class="d-none" 
                               accept="image/*" 
                               required
                               onchange="previewImage(this)">
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle me-1"></i>Recommended: Square image, at least 300x300 pixels
                    </small>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="upload" class="btn btn-primary btn-lg fw-bold">
                        <i class="fas fa-upload me-2"></i> Upload & Save
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
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

    // Image preview
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>