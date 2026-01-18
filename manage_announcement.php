<?php
session_start();
require_once('db.php');

// Authority Check: Only Admins and Teachers
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'teacher')) {
    header("Location: index.php");
    exit();
}

// Handle Posting New Announcement
if (isset($_POST['post_news'])) {
    $subject = mysqli_real_escape_string($connection, $_POST['subject_name']);
    $title = mysqli_real_escape_string($connection, $_POST['title']);
    $message = mysqli_real_escape_string($connection, $_POST['message']);
    
    // Updated SQL to match your table columns: subject_name, title, message
    $sql = "INSERT INTO announcements (subject_name, title, message, created_at) 
            VALUES ('$subject', '$title', '$message', NOW())";
    
    if (mysqli_query($connection, $sql)) {
        header("Location: manage_announcement.php?success=1");
        exit();
    }
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($connection, "DELETE FROM announcements WHERE id = $id");
    header("Location: manage_announcement.php");
    exit();
}

$all_announcements = mysqli_query($connection, "SELECT * FROM announcements ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LMS Project | Manage News</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 280px; background: #0d47a1; height: 100vh; position: fixed; color: white; padding: 30px 20px; }
        .main-content { margin-left: 280px; padding: 40px; }
        .btn-elite { background: #0d47a1; color: white; border-radius: 10px; padding: 12px; border: none; }
        .card { border-radius: 15px; border: none; }
    </style>
</head>
<body>
    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <i class="fas fa-university fa-3x mb-3"></i>
            <h3 class="fw-bold">LMS Project</h3>
        </div>
        <a href="index.php" class="btn btn-light w-100 text-primary fw-bold mb-3">← Dashboard</a>
    </div>

    <div class="main-content">
        <h1 class="fw-bold mb-4">Announcement Center</h1>

        <div class="row">
            <div class="col-md-5">
                <div class="card p-4 shadow-sm mb-4">
                    <h5 class="fw-bold mb-3">New Announcement</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="fw-bold small mb-2 text-muted">SUBJECT/CATEGORY</label>
                            <input type="text" name="subject_name" class="form-control bg-light" placeholder="e.g. General" required>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold small mb-2 text-muted">TITLE</label>
                            <input type="text" name="title" class="form-control bg-light" required>
                        </div>
                        <div class="mb-4">
                            <label class="fw-bold small mb-2 text-muted">MESSAGE</label>
                            <textarea name="message" class="form-control bg-light" rows="5" required></textarea>
                        </div>
                        <button type="submit" name="post_news" class="btn btn-elite w-100 shadow-sm">Post to Dashboard</button>
                    </form>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card p-4 shadow-sm">
                    <h5 class="fw-bold mb-3">Recent Posts</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Subject</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($all_announcements)): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $row['title']; ?></td>
                                    <td><span class="badge bg-primary opacity-75"><?php echo $row['subject_name']; ?></span></td>
                                    <td>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="text-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>