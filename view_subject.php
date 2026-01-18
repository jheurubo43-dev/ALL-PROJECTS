<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Verify enrollment
$en_check = mysqli_query($connection, "SELECT * FROM enrollments WHERE student_id = $user_id AND subject_id = $subject_id");
if (!$en_check || mysqli_num_rows($en_check) == 0) {
    header("Location: index.php?error=not_enrolled");
    exit();
}

// Fetch subject info
$subject_query = mysqli_query($connection, "
    SELECT s.*, u.name as teacher_name 
    FROM subjects s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    WHERE s.id = $subject_id
");
$subject = mysqli_fetch_assoc($subject_query);

if (!$subject) {
    die("Subject not found.");
}

// Fetch announcements (subject-specific + global)
$announcements = mysqli_query($connection, "
    SELECT * FROM announcements 
    WHERE subject_name = '{$subject['name']}' 
       OR subject_name = 'Global'
    ORDER BY created_at DESC 
    LIMIT 5
");

// Fetch learning materials for this subject
$materials_query = mysqli_query($connection, "
    SELECT * FROM learning_materials 
    WHERE subject_id = $subject_id 
    ORDER BY uploaded_at DESC
");

// Fetch assignments
$assign_query = mysqli_query($connection, "
    SELECT * FROM assignments 
    WHERE subject_id = $subject_id 
    ORDER BY due_date ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Classroom | <?php echo htmlspecialchars($subject['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .section-card { 
            border: 1px solid #e0e0e0; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            margin-bottom: 2rem; 
        }
        .material-item {
            padding: 1rem 0;
            border-bottom: 1px solid #f1f3f5;
        }
        .material-item:last-child {
            border-bottom: none;
        }
        .download-btn {
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-normal"><?php echo htmlspecialchars($subject['name']); ?></h1>
                <p class="text-muted mb-0">Teacher: <?php echo htmlspecialchars($subject['teacher_name'] ?? 'Not assigned'); ?></p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>

        <!-- Announcements -->
        <?php if (mysqli_num_rows($announcements) > 0): ?>
            <div class="section-card">
                <div class="card-body">
                    <h5 class="fw-medium mb-3">Announcements</h5>
                    <?php while ($ann = mysqli_fetch_assoc($announcements)): ?>
                        <div class="alert alert-light border mb-3 p-3">
                            <div class="d-flex justify-content-between">
                                <strong>
                                    <?php echo htmlspecialchars($ann['title']); ?>
                                    <?php if ($ann['subject_name'] === 'Global'): ?>
                                        <span class="badge bg-info ms-2">Global</span>
                                    <?php endif; ?>
                                </strong>
                                <small class="text-muted">
                                    <?php echo date('M d, Y • H:i', strtotime($ann['created_at'])); ?>
                                </small>
                            </div>
                            <p class="mb-0 mt-2 text-muted">
                                <?php echo nl2br(htmlspecialchars($ann['message'])); ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-light text-center mb-5">
                No announcements for this subject yet.
            </div>
        <?php endif; ?>

        <!-- Learning Materials -->
        <div class="section-card">
            <div class="card-body">
                <h5 class="fw-medium mb-3">Learning Materials</h5>
                <?php if (mysqli_num_rows($materials_query) > 0): ?>
                    <?php while ($mat = mysqli_fetch_assoc($materials_query)): ?>
                        <div class="material-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($mat['title']); ?></strong><br>
                                <small class="text-muted">
                                    Uploaded: <?php echo date('M d, Y', strtotime($mat['uploaded_at'])); ?>
                                </small>
                            </div>
                            <?php if (!empty($mat['file_path']) && file_exists($mat['file_path'])): ?>
                                <a href="<?php echo htmlspecialchars($mat['file_path']); ?>" 
                                   class="btn btn-sm btn-outline-primary download-btn" 
                                   download>
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">File not available</span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No learning materials uploaded for this subject yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assignments -->
        <h5 class="fw-medium mb-3">Assignments</h5>
        <?php if (mysqli_num_rows($assign_query) > 0): ?>
            <?php while ($task = mysqli_fetch_assoc($assign_query)): 
                $t_id = $task['id'];
                $grade = "Not Submitted";
                $is_submitted = false;
                
                $sub_q = mysqli_query($connection, "SELECT grade FROM submissions WHERE assignment_id = $t_id AND student_id = $user_id");
                if ($sub_q && mysqli_num_rows($sub_q) > 0) {
                    $sub_data = mysqli_fetch_assoc($sub_q);
                    $grade = $sub_data['grade'];
                    $is_submitted = true;
                }
            ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-medium mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                <p class="text-muted small mb-2">
                                    <?php echo htmlspecialchars($task['instructions'] ?? 'No instructions provided.'); ?>
                                </p>
                                <span class="badge bg-warning text-dark">
                                    Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                </span>
                            </div>
                            <div class="text-end">
                                <?php if ($is_submitted): ?>
                                    <span class="badge bg-success px-3 py-2">Submitted</span>
                                    <div class="mt-2">
                                        <strong>Grade:</strong> <?php echo htmlspecialchars($grade); ?>
                                    </div>
                                <?php else: ?>
                                    <a href="submit_assignment.php?id=<?php echo $task['id']; ?>" 
                                       class="btn btn-primary btn-sm px-4">
                                        Submit Now
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5 bg-white rounded shadow-sm">
                <p class="text-muted">No assignments posted yet.</p>
            </div>
        <?php endif; ?>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>