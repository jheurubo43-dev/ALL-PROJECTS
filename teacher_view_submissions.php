<?php
session_start();
require_once('db.php');

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 2. Handle Grading Logic
if (isset($_POST['save_grade'])) {
    $sub_id = intval($_POST['submission_id']);
    $grade = mysqli_real_escape_string($connection, $_POST['grade']);
    $feedback = mysqli_real_escape_string($connection, $_POST['feedback']);
    
    $update_query = "UPDATE submissions SET grade = '$grade', feedback = '$feedback' WHERE id = $sub_id";
    mysqli_query($connection, $update_query);
    $msg = "Grade and feedback saved successfully!";
}

// 3. Fetch Assignment Title
$assign_info = mysqli_query($connection, "SELECT title FROM assignments WHERE id = $assignment_id");
$assignment_data = mysqli_fetch_assoc($assign_info);

// 4. Fetch all submissions
$query = "SELECT s.*, u.name as student_name 
          FROM submissions s 
          JOIN users u ON s.student_id = u.id 
          WHERE s.assignment_id = $assignment_id
          ORDER BY s.submitted_at DESC";
$result = mysqli_query($connection, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher | Review Submissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .submission-card { border-radius: 15px; border: none; transition: 0.3s; }
        .submission-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .file-download-zone { background: #e9ecef; border-radius: 10px; padding: 20px; text-align: center; }
    </style>
</head>
<body class="py-5">

<div class="container" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Review Submissions</h2>
            <p class="text-muted">Assignment: <span class="text-primary fw-bold"><?php echo htmlspecialchars($assignment_data['title'] ?? 'N/A'); ?></span></p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill">Back to Dashboard</a>
    </div>

    <?php if(isset($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card submission-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($row['student_name']); ?></h5>
                            <small class="text-muted mb-4"><i class="far fa-clock me-1"></i> <?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></small>
                            
                            <div class="file-download-zone mb-4">
                                <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i><br>
                                <?php if (!empty($row['file_path']) && file_exists($row['file_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($row['file_path']); ?>" download class="btn btn-primary w-100 rounded-pill fw-bold">
                                        <i class="fas fa-download me-2"></i> Download File
                                    </a>
                                <?php else: ?>
                                    <span class="text-danger small fw-bold">File not found on server</span>
                                <?php endif; ?>
                            </div>

                            <form method="POST" class="mt-auto">
                                <input type="hidden" name="submission_id" value="<?php echo $row['id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">GRADE</label>
                                    <input type="text" name="grade" class="form-control" placeholder="e.g. 95/100" value="<?php echo htmlspecialchars($row['grade']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">FEEDBACK</label>
                                    <textarea name="feedback" class="form-control" rows="2" placeholder="Great job!"><?php echo htmlspecialchars($row['feedback']); ?></textarea>
                                </div>
                                <button type="submit" name="save_grade" class="btn btn-success w-100 rounded-pill fw-bold">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-ghost fa-4x text-muted mb-3 opacity-25"></i>
                <h4 class="text-muted">No one has submitted this yet.</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>