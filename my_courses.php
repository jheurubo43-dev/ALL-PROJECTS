<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all enrolled subjects
$courses_query = mysqli_query($connection, "
    SELECT s.id, s.name, u.name AS teacher_name,
           (SELECT COUNT(*) FROM assignments a WHERE a.subject_id = s.id) AS assignment_count,
           (SELECT COUNT(*) FROM submissions sub 
            JOIN assignments a ON sub.assignment_id = a.id 
            WHERE a.subject_id = s.id AND sub.student_id = $user_id) AS submitted_count
    FROM subjects s
    JOIN enrollments e ON s.id = e.subject_id
    LEFT JOIN users u ON s.teacher_id = u.id
    WHERE e.student_id = $user_id
    ORDER BY s.name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1000px; }
        .card { border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .course-card { transition: transform 0.2s; }
        .course-card:hover { transform: translateY(-3px); }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-normal">My Courses</h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if (mysqli_num_rows($courses_query) > 0): ?>
        <div class="row g-4">
            <?php while ($course = mysqli_fetch_assoc($courses_query)): ?>
                <div class="col-lg-6">
                    <div class="card course-card h-100">
                        <div class="card-body">
                            <h5 class="card-title fw-medium mb-2">
                                <?php echo htmlspecialchars($course['name']); ?>
                            </h5>
                            <p class="text-muted mb-3">
                                Teacher: <?php echo htmlspecialchars($course['teacher_name'] ?? 'Not assigned'); ?>
                            </p>

                            <div class="row text-center g-3 mb-3">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <div class="fw-bold"><?php echo $course['assignment_count']; ?></div>
                                        <small class="text-muted">Assignments</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <div class="fw-bold"><?php echo $course['submitted_count']; ?></div>
                                        <small class="text-muted">Submitted</small>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <a href="view_subject.php?id=<?php echo $course['id']; ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-eye me-1"></i> Open Course
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-light text-center py-5">
            <i class="fas fa-book-open fa-2x text-muted mb-3 d-block"></i>
            You are not enrolled in any courses yet.<br>
            <a href="enroll.php" class="btn btn-primary mt-3">Browse Subjects</a>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>