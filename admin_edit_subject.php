<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$subject_id = intval($_GET['id'] ?? 0);
if ($subject_id === 0) {
    header("Location: admin_manage_subjects.php");
    exit();
}

$subject_result = mysqli_query($connection, "SELECT * FROM subjects WHERE id = $subject_id");
$subject = mysqli_fetch_assoc($subject_result);

if (!$subject) {
    die("Course not found.");
}

$teachers_result = mysqli_query($connection, "SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name ASC");

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $teacher_id = $_POST['teacher_id'] === '' ? null : (int)$_POST['teacher_id'];

    if ($name === '') {
        $message = '<div class="alert alert-danger">Course name is required.</div>';
    } else {
        if ($teacher_id === null) {
            $stmt = $connection->prepare("UPDATE subjects SET name = ?, teacher_id = NULL WHERE id = ?");
            $stmt->bind_param("si", $name, $subject_id);
        } else {
            $stmt = $connection->prepare("UPDATE subjects SET name = ?, teacher_id = ? WHERE id = ?");
            $stmt->bind_param("sii", $name, $teacher_id, $subject_id);
        }

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Course updated successfully!</div>';
            // Refresh data
            $subject_result = mysqli_query($connection, "SELECT * FROM subjects WHERE id = $subject_id");
            $subject = mysqli_fetch_assoc($subject_result);
        } else {
            $message = '<div class="alert alert-danger">Error updating course.</div>';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Edit Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-edit me-2"></i>Edit Course</h2>
        <a href="admin_manage_subjects.php" class="btn btn-secondary">Back</a>
    </div>

    <?php echo $message; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Course Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" required 
                           value="<?php echo htmlspecialchars($subject['name']); ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Assigned Instructor</label>
                    <select class="form-select" name="teacher_id">
                        <option value="">None (admin-managed)</option>
                        <?php while ($teacher = mysqli_fetch_assoc($teachers_result)): ?>
                            <option value="<?php echo $teacher['id']; ?>"
                                <?php echo ($subject['teacher_id'] ?? '') == $teacher['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-2"></i> Update Course
                </button>
                <a href="admin_manage_subjects.php" class="btn btn-outline-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>