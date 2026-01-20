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

// 2. Fetch Subject Name
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
        
        // Insert into attendance table using both ID and Name for compatibility
        $sql = "INSERT INTO attendance (student_id, subject_id, subject, date, status, recorded_by) 
                VALUES ($student_id, $subject_id, '$subject_name', '$date', '$status', $teacher_id)";
        mysqli_query($connection, $sql);
    }
    header("Location: manage_subject.php?id=$subject_id&status=attendance_saved");
    exit();
}

// 4. Fetch Students enrolled in this subject
$students_query = mysqli_query($connection, "
    SELECT u.id, u.name 
    FROM users u
    JOIN enrollments e ON u.id = e.student_id
    WHERE e.subject_id = $subject_id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Attendance | <?php echo htmlspecialchars($subject_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .attendance-card { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .status-radio { display: none; }
        .status-label { cursor: pointer; padding: 8px 15px; border-radius: 20px; border: 2px solid #ddd; transition: 0.3s; font-size: 0.9rem; }
        input[value="present"]:checked + .status-label { background: #d1e7dd; border-color: #0f5132; color: #0f5132; }
        input[value="absent"]:checked + .status-label { background: #f8d7da; border-color: #842029; color: #842029; }
        input[value="late"]:checked + .status-label { background: #fff3cd; border-color: #664d03; color: #664d03; }
        input[value="excused"]:checked + .status-label { background: #e2e3e5; border-color: #383d41; color: #383d41; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card attendance-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold m-0 text-primary">Attendance</h3>
                        <a href="manage_subject.php?id=<?php echo $subject_id; ?>" class="btn btn-outline-secondary btn-sm">Back</a>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Date</label>
                            <input type="date" name="attendance_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($student = mysqli_fetch_assoc($students_query)): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
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
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <button type="submit" name="save_attendance" class="btn btn-primary w-100 btn-lg rounded-pill fw-bold mt-4">
                            Save Attendance
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>