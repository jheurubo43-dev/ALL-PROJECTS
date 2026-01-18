<?php
session_start();
require_once('db.php');

// FIXED: Allow both teacher and admin roles
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

$subject_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// FIXED: If Admin, bypass the teacher-ownership check
if ($user_role === 'admin') {
    $sub_query = mysqli_query($connection, "SELECT * FROM subjects WHERE id = $subject_id");
} else {
    $sub_query = mysqli_query($connection, "SELECT * FROM subjects WHERE id = $subject_id AND teacher_id = $user_id");
}

$subject = mysqli_fetch_assoc($sub_query);

if (!$subject) {
    die("Unauthorized or subject not found.");
}

// Handle Grade Updates (Existing Logic)
if (isset($_POST['update_grade'])) {
    $submission_id = (int)$_POST['sub_id'];
    $grade_val = mysqli_real_escape_string($connection, $_POST['grade_val']);
    mysqli_query($connection, "UPDATE submissions SET grade = '$grade_val' WHERE id = $submission_id");
    header("Location: manage_subject.php?id=$subject_id&grade_updated=1");
    exit();
}

// Fetch roster (students enrolled)
$roster = mysqli_query($connection, "SELECT u.id, u.name, e.enrolled_at 
                                      FROM enrollments e 
                                      JOIN users u ON e.student_id = u.id 
                                      WHERE e.subject_id = $subject_id");

// Fetch submissions
$subs_query = mysqli_query($connection, "
    SELECT s.id, s.file_path, s.grade, s.submitted_at,
           u.name AS student_name, 
           a.title AS assignment_title, a.due_date
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    JOIN assignments a ON s.assignment_id = a.id
    WHERE a.subject_id = $subject_id
    ORDER BY s.submitted_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage | <?php echo htmlspecialchars($subject['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><?php echo htmlspecialchars($subject['name']); ?></h2>
        <div>
            <a href="post_assignment.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-primary me-2">
                <i class="fas fa-plus me-1"></i> New Assignment
            </a>
            <a href="admin_manage_subjects.php" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Enrolled Students</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php while($stu = mysqli_fetch_assoc($roster)): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($stu['name']); ?>
                                <small class="text-muted"><?php echo date('M d', strtotime($stu['enrolled_at'])); ?></small>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Recent Submissions</div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($subs_query) > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Assignment</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($subs_query)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['assignment_title']); ?></td>
                                        <td>
                                            <form method="POST" class="d-flex align-items-center gap-2">
                                                <input type="hidden" name="sub_id" value="<?php echo $row['id']; ?>">
                                                <input type="text" name="grade_val" class="form-control form-control-sm" 
                                                       value="<?php echo htmlspecialchars($row['grade'] ?? 'Ungraded'); ?>" 
                                                       style="width: 90px;">
                                                <button type="submit" name="update_grade" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No submissions yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>