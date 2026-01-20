<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Handle subject deletion
if (isset($_GET['delete_subject'])) {
    $sub_id = (int)$_GET['delete_subject'];
    $check = mysqli_query($connection, "SELECT teacher_id FROM subjects WHERE id = $sub_id");
    $sub = mysqli_fetch_assoc($check);

    if ($sub && ($role === 'admin' || $sub['teacher_id'] == $user_id)) {
        mysqli_query($connection, "DELETE FROM learning_materials WHERE subject_id = $sub_id");
        mysqli_query($connection, "DELETE FROM enrollments WHERE subject_id = $sub_id");
        mysqli_query($connection, "DELETE FROM assignments WHERE subject_id = $sub_id");
        mysqli_query($connection, "DELETE FROM attendance WHERE subject_id = $sub_id");
        mysqli_query($connection, "DELETE FROM subjects WHERE id = $sub_id");
        $delete_msg = "Subject deleted successfully!";
    }
}

// Fetch User Info
$user_query = mysqli_query($connection, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// ADMIN STATS
if ($role === 'admin') {
    // Enhanced admin statistics
    $users_total     = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
    $admins_count    = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users WHERE role='admin'"))['cnt'];
    $teachers_count  = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users WHERE role='teacher'"))['cnt'];
    $students_count  = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM users WHERE role='student'"))['cnt'];
    
    $count_subjects = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as total FROM subjects"))['total'];
    $enrollments_total  = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM enrollments"))['cnt'];
    $assignments_total  = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM assignments"))['cnt'];
    $submissions_total  = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as cnt FROM submissions"))['cnt'];
    $count_attendance = mysqli_fetch_assoc(mysqli_query($connection, "SELECT COUNT(*) as total FROM attendance"))['total'];
    
    $recent_activity = mysqli_query($connection, "
        SELECT u.name, s.name as subject, a.status, a.date
        FROM attendance a
        JOIN users u ON a.student_id = u.id
        JOIN subjects s ON a.subject_id = s.id
        ORDER BY a.id DESC LIMIT 5");
    
    // Recent users
    $recent_users = mysqli_query($connection, "SELECT name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
}

// Role-Based Subject Fetching
if ($role == 'teacher') {
    $subjects_query = mysqli_query($connection, "SELECT * FROM subjects WHERE teacher_id = $user_id");
} elseif ($role == 'student') {
    $subjects_query = mysqli_query($connection, "SELECT s.* FROM subjects s
                        JOIN enrollments e ON s.id = e.subject_id
                        WHERE e.student_id = $user_id");
} else {
    $subjects_query = mysqli_query($connection, "SELECT * FROM subjects");
}

$ann_query = mysqli_query($connection, "SELECT * FROM announcements ORDER BY id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LMS Pro | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; }
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; margin: 0; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        
        .sidebar { width: var(--sidebar-width); background: #ffffff; height: 100vh; position: fixed; border-right: 1px solid #e2e8f0; overflow-y: auto; }
        [data-bs-theme="dark"] .sidebar { background: #1e293b; border-right: 1px solid #334155; }
        .main-content { margin-left: var(--sidebar-width); padding: 2rem 2.5rem; }
        
        .nav-link { color: #475569; padding: 0.8rem 1.5rem; margin: 0.2rem 1rem; border-radius: 10px; display: flex; align-items: center; text-decoration: none; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        [data-bs-theme="dark"] .nav-link { color: #cbd5e1; }

        .stat-card { border: none; border-radius: 15px; color: white; transition: 0.3s; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); }
        .activity-item { border-left: 3px solid #3b82f6; padding-left: 15px; margin-bottom: 15px; position: relative; }
        
        .course-item { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; }
        [data-bs-theme="dark"] .course-item { background: #334155; border-color: #475569; }
        .profile-img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid #3b82f6; }
        
        .live-clock { font-size: 0.9rem; font-weight: 600; color: #3b82f6; }
        
        /* Admin quick links */
        .quick-link {
            display: block;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.2s;
            color: #495057;
            text-decoration: none;
        }
        [data-bs-theme="dark"] .quick-link {
            background: #334155;
            border-color: #475569;
            color: #e2e8f0;
        }
        .quick-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            color: #3b82f6;
            border-color: #3b82f6;
        }
        .quick-link i {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: #3b82f6;
        }
        
        /* User activity item */
        .user-activity-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        [data-bs-theme="dark"] .user-activity-item {
            border-bottom-color: #334155;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        
        /* Mini stat cards for admin */
        .mini-stat {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
        }
        [data-bs-theme="dark"] .mini-stat {
            background: #334155;
            border-color: #475569;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="p-4 text-center border-bottom border-secondary-subtle">
        <img src="uploads/<?php echo !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default.png'; ?>"
             class="profile-img mb-2" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>';">
        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($user['name']); ?></h6>
        <span class="badge bg-primary rounded-pill mt-1"><?php echo ucfirst($role); ?></span>
    </div>

    <nav class="mt-3">
        <a href="index.php" class="nav-link active"><i class="fas fa-home me-2"></i> Dashboard</a>
       
        <?php if($role == 'admin'): ?>
            <div class="px-4 mt-4 mb-2 small text-muted text-uppercase fw-bold">Management</div>
            <a href="manage_users.php" class="nav-link"><i class="fas fa-users me-2"></i> Users</a>
            <a href="admin_manage_subjects.php" class="nav-link"><i class="fas fa-book me-2"></i> Subjects</a>
            <a href="admin_attendance_report.php" class="nav-link"><i class="fas fa-clipboard-user me-2"></i> Attendance</a>
            <a href="admin_gradebook.php" class="nav-link"><i class="fas fa-chart-column me-2"></i> Gradebook</a>
            <a href="admin_global_announcements.php" class="nav-link"><i class="fas fa-bullhorn me-2"></i> Announcements</a>
        <?php elseif($role == 'teacher'): ?>
            <div class="px-4 mt-4 mb-2 small text-muted text-uppercase fw-bold">Teaching</div>
            <a href="add_subject.php" class="nav-link"><i class="fas fa-plus me-2"></i> New Course</a>
            <a href="upload_learning_material.php" class="nav-link"><i class="fas fa-file-upload me-2"></i> Upload Materials</a>
            <a href="view_submissions.php" class="nav-link"><i class="fas fa-tasks me-2"></i> Submissions</a>
            <a href="teacher_class_summary.php" class="nav-link"><i class="fas fa-chart-bar me-2"></i> Reports</a>
        <?php endif; ?>

        <a href="profile.php" class="nav-link mt-3"><i class="fas fa-user-cog me-2"></i> Profile</a>
       
        <div class="px-3 mt-4">
            <button id="theme-toggle" class="btn btn-sm btn-outline-secondary w-100">
                <i class="fas fa-moon me-1"></i> <span id="theme-text">Dark Mode</span>
            </button>
        </div>
        <a href="logout.php" class="nav-link text-danger mt-2"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a>
    </nav>
</div>

<div class="main-content">
    <header class="mb-4 d-flex justify-content-between align-items-start">
        <div>
            <h2 class="fw-bold">Welcome back, <?php echo explode(' ', $user['name'])[0]; ?>!</h2>
            <p class="text-muted">Here is what is happening in the LMS today.</p>
        </div>
        <div class="text-end d-none d-md-block">
            <div id="liveDate" class="text-muted small fw-bold"></div>
            <div id="liveClock" class="live-clock"></div>
        </div>
    </header>

    <?php if ($role === 'admin'): ?>
        <!-- Enhanced Admin Statistics -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card bg-primary p-4 shadow-sm">
                    <h6 class="text-white-50 small fw-bold">TOTAL USERS</h6>
                    <h2 class="fw-bold mb-0"><?php echo $users_total; ?></h2>
                    <i class="fas fa-users fa-3x position-absolute end-0 bottom-0 opacity-25 m-3"></i>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card bg-info p-4 shadow-sm">
                    <h6 class="text-white-50 small fw-bold">STUDENTS</h6>
                    <h2 class="fw-bold mb-0"><?php echo $students_count; ?></h2>
                    <i class="fas fa-user-graduate fa-3x position-absolute end-0 bottom-0 opacity-25 m-3"></i>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card bg-warning p-4 shadow-sm">
                    <h6 class="text-white-50 small fw-bold">TEACHERS</h6>
                    <h2 class="fw-bold mb-0"><?php echo $teachers_count; ?></h2>
                    <i class="fas fa-chalkboard-teacher fa-3x position-absolute end-0 bottom-0 opacity-25 m-3"></i>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card stat-card bg-danger p-4 shadow-sm">
                    <h6 class="text-white-50 small fw-bold">ADMINS</h6>
                    <h2 class="fw-bold mb-0"><?php echo $admins_count; ?></h2>
                    <i class="fas fa-user-shield fa-3x position-absolute end-0 bottom-0 opacity-25 m-3"></i>
                </div>
            </div>
        </div>

        <!-- Secondary Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="mini-stat">
                    <div class="text-success fw-bold h4 mb-1"><?php echo $count_subjects; ?></div>
                    <small class="text-muted">Total Courses</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="mini-stat">
                    <div class="text-primary fw-bold h4 mb-1"><?php echo $enrollments_total; ?></div>
                    <small class="text-muted">Enrollments</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="mini-stat">
                    <div class="text-info fw-bold h4 mb-1"><?php echo $assignments_total; ?></div>
                    <small class="text-muted">Assignments</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="mini-stat">
                    <div class="text-warning fw-bold h4 mb-1"><?php echo $submissions_total; ?></div>
                    <small class="text-muted">Submissions</small>
                </div>
            </div>
        </div>

        <!-- Quick Admin Links -->
        <div class="row g-3 mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6">
                <a href="manage_users.php" class="quick-link">
                    <i class="fas fa-users"></i>
                    <div class="mt-2 fw-medium small">Manage Users</div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <a href="admin_manage_subjects.php" class="quick-link">
                    <i class="fas fa-book"></i>
                    <div class="mt-2 fw-medium small">Manage Courses</div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <a href="admin_gradebook.php" class="quick-link">
                    <i class="fas fa-chart-column"></i>
                    <div class="mt-2 fw-medium small">Gradebook</div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <a href="admin_attendance_report.php" class="quick-link">
                    <i class="fas fa-clipboard-user"></i>
                    <div class="mt-2 fw-medium small">Attendance</div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <a href="admin_global_announcements.php" class="quick-link">
                    <i class="fas fa-bullhorn"></i>
                    <div class="mt-2 fw-medium small">Announcements</div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <a href="admin_dashboard.php" class="quick-link">
                    <i class="fas fa-chart-line"></i>
                    <div class="mt-2 fw-medium small">Full Report</div>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <?php if($role === 'admin'): ?>
                <!-- Recent Attendance Activity -->
                <div class="card p-4 border-0 shadow-sm mb-4">
                    <h5 class="fw-bold mb-4"><i class="fas fa-clock me-2 text-primary"></i>Recent Attendance Activity</h5>
                    <?php 
                    if (mysqli_num_rows($recent_activity) > 0):
                        while($act = mysqli_fetch_assoc($recent_activity)): 
                    ?>
                        <div class="activity-item">
                            <span class="fw-bold text-primary"><?php echo htmlspecialchars($act['name']); ?></span>
                            was marked <span class="badge bg-light text-dark"><?php echo ucfirst($act['status']); ?></span>
                            in <strong><?php echo htmlspecialchars($act['subject']); ?></strong>
                            <div class="text-muted small"><?php echo date('M d, g:i A', strtotime($act['date'])); ?></div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <p class="text-muted text-center py-3">No recent attendance activity.</p>
                    <?php endif; ?>
                </div>

                <!-- Recent Users -->
                <div class="card p-4 border-0 shadow-sm mb-4">
                    <h5 class="fw-bold mb-4"><i class="fas fa-user-plus me-2 text-success"></i>Recently Joined Users</h5>
                    <?php 
                    if (mysqli_num_rows($recent_users) > 0):
                        while($u = mysqli_fetch_assoc($recent_users)): 
                    ?>
                        <div class="user-activity-item">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                            </div>
                            <div class="flex-grow-1">
                                <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                <small class="text-muted d-block"><?php echo ucfirst($u['role']); ?> • <?php echo date('M d, Y', strtotime($u['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <p class="text-muted text-center py-3">No recent users.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="card p-4 border-0 shadow-sm">
                <h5 class="fw-bold mb-4"><i class="fas fa-book-open me-2 text-info"></i>Your Courses</h5>
                <div class="row g-3">
                    <?php 
                    if (mysqli_num_rows($subjects_query) > 0):
                        while($sub = mysqli_fetch_assoc($subjects_query)): 
                    ?>
                        <div class="col-md-6">
                            <div class="course-item">
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($sub['name']); ?></h6>
                                <p class="text-muted small mb-0">Course ID: #<?php echo $sub['id']; ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <a href="<?php echo ($role == 'student' ? 'view_subject.php' : 'manage_subject.php'); ?>?id=<?php echo $sub['id']; ?>"
                                       class="btn btn-sm btn-primary px-3 rounded-pill">
                                        <i class="fas fa-arrow-right me-1"></i> Open
                                    </a>
                                    <?php if($role == 'admin' || $role == 'teacher'): ?>
                                        <a href="?delete_subject=<?php echo $sub['id']; ?>" class="text-danger small" onclick="return confirm('Delete this course and all its data?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div class="col-12">
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No courses available yet.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-4 border-0 shadow-sm">
                <h5 class="fw-bold mb-4"><i class="fas fa-bullhorn me-2 text-warning"></i>Announcements</h5>
                <?php 
                if (mysqli_num_rows($ann_query) > 0):
                    while($ann = mysqli_fetch_assoc($ann_query)): 
                ?>
                    <div class="mb-3 pb-3 border-bottom border-light">
                        <strong class="d-block text-primary small"><?php echo htmlspecialchars($ann['title']); ?></strong>
                        <p class="small text-muted mb-1"><?php echo htmlspecialchars(substr($ann['message'], 0, 80)); ?>...</p>
                        <small class="text-muted" style="font-size: 10px;">
                            <i class="fas fa-calendar me-1"></i><?php echo date('M d', strtotime($ann['created_at'])); ?>
                        </small>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                        <p class="text-muted small">No announcements available.</p>
                    </div>
                <?php endif; ?>
                <?php if($role === 'admin'): ?>
                    <a href="admin_global_announcements.php" class="btn btn-outline-primary btn-sm w-100 mt-2">
                        <i class="fas fa-plus me-1"></i> Create Announcement
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Theme Toggle Script
    const toggleBtn = document.getElementById('theme-toggle');
    const themeText = document.getElementById('theme-text');
    const html = document.documentElement;
    
    toggleBtn.addEventListener('click', () => {
        const currentTheme = html.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        themeText.textContent = newTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
    });
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme');
    if(savedTheme) {
        html.setAttribute('data-bs-theme', savedTheme);
        themeText.textContent = savedTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
    }

    // Running Clock and Date Script
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        
        document.getElementById('liveDate').textContent = now.toLocaleDateString('en-US', options);
        document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-US', { hour12: true });
    }
    setInterval(updateClock, 1000);
    updateClock(); // Initial call
</script>
</body>
</html>