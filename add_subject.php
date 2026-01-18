<?php
session_start();
require_once('db.php');

// Security: Only Teachers and Admins can create subjects
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle Form Submission
if (isset($_POST['create_subject'])) {
    $subject_name = mysqli_real_escape_string($connection, $_POST['subject_name']);
    
    // Insert new subject using the current teacher's ID
    $query = "INSERT INTO subjects (name, teacher_id) VALUES ('$subject_name', '$user_id')";
    
    if (mysqli_query($connection, $query)) {
        header("Location: index.php?success=created");
        exit();
    } else {
        $message = "Error: " . mysqli_error($connection);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LMS Pro | Create Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f9; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .create-card { background: white; border-radius: 20px; padding: 40px; width: 100%; max-width: 500px; border: none; }
    </style>
</head>
<body>
    <div class="card create-card shadow-lg">
        <div class="text-center mb-4">
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                <i class="fas fa-plus text-primary fa-2x"></i>
            </div>
            <h2 class="fw-bold">Create New Class</h2>
            <p class="text-muted small">Enter the course name to begin teaching</p>
        </div>

        <?php if($message): ?>
            <div class="alert alert-danger small"><?php echo $message; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-4">
                <label class="form-label fw-bold small text-uppercase">Subject Name</label>
                <input type="text" name="subject_name" class="form-control form-control-lg bg-light border-0" placeholder="e.g., Advanced Mathematics" required>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" name="create_subject" class="btn btn-primary btn-lg fw-bold">Publish Subject</button>
                <a href="index.php" class="btn btn-link text-muted text-decoration-none">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>