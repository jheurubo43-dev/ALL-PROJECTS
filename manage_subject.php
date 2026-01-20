<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

$subject_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Verify ownership
if ($user_role === 'admin') {
    $sub_query = mysqli_query($connection, "SELECT * FROM subjects WHERE id = $subject_id");
} else {
    $sub_query = mysqli_query($connection, "SELECT * FROM subjects WHERE id = $subject_id AND teacher_id = $user_id");
}

$subject = mysqli_fetch_assoc($sub_query);

if (!$subject) {
    die("Unauthorized or subject not found.");
}

// Handle grade updates
if (isset($_POST['update_grade'])) {
    $submission_id = (int)$_POST['sub_id'];
    $grade_val = mysqli_real_escape_string($connection, trim($_POST['grade_val']));
    mysqli_query($connection, "UPDATE submissions SET grade = '$grade_val' WHERE id = $submission_id");
    $success_msg = "Grade updated successfully!";
}

// Fetch enrolled students
$roster = mysqli_query($connection, "
    SELECT u.id, u.name, e.enrolled_at 
    FROM enrollments e 
    JOIN users u ON e.student_id = u.id 
    WHERE e.subject_id = $subject_id
    ORDER BY u.name
");

// Fetch recent submissions
$subs_query = mysqli_query($connection, "
    SELECT s.id, s.file_path, s.grade, s.submitted_at,
           u.name AS student_name, 
           a.title AS assignment_title
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    JOIN assignments a ON s.assignment_id = a.id
    WHERE a.subject_id = $subject_id
    ORDER BY s.submitted_at DESC
    LIMIT 20
");

// Fetch assignments
$assignments = mysqli_query($connection, "
    SELECT * FROM assignments 
    WHERE subject_id = $subject_id 
    ORDER BY due_date DESC
");
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($subject['name']); ?> | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 2rem; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        .page-header {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        [data-bs-theme="dark"] .card { background: #1e293b; }
        [data-bs-theme="dark"] .table { color: #e2e8f0; }
        .student-item {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        [data-bs-theme="dark"] .student-item {
            border-bottom-color: #334155;
        }
    </style>
</head>
<body>

<div class="container" style="max-width: 1400px;">
    <div class="page-header shadow">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-chalkboard me-2"></i><?php echo htmlspecialchars($subject['name']); ?>
                </h2>
                <p class="mb-0 opacity-75">Course Management Dashboard</p>
            </div>
            <div>
                <a href="create_assignment.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-light me-2">
                    <i class="fas fa-plus me-1"></i>New Assignment
                </a>
                <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'admin_manage_subjects.php' : 'index.php'; ?>" 
                   class="btn btn-outline-light rounded-pill">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Enrolled Students -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-users me-2 text-primary"></i>
                        Enrolled Students (<?php echo mysqli_num_rows($roster); ?>)
                    </h5>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    <?php if (mysqli_num_rows($roster) > 0): ?>
                        <?php while($stu = mysqli_fetch_assoc($roster)): ?>
                            <div class="student-item">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 35px; height: 35px; font-size: 0.9rem;">
                                        <?php echo strtoupper(substr($stu['name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($stu['name']); ?></div>
                                        <small class="text-muted">
                                            Enrolled: <?php echo date('M d, Y', strtotime($stu['enrolled_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3 opacity-25"></i>
                            <p class="text-muted">No students enrolled yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Assignments List -->
            <div class="card mt-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-clipboard-list me-2 text-success"></i>Assignments
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($assignments) > 0): ?>
                        <?php while($assign = mysqli_fetch_assoc($assignments)): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($assign['title']); ?></h6>
                                <small class="text-muted">
                                    <i class="far fa-calendar me-1"></i>
                                    Due: <?php echo date('M d, Y', strtotime($assign['due_date'])); ?>
                                </small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No assignments yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Submissions -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-file-alt me-2 text-warning"></i>Recent Submissions
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (mysqli_num_rows($subs_query) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Student</th>
                                        <th>Assignment</th>
                                        <th>Submitted</th>
                                        <th>Grade</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($subs_query)): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['assignment_title']); ?></td>
                                        <td class="text-muted small">
                                            <?php echo date('M d, g:i A', strtotime($row['submitted_at'])); ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline-flex align-items-center gap-2">
                                                <input type="hidden" name="sub_id" value="<?php echo $row['id']; ?>">
                                                <input type="text" 
                                                       name="grade_val" 
                                                       class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars($row['grade'] ?? ''); ?>" 
                                                       placeholder="Grade"
                                                       style="width: 90px;">
                                                <button type="submit" name="update_grade" class="btn btn-sm btn-success">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <a href="grade_submission.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3 opacity-25"></i>
                            <p class="text-muted">No submissions yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const savedTheme = localStorage.getItem('theme');
    if(savedTheme) document.documentElement.setAttribute('data-bs-theme', savedTheme);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>