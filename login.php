<?php
session_start();
require_once('db.php');

$message = '';

if (isset($_POST['login'])) {
    $name = trim($_POST['name']);
    $password = $_POST['password'];

    if (empty($name) || empty($password)) {
        $message = '<div class="alert alert-danger">Please enter both name and password.</div>';
    } else {
        $stmt = mysqli_prepare($connection, "SELECT id, name, password, role FROM users WHERE name = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $user_id, $db_name, $hashed_password, $role);
        
        if (mysqli_stmt_fetch($stmt)) {
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = $role;
                header("Location: index.php");
                exit();
            } else {
                $message = '<div class="alert alert-danger">Invalid password.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Name not found.</div>';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LMS Project | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f0f2f5; height: 100vh; display: flex; align-items: center; font-family: 'Inter', sans-serif; }
        .login-container { max-width: 900px; margin: auto; }
        /* (keep your existing styles) */
    </style>
</head>
<body>
<div class="container login-container">
    <!-- (keep your existing HTML structure) -->
    <div class="row shadow-lg overflow-hidden" style="border-radius: 20px;">
        <!-- Left side with image/text -->
        <div class="col-md-5 bg-primary text-white p-5 d-flex flex-column justify-content-center">
            <h1 class="fw-bold">LMS Project</h1>
            <p class="opacity-75">Professional Learning Gateway</p>
        </div>
        <div class="col-md-7 form-side bg-white p-5">
            <h2 class="fw-bold mb-4 text-dark">Welcome Back</h2>
            <?php echo $message; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">FULL NAME</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter your name" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">PASSWORD</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100 shadow-sm mb-3">Sign In</button>
            </form>
            <div class="text-center mt-4">
                <p class="text-muted small">New user? <a href="register.php" class="text-primary fw-bold">Register Account</a></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>