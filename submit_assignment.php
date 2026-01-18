<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$assignment_id = intval($_GET['id']);
$student_id = $_SESSION['user_id'];

if (isset($_POST['upload_work'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_name = time() . "_" . basename($_FILES["fileToUpload"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        mysqli_query($connection, "INSERT INTO submissions (assignment_id, student_id, file_path) VALUES ($assignment_id, $student_id, '$target_file')");
        header("Location: index.php?status=success");
        exit();
    }
}

$assign_q = mysqli_query($connection, "SELECT * FROM assignments WHERE id = $assignment_id");
$assign = mysqli_fetch_assoc($assign_q);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Work</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="card shadow border-0 mx-auto p-5" style="max-width: 500px; border-radius: 20px;">
        <h3 class="fw-bold text-center mb-4">Upload Assignment</h3>
        <p class="text-center text-muted"><?php echo htmlspecialchars($assign['title']); ?></p>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="fileToUpload" class="form-control mb-4" required>
            <button type="submit" name="upload_work" class="btn btn-primary w-100 btn-lg rounded-pill">Submit Now</button>
            <a href="index.php" class="btn btn-link w-100 text-muted mt-2">Cancel</a>
        </form>
    </div>
</body>
</html>