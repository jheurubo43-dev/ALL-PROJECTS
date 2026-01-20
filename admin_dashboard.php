<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current user data for profile pic
$user_query = mysqli_query($connection, "SELECT name, profile_pic FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

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
    <title>Admin | Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .container { max-width: 1200px; }
        .profile-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #e9ecef;
        }
        .stat-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            padding: 1.5rem;
            text-align: center;
        }
        .quick-link {
            display: block;
            background: white;
            border-radius: 14px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            transition: all 0.2s;
            color: #495057;
            text-decoration: none;
        }
        .quick-link:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            color: #3b82f6;
        }
        .quick-link i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #3b82f6;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <!-- Profile Header -->
    <div class="d-flex align-items-center mb-5 bg-white rounded-4 shadow-sm p-4">
        <img src="uploads/<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default.png'; ?>" 
             class="profile-img me-4"
             alt="Admin Profile"
             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&size=80&background=3b82f6&color=fff';">
        <div>
            <h3 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($user['name']); ?></h3>
            <span class="badge bg-primary text-white px-3 py-2">Administrator</span>
        </div>
        <div class="ms-auto">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-tachometer-alt me-2"></i> Main Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <h4 class="fw-bold text-primary"><?php echo $users_total; ?></h4>
                <p class="text-muted mb-0">Total Users</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <h4 class="fw-bold text-info"><?php echo $students_count; ?></h4>
                <p class="text-muted mb-0">Students</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <h4 class="fw-bold text-warning"><?php echo $teachers_count; ?></h4>
                <p class="text-muted mb-0">Teachers</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <h4 class="fw-bold text-danger"><?php echo $admins_count; ?></h4>
                <p class="text-muted mb-0">Admins</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <h4 class="fw-bold text-success"><?php echo $subjects_total; ?></h4>
                <p class="text-muted mb-0">Courses</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <h4 class="fw-bold text-primary"><?php echo $enrollments_total; ?></h4>
                <p class="text-muted mb-0">Enrollments</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <h4 class="fw-bold text-info"><?php echo $assignments_total; ?></h4>
                <p class="text-muted mb-0">Assignments</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <h4 class="fw-bold text-warning"><?php echo $submissions_total; ?></h4>
                <p class="text-muted mb-0">Submissions</p>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Recent Users</div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($recent_users) > 0): ?>
                        <?php while($u = mysqli_fetch_assoc($recent_users)): ?>
                            <div class="d-flex align-items-center py-2 border-bottom">
                                <div class="me-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                    <small class="text-muted d-block"><?php echo ucfirst($u['role']); ?> • <?php echo date('M d, Y', strtotime($u['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">No recent users</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Recent Announcements</div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($recent_ann) > 0): ?>
                        <?php while($a = mysqli_fetch_assoc($recent_ann)): ?>
                            <div class="py-2 border-bottom">
                                <strong><?php echo htmlspecialchars($a['title']); ?></strong>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($a['subject_name'] ?: 'Global'); ?> • <?php echo date('M d, Y', strtotime($a['created_at'])); ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">No recent announcements</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Admin Links -->
    <div class="row g-4 mt-5">
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
            <a href="admin_gradebook.php" class="quick-link">
                <i class="fas fa-chart-column"></i>
                <div class="mt-2 fw-medium">Global Gradebook</div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="admin_attendance_report.php" class="quick-link">
                <i class="fas fa-clipboard-user"></i>
                <div class="mt-2 fw-medium">Attendance Report</div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="admin_global_announcements.php" class="quick-link">
                <i class="fas fa-bullhorn"></i>
                <div class="mt-2 fw-medium">Global Announcements</div>
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