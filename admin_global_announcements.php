<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = '';

// Handle deleting an announcement
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $delete_query = "DELETE FROM announcements WHERE id = $del_id AND subject_name = 'Global'";
    
    if (mysqli_query($connection, $delete_query)) {
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Announcement deleted.</div>';
    }
}

// Handle posting new announcement
if (isset($_POST['post_announcement'])) {
    $title = mysqli_real_escape_string($connection, trim($_POST['title']));
    $content = mysqli_real_escape_string($connection, trim($_POST['content']));
    
    $insert_query = "INSERT INTO announcements (subject_name, title, message, created_at) 
                     VALUES ('Global', '$title', '$content', NOW())";
    
    if (mysqli_query($connection, $insert_query)) {
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Global announcement posted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Error: ' . mysqli_error($connection) . '</div>';
    }
}

// Fetch all global announcements
$ann_result = mysqli_query($connection, "SELECT * FROM announcements WHERE subject_name = 'Global' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Global Announcements | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 2rem; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        .page-header {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        [data-bs-theme="dark"] .card { background: #1e293b; }
        .announcement-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        [data-bs-theme="dark"] .announcement-item {
            background: #334155;
            border-color: #475569;
        }
        .announcement-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container" style="max-width: 1200px;">
    <div class="page-header shadow">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-bullhorn me-2"></i>Global Announcements
                </h2>
                <p class="mb-0 opacity-75">Broadcast messages to all users</p>
            </div>
            <a href="index.php" class="btn btn-light rounded-pill">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="row g-4">
        <!-- Create New Announcement -->
        <div class="col-md-5">
            <div class="card p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-megaphone me-2 text-danger"></i>Create Broadcast
                </h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-heading me-2 text-primary"></i>Title
                        </label>
                        <input type="text" 
                               name="title" 
                               class="form-control form-control-lg" 
                               placeholder="e.g., School Holiday Reminder" 
                               required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-align-left me-2 text-primary"></i>Message
                        </label>
                        <textarea name="content" 
                                  class="form-control" 
                                  rows="6" 
                                  placeholder="Write your announcement here..." 
                                  required></textarea>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-info-circle me-1"></i>This will be visible to all users on their dashboard
                        </small>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="post_announcement" class="btn btn-danger btn-lg fw-bold">
                            <i class="fas fa-paper-plane me-2"></i>Broadcast to Everyone
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Announcement History -->
        <div class="col-md-7">
            <div class="card p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-history me-2 text-primary"></i>Announcement History
                </h5>
                <?php if(mysqli_num_rows($ann_result) > 0): ?>
                    <div style="max-height: 600px; overflow-y: auto;">
                        <?php while($ann = mysqli_fetch_assoc($ann_result)): ?>
                            <div class="announcement-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold text-primary mb-0">
                                        <?php echo htmlspecialchars($ann['title']); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="far fa-calendar me-1"></i>
                                        <?php echo date('M d, Y', strtotime($ann['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="text-muted mb-3">
                                    <?php echo nl2br(htmlspecialchars($ann['message'])); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-globe me-1"></i>Visible to all users
                                    </small>
                                    <a href="?delete_id=<?php echo $ann['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Delete this announcement?')">
                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3 opacity-25"></i>
                        <p class="text-muted">No global announcements yet.</p>
                        <p class="text-muted small">Create your first announcement to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const savedTheme = localStorage.getItem('theme');
    if(savedTheme) document.documentElement.setAttribute('data-bs-theme', savedTheme);
</script>
</body>
</html>