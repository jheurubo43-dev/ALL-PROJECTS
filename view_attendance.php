<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Fetch Subject Details
$sub_query = mysqli_query($connection, "SELECT name FROM subjects WHERE id = $subject_id");
$subject = mysqli_fetch_assoc($sub_query);
$subject_name = $subject['name'] ?? 'All Subjects';

// 2. Fetch Attendance Records (Using the subject_id column for accuracy)
$query_str = "SELECT * FROM attendance WHERE student_id = $user_id";
if($subject_id > 0) {
    $query_str .= " AND subject_id = $subject_id";
}
$query_str .= " ORDER BY date DESC";
$attendance_query = mysqli_query($connection, $query_str);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-pill { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .bg-present { background: #d1e7dd; color: #0f5132; }
        .bg-absent { background: #f8d7da; color: #842029; }
        .bg-late { background: #fff3cd; color: #664d03; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold m-0">Attendance Report: <?php echo htmlspecialchars($subject_name); ?></h5>
            </div>
            <div class="card-body">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($attendance_query) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($attendance_query)): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td>
                                        <span class="status-pill bg-<?php echo strtolower($row['status']); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-4">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <a href="index.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>