<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all submissions by this student
$submissions_query = mysqli_query($connection, "
    SELECT 
        sub.id, sub.file_path, sub.grade, sub.submitted_at,
        a.title AS assignment_title, a.due_date,
        s.name AS subject_name, s.id AS subject_id
    FROM submissions sub
    JOIN assignments a ON sub.assignment_id = a.id
    JOIN subjects s ON a.subject_id = s.id
    WHERE sub.student_id = $user_id
    ORDER BY sub.submitted_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Submissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1000px; }
        .card { border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .status-badge {
            min-width: 100px;
            text-align: center;
        }
    </style>
</head>
<button id="theme-toggle" class="btn btn-outline-secondary theme-toggle">
    <i class="fas fa-moon me-2"></i> Dark Mode
</button>
<body class="p-4 p-md-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-normal">My Submissions</h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if (mysqli_num_rows($submissions_query) > 0): ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Assignment</th>
                                <th>Submitted</th>
                                <th>File</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($sub = mysqli_fetch_assoc($submissions_query)): ?>
                            <tr>
                                <td>
                                    <a href="view_subject.php?id=<?php echo $sub['subject_id']; ?>" class="text-primary">
                                        <?php echo htmlspecialchars($sub['subject_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($sub['assignment_title']); ?></strong><br>
                                    <small class="text-muted">Due: <?php echo date('M d, Y', strtotime($sub['due_date'])); ?></small>
                                </td>
                                <td class="text-muted">
                                    <?php echo date('M d, H:i', strtotime($sub['submitted_at'])); ?>
                                </td>
                                <td>
                                    <?php if (!empty($sub['file_path']) && file_exists($sub['file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($sub['file_path']); ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           download>
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">No file</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sub['grade'] && $sub['grade'] !== 'Ungraded'): ?>
                                        <span class="badge bg-success status-badge">
                                            <?php echo htmlspecialchars($sub['grade']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary status-badge">Ungraded</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-light text-center py-5">
            <i class="fas fa-file-upload fa-2x text-muted mb-3 d-block"></i>
            You haven't submitted any work yet.
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>