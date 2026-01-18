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

// Handle Upload
if (isset($_POST['upload'])) {
    $target_dir = "uploads/";
    
    // Create folder if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file = $_FILES["profile_img"];
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    
    // Generate unique name to prevent overwriting
    $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Validation
    $check = getimagesize($file["tmp_name"]);
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    if($check === false) {
        $message = "File is not an image.";
        $alert_type = "danger";
    } elseif (!in_array($file_extension, $allowed_types)) {
        $message = "Only JPG, JPEG, PNG & GIF files are allowed.";
        $alert_type = "danger";
    } elseif ($file["size"] > 2000000) { // 2MB limit
        $message = "File is too large (Max 2MB).";
        $alert_type = "danger";
    } else {
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            // Update Database
            mysqli_query($connection, "UPDATE users SET profile_pic = '$new_filename' WHERE id = $user_id");
            $message = "Profile picture updated successfully!";
            $alert_type = "success";
        } else {
            $message = "Error uploading file.";
            $alert_type = "danger";
        }
    }
}

// Fetch current user data
$user_query = mysqli_query($connection, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .profile-card { max-width: 450px; margin: 80px auto; border: none; border-radius: 20px; }
        .preview-img { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 5px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container">
    <div class="card profile-card shadow-sm p-4 text-center">
        <h3 class="fw-bold mb-4">Account Settings</h3>

        <?php if($message): ?>
            <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <img src="uploads/<?php echo !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.png'; ?>" 
                 class="preview-img mb-3"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&size=150'">
            <p class="text-muted mb-0"><?php echo $user['name']; ?></p>
            <span class="badge bg-light text-dark border"><?php echo strtoupper($user['role']); ?></span>
        </div>

        <form method="POST" enctype="multipart/form-data" class="text-start">
            <div class="mb-3">
                <label class="form-label small fw-bold">Choose New Photo</label>
                <input type="file" name="profile_img" class="form-control" accept="image/*" required>
            </div>
            <button type="submit" name="upload" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">
                <i class="fas fa-upload me-2"></i> Save Changes
            </button>
        </form>

        <a href="index.php" class="btn btn-link mt-3 text-decoration-none text-muted">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>