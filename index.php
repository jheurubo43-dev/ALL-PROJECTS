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
        // DELETE IN THE CORRECT ORDER - child tables first, then parent
        mysqli_query($connection, "DELETE FROM learning_materials WHERE subject_id = $sub_id");
        mysqli_query($connection, "DELETE FROM enrollments WHERE subject_id = $sub_id");
        mysqli_query($connection, "DELETE FROM assignments WHERE subject_id = $sub_id");
        mysqli_query($connection, "DELETE FROM attendance WHERE subject_id = $sub_id");
        // Delete the parent table LAST
        mysqli_query($connection, "DELETE FROM subjects WHERE id = $sub_id");
        
        $delete_msg = "Subject deleted successfully!";
    } else {
        $delete_msg = "You are not allowed to delete this subject.";
    }
}

// Fetch User Info
$user_query = mysqli_query($connection, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);


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

$ann_query = mysqli_query($connection, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
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
        :root {
            --sidebar-width: 260px;
        }
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            transition: background-color 0.3s, color 0.3s;
        }
        [data-bs-theme="dark"] body {
            background: #0f172a;
            color: #e2e8f0;
        }
        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            height: 100vh;
            position: fixed;
            border-right: 1px solid #e2e8f0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.04);
            overflow-y: auto;
            transition: background-color 0.3s, border-color 0.3s;
        }
        [data-bs-theme="dark"] .sidebar {
            background: #1e293b;
            border-right: 1px solid #334155;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 3rem 2.5rem;
            transition: background-color 0.3s;
        }
        h3 {
           margin: auto;
           font-size: 18px;
        }
        .nav-link {
            color: #475569;
            padding: 0.9rem 1.5rem;
            margin: 0.25rem 1rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.2s;
        }
        i {
            margin-right: 10px;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        .nav-cs {
            font-size: 14px;
        }
        [data-bs-theme="dark"] .nav-link {
            color: #cbd5e1;
        }
        [data-bs-theme="dark"] .nav-link:hover,
        [data-bs-theme="dark"] .nav-link.active {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            transition: border-color 0.3s;
        }
        [data-bs-theme="dark"] .sidebar-header {
            border-bottom: 1px solid #334155;
        }
        .profile-initial {
            width: 64px;
            height: 64px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.6rem;
            margin: 0 auto 1rem;
        }
        .section-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            padding: 0 1.5rem 0.6rem;
            letter-spacing: 0.5px;
        }
        [data-bs-theme="dark"] .section-label {
            color: #94a3b8;
        }
        .card {
            background: white;
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        [data-bs-theme="dark"] .card {
            background: #1e293b;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
        }
        .course-item {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            transition: all 0.2s;
        }
        .course-item:hover {
            border-color: #3b82f6;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.12);
            transform: translateY(-2px);
        }
        [data-bs-theme="dark"] .course-item {
            background: #334155;
            border-color: #475569;
        }
        [data-bs-theme="dark"] .course-item:hover {
            border-color: #60a5fa;
            box-shadow: 0 6px 20px rgba(96, 165, 250, 0.2);
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="profile-initial mx-auto">
            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
        </div>
        <h6 class="fw-medium mb-0"><?php echo htmlspecialchars($user['name']); ?></h6>
        <span class="badge bg-light text-dark border badge-role mt-1">
           <h3> <?php echo ucfirst($role); ?> </h3> 
        </span>
    </div>

    <nav class="mt-3 nav-cs">
        <a href="index.php" class="nav-link active">
            <i class="fas fa-home"></i> Dashboard
        </a>

        <?php if($role == 'teacher'): ?>
            <div class="teacher-section ">
                <div class="section-label">Teaching Tools</div>
                <a href="add_subject.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i> Create Course
                </a>
                <a href="create_assignment_list.php" class="nav-link">
                    <i class="fas fa-file-alt"></i> Post Assignment
                </a>
                <a href="post_subject_announcement.php" class="nav-link">
                    <i class="fas fa-bullhorn"></i> Post Announcement
                </a>
                <a href="upload_learning_material.php" class="nav-link">
                    <i class="fas fa-upload"></i> Upload Material
                </a>
                <a href="my_announcements.php" class="nav-link">
                    <i class="fas fa-list"></i> My Announcements
                </a>
                <a href="teacher_class_summary.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i> Class Summary
                </a>
            </div>
        <?php elseif($role == 'student'): ?>
            <div class="student-section">
                <div class="section-label">My Learning</div>
                <a href="enroll.php" class="nav-link">
                    <i class="fas fa-book-open"></i> Enroll in Subjects
                </a>
                <a href="my_courses.php" class="nav-link">
                    <i class="fas fa-book"></i> My Courses
                </a>
                <a href="my_performance.php" class="nav-link">
                    <i class="fas fa-chart-line"></i> Grades & Performance
                </a>
                <a href="my_submissions.php" class="nav-link">
                    <i class="fas fa-file-upload"></i> My Submissions
                </a>
            </div>
        <?php endif; ?>

        <a href="profile.php" class="nav-link">
            <i class="fas fa-user-edit"></i> Edit Profile
        </a>

        <?php if($role === 'admin'): ?>
            <div class="admin-section">
                <div class="section-label">Admin Tools</div>
                <a href="admin_dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Admin Overview
                </a>
                <a href="manage_users.php" class="nav-link">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="admin_manage_subjects.php" class="nav-link">
                    <i class="fas fa-book"></i> Manage Courses
                </a>
                <a href="admin_global_announcements.php" class="nav-link">
                    <i class="fas fa-bullhorn"></i> Global Announcements
                </a>
            </div>
        <?php endif; ?>

        <!-- Dark Mode Toggle -->
        <div class="mt-4 px-3">
            <button id="theme-toggle" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center">
                <i class="fas fa-moon me-2"></i> Dark Mode
            </button>
        </div>

        <a href="logout.php" class="nav-link text-danger mt-3">
            <i class="fas fa-sign-out-alt"></i> Sign Out
        </a>
    </nav>
</div>

<div class="main-content">
    <h1 class="fw-normal mb-4">Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>

    <?php if(isset($delete_msg)): ?>
        <div class="alert alert-<?php echo strpos($delete_msg, 'success') !== false ? 'success' : 'danger'; ?> alert-dismissible fade show">
            <?php echo $delete_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Action completed successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Courses and Announcements -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">Your Courses</h5>
                    <?php if(mysqli_num_rows($subjects_query) > 0): ?>
                        <div class="row g-4">
                            <?php while($sub = mysqli_fetch_assoc($subjects_query)): 
                                $count_q = mysqli_query($connection, "SELECT COUNT(*) as cnt FROM assignments WHERE subject_id = " . (int)$sub['id']);
                                $count = mysqli_fetch_assoc($count_q)['cnt'];
                            ?>
                                <div class="col-md-6">
                                    <div class="course-item">
                                        <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($sub['name']); ?></h6>
                                        <small class="text-muted d-block mb-2">ID: #<?php echo str_pad($sub['id'], 4, '0', STR_PAD_LEFT); ?></small>
                                        <?php if($count > 0): ?>
                                            <span class="badge bg-warning text-dark mb-3 d-inline-block"><?php echo $count; ?> assignments</span>
                                        <?php endif; ?>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <?php if($role == 'teacher'): ?>
                                                <a href="record_attendance.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-outline-primary">Attendance</a>
                                                <a href="manage_subject.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-outline-dark">Manage</a>
                                            <?php else: ?>
                                                <a href="view_subject.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-primary">Open Course</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($role === 'admin' || ($role === 'teacher' && $sub['teacher_id'] == $user_id)): ?>
                                            <div class="mt-3 text-end">
                                                <a href="?delete_subject=<?php echo $sub['id']; ?>" 
                                                   class="text-danger small text-decoration-none"
                                                   onclick="return confirm('Delete this course and all related data?');">
                                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-book-open fa-2x mb-3 d-block opacity-50"></i>
                            No courses yet.
                            <?php if($role == 'student'): ?>
                                <div class="mt-3">
                                    <a href="enroll.php" class="btn btn-primary">Browse Subjects</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">Announcements</h5>
                    <?php if(mysqli_num_rows($ann_query) > 0): ?>
                        <?php while($ann = mysqli_fetch_assoc($ann_query)): ?>
                            <div class="announcement-item">
                                <strong class="d-block mb-1"><?php echo htmlspecialchars($ann['title']); ?></strong>
                                <small class="text-muted d-block mb-1">
                                    <?php echo htmlspecialchars($ann['subject_name']); ?> • 
                                    <?php echo date('M d, Y', strtotime($ann['created_at'])); ?>
                                </small>
                                <p class="text-muted small mb-0">
                                    <?php echo nl2br(htmlspecialchars(substr($ann['message'], 0, 120))) . (strlen($ann['message']) > 120 ? '...' : ''); ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-bullhorn fa-2x mb-3 d-block opacity-50"></i>
                            No announcements yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Theme Toggle Script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const currentTheme = localStorage.getItem('theme') || 'light';

        // Apply saved theme
        html.setAttribute('data-bs-theme', currentTheme);
        toggleBtn.innerHTML = currentTheme === 'dark' 
            ? '<i class="fas fa-sun me-2"></i> Light Mode'
            : '<i class="fas fa-moon me-2"></i> Dark Mode';

        toggleBtn.addEventListener('click', () => {
            const newTheme = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            toggleBtn.innerHTML = newTheme === 'dark'
                ? '<i class="fas fa-sun me-2"></i> Light Mode'
                : '<i class="fas fa-moon me-2"></i> Dark Mode';
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>