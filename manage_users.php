<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = '';

// Handle role change
if (isset($_POST['change_role'])) {
    $user_id_to_change = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];

    if ($user_id_to_change === $_SESSION['user_id']) {
        $message = '<div class="alert alert-warning">You cannot change your own role.</div>';
    } elseif (!in_array($new_role, ['admin', 'teacher', 'student'])) {
        $message = '<div class="alert alert-danger">Invalid role.</div>';
    } else {
        mysqli_query($connection, "UPDATE users SET role = '$new_role' WHERE id = $user_id_to_change");
        $message = '<div class="alert alert-success">Role updated.</div>';
    }
}

// Handle password reset
if (isset($_GET['reset_password'])) {
    $reset_id = (int)$_GET['reset_password'];

    if ($reset_id === $_SESSION['user_id']) {
        $message = '<div class="alert alert-warning">You cannot reset your own password here.</div>';
    } else {
        // Set password to a simple default (you can change this value anytime)
        $default_password = '123456'; // ← change this to whatever you want as default
        mysqli_query($connection, "UPDATE users SET password = '$default_password' WHERE id = $reset_id");
        $message = '<div class="alert alert-success">Password reset to <strong>' . $default_password . '</strong> for that user.</div>';
    }
}

// Handle delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    if ($delete_id !== $_SESSION['user_id']) {
        mysqli_query($connection, "DELETE FROM users WHERE id = $delete_id");
        $message = '<div class="alert alert-success">User deleted.</div>';
    } else {
        $message = '<div class="alert alert-warning">Cannot delete your own account.</div>';
    }
}

// Fetch all users
$users_query = mysqli_query($connection, "SELECT * FROM users ORDER BY role DESC, name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1100px; }
        .card { border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .table th { background-color: #f1f3f5; }
        .badge-role { min-width: 90px; text-align: center; }
        .btn-sm { font-size: 0.85rem; }
        .text-reset-link { color: #0d6efd; text-decoration: none; }
        .text-reset-link:hover { text-decoration: underline; }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-normal">Manage Users</h2>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Change Role</th>
                            <th>Reset Password</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = mysqli_fetch_assoc($users_query)): ?>
                        <tr>
                            <td class="text-muted"><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['name']); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td>
                                <?php
                                $badge_class = match($u['role']) {
                                    'admin'   => 'bg-danger',
                                    'teacher' => 'bg-warning text-dark',
                                    'student' => 'bg-success',
                                    default   => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?php echo $badge_class; ?> badge-role">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" class="d-flex gap-2 align-items-center">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="new_role" class="form-select form-select-sm">
                                            <option value="student"  <?php echo $u['role'] === 'student'  ? 'selected' : ''; ?>>Student</option>
                                            <option value="teacher"  <?php echo $u['role'] === 'teacher'  ? 'selected' : ''; ?>>Teacher</option>
                                            <option value="admin"    <?php echo $u['role'] === 'admin'    ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <button type="submit" name="change_role" class="btn btn-sm btn-outline-primary">
                                            Update
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <small class="text-muted">(Your account)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <a href="?reset_password=<?php echo $u['id']; ?>" 
                                       class="text-reset-link small"
                                       onclick="return confirm('Reset password for <?php echo htmlspecialchars(addslashes($u['name'])); ?> to 123456?');">
                                        <i class="fas fa-key me-1"></i> Reset
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <a href="?delete_id=<?php echo $u['id']; ?>" 
                                       class="text-danger text-decoration-none small"
                                       onclick="return confirm('Delete user <?php echo htmlspecialchars(addslashes($u['name'])); ?>?');">
                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($users_query) === 0): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>