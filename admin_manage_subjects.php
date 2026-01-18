<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = '';

// Handle delete subject
if (isset($_GET['delete_id'])) {
    $sub_id = (int)$_GET['delete_id'];
    
    $check = mysqli_query($connection, "SELECT id FROM subjects WHERE id = $sub_id");
    if (mysqli_num_rows($check) > 0) {
        // Clean up related data first
        mysqli_query($connection, "DELETE FROM enrollments WHERE subject_id = $sub_id");
        mysqli_query($connection, "DELETE FROM assignments WHERE subject_id = $sub_id");
        mysqli_query($connection, "DELETE FROM attendance WHERE subject_id = $sub_id");
        mysqli_query($connection, "DELETE FROM subjects WHERE id = $sub_id");
        
        $message = '<div class="alert alert-success">Subject and all related data deleted successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Subject not found.</div>';
    }
}

// Get all subjects with extra info
$subjects_query = mysqli_query($connection, "
    SELECT 
        s.id, s.name, s.teacher_id,
        u.name AS teacher_name,
        (SELECT COUNT(*) FROM enrollments e WHERE e.subject_id = s.id) AS enrolled_count
    FROM subjects s
    LEFT JOIN users u ON s.teacher_id = u.id
    ORDER BY s.name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Manage Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-book me-2"></i>Global Course Management</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php echo $message; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (mysqli_num_rows($subjects_query) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Subject Name</th>
                                <th>Instructor</th>
                                <th>Students</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($subjects_query)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['teacher_name'] ?? 'No teacher assigned'); ?></td>
                                <td><span class="badge bg-info"><?php echo $row['enrolled_count']; ?></span></td>
                                <td class="text-center">
                                    <a href="manage_subject.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary me-2">
                                        <i class="fas fa-external-link-alt me-1"></i> Open
                                    </a>
                                    <a href="?delete_id=<?php echo $row['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this subject?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No subjects have been created yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>