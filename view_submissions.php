<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all assignments from teacher's subjects with submission counts
$query = "SELECT 
            a.id, a.title, a.due_date, a.created_at,
            s.name as subject_name,
            COUNT(DISTINCT sub.id) as submission_count,
            COUNT(DISTINCT CASE WHEN sub.grade IS NOT NULL AND sub.grade != '' THEN sub.id END) as graded_count
          FROM assignments a
          JOIN subjects s ON a.subject_id = s.id
          LEFT JOIN submissions sub ON a.id = sub.assignment_id
          WHERE s.teacher_id = $user_id
          GROUP BY a.id
          ORDER BY a.created_at DESC";

$result = mysqli_query($connection, $query);
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Submissions | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 2rem; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        .card { 
            border-radius: 15px; 
            border: none; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        [data-bs-theme="dark"] .card { 
            background: #1e293b; 
        }
        [data-bs-theme="dark"] .table { 
            color: #e2e8f0; 
        }
        .assignment-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
            background: white;
        }
        [data-bs-theme="dark"] .assignment-card {
            background: #334155;
            border-color: #475569;
        }
        .assignment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
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
<body>
<div class="container" style="max-width: 1100px;">
    <div class="page-header shadow">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1">
                    <i class="fas fa-tasks me-2"></i>Student Submissions
                </h3>
                <p class="mb-0 opacity-75">View and grade student work</p>
            </div>
            <a href="index.php" class="btn btn-light rounded-pill">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="row">
            <?php while($assignment = mysqli_fetch_assoc($result)): ?>
                <div class="col-lg-6 mb-4">
                    <div class="assignment-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                <p class="text-muted small mb-0">
                                    <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($assignment['subject_name']); ?>
                                </p>
                            </div>
                            <?php if ($assignment['submission_count'] > 0): ?>
                                <span class="badge bg-primary rounded-pill">
                                    <?php echo $assignment['submission_count']; ?> 
                                    submission<?php echo $assignment['submission_count'] != 1 ? 's' : ''; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">
                                <i class="far fa-calendar me-1"></i>
                                <strong>Due:</strong> <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                            </small>
                            <small class="text-muted d-block">
                                <i class="far fa-clock me-1"></i>
                                <strong>Posted:</strong> <?php echo date('M d, Y', strtotime($assignment['created_at'])); ?>
                            </small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <?php if ($assignment['submission_count'] > 0): ?>
                                <div class="small">
                                    <span class="badge bg-success me-1"><?php echo $assignment['graded_count']; ?> graded</span>
                                    <span class="badge bg-warning text-dark"><?php echo $assignment['submission_count'] - $assignment['graded_count']; ?> pending</span>
                                </div>
                                <a href="teacher_view_submissions.php?id=<?php echo $assignment['id']; ?>" 
                                   class="btn btn-primary btn-sm rounded-pill">
                                    <i class="fas fa-eye me-1"></i> View Submissions
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">No submissions yet</span>
                                <button class="btn btn-outline-secondary btn-sm rounded-pill" disabled>
                                    <i class="fas fa-inbox me-1"></i> No Submissions
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-clipboard-list fa-4x text-muted mb-3 opacity-25"></i>
                <h4 class="text-muted mb-2">No Assignments Yet</h4>
                <p class="text-muted">You haven't created any assignments yet. Create an assignment to start receiving submissions.</p>
                <a href="index.php" class="btn btn-primary rounded-pill mt-3">
                    <i class="fas fa-plus me-2"></i>Create Your First Assignment
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
</body>
</html>