<?php
session_start();
require_once('db.php');

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle Enrollment Action
if (isset($_GET['join_id']) && $role == 'student') {
    $sub_id = intval($_GET['join_id']);
    
    // Check if already joined
    $check = mysqli_query($connection, "SELECT * FROM enrollments WHERE student_id = $user_id AND subject_id = $sub_id");
    
    if (mysqli_num_rows($check) == 0) {
        $sql = "INSERT INTO enrollments (student_id, subject_id, enrolled_at) VALUES ($user_id, $sub_id, NOW())";
        mysqli_query($connection, $sql);
        header("Location: index.php?enrolled=true");
        exit();
    }
}

$all_subjects = mysqli_query($connection, "SELECT s.*, u.name as teacher_name FROM subjects s LEFT JOIN users u ON s.teacher_id = u.id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>LMS | Enrollment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light p-5">
    <div class="container">
        <h2 class="fw-bold mb-4">Available Subjects</h2>
        <div class="row">
            <?php while($row = mysqli_fetch_assoc($all_subjects)): ?>
            <div class="col-md-4 mb-4">
                <div class="card p-4 shadow-sm border-0" style="border-radius: 15px;">
                    <h5 class="fw-bold"><?php echo $row['name']; ?></h5>
                    <p class="text-muted small">Teacher: <?php echo $row['teacher_name']; ?></p>
                    <?php if($role == 'student'): ?>
                        <a href="?join_id=<?php echo $row['id']; ?>" class="btn btn-primary w-100">Enroll Now</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>