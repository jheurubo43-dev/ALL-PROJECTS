<?php
session_start();
require_once('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

$en_check = mysqli_query($connection, "SELECT * FROM enrollments WHERE student_id = $user_id AND subject_id = $subject_id");
if (!$en_check || mysqli_num_rows($en_check) == 0) {
    header("Location: index.php?error=not_enrolled");
    exit();
}

$subject_query = mysqli_query($connection, "
    SELECT s.*, u.name as teacher_name 
    FROM subjects s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    WHERE s.id = $subject_id
");
$subject = mysqli_fetch_assoc($subject_query);
if (!$subject) die("Subject not found");

$announcements = mysqli_query($connection, "
    SELECT * FROM announcements 
    WHERE subject_name = '{$subject['name']}' OR subject_name = 'Global'
    ORDER BY created_at DESC LIMIT 5
");

$materials_query = mysqli_query($connection, "
    SELECT * FROM learning_materials 
    WHERE subject_id = $subject_id 
    ORDER BY uploaded_at DESC
");

$assign_query = mysqli_query($connection, "
    SELECT * FROM assignments 
    WHERE subject_id = $subject_id 
    ORDER BY due_date ASC
");
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Classroom | <?php echo htmlspecialchars($subject['name']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body{
    min-height:100vh;
    background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    padding:2rem;
}
[data-bs-theme="dark"] body{
    background: linear-gradient(135deg,#1e3a8a 0%,#581c87 100%);
}
.main-card{
    background:#fff;
    border-radius:20px;
    padding:2rem;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
    margin-bottom:2rem;
}
[data-bs-theme="dark"] .main-card{
    background:#1e293b;
}
.section-title{
    font-weight:600;
    margin-bottom:1.5rem;
}
.material-item{
    padding:1rem 0;
    border-bottom:1px solid #e5e7eb;
}
.material-item:last-child{border-bottom:none}
</style>
</head>
<body>
<div class="container" style="max-width:1100px">

<div class="main-card text-center mb-4">
    <i class="fas fa-book-open fa-2x text-primary mb-2"></i>
    <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($subject['name']); ?></h3>
    <p class="text-muted">Teacher: <?php echo htmlspecialchars($subject['teacher_name']); ?></p>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
</div>

<?php if(mysqli_num_rows($announcements)>0): ?>
<div class="main-card">
    <h5 class="section-title">Announcements</h5>
    <?php while($ann=mysqli_fetch_assoc($announcements)): ?>
        <div class="alert alert-light border mb-3">
            <strong><?php echo htmlspecialchars($ann['title']); ?></strong>
            <?php if($ann['subject_name']==='Global'): ?>
                <span class="badge bg-info ms-2">Global</span>
            <?php endif; ?>
            <p class="mb-1 mt-2 text-muted"><?php echo nl2br(htmlspecialchars($ann['message'])); ?></p>
            <small class="text-muted"><?php echo date('M d, Y • H:i', strtotime($ann['created_at'])); ?></small>
        </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<div class="main-card">
    <h5 class="section-title">Learning Materials</h5>
    <?php if(mysqli_num_rows($materials_query)>0): ?>
        <?php while($mat=mysqli_fetch_assoc($materials_query)): ?>
            <div class="material-item d-flex justify-content-between align-items-center">
                <div>
                    <strong><?php echo htmlspecialchars($mat['title']); ?></strong><br>
                    <small class="text-muted">Uploaded <?php echo date('M d, Y', strtotime($mat['uploaded_at'])); ?></small>
                </div>
                <?php if(!empty($mat['file_path']) && file_exists($mat['file_path'])): ?>
                    <a href="<?php echo htmlspecialchars($mat['file_path']); ?>" download class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-1"></i>Download
                    </a>
                <?php else: ?>
                    <span class="text-muted">Unavailable</span>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-muted text-center">No materials yet.</p>
    <?php endif; ?>
</div>

<div class="main-card">
    <h5 class="section-title">Assignments</h5>
    <?php if(mysqli_num_rows($assign_query)>0): ?>
        <?php while($task=mysqli_fetch_assoc($assign_query)):
            $t_id=$task['id'];
            $is_sub=false;
            $grade="Not Submitted";
            $sub_q=mysqli_query($connection,"SELECT grade FROM submissions WHERE assignment_id=$t_id AND student_id=$user_id");
            if($sub_q && mysqli_num_rows($sub_q)>0){$is_sub=true;$grade=mysqli_fetch_assoc($sub_q)['grade'];}
        ?>
        <div class="border rounded p-3 mb-3">
            <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
            <p class="text-muted small mb-2"><?php echo htmlspecialchars($task['instructions']); ?></p>
            <div class="d-flex justify-content-between align-items-center">
                <span class="badge bg-warning text-dark">Due <?php echo date('M d, Y', strtotime($task['due_date'])); ?></span>
                <?php if($is_sub): ?>
                    <span class="badge bg-success">Submitted • <?php echo htmlspecialchars($grade); ?></span>
                <?php else: ?>
                    <a href="submit_assignment.php?id=<?php echo $t_id; ?>" class="btn btn-primary btn-sm">Submit</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-muted text-center">No assignments yet.</p>
    <?php endif; ?>
</div>

</div>
<script>
const savedTheme=localStorage.getItem('theme');
if(savedTheme){document.documentElement.setAttribute('data-bs-theme',savedTheme)}
</script>
</body>
</html>
