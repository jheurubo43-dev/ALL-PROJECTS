<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = '';

// Handle new announcement
if (isset($_POST['post_global'])) {
    $title   = mysqli_real_escape_string($connection, $_POST['title']);
    $message_text = mysqli_real_escape_string($connection, $_POST['message']);
    
    if (!empty($title) && !empty($message_text)) {
        $sql = "INSERT INTO announcements (subject_name, title, message, created_at) 
                VALUES ('Global', '$title', '$message_text', NOW())";
        if (mysqli_query($connection, $sql)) {
            $message = '<div class="alert alert-success">Global announcement posted.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . mysqli_error($connection) . '</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Title and message are required.</div>';
    }
}

// Handle delete
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    mysqli_query($connection, "DELETE FROM announcements WHERE id = $del_id AND subject_name = 'Global'");
    $message = '<div class="alert alert-success">Announcement deleted.</div>';
}

// Fetch only global announcements
$ann_query = mysqli_query($connection, "SELECT * FROM announcements WHERE subject_name = 'Global' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Global Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 900px; }
        .card { border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-control, .form-control:focus { border-color: #ced4da; box-shadow: none; }
        .btn-primary { background-color: #0d6efd; border: none; }
        .btn-danger { background-color: #dc3545; border: none; }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-normal">Global Announcements</h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <!-- Post New Announcement Form -->
    <div class="card mb-5">
        <div class="card-body">
            <h5 class="card-title mb-4">Post New Global Message</h5>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-medium">Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. System Maintenance Notice">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-medium">Message</label>
                    <textarea name="message" class="form-control" rows="5" required placeholder="This message will appear on every user's dashboard..."></textarea>
                </div>
                <button type="submit" name="post_global" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i> Post to All Users
                </button>
            </form>
        </div>
    </div>

    <!-- List of Existing Global Announcements -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-4">Existing Global Announcements</h5>
            
            <?php if (mysqli_num_rows($ann_query) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ann = mysqli_fetch_assoc($ann_query)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($ann['title']); ?></strong><br>
                                    <small class="text-muted">
                                        <?php echo nl2br(htmlspecialchars(substr($ann['message'], 0, 100))) . (strlen($ann['message']) > 100 ? '...' : ''); ?>
                                    </small>
                                </td>
                                <td class="text-muted"><?php echo date('M d, Y • H:i', strtotime($ann['created_at'])); ?></td>
                                <td>
                                    <a href="?delete_id=<?php echo $ann['id']; ?>" 
                                       class="text-danger text-decoration-none small"
                                       onclick="return confirm('Delete this announcement?');">
                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    No global announcements yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>