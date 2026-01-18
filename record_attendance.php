<?php
session_start();
require_once('db.php');

// 1. Security Check: Only teachers can record attendance
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$teacher_id = $_SESSION['user_id'];

// 2. Fetch Subject Name (needed for the 'subject' varchar column)
$sub_query = mysqli_query($connection, "SELECT name FROM subjects WHERE id = $subject_id");
$subject = mysqli_fetch_assoc($sub_query);

if (!$subject) {
    die("Error: Subject not found.");
}

$subject_name = $subject['name'];

// 3. Handle Form Submission
if (isset($_POST['save_attendance'])) {
    $date = $_POST['attendance_date'];
    
    foreach ($_POST['status'] as $student_id => $status) {
        $student_id = intval($student_id);
        $status = mysqli_real_escape_string($connection, $status);
        
        // Insert into your attendance table
        $sql = "INSERT INTO attendance (student_id, subject, date, status, recorded_by) 
                VALUES ($student_id, '$subject_name', '$date', '$status', $teacher_id)";
        mysqli_query($connection, $sql);
    }
    
    header("Location: index.php?status=success");
    exit();
}

// 4. Fetch Enrolled Students
$students_query = mysqli_query($connection, "SELECT u.id, u.name FROM enrollments e 
                                           JOIN users u ON e.student_id = u.id 
                                           WHERE e.subject_id = $subject_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Attendance | <?php echo htmlspecialchars($subject_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .attendance-card { background: white; border-radius: 15px; padding: 30px; border: none; }
        .status-radio { display: none; }
        .status-label { 
            padding: 5px 15px; 
            border-radius: 20px; 
            border: 1px solid #ddd; 
            cursor: pointer; 
            font-size: 0.85rem;
            transition: 0.2s;
        }
        /* Color coding for statuses */
        input[value="present"]:checked + .status-label { background: #d1e7dd; color: #0f5132; border-color: #badbcc; }
        input[value="absent"]:checked + .status-label { background: #f8d7da; color: #842029; border-color: #f5c2c7; }
        input[value="late"]:checked + .status-label { background: #fff3cd; color: #664d03; border-color: #ffecb5; }
        input[value="excused"]:checked + .status-label { background: #e2e3e5; color: #41464b; border-color: #d3d6d8; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="attendance-card shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold mb-0 text-dark">Record Attendance</h3>
                        <a href="index.php" class="btn btn-light btn-sm rounded-pill px-3 border">Cancel</a>
                    </div>
                    
                    <h5 class="text-primary mb-4"><?php echo htmlspecialchars($subject_name); ?></h5>

                    <form method="POST">
                        <div class="mb-4 row">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-muted text-uppercase">Session Date</label>
                                <input type="date" name="attendance_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">Student Name</th>
                                    <th class="border-0 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($student = mysqli_fetch_assoc($students_query)): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <label>
                                                <input type="radio" name="status[<?php echo $student['id']; ?>]" value="present" class="status-radio" checked>
                                                <span class="status-label">Present</span>
                                            </label>
                                            <label>
                                                <input type="radio" name="status[<?php echo $student['id']; ?>]" value="absent" class="status-radio">
                                                <span class="status-label">Absent</span>
                                            </label>
                                            <label>
                                                <input type="radio" name="status[<?php echo $student['id']; ?>]" value="late" class="status-radio">
                                                <span class="status-label">Late</span>
                                            </label>
                                            <label>
                                                <input type="radio" name="status[<?php echo $student['id']; ?>]" value="excused" class="status-radio">
                                                <span class="status-label">Excused</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <button type="submit" name="save_attendance" class="btn btn-primary w-100 btn-lg rounded-pill fw-bold mt-4 shadow-sm">
                            <i class="fas fa-check-circle me-2"></i> Submit Attendance
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>