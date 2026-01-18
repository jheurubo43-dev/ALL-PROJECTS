<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$subject_id = intval($_GET['subject_id']);

if (isset($_POST['submit_assignment'])) {
    $title = mysqli_real_escape_string($connection, $_POST['title']);
    $instructions = mysqli_real_escape_string($connection, $_POST['instructions']);
    $due_date = $_POST['due_date'];

    // Insert into your existing assignments table structure
    $query = "INSERT INTO assignments (title, instructions, subject, due_date) 
              VALUES ('$title', '$instructions', '$subject_id', '$due_date')";
    
    if (mysqli_query($connection, $query)) {
        header("Location: manage_subject.php?id=$subject_id&status=success");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post New Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container" style="max-width: 600px;">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
            <h3 class="fw-bold mb-4">Post Assignment</h3>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Assignment Title</label>
                    <input type="text" name="title" class="form-control border-0 bg-light" placeholder="e.g. Midterm Essay" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Instructions</label>
                    <textarea name="instructions" class="form-control border-0 bg-light" rows="4" placeholder="Describe the task..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Due Date</label>
                    <input type="date" name="due_date" class="form-control border-0 bg-light" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" name="submit_assignment" class="btn btn-primary fw-bold">Post to Classroom</button>
                    <a href="index.php" class="btn btn-link text-muted">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>