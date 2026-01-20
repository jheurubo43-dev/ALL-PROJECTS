<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$current_admin_id = $_SESSION['user_id'];
$message = '';

// Handle add new user
if (isset($_POST['add_user'])) {
    $new_name     = mysqli_real_escape_string($connection, trim($_POST['name']));
    $new_username = mysqli_real_escape_string($connection, trim($_POST['username']));
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $new_role     = mysqli_real_escape_string($connection, $_POST['role']);

    $check_user = mysqli_query($connection, "SELECT id FROM users WHERE username = '$new_username'");
    
    if (mysqli_num_rows($check_user) > 0) {
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Username already exists.</div>';
    } else {
        $insert_query = "INSERT INTO users (name, username, password, role, created_at) 
                         VALUES ('$new_name', '$new_username', '$new_password', '$new_role', NOW())";
        
        if (mysqli_query($connection, $insert_query)) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>User <b>' . htmlspecialchars($new_name) . '</b> created successfully!</div>';
        }
    }
}

// Handle role change
if (isset($_POST['change_role'])) {
    $user_id_to_change = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];

    if ($user_id_to_change === $current_admin_id) {
        $message = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Cannot change your own role.</div>';
    } else {
        mysqli_query($connection, "UPDATE users SET role = '$new_role' WHERE id = $user_id_to_change");
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Role updated successfully.</div>';
    }
}

// Handle password reset
if (isset($_GET['reset_password'])) {
    $reset_id = (int)$_GET['reset_password'];
    if ($reset_id !== $current_admin_id) {
        $default_pass = password_hash('123456', PASSWORD_DEFAULT);
        mysqli_query($connection, "UPDATE users SET password = '$default_pass' WHERE id = $reset_id");
        $message = '<div class="alert alert-info"><i class="fas fa-key me-2"></i>Password reset to: 123456</div>';
    }
}

// Handle user deletion
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    if ($del_id !== $current_admin_id) {
        if (mysqli_query($connection, "DELETE FROM users WHERE id = $del_id")) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>User deleted.</div>';
        }
    }
}

// Search filter
$search = $_GET['search'] ?? '';
$where = $search !== '' ? "WHERE name LIKE '%$search%' OR username LIKE '%$search%'" : "";
$users_query = mysqli_query($connection, "SELECT * FROM users $where ORDER BY role ASC, name ASC");
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 2rem; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        .page-header {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
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
        [data-bs-theme="dark"] .table { color: #e2e8f0; }
    </style>
</head>
<body>

<div class="container" style="max-width: 1400px;">
    <div class="page-header shadow">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-users-cog me-2"></i>User Management
                </h2>
                <p class="mb-0 opacity-75">Create, edit, and manage system accounts</p>
            </div>
            <div>
                <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus me-1"></i>Add User
                </button>
                <a href="index.php" class="btn btn-outline-light rounded-pill">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <?php echo $message; ?>

    <!-- Search Bar -->
    <div class="card mb-4 p-4">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           class="form-control border-start-0" 
                           placeholder="Search by name or username..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-lg w-100">Search</button>
            </div>
        </form>
        <?php if ($search): ?>
            <div class="mt-3">
                <a href="manage_users.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Clear Search
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="fw-bold mb-0">All Users</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th class="text-center">Change Role</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = mysqli_fetch_assoc($users_query)): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="fw-semibold"><?php echo htmlspecialchars($u['name']); ?></span>
                            </div>
                        </td>
                        <td class="text-muted">@<?php echo htmlspecialchars($u['username']); ?></td>
                        <td>
                            <?php 
                                $badge = $u['role'] == 'admin' ? 'bg-danger' : 
                                        ($u['role'] == 'teacher' ? 'bg-primary' : 'bg-info');
                            ?>
                            <span class="badge <?php echo $badge; ?> px-3">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td class="text-muted small">
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td class="text-center">
                            <?php if ($u['id'] !== $current_admin_id): ?>
                            <form method="POST" class="d-flex justify-content-center gap-2">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <select name="new_role" class="form-select form-select-sm" style="width: 120px;">
                                    <option value="student" <?php if($u['role']=='student') echo 'selected'; ?>>Student</option>
                                    <option value="teacher" <?php if($u['role']=='teacher') echo 'selected'; ?>>Teacher</option>
                                    <option value="admin" <?php if($u['role']=='admin') echo 'selected'; ?>>Admin</option>
                                </select>
                                <button type="submit" name="change_role" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small fst-italic">Current User</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php if ($u['id'] !== $current_admin_id): ?>
                                <a href="?reset_password=<?php echo $u['id']; ?>" 
                                   class="btn btn-sm btn-outline-warning me-2" 
                                   onclick="return confirm('Reset password to 123456?');">
                                    <i class="fas fa-key"></i>
                                </a>
                                <a href="?delete_id=<?php echo $u['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Delete this user permanently?');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 bg-primary text-white" style="border-radius: 20px 20px 0 0;">
                <h5 class="fw-bold">
                    <i class="fas fa-user-plus me-2"></i>Create New Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="name" class="form-control form-control-lg" placeholder="e.g., John Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" class="form-control form-control-lg" placeholder="e.g., johndoe123" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Temporary Password</label>
                        <input type="password" name="password" class="form-control form-control-lg" value="123456" required>
                        <small class="text-muted">User can change this after first login</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Role</label>
                        <select name="role" class="form-select form-select-lg" required>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="add_user" class="btn btn-primary btn-lg fw-bold">
                            <i class="fas fa-user-check me-2"></i>Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const savedTheme = localStorage.getItem('theme');
    if(savedTheme) document.documentElement.setAttribute('data-bs-theme', savedTheme);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>