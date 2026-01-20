<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$search_student = $_GET['student'] ?? '';
$search_course  = $_GET['course'] ?? '';

$where_clauses = [];
if ($search_student !== '') {
    $where_clauses[] = "u.name LIKE '%" . mysqli_real_escape_string($connection, $search_student) . "%'";
}
if ($search_course !== '') {
    $where_clauses[] = "s.name LIKE '%" . mysqli_real_escape_string($connection, $search_course) . "%'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

$query = "
    SELECT 
        sub.id,
        u.name AS student_name,
        s.name AS course_name,
        a.title AS assignment_title,
        sub.grade,
        sub.submitted_at
    FROM submissions sub
    JOIN users u ON sub.student_id = u.id
    JOIN assignments a ON sub.assignment_id = a.id
    JOIN subjects s ON a.subject_id = s.id
    $where_sql
    ORDER BY sub.submitted_at DESC
    LIMIT 100
";
$result = mysqli_query($connection, $query);
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Global Gradebook | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 2rem; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        .page-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-chart-line me-2"></i>Global Gradebook
                    </h2>
                    <p class="mb-0 opacity-75">View all student grades across all courses</p>
                </div>
                <a href="index.php" class="btn btn-light rounded-pill">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Search Filters -->
        <div class="card mb-4 p-4">
            <h5 class="fw-bold mb-3">
                <i class="fas fa-filter me-2 text-primary"></i>Search Filters
            </h5>
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Student Name</label>
                    <input type="text" 
                           name="student" 
                           class="form-control" 
                           placeholder="Search by student name..." 
                           value="<?php echo htmlspecialchars($search_student); ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Course Name</label>
                    <input type="text" 
                           name="course" 
                           class="form-control" 
                           placeholder="Search by course/subject..." 
                           value="<?php echo htmlspecialchars($search_course); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid w-100">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Search
                        </button>
                    </div>
                </div>
            </form>
            <?php if ($search_student || $search_course): ?>
                <div class="mt-3">
                    <a href="admin_gradebook.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Grades Table -->
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="fw-bold mb-0">Grade Records</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4"><i class="fas fa-user me-2"></i>Student</th>
                            <th><i class="fas fa-book me-2"></i>Course</th>
                            <th><i class="fas fa-clipboard-list me-2"></i>Assignment</th>
                            <th><i class="fas fa-award me-2"></i>Grade</th>
                            <th><i class="far fa-calendar me-2"></i>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($result) > 0):
                            while($row = mysqli_fetch_assoc($result)): 
                        ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['assignment_title']); ?></td>
                                <td>
                                    <?php if (!empty($row['grade'])): ?>
                                        <span class="badge bg-success px-3">
                                            <?php echo htmlspecialchars($row['grade']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark px-3">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?php echo date('M d, Y g:i A', strtotime($row['submitted_at'])); ?>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-25 d-block"></i>
                                    <p class="text-muted">No grade records found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const savedTheme = localStorage.getItem('theme');
        if(savedTheme) document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>
</body>
</html>