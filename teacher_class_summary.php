<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all subjects taught by this teacher
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

// For each subject, calculate simple attendance % (present / total records)
$attendance_stats = [];
while ($sub = mysqli_fetch_assoc($subjects_query)) {
    $sub_id = $sub['id'];

    $att_total_q = mysqli_query($connection, "
        SELECT COUNT(*) as total 
        FROM attendance 
        WHERE subject_id = $sub_id
    ");
    $att_total = mysqli_fetch_assoc($att_total_q)['total'] ?? 0;

    $att_present_q = mysqli_query($connection, "
        SELECT COUNT(*) as present 
        FROM attendance 
        WHERE subject_id = $sub_id AND status = 'present'
    ");
    $att_present = mysqli_fetch_assoc($att_present_q)['present'] ?? 0;

    $att_percent = ($att_total > 0) ? round(($att_present / $att_total) * 100, 1) : 0;

    $attendance_stats[$sub_id] = [
        'student_count'     => $sub['student_count'],
        'assignment_count'  => $sub['assignment_count'],
        'submission_count'  => $sub['submission_count'],
        'attendance_percent'=> $att_percent,
        'name'              => $sub['name']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Class Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1000px; }
        .card { border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-badge { font-size: 1.1rem; min-width: 80px; text-align: center; }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-normal">Class Summary</h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if (empty($attendance_stats)): ?>
        <div class="alert alert-light text-center py-5">
            <i class="fas fa-book-open fa-2x text-muted mb-3 d-block"></i>
            You don't have any classes yet.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($attendance_stats as $sub_id => $stats): ?>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title fw-medium mb-3">
                                <?php echo htmlspecialchars($stats['name']); ?>
                            </h5>

                            <div class="row text-center g-3">
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-light rounded">
                                        <div class="stat-badge bg-primary text-white">
                                            <?php echo $stats['student_count']; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">Students</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-light rounded">
                                        <div class="stat-badge bg-info text-white">
                                            <?php echo $stats['assignment_count']; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">Assignments</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-light rounded">
                                        <div class="stat-badge bg-success text-white">
                                            <?php echo $stats['submission_count']; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">Submissions</small>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-light rounded">
                                        <div class="stat-badge 
                                            <?php 
                                                if ($stats['attendance_percent'] >= 80) echo 'bg-success';
                                                elseif ($stats['attendance_percent'] >= 60) echo 'bg-warning text-dark';
                                                else echo 'bg-danger';
                                            ?> text-white">
                                            <?php echo $stats['attendance_percent']; ?>%
                                        </div>
                                        <small class="text-muted d-block mt-1">Attendance</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 text-center">
                                <a href="manage_subject.php?id=<?php echo $sub_id; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-cog me-1"></i> Manage Class
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>