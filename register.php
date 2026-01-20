<?php
require_once('db.php');

$message = '';

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($password)) {
        $message = '<div class="alert alert-danger">Name and password are required.</div>';
    } else {
        // Check if name already exists
        $check = mysqli_prepare($connection, "SELECT id FROM users WHERE name = ?");
        mysqli_stmt_bind_param($check, "s", $name);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        
        if (mysqli_stmt_num_rows($check) > 0) {
            $message = '<div class="alert alert-danger">Name already taken.</div>';
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = mysqli_prepare($connection, "INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $name, $name, $hashed_password, $role);
            
            if (mysqli_stmt_execute($stmt)) {
                header("Location: login.php?msg=Registration successful! Please log in.");
                exit();
            } else {
                $message = '<div class="alert alert-danger">Error: " . mysqli_error($connection) . "</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LMS Project | Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f0f2f5; height: 100vh; display: flex; align-items: center; font-family: 'Inter', sans-serif; }
        .reg-card { max-width: 450px; margin: auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .btn-primary { background: #0d47a1; border: none; padding: 12px; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <div class="reg-card">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-dark">Create Account</h3>
        </div>
        <?php echo $message; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">FULL NAME</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">USER ROLE</label>
                <select name="role" class="form-select">
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" name="register" class="btn btn-primary w-100 mb-3">Create Account</button>
        </form>
        <div class="text-center">
            <a href="login.php" class="text-muted small text-decoration-none">Back to Login</a>
        </div>
    </div>
</div>
</body>
</html>l