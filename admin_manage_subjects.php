<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = '';

// Handle deleting a course
if (isset($_GET['delete_id'])) {
    $sub_id = intval($_GET['delete_id']);
    mysqli_query($connection, "DELETE FROM enrollments WHERE subject_id = $sub_id");
    mysqli_query($connection, "DELETE FROM subjects WHERE id = $sub_id");
    $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Course deleted successfully.</div>';
}

// Fetch all courses with teacher names
$query = "
    SELECT s.id, s.name as subject_name, u.name as teacher_name 
    FROM subjects s
    LEFT JOIN users u ON s.teacher_id = u.id
    ORDER BY s.name ASC
";
$subjects_result = mysqli_query($connection, $query);
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Courses | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 2rem; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        .page-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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
    </style>
</head>
<body>

<div class="container" style="max-width: 1200px;">
    <div class="page-header shadow">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="fas fa-book me-2"></i>Course Management</h2>
                <p class="mb-0 opacity-75">Manage all courses in the system</p>
            </div>
            <div>
                <a href="admin_add_subject.php" class="btn btn-light me-2">
                    <i class="fas fa-plus me-1"></i>New Course
                </a>
                <a href="index.php" class="btn btn-outline-light rounded-pill">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="fw-bold mb-0">All Courses</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4"><i class="fas fa-book me-2"></i>Course Name</th>
                        <th><i class="fas fa-user-tie me-2"></i>Instructor</th>
                        <th><i class="fas fa-users me-2"></i>Enrollments</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($subjects_result)): 
                        $sid = $row['id'];
                        $count_q = mysqli_query($connection, "SELECT COUNT(*) as total FROM enrollments WHERE subject_id = $sid");
                        $count_data = mysqli_fetch_assoc($count_q);
                    ?>
                    <tr>
                        <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                        <td>
                            <?php if ($row['teacher_name']): ?>
                                <span class="text-primary">
                                    <i class="fas fa-chalkboard-teacher me-1"></i>
                                    <?php echo htmlspecialchars($row['teacher_name']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">
                                    <i class="fas fa-user-slash me-1"></i>Unassigned
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info text-white">
                                <?php echo $count_data['total']; ?> students
                            </span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary me-2" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#viewStudents<?php echo $sid; ?>">
                                <i class="fas fa-users me-1"></i> View Students
                            </button>
                            <a href="admin_edit_subject.php?id=<?php echo $sid; ?>" 
                               class="btn btn-sm btn-outline-secondary me-2">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete_id=<?php echo $sid; ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Delete this course and unenroll all students?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>

                    <!-- Modal for viewing students -->
                    <div class="modal fade" id="viewStudents<?php echo $sid; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">
                                        <i class="fas fa-users me-2"></i>
                                        Students in <?php echo htmlspecialchars($row['subject_name']); ?>
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="small text-muted mb-3">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>
                                        <strong>Instructor:</strong> <?php echo htmlspecialchars($row['teacher_name'] ?? 'Unassigned'); ?>
                                    </p>
                                    <ul class="list-group list-group-flush">
                                        <?php
                                        $students_q = mysqli_query($connection, "
                                            SELECT u.name FROM users u 
                                            JOIN enrollments e ON u.id = e.student_id 
                                            WHERE e.subject_id = $sid
                                            ORDER BY u.name
                                        ");
                                        if(mysqli_num_rows($students_q) > 0):
                                            while($st = mysqli_fetch_assoc($students_q)): ?>
                                                <li class="list-group-item">
                                                    <i class="fas fa-user-graduate me-2 text-primary"></i>
                                                    <?php echo htmlspecialchars($st['name']); ?>
                                                </li>
                                            <?php endwhile;
                                        else: ?>
                                            <li class="list-group-item text-muted text-center py-4">
                                                <i class="fas fa-inbox fa-2x mb-2 opacity-25 d-block"></i>
                                                No students enrolled yet.
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
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