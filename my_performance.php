<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all enrolled subjects for this student
$enrolled_query = mysqli_query($connection, "
    SELECT s.id, s.name, u.name AS teacher_name
    FROM subjects s
    JOIN enrollments e ON s.id = e.subject_id
    LEFT JOIN users u ON s.teacher_id = u.id
    WHERE e.student_id = $user_id
    ORDER BY s.name
");

$performance = [];
$total_subjects = 0;
$total_grades = 0;
$total_grade_sum = 0;

while ($sub = mysqli_fetch_assoc($enrolled_query)) {
    $sub_id = $sub['id'];
    $total_subjects++;

    // Assignments count & submitted count
    $assign_count_q = mysqli_query($connection, "SELECT COUNT(*) as cnt FROM assignments WHERE subject_id = $sub_id");
    $assign_count = mysqli_fetch_assoc($assign_count_q)['cnt'];

    $submitted_q = mysqli_query($connection, "
        SELECT COUNT(*) as cnt 
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        WHERE a.subject_id = $sub_id AND s.student_id = $user_id
    ");
    $submitted_count = mysqli_fetch_assoc($submitted_q)['cnt'];

    // Average grade
    $grades_q = mysqli_query($connection, "
        SELECT AVG(CAST(grade AS DECIMAL(5,2))) as avg_grade
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        WHERE a.subject_id = $sub_id AND s.student_id = $user_id AND grade IS NOT NULL AND grade REGEXP '^[0-9.]+$'
    ");
    $avg_grade_row = mysqli_fetch_assoc($grades_q);
    $avg_grade = $avg_grade_row['avg_grade'] ? round($avg_grade_row['avg_grade'], 2) : 'N/A';

    if ($avg_grade !== 'N/A') {
        $total_grades++;
        $total_grade_sum += $avg_grade;
    }

    // Attendance percentage
    $att_total_q = mysqli_query($connection, "SELECT COUNT(*) as total FROM attendance WHERE student_id = $user_id AND subject_id = $sub_id");
    $att_total = mysqli_fetch_assoc($att_total_q)['total'] ?? 0;

    $att_present_q = mysqli_query($connection, "SELECT COUNT(*) as present FROM attendance WHERE student_id = $user_id AND subject_id = $sub_id AND status = 'present'");
    $att_present = mysqli_fetch_assoc($att_present_q)['present'] ?? 0;

    $att_percent = ($att_total > 0) ? round(($att_present / $att_total) * 100, 1) : 0;

    $performance[] = [
        'id' => $sub_id,
        'name' => $sub['name'],
        'teacher' => $sub['teacher_name'] ?? 'N/A',
        'assignments' => $assign_count,
        'submitted' => $submitted_count,
        'avg_grade' => $avg_grade,
        'attendance' => $att_percent
    ];
}

$overall_avg_grade = ($total_grades > 0) ? round($total_grade_sum / $total_grades, 2) : 'N/A';
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Grades & Performance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #2c5282;
            --primary-dark: #1a365d;
        }
        body {
            background: #f7fafc;
            color: #1a202c;
            font-family: 'Inter', system-ui, sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }
        [data-bs-theme="dark"] body {
            background: #0f172a;
            color: #e2e8f0;
        }
        .card {
            background: white;
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        [data-bs-theme="dark"] .card {
            background: #1e293b;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
        }
        .stat-badge {
            font-size: 1.1rem;
            min-width: 80px;
            text-align: center;
        }
        .overall-card {
            background: #e9f7ff;
            border-color: #b3e0ff;
        }
        [data-bs-theme="dark"] .overall-card {
            background: #1e40af;
            border-color: #3b82f6;
        }
        .theme-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Theme Toggle Button (top right) -->
<button id="theme-toggle" class="btn btn-outline-secondary theme-toggle">
    <i class="fas fa-moon me-2"></i> Dark Mode
</button>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-normal">My Grades & Performance</h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if (empty($performance)): ?>
        <div class="alert alert-light text-center py-5">
            <i class="fas fa-book-open fa-2x text-muted mb-3 d-block"></i>
            You are not enrolled in any subjects yet.
        </div>
    <?php else: ?>
        <!-- Overall Summary -->
        <div class="card overall-card mb-5">
            <div class="card-body text-center">
                <h5 class="fw-medium mb-3">Overall Performance</h5>
                <div class="row g-4 justify-content-center">
                    <div class="col-6 col-md-4">
                        <div class="p-3">
                            <div class="stat-badge bg-primary text-white"><?php echo $total_subjects; ?></div>
                            <small class="text-muted d-block mt-1">Subjects Enrolled</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3">
                            <div class="stat-badge bg-success text-white"><?php echo $overall_avg_grade; ?></div>
                            <small class="text-muted d-block mt-1">Average Grade</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Per Subject Details -->
        <h5 class="fw-medium mb-3">Per Subject Breakdown</h5>
        <div class="row g-4">
            <?php foreach ($performance as $sub): ?>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-medium mb-2"><?php echo htmlspecialchars($sub['name']); ?></h6>
                            <small class="text-muted d-block mb-3">Teacher: <?php echo htmlspecialchars($sub['teacher']); ?></small>

                            <div class="row text-center g-3">
                                <div class="col-6 col-sm-3">
                                    <div class="p-2 bg-light rounded">
                                        <div class="stat-badge bg-primary text-white"><?php echo $sub['assignments']; ?></div>
                                        <small class="text-muted d-block mt-1">Assignments</small>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="p-2 bg-light rounded">
                                        <div class="stat-badge bg-info text-white"><?php echo $sub['submitted']; ?></div>
                                        <small class="text-muted d-block mt-1">Submitted</small>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="p-2 bg-light rounded">
                                        <div class="stat-badge bg-success text-white"><?php echo $sub['avg_grade']; ?></div>
                                        <small class="text-muted d-block mt-1">Avg Grade</small>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="p-2 bg-light rounded">
                                        <div class="stat-badge 
                                            <?php 
                                                if ($sub['attendance'] >= 80) echo 'bg-success';
                                                elseif ($sub['attendance'] >= 60) echo 'bg-warning text-dark';
                                                else echo 'bg-danger';
                                            ?> text-white">
                                            <?php echo $sub['attendance']; ?>%
                                        </div>
                                        <small class="text-muted d-block mt-1">Attendance</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 text-center">
                                <a href="view_subject.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> View Course
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Theme Toggle Script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('theme') || 'light';

        // Apply saved theme
        html.setAttribute('data-bs-theme', savedTheme);
        updateToggleText(savedTheme);

        toggleBtn.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateToggleText(newTheme);
        });

        function updateToggleText(theme) {
            toggleBtn.innerHTML = theme === 'dark'
                ? '<i class="fas fa-sun me-2"></i> Light Mode'
                : '<i class="fas fa-moon me-2"></i> Dark Mode';
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>