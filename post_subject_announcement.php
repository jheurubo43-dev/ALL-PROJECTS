<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Fetch subjects that THIS teacher teaches
$subjects = mysqli_query($connection, "
    SELECT id, name 
    FROM subjects 
    WHERE teacher_id = $user_id 
    ORDER BY name
");

// Handle posting new announcement
if (isset($_POST['post_announcement'])) {
    $subject_id = (int)$_POST['subject_id'];
    $title      = mysqli_real_escape_string($connection, $_POST['title']);
    $message_text = mysqli_real_escape_string($connection, $_POST['message']);

    // Security: make sure this teacher owns the subject
    $check = mysqli_query($connection, "SELECT id FROM subjects WHERE id = $subject_id AND teacher_id = $user_id");
    if (mysqli_num_rows($check) === 0) {
        $message = '<div class="alert alert-danger">You do not own this subject.</div>';
    } elseif (empty($title) || empty($message_text)) {
        $message = '<div class="alert alert-warning">Title and message are required.</div>';
    } else {
        $sql = "INSERT INTO announcements (subject_name, title, message, created_at) 
                VALUES ((SELECT name FROM subjects WHERE id = $subject_id), '$title', '$message_text', NOW())";
        if (mysqli_query($connection, $sql)) {
            $message = '<div class="alert alert-success">Announcement posted successfully.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . mysqli_error($connection) . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Post Subject Announcement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 700px; }
        .card { border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-normal">Post Subject Announcement</h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label fw-medium">Select Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">-- Choose your subject --</option>
                        <?php while ($sub = mysqli_fetch_assoc($subjects)): ?>
                            <option value="<?php echo $sub['id']; ?>">
                                <?php echo htmlspecialchars($sub['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Title</label>
                    <input type="text" name="title" class="form-control" required 
                           placeholder="e.g. Quiz 1 Reminder">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">Message</label>
                    <textarea name="message" class="form-control" rows="6" required 
                              placeholder="Write your announcement here..."></textarea>
                </div>

                <button type="submit" name="post_announcement" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i> Post Announcement
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>