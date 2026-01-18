<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

if (isset($_POST['submit_assignment'])) {
    $title = mysqli_real_escape_string($connection, $_POST['title']);
    $instructions = mysqli_real_escape_string($connection, $_POST['instructions']); 
    $subject_id_post = (int)$_POST['subject_id']; // from hidden input
    $due_date = mysqli_real_escape_string($connection, $_POST['due_date']);

    // FIXED: use subject_id (integer) instead of subject
    $query = "INSERT INTO assignments (title, instructions, subject_id, due_date) 
              VALUES ('$title', '$instructions', $subject_id_post, '$due_date')";
    
    if (mysqli_query($connection, $query)) {
        header("Location: index.php?status=success");
        exit();
    } else {
        $error = "Error posting assignment: " . mysqli_error($connection);
    }
}

$sub_info = mysqli_query($connection, "SELECT name FROM subjects WHERE id = $subject_id");
$subject = mysqli_fetch_assoc($sub_info);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f9; min-height: 100vh; display: flex; align-items: center; }
        .form-card { border-radius: 20px; border: none; width: 100%; max-width: 600px; margin: auto; overflow: hidden; }
        .header-blue { background: #0d47a1; color: white; padding: 30px; }
    </style>
</head>
<body>
    <div class="card form-card shadow-lg">
        <div class="header-blue text-center">
            <h3 class="fw-bold mb-0">Post New Assignment</h3>
            <p class="mb-0 opacity-75">Class: <?php echo htmlspecialchars($subject['name'] ?? 'General'); ?></p>
        </div>
        <div class="card-body p-5">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Title</label>
                    <input type="text" name="title" class="form-control bg-light border-0 p-3" placeholder="e.g. Midterm Essay" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Instructions</label>
                    <textarea name="instructions" class="form-control bg-light border-0 p-3" rows="4" required></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase text-muted">Due Date</label>
                    <input type="date" name="due_date" class="form-control bg-light border-0 p-3" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" name="submit_assignment" class="btn btn-primary btn-lg fw-bold rounded-pill">Publish Assignment</button>
                    <a href="index.php" class="btn btn-link text-muted">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>