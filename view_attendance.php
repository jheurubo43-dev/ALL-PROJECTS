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

// 2. Fetch Attendance Records for this student
$query_str = "SELECT * FROM attendance WHERE student_id = $user_id";
if($subject_id > 0) {
    $query_str .= " AND subject = '$subject_name'";
}
$query_str .= " ORDER BY date DESC";
$attendance_query = mysqli_query($connection, $query_str);

// 3. Calculate Statistics
$total_sessions = mysqli_num_rows($attendance_query);
$present_count = 0;
if($total_sessions > 0) {
    $count_q = mysqli_query($connection, "SELECT COUNT(*) as count FROM attendance WHERE student_id = $user_id AND status = 'present' " . ($subject_id > 0 ? "AND subject = '$subject_name'" : ""));
    $present_count = mysqli_fetch_assoc($count_q)['count'];
}
$attendance_rate = ($total_sessions > 0) ? round(($present_count / $total_sessions) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance | <?php echo htmlspecialchars($subject_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .stats-card { background: white; border-radius: 15px; border: none; }
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; text-transform: capitalize; }
        .bg-present { background: #d1e7dd; color: #0f5132; }
        .bg-absent { background: #f8d7da; color: #842029; }
        .bg-late { background: #fff3cd; color: #664d03; }
        .bg-excused { background: #e2e3e5; color: #41464b; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Attendance Report</h2>
            <a href="index.php" class="btn btn-outline-dark rounded-pill">Back to Dashboard</a>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card p-4 shadow-sm text-center">
                    <h1 class="fw-bold text-primary mb-0"><?php echo $attendance_rate; ?>%</h1>
                    <p class="text-muted mb-0">Attendance Rate</p>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card stats-card p-4 shadow-sm">
                    <h5 class="fw-bold"><?php echo htmlspecialchars($subject_name); ?></h5>
                    <p class="text-muted mb-0">Total Sessions Recorded: <strong><?php echo $total_sessions; ?></strong></p>
                    <p class="text-muted mb-0">Days Present: <strong><?php echo $present_count; ?></strong></p>
                </div>
            </div>
        </div>

        <div class="card stats-card shadow-sm">
            <div class="table-responsive p-3">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($attendance_query) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($attendance_query)): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td>
                                        <span class="status-pill bg-<?php echo $row['status']; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No attendance records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>