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
        sub.id, sub.file_path, sub.grade, sub.feedback, sub.submitted_at,
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
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Submissions | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        .container { max-width: 1100px; }
        .card { 
            border: 1px solid #e0e0e0; 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        [data-bs-theme="dark"] .card {
            background: #1e293b;
            border-color: #334155;
        }
        [data-bs-theme="dark"] .table {
            color: #e2e8f0;
        }
        .status-badge {
            min-width: 100px;
            text-align: center;
        }
        .feedback-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="container">
    <!-- Page Header -->
    <div class="page-header shadow">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-file-alt me-2"></i>My Submissions
                </h2>
                <p class="mb-0 opacity-75">Track your submitted assignments and grades</p>
            </div>
            <a href="index.php" class="btn btn-light rounded-pill">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if (mysqli_num_rows($submissions_query) > 0): ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">Subject</th>
                                <th>Assignment</th>
                                <th>Submitted</th>
                                <th>File</th>
                                <th>Grade</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($sub = mysqli_fetch_assoc($submissions_query)): ?>
                            <tr>
                                <td class="px-4">
                                    <a href="view_subject.php?id=<?php echo $sub['subject_id']; ?>" 
                                       class="text-decoration-none fw-semibold">
                                        <i class="fas fa-book me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($sub['subject_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($sub['assignment_title']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="far fa-calendar me-1"></i>
                                        Due: <?php echo date('M d, Y', strtotime($sub['due_date'])); ?>
                                    </small>
                                </td>
                                <td class="text-muted">
                                    <i class="far fa-clock me-1"></i>
                                    <?php echo date('M d, g:i A', strtotime($sub['submitted_at'])); ?>
                                </td>
                                <td>
                                    <?php if (!empty($sub['file_path']) && file_exists('uploads/submissions/' . $sub['file_path'])): ?>
                                        <a href="uploads/submissions/<?php echo htmlspecialchars($sub['file_path']); ?>" 
                                           class="btn btn-sm btn-outline-primary rounded-pill" 
                                           download>
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <i class="fas fa-exclamation-circle me-1"></i>No file
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($sub['grade'])): ?>
                                        <span class="badge bg-success status-badge">
                                            <i class="fas fa-award me-1"></i>
                                            <?php echo htmlspecialchars($sub['grade']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary status-badge">
                                            <i class="far fa-clock me-1"></i>Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($sub['feedback'])): ?>
                                        <button class="btn btn-sm btn-outline-info rounded-pill" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#feedbackModal<?php echo $sub['id']; ?>">
                                            <i class="fas fa-comment-dots me-1"></i> View
                                        </button>
                                        
                                        <!-- Feedback Modal -->
                                        <div class="modal fade" id="feedbackModal<?php echo $sub['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-comment me-2"></i>Teacher Feedback
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($sub['feedback'])); ?></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <i class="far fa-comment-slash me-1"></i>No feedback
                                        </span>
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
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-file-upload fa-4x text-muted mb-3 opacity-25"></i>
                <h4 class="text-muted mb-2">No Submissions Yet</h4>
                <p class="text-muted">You haven't submitted any assignments. Check your subjects for pending work.</p>
                <a href="index.php" class="btn btn-primary rounded-pill mt-3">
                    <i class="fas fa-book me-2"></i>View My Subjects
                </a>
            </div>
        </div>
    <?php endif; ?>
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