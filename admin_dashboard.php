<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// === Statistics ===
$users_total     = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
$admins_count    = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users WHERE role='admin'"))['cnt'];
$teachers_count  = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users WHERE role='teacher'"))['cnt'];
$students_count  = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users WHERE role='student'"))['cnt'];

$subjects_total     = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM subjects"))['cnt'];
$enrollments_total  = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM enrollments"))['cnt'];
$assignments_total  = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM assignments"))['cnt'];
$submissions_total  = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM submissions"))['cnt'];

// Recent activity
$recent_users = mysqli_query($connection, "SELECT name, role, created_at FROM users ORDER BY created_at DESC LIMIT 6");
$recent_ann   = mysqli_query($connection, "SELECT title, subject_name, created_at FROM announcements ORDER BY created_at DESC LIMIT 6");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .page-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e9ecef;
            padding: 2rem 0;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1.75rem;
            text-align: center;
            transition: box-shadow 0.2s ease;
            height: 100%;
        }
        .stat-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .stat-icon {
            font-size: 2.2rem;
            color: #495057;
            margin-bottom: 1rem;
        }
        .stat-number {
            font-size: 2.4rem;
            font-weight: 600;
            color: #212529;
        }
        i {
            padding-left: 12px;
        }
        .stat-label {
            color: #6c757d;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        .section-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
        }
        .section-header {
            background-color: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }
        .activity-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f3f5;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .quick-link {
            display: block;
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            color: #212529;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .quick-link:hover {
            background-color: #f1f3f5;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.06);
            color: #212529;
        }
        .quick-link i {
            font-size: 1.8rem;
            margin-bottom: 0.75rem;
            color: #495057;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="page-header text-center">
        <h1 class="display-6 fw-normal">Admin Dashboard</h1>
        <p class="text-muted">System overview as of <?php echo date('F j, Y'); ?></p>
    </div>

    <!-- Statistics Grid -->
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <i class="fas fa-users stat-icon"></i>
                <div class="stat-number"><?php echo number_format($users_total); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <i class="fas fa-user-graduate stat-icon"></i>
                <div class="stat-number"><?php echo number_format($students_count); ?></div>
                <div class="stat-label">Students</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <i class="fas fa-chalkboard-teacher stat-icon"></i>
                <div class="stat-number"><?php echo number_format($teachers_count); ?></div>
                <div class="stat-label">Teachers</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <i class="fas fa-user-shield stat-icon"></i>
                <div class="stat-number"><?php echo number_format($admins_count); ?></div>
                <div class="stat-label">Administrators</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <i class="fas fa-book stat-icon"></i>
                <div class="stat-number"><?php echo number_format($subjects_total); ?></div>
                <div class="stat-label">Courses</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <i class="fas fa-user-check stat-icon"></i>
                <div class="stat-number"><?php echo number_format($enrollments_total); ?></div>
                <div class="stat-label">Enrollments</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <i class="fas fa-tasks stat-icon"></i>
                <div class="stat-number"><?php echo number_format($assignments_total); ?></div>
                <div class="stat-label">Assignments</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <i class="fas fa-file-upload stat-icon"></i>
                <div class="stat-number"><?php echo number_format($submissions_total); ?></div>
                <div class="stat-label">Submissions</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- Recent Users -->
        <div class="col-lg-6">
            <div class="section-card">
                <div class="section-header">
                    Recent Registered Users
                </div>
                <div>
                    <?php if (mysqli_num_rows($recent_users) > 0): ?>
                        <?php while ($u = mysqli_fetch_assoc($recent_users)): ?>
                            <div class="activity-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                    <span class="badge bg-light text-dark ms-2"><?php echo ucfirst($u['role']); ?></span>
                                </div>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="activity-item text-center text-muted py-4">No recent users</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Announcements -->
        <div class="col-lg-6">
            <div class="section-card">
                <div class="section-header">
                    Recent Announcements
                </div>
                <div>
                    <?php if (mysqli_num_rows($recent_ann) > 0): ?>
                        <?php while ($a = mysqli_fetch_assoc($recent_ann)): ?>
                            <div class="activity-item">
                                <strong><?php echo htmlspecialchars($a['title']); ?></strong><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($a['subject_name'] ?: 'General'); ?> • 
                                    <?php echo date('M d, Y', strtotime($a['created_at'])); ?>
                                </small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="activity-item text-center text-muted py-4">No recent announcements</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Admin Links -->
    <div class="row g-4">
        <div class="col-md-3 col-sm-6">
            <a href="manage_users.php" class="quick-link">
                <i class="fas fa-users"></i>
                <div class="mt-2 fw-medium">Manage Users</div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="admin_manage_subjects.php" class="quick-link">
                <i class="fas fa-book"></i>
                <div class="mt-2 fw-medium">Manage Courses</div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="manage_announcement.php" class="quick-link">
                <i class="fas fa-bullhorn"></i>
                <div class="mt-2 fw-medium">Announcements</div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="index.php" class="quick-link">
                <i class="fas fa-tachometer-alt"></i>
                <div class="mt-2 fw-medium">Main Dashboard</div>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>