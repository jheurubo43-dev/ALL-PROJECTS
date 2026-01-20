<?php
session_start();
require_once('db.php');

// 1. Security Check: Only teachers can access this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Get Subject ID from the URL (e.g., post_assignment.php?subject_id=5)
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Verify teacher owns this subject
$verify = mysqli_query($connection, "SELECT id, name FROM subjects WHERE id = $subject_id AND teacher_id = $user_id");
if (mysqli_num_rows($verify) === 0) {
    die("Unauthorized: You don't own this subject or the subject doesn't exist.");
}
$subject = mysqli_fetch_assoc($verify);

// 2. Handle Assignment Creation
if (isset($_POST['submit_assignment'])) {
    $title = mysqli_real_escape_string($connection, trim($_POST['title']));
    $instructions = mysqli_real_escape_string($connection, trim($_POST['instructions'])); 
    $due_date = mysqli_real_escape_string($connection, $_POST['due_date']);

    if (empty($title) || empty($instructions) || empty($due_date)) {
        $error = "All fields are required.";
    } else {
        // Query matching your database: title, instructions, subject_id, due_date
        $query = "INSERT INTO assignments (title, instructions, subject_id, due_date) 
                  VALUES ('$title', '$instructions', $subject_id, '$due_date')";
        
        if (mysqli_query($connection, $query)) {
            // Success: Redirect back to the specific subject management page
            header("Location: manage_subject.php?id=$subject_id&success=assignment_created");
            exit();
        } else {
            $error = "Error posting assignment: " . mysqli_error($connection);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Assignment | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; 
            display: flex; 
            align-items: center;
            padding: 2rem 0; /* Vertical padding for mobile scroll */
        }
        .form-card { 
            border-radius: 20px; 
            border: none; 
            width: 100%; 
            max-width: 700px; 
            margin: auto; 
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .header-gradient { 
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
            color: white; 
            padding: 2.5rem;
            text-align: center;
        }
        .form-control-lg { border-radius: 12px; }
        [data-bs-theme="dark"] .form-card {
            background: #1e293b;
        }
        .btn-primary {
            background: #1976d2;
            border: none;
            padding: 12px;
            border-radius: 12px;
            transition: 0.3s;
        }
        .btn-primary:hover {
            background: #0d47a1;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="card form-card">
            <div class="header-gradient">
                <div class="mb-3">
                    <i class="fas fa-clipboard-list fa-3x"></i>
                </div>
                <h3 class="fw-bold mb-2">Create New Assignment</h3>
                <p class="mb-0 opacity-75">
                    <i class="fas fa-book me-2"></i>Subject: <?php echo htmlspecialchars($subject['name']); ?>
                </p>
            </div>
            
            <div class="card-body p-4 p-md-5">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-heading me-2 text-primary"></i>Assignment Title
                        </label>
                        <input type="text" 
                               name="title" 
                               class="form-control form-control-lg" 
                               placeholder="e.g. Midterm Essay, Chapter 5 Quiz" 
                               required
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-align-left me-2 text-primary"></i>Instructions
                        </label>
                        <textarea name="instructions" 
                                  class="form-control" 
                                  rows="5" 
                                  placeholder="Provide clear instructions for your students..."
                                  required><?php echo isset($_POST['instructions']) ? htmlspecialchars($_POST['instructions']) : ''; ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-calendar-alt me-2 text-primary"></i>Due Date
                        </label>
                        <input type="date" 
                               name="due_date" 
                               class="form-control form-control-lg" 
                               required
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>">
                        <small class="text-muted mt-1 d-block">
                            <i class="fas fa-info-circle me-1"></i>Select when students should submit this assignment
                        </small>
                    </div>

                    <div class="d-grid gap-2 mt-5">
                        <button type="submit" name="submit_assignment" class="btn btn-primary btn-lg fw-bold text-white">
                            <i class="fas fa-paper-plane me-2"></i> Publish Assignment
                        </button>
                        <a href="manage_subject.php?id=<?php echo $subject_id; ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times me-2"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Theme persistence from Dashboard
        const savedTheme = localStorage.getItem('theme');
        if(savedTheme) {
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        }
    </script>
</body>
</html>