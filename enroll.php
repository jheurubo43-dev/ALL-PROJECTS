<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle the join button click
if (isset($_GET['join_id'])) {
    $sub_id = intval($_GET['join_id']);
    
    // Check if student is already enrolled
    $check = mysqli_query($connection, "SELECT * FROM enrollments WHERE student_id = $user_id AND subject_id = $sub_id");
    
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($connection, "INSERT INTO enrollments (student_id, subject_id, enrolled_at) VALUES ($user_id, $sub_id, NOW())");
        header("Location: enroll.php?success=1");
        exit();
    }
}

// Get all subjects that exist
$subjects = mysqli_query($connection, "
    SELECT s.*, u.name as teacher_name,
           (SELECT COUNT(*) FROM enrollments WHERE subject_id = s.id) as enrollment_count
    FROM subjects s 
    LEFT JOIN users u ON s.teacher_id = u.id
    ORDER BY s.name
");
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enroll in Courses | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 2rem; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        
        .page-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .subject-card { 
            border: 1px solid #e2e8f0;
            border-radius: 15px; 
            background: white; 
            padding: 1.5rem; 
            transition: all 0.3s;
            height: 100%;
        }
        [data-bs-theme="dark"] .subject-card {
            background: #1e293b;
            border-color: #334155;
        }
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: #3b82f6;
        }
        .teacher-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 1rem;
        }
        [data-bs-theme="dark"] .teacher-info {
            background: #334155;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1200px;">
        <div class="page-header shadow">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-graduation-cap me-2"></i>Available Courses
                    </h2>
                    <p class="mb-0 opacity-75">Browse and enroll in courses</p>
                </div>
                <a href="index.php" class="btn btn-light rounded-pill">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm">
                <i class="fas fa-check-circle me-2"></i>
                Successfully enrolled! The course is now available in your dashboard.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php 
            if (mysqli_num_rows($subjects) > 0):
                while($row = mysqli_fetch_assoc($subjects)): 
                    $sid = $row['id'];
                    $en_check = mysqli_query($connection, "SELECT * FROM enrollments WHERE student_id = $user_id AND subject_id = $sid");
                    $is_enrolled = (mysqli_num_rows($en_check) > 0);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="subject-card shadow-sm">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="text-primary">
                                <i class="fas fa-book fa-2x"></i>
                            </div>
                            <?php if($is_enrolled): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Enrolled
                                </span>
                            <?php endif; ?>
                        </div>

                        <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($row['name']); ?></h5>
                        
                        <div class="teacher-info">
                            <small class="text-muted d-block mb-1">
                                <i class="fas fa-chalkboard-teacher me-1"></i>Instructor
                            </small>
                            <strong><?php echo htmlspecialchars($row['teacher_name'] ?? 'To Be Assigned'); ?></strong>
                        </div>

                        <div class="d-flex align-items-center mb-3 text-muted small">
                            <i class="fas fa-users me-2"></i>
                            <span><?php echo $row['enrollment_count']; ?> student<?php echo $row['enrollment_count'] != 1 ? 's' : ''; ?> enrolled</span>
                        </div>

                        <div class="d-grid">
                            <?php if($is_enrolled): ?>
                                <a href="view_subject.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-right me-2"></i>View Course
                                </a>
                            <?php else: ?>
                                <a href="?join_id=<?php echo $row['id']; ?>" 
                                   class="btn btn-primary"
                                   onclick="return confirm('Enroll in <?php echo htmlspecialchars($row['name']); ?>?')">
                                    <i class="fas fa-plus me-2"></i>Enroll Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3 opacity-25"></i>
                        <h4 class="text-muted">No Courses Available</h4>
                        <p class="text-muted">Check back later for new courses.</p>
                    </div>
                </div>
            <?php endif; ?>
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