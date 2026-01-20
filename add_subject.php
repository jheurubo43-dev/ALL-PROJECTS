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
    $subject_name = mysqli_real_escape_string($connection, trim($_POST['subject_name']));
    
    if (empty($subject_name)) {
        $message = "Subject name is required.";
    } else {
        // Insert new subject using the current teacher's ID
        $query = "INSERT INTO subjects (name, teacher_id) VALUES ('$subject_name', '$user_id')";
        
        if (mysqli_query($connection, $query)) {
            header("Location: index.php?success=created");
            exit();
        } else {
            $message = "Error: " . mysqli_error($connection);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create New Course | LMS Pro</title>
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
        .create-card { 
            background: white; 
            border-radius: 20px; 
            padding: 3rem; 
            width: 100%; 
            max-width: 550px; 
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        [data-bs-theme="dark"] .create-card {
            background: #1e293b;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
    </style>
</head>
<body>
    <div class="card create-card">
        <div class="text-center mb-4">
            <div class="icon-circle">
                <i class="fas fa-book-medical text-white fa-2x"></i>
            </div>
            <h2 class="fw-bold mb-2">Create New Course</h2>
            <p class="text-muted">Enter the course name to begin teaching</p>
        </div>

        <?php if($message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-heading me-2 text-primary"></i>Course Name
                </label>
                <input type="text" 
                       name="subject_name" 
                       class="form-control form-control-lg" 
                       placeholder="e.g., Advanced Mathematics, World History" 
                       required
                       autofocus>
                <small class="text-muted mt-1 d-block">
                    <i class="fas fa-info-circle me-1"></i>Choose a clear, descriptive name for your course
                </small>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" name="create_subject" class="btn btn-primary btn-lg fw-bold">
                    <i class="fas fa-rocket me-2"></i>Create Course
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        // Theme persistence
        const savedTheme = localStorage.getItem('theme');
        if(savedTheme) {
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        }
    </script>
</body>
</html>