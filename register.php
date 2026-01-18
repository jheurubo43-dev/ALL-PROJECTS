<?php
require_once('db.php');

if (isset($_POST['register'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);
    $role = $_POST['role'];

    // Maps Name to both name and username columns to match your structure
    $sql = "INSERT INTO users (name, username, password, role) VALUES ('$name', '$name', '$password', '$role')";
    
    if (mysqli_query($connection, $sql)) {
        header("Location: login.php?msg=Registration successful!");
        exit(); 
    } else {
        $error = "Error: " . mysqli_error($connection);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LMS Project | Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; height: 100vh; display: flex; align-items: center; font-family: 'Inter', sans-serif; }
        .reg-card { max-width: 450px; margin: auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .btn-primary { background: #0d47a1; border: none; padding: 12px; border-radius: 10px; font-weight: 600; }
        .form-control, .form-select { padding: 12px; border-radius: 10px; background: #f8f9fa; }
    </style>
</head>
<body>
<div class="container">
    <div class="reg-card">
        <h3 class="fw-bold text-center mb-4 text-dark">Create Account</h3>
        <?php if(isset($error)) echo "<div class='alert alert-danger py-2 small'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">FULL NAME</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">USER ROLE</label>
                <select name="role" class="form-select">
                    <option value="student">student</option>
                    <option value="teacher">teacher</option>
                    <option value="admin">admin</option>
                </select>
            </div>
            <button type="submit" name="register" class="btn btn-primary w-100 mb-3">Create Professional Account</button>
        </form>
        <div class="text-center"><a href="login.php" class="text-muted small text-decoration-none">Back to Login</a></div>
    </div>
</div>
</body>
</html>