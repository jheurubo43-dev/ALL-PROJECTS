<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$subjects_query = mysqli_query($connection, "
    SELECT s.id, s.name,
           (SELECT COUNT(*) FROM enrollments e WHERE e.subject_id = s.id) AS student_count,
           (SELECT COUNT(*) FROM assignments a WHERE a.subject_id = s.id) AS assignment_count,
           (SELECT COUNT(*) FROM submissions sub 
            JOIN assignments ass ON sub.assignment_id = ass.id 
            WHERE ass.subject_id = s.id) AS submission_count
    FROM subjects s 
    WHERE s.teacher_id = $user_id
    ORDER BY s.name
");

$attendance_stats = [];
while ($sub = mysqli_fetch_assoc($subjects_query)) {
    $sub_id = $sub['id'];

    $att_total = mysqli_fetch_assoc(mysqli_query($connection,"
        SELECT COUNT(*) total FROM attendance WHERE subject_id = $sub_id
    "))['total'] ?? 0;

    $att_present = mysqli_fetch_assoc(mysqli_query($connection,"
        SELECT COUNT(*) present FROM attendance 
        WHERE subject_id = $sub_id AND status = 'present'
    "))['present'] ?? 0;

    $attendance_stats[$sub_id] = [
        'name' => $sub['name'],
        'student_count' => $sub['student_count'],
        'assignment_count' => $sub['assignment_count'],
        'submission_count' => $sub['submission_count'],
        'attendance_percent' => $att_total ? round(($att_present / $att_total) * 100, 1) : 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Class Summary</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
body{
    min-height:100vh;
    background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    padding:2rem;
}
[data-bs-theme="dark"] body{
    background: linear-gradient(135deg,#1e3a8a 0%,#581c87 100%);
}
.main-card{
    background:#fff;
    border-radius:20px;
    padding:2rem;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
}
[data-bs-theme="dark"] .main-card{
    background:#1e293b;
}
.stat-badge{
    font-size:1.1rem;
    padding:.4rem .8rem;
    border-radius:8px;
}
</style>
</head>

<body>

<div class="container" style="max-width:1100px">

    <!-- HEADER / ADD SUBJECT -->
    <div class="main-card mb-4 text-center">
        <div class="mb-3">
            <i class="fas fa-chalkboard-teacher fa-2x text-primary"></i>
        </div>
        <h3 class="fw-bold mb-1">Teacher Class Summary</h3>
        <p class="text-muted mb-3">Overview of your courses and activity</p>

        <div class="d-flex justify-content-center gap-2">
            <a href="add_subject.php" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>Create New Course
            </a>
            <a href="index.php" class="btn btn-outline-secondary btn-lg">
                Dashboard
            </a>
        </div>
    </div>

    <!-- CLASSES -->
    <?php if (empty($attendance_stats)): ?>
        <div class="main-card text-center py-5">
            <i class="fas fa-book-open fa-2x text-muted mb-3"></i>
            <p class="mb-0">You don’t have any classes yet.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($attendance_stats as $sub_id => $stats): ?>
                <div class="col-lg-6">
                    <div class="main-card h-100">
                        <h5 class="fw-semibold mb-4">
                            <?php echo htmlspecialchars($stats['name']); ?>
                        </h5>

                        <div class="row text-center g-3">
                            <div class="col-6 col-md-3">
                                <div class="stat-badge bg-primary text-white">
                                    <?php echo $stats['student_count']; ?>
                                </div>
                                <small class="text-muted d-block">Students</small>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-badge bg-info text-white">
                                    <?php echo $stats['assignment_count']; ?>
                                </div>
                                <small class="text-muted d-block">Assignments</small>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-badge bg-success text-white">
                                    <?php echo $stats['submission_count']; ?>
                                </div>
                                <small class="text-muted d-block">Submissions</small>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="stat-badge 
                                    <?php
                                    if($stats['attendance_percent']>=80) echo 'bg-success';
                                    elseif($stats['attendance_percent']>=60) echo 'bg-warning text-dark';
                                    else echo 'bg-danger';
                                    ?> text-white">
                                    <?php echo $stats['attendance_percent']; ?>%
                                </div>
                                <small class="text-muted d-block">Attendance</small>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="manage_subject.php?id=<?php echo $sub_id; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-cog me-1"></i>Manage Class
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
const savedTheme = localStorage.getItem('theme');
if(savedTheme){
    document.documentElement.setAttribute('data-bs-theme', savedTheme);
}
</script>

</body>
</html>
