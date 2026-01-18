<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle delete (only own announcements)
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // Security: only delete if teacher owns the subject of this announcement
    $check = mysqli_query($connection, "
        SELECT a.id 
        FROM announcements a
        JOIN subjects s ON a.subject_name = s.name
        WHERE a.id = $del_id AND s.teacher_id = $user_id
    ");
    
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($connection, "DELETE FROM announcements WHERE id = $del_id");
        $message = '<div class="alert alert-success">Announcement deleted.</div>';
    } else {
        $message = '<div class="alert alert-danger">You can only delete your own announcements.</div>';
    }
}

// Fetch only announcements from subjects THIS teacher owns
$ann_query = mysqli_query($connection, "
    SELECT a.*, s.name AS subject_name 
    FROM announcements a
    JOIN subjects s ON a.subject_name = s.name
    WHERE s.teacher_id = $user_id
    ORDER BY a.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 900px; }
        .card { border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .ann-item { border-bottom: 1px solid #f1f3f5; padding: 1rem 0; }
        .ann-item:last-child { border-bottom: none; }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-normal">My Announcements</h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if (mysqli_num_rows($ann_query) > 0): ?>
                <?php while ($ann = mysqli_fetch_assoc($ann_query)): ?>
                    <div class="ann-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-medium mb-1">
                                    <?php echo htmlspecialchars($ann['title']); ?>
                                </h6>
                                <small class="text-muted">
                                    Subject: <?php echo htmlspecialchars($ann['subject_name']); ?> • 
                                    Posted: <?php echo date('M d, Y • H:i', strtotime($ann['created_at'])); ?>
                                </small>
                                <p class="mb-2 mt-2 text-muted">
                                    <?php echo nl2br(htmlspecialchars($ann['message'])); ?>
                                </p>
                            </div>
                            <a href="?delete_id=<?php echo $ann['id']; ?>" 
                               class="text-danger small text-decoration-none"
                               onclick="return confirm('Delete this announcement?');">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted text-center py-4">You haven't posted any announcements yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="post_subject_announcement.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Post New Announcement
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>