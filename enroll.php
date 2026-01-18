<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle the join button click
if (isset($_GET['join_id'])) {
    $sub_id = intval($_GET['join_id']);
    
    // Check if student is already enrolled
    $check = mysqli_query($connection, "SELECT * FROM enrollments WHERE student_id = $user_id AND subject_id = $sub_id");
    
    if (mysqli_num_rows($check) == 0) {
        // Insert into the table shown in your screenshot
        mysqli_query($connection, "INSERT INTO enrollments (student_id, subject_id, enrolled_at) VALUES ($user_id, $sub_id, NOW())");
        header("Location: index.php"); // Send them back to dashboard to see their new class
        exit();
    }
}

// Get all subjects that exist
$subjects = mysqli_query($connection, "SELECT s.*, u.name as teacher_name FROM subjects s LEFT JOIN users u ON s.teacher_id = u.id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enroll in Subjects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f9; padding: 50px; }
        .subject-card { border: none; border-radius: 15px; background: white; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="fw-bold mb-4">Available Subjects</h2>
        <div class="row">
            <?php while($row = mysqli_fetch_assoc($subjects)): 
                // Check if already enrolled to show/hide button
                $sid = $row['id'];
                $en_check = mysqli_query($connection, "SELECT * FROM enrollments WHERE student_id = $user_id AND subject_id = $sid");
                $is_enrolled = (mysqli_num_rows($en_check) > 0);
            ?>
            <div class="col-md-4">
                <div class="subject-card">
                    <h5 class="fw-bold"><?php echo $row['name']; ?></h5>
                    <p class="text-muted">Instructor: <?php echo $row['teacher_name'] ?? 'TBD'; ?></p>
                    <?php if($is_enrolled): ?>
                        <button class="btn btn-success w-100 disabled">Already Enrolled</button>
                    <?php else: ?>
                        <a href="?join_id=<?php echo $row['id']; ?>" class="btn btn-primary w-100">Enroll Now</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <a href="index.php" class="btn btn-link mt-3 text-dark">← Back to Dashboard</a>
    </div>
</body>
</html>