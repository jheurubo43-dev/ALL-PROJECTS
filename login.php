<?php
session_start();
require_once('db.php');

if (isset($_POST['login'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);

    // Check database using the 'name' column
    $query = "SELECT * FROM users WHERE name = '$name' AND password = '$password' LIMIT 1";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Access Denied. Please verify your Name and Password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LMS Project | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; height: 100vh; display: flex; align-items: center; font-family: 'Inter', sans-serif; }
        .login-container { max-width: 900px; margin: auto; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .brand-side { background: #0d47a1; color: white; padding: 60px; display: flex; flex-direction: column; justify-content: center; text-align: center; }
        .form-side { padding: 60px; }
        .btn-primary { background: #0d47a1; border: none; padding: 14px; border-radius: 10px; font-weight: 600; transition: 0.3s; }
        .btn-primary:hover { background: #1565c0; transform: translateY(-2px); }
        .form-control { padding: 14px; border-radius: 10px; background: #f8f9fa; border: 1px solid #eee; }
    </style>
</head>
<body>
<div class="container login-container shadow">
    <div class="row">
        <div class="col-md-5 brand-side d-none d-md-flex">
            <i class="fas fa-graduation-cap fa-4x mb-4"></i>
            <h1 class="fw-bold">LMS Project</h1>
            <p class="opacity-75">Professional Learning Gateway</p>
        </div>
        <div class="col-md-7 form-side">
            <h2 class="fw-bold mb-4 text-dark">Welcome Back</h2>
            <?php if(isset($error)) echo "<div class='alert alert-danger py-2 small'>$error</div>"; ?>
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
                <p class="text-muted small">New user? <a href="register.php" class="text-primary text-decoration-none fw-bold">Register Account</a></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>