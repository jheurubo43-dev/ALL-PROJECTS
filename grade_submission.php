<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user info for sidebar
$user_query = mysqli_query($connection, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Fetch submission details and verify ownership
$query = "SELECT s.*, u.name as student_name, u.email as student_email,
                 a.title as assignment_title, a.subject_id,
                 sub.name as subject_name, sub.teacher_id
          FROM submissions s
          JOIN users u ON s.student_id = u.id
          JOIN assignments a ON s.assignment_id = a.id
          JOIN subjects sub ON a.subject_id = sub.id
          WHERE s.id = $submission_id AND sub.teacher_id = $user_id";

$result = mysqli_query($connection, $query);

if (mysqli_num_rows($result) === 0) {
    die("Submission not found or you don't have permission to grade it.");
}

$submission = mysqli_fetch_assoc($result);

// Handle grade submission
if (isset($_POST['save_grade'])) {
    $grade = mysqli_real_escape_string($connection, trim($_POST['grade']));
    $feedback = mysqli_real_escape_string($connection, trim($_POST['feedback']));
    
    $update = "UPDATE submissions SET grade = '$grade', feedback = '$feedback' WHERE id = $submission_id";
    
    if (mysqli_query($connection, $update)) {
        $success_msg = "Grade saved successfully!";
        // Refresh submission data
        $result = mysqli_query($connection, $query);
        $submission = mysqli_fetch_assoc($result);
    } else {
        $error_msg = "Error saving grade: " . mysqli_error($connection);
    }
}

// Determine active nav item
$current_script = basename($_SERVER['PHP_SELF']);
$active_dashboard = ($current_script === 'index.php') ? 'active' : '';
$active_submissions = ($current_script === 'grade_submission.php' || $current_script === 'view_submissions.php') ? 'active' : '';
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Grade Submission | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; }
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; margin: 0; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        
        .sidebar { width: var(--sidebar-width); background: #ffffff; height: 100vh; position: fixed; border-right: 1px solid #e2e8f0; overflow-y: auto; }
        [data-bs-theme="dark"] .sidebar { background: #1e293b; border-right: 1px solid #334155; }
        .main-content { margin-left: var(--sidebar-width); padding: 2rem 2.5rem; }
        
        .nav-link { color: #475569; padding: 0.8rem 1.5rem; margin: 0.2rem 1rem; border-radius: 10px; display: flex; align-items: center; text-decoration: none; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        [data-bs-theme="dark"] .nav-link { color: #cbd5e1; }

        .profile-img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid #3b82f6; }
        
        .live-clock { font-size: 0.9rem; font-weight: 600; color: #3b82f6; }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.8rem;
        }
        
        .file-preview {
            background: #f1f3f5;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
        }
        [data-bs-theme="dark"] .file-preview {
            background: #334155;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="p-4 text-center border-bottom border-secondary-subtle">
        <img src="uploads/<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default.png'; ?>"
             class="profile-img mb-2" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>';">
        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($user['name']); ?></h6>
        <span class="badge bg-primary rounded-pill mt-1"><?php echo ucfirst($_SESSION['role']); ?></span>
    </div>

    <nav class="mt-3">
        <a href="index.php" class="nav-link <?php echo $active_dashboard; ?>"><i class="fas fa-home me-2"></i> Dashboard</a>
       
        <div class="px-4 mt-4 mb-2 small text-muted text-uppercase fw-bold">Teaching</div>
        <a href="add_subject.php" class="nav-link"><i class="fas fa-plus me-2"></i> New Course</a>
        <a href="upload_learning_material.php" class="nav-link"><i class="fas fa-file-upload me-2"></i> Upload Materials</a>
        <a href="view_submissions.php" class="nav-link <?php echo $active_submissions; ?>"><i class="fas fa-tasks me-2"></i> Submissions</a>
        <a href="teacher_class_summary.php" class="nav-link"><i class="fas fa-chart-bar me-2"></i> Reports</a>

        <a href="profile.php" class="nav-link mt-3"><i class="fas fa-user-cog me-2"></i> Profile</a>
       
        <div class="px-3 mt-4">
            <button id="theme-toggle" class="btn btn-sm btn-outline-secondary w-100">
                <i class="fas fa-moon me-1"></i> <span id="theme-text">Dark Mode</span>
            </button>
        </div>
        <a href="logout.php" class="nav-link text-danger mt-2"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a>
    </nav>
</div>

<div class="main-content">
    <header class="mb-4 d-flex justify-content-between align-items-start">
        <div>
            <h2 class="fw-bold">Grade Submission</h2>
            <p class="text-muted">Review and grade the student's assignment.</p>
        </div>
        <div class="text-end d-none d-md-block">
            <div id="liveDate" class="text-muted small fw-bold"></div>
            <div id="liveClock" class="live-clock"></div>
        </div>
    </header>

    <div class="mb-4">
        <a href="view_submissions.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to Submissions
        </a>
    </div>

    <?php if(isset($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold mb-4"><i class="fas fa-file-alt text-primary me-2"></i> Submission Details</h5>
                
                <div class="d-flex align-items-center mb-4">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($submission['student_name'], 0, 1)); ?>
                    </div>
                    <div class="ms-4">
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($submission['student_name']); ?></h5>
                        <p class="text-muted mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($submission['student_email']); ?></p>
                        <p class="mb-1"><strong>Assignment:</strong> <?php echo htmlspecialchars($submission['assignment_title']); ?></p>
                        <p class="mb-0"><strong>Course:</strong> <?php echo htmlspecialchars($submission['subject_name']); ?></p>
                    </div>
                </div>

                <hr>

                <p class="mb-4">
                    <strong>Submitted on:</strong> 
                    <?php echo date('l, F j, Y \a\t g:i A', strtotime($submission['submitted_at'])); ?>
                </p>

                <div class="file-preview mb-4">
                    <?php if (!empty($submission['file_path']) && file_exists('uploads/submissions/' . $submission['file_path'])): ?>
                        <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                        <p class="mb-3"><?php echo htmlspecialchars($submission['file_path']); ?></p>
                        <a href="uploads/submissions/<?php echo htmlspecialchars($submission['file_path']); ?>" 
                           download 
                           class="btn btn-primary rounded-pill">
                            <i class="fas fa-download me-2"></i> Download File
                        </a>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                        <p class="text-danger fw-bold">File not found on server</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($submission['grade']) || !empty($submission['feedback'])): ?>
                    <div class="bg-light rounded p-3">
                        <?php if (!empty($submission['grade'])): ?>
                            <p class="mb-2"><strong>Current Grade:</strong> <?php echo htmlspecialchars($submission['grade']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($submission['feedback'])): ?>
                            <p class="mb-0"><strong>Current Feedback:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($submission['feedback'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold mb-4"><i class="fas fa-edit text-success me-2"></i> Grade & Feedback</h5>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Grade</label>
                        <input type="text" 
                               name="grade" 
                               class="form-control" 
                               placeholder="e.g. 95/100, A+, Excellent"
                               value="<?php echo htmlspecialchars($submission['grade'] ?? ''); ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Feedback</label>
                        <textarea name="feedback" 
                                  class="form-control" 
                                  rows="8" 
                                  placeholder="Provide constructive feedback..."><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" name="save_grade" class="btn btn-success w-100">
                        <i class="fas fa-save me-2"></i> Save Grade
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Theme Toggle Script
    const toggleBtn = document.getElementById('theme-toggle');
    const themeText = document.getElementById('theme-text');
    const html = document.documentElement;
    
    toggleBtn.addEventListener('click', () => {
        const currentTheme = html.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        themeText.textContent = newTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
    });
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme');
    if(savedTheme) {
        html.setAttribute('data-bs-theme', savedTheme);
        themeText.textContent = savedTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
    }

    // Running Clock and Date Script
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        
        document.getElementById('liveDate').textContent = now.toLocaleDateString('en-US', options);
        document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', { hour12: true });
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>