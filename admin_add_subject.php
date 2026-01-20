<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = '';
$teachers_result = mysqli_query($connection, "SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($connection, trim($_POST['name']));
    $teacher_id = $_POST['teacher_id'] === '' ? null : (int)$_POST['teacher_id'];

    if ($name === '') {
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Course name is required.</div>';
    } else {
        if ($teacher_id === null) {
            $stmt = $connection->prepare("INSERT INTO subjects (name) VALUES (?)");
            $stmt->bind_param("s", $name);
        } else {
            $stmt = $connection->prepare("INSERT INTO subjects (name, teacher_id) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $teacher_id);
        }

        if ($stmt->execute()) {
            header("Location: admin_manage_subjects.php?success=created");
            exit();
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Error creating course.</div>';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add New Course | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            min-height: 100vh; 
            display: flex; 
            align-items: center;
            padding: 2rem;
        }
        [data-bs-theme="dark"] body {
            background: linear-gradient(135deg, #581c87 0%, #6b21a8 100%);
        }
        .card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            margin: auto;
        }
        [data-bs-theme="dark"] .card {
            background: #1e293b;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <div class="icon-circle">
                    <i class="fas fa-book-medical text-white fa-2x"></i>
                </div>
                <h3 class="fw-bold mb-2">Add New Course</h3>
                <p class="text-muted">Create a new course in the system</p>
            </div>

            <?php echo $message; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-heading me-2 text-primary"></i>Course Name
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg" 
                           name="name" 
                           placeholder="e.g., Introduction to Computer Science"
                           required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Assign Instructor
                    </label>
                    <select class="form-select form-select-lg" name="teacher_id">
                        <option value="">None (admin-managed)</option>
                        <?php 
                        mysqli_data_seek($teachers_result, 0);
                        while ($teacher = mysqli_fetch_assoc($teachers_result)): 
                        ?>
                            <option value="<?php echo $teacher['id']; ?>"
                                <?php echo (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted mt-1 d-block">
                        <i class="fas fa-info-circle me-1"></i>You can assign an instructor now or later
                    </small>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold">
                        <i class="fas fa-plus-circle me-2"></i> Create Course
                    </button>
                    <a href="admin_manage_subjects.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const savedTheme = localStorage.getItem('theme');
    if(savedTheme) document.documentElement.setAttribute('data-bs-theme', savedTheme);
</script>
</body>
</html>