<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Fetch only subjects taught by this teacher
$my_subjects = mysqli_query($connection, "SELECT * FROM subjects WHERE teacher_id = $user_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Subject | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f9; padding: 50px; }
        .list-group-item { border: none; margin-bottom: 10px; border-radius: 10px !important; transition: 0.3s; }
        .list-group-item:hover { transform: scale(1.02); background: #0d47a1; color: white; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <h3 class="fw-bold mb-4">Choose a Subject</h3>
        <div class="list-group shadow-sm">
            <?php while($sub = mysqli_fetch_assoc($my_subjects)): ?>
                <a href="create_assignment.php?subject_id=<?php echo $sub['id']; ?>" class="list-group-item list-group-item-action p-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold"><?php echo $sub['name']; ?></h5>
                            <small class="opacity-75">Click to post assignment</small>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
        <a href="index.php" class="btn btn-link mt-4 text-muted"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
    </div>
</body>
</html>