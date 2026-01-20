<?php
session_start();
require_once('db.php');

// Security: Only Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// --- HANDLE FILTERS ---
$filter_subject = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// --- FETCH DATA ---
$query = "SELECT a.*, u.name as student_name, s.name as subject_name 
          FROM attendance a
          JOIN users u ON a.student_id = u.id
          JOIN subjects s ON a.subject_id = s.id
          WHERE 1=1";

if ($filter_subject) {
    $query .= " AND a.subject_id = $filter_subject";
}
if ($filter_date) {
    $query .= " AND a.date = '$filter_date'";
}

$query .= " ORDER BY a.date DESC LIMIT 100";
$results = mysqli_query($connection, $query);

// Fetch subjects for the filter dropdown
$subjects_list = mysqli_query($connection, "SELECT id, name FROM subjects ORDER BY name");

// Calculate statistics
$total_records = mysqli_num_rows($results);
$stats_query = "SELECT status, COUNT(*) as count FROM attendance WHERE 1=1";
if ($filter_subject) $stats_query .= " AND subject_id = $filter_subject";
if ($filter_date) $stats_query .= " AND date = '$filter_date'";
$stats_query .= " GROUP BY status";
$stats_result = mysqli_query($connection, $stats_query);
$stats = [];
while ($stat = mysqli_fetch_assoc($stats_result)) {
    $stats[$stat['status']] = $stat['count'];
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Report | LMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 2rem; }
        [data-bs-theme="dark"] body { background: #0f172a; color: #e2e8f0; }
        .page-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        [data-bs-theme="dark"] .card {
            background: #1e293b;
        }
        [data-bs-theme="dark"] .table {
            color: #e2e8f0;
        }
        .stat-mini {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        [data-bs-theme="dark"] .stat-mini {
            background: #334155;
            border-color: #475569;
        }
    </style>
</head>
<body>

<div class="container" style="max-width: 1200px;">
    <div class="page-header shadow">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-clipboard-check me-2"></i>Attendance Report
                </h2>
                <p class="mb-0 opacity-75">Track student attendance across all courses</p>
            </div>
            <a href="index.php" class="btn btn-light rounded-pill">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-mini">
                <div class="text-success fw-bold h4 mb-1"><?php echo $stats['present'] ?? 0; ?></div>
                <small class="text-muted">Present</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-mini">
                <div class="text-danger fw-bold h4 mb-1"><?php echo $stats['absent'] ?? 0; ?></div>
                <small class="text-muted">Absent</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-mini">
                <div class="text-warning fw-bold h4 mb-1"><?php echo $stats['late'] ?? 0; ?></div>
                <small class="text-muted">Late</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-mini">
                <div class="text-primary fw-bold h4 mb-1"><?php echo $total_records; ?></div>
                <small class="text-muted">Total Records</small>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card p-4 mb-4">
        <h5 class="fw-bold mb-3">
            <i class="fas fa-filter me-2 text-primary"></i>Filter Records
        </h5>
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Filter by Course</label>
                <select name="subject_id" class="form-select">
                    <option value="">All Courses</option>
                    <?php 
                    mysqli_data_seek($subjects_list, 0);
                    while($sub = mysqli_fetch_assoc($subjects_list)): 
                    ?>
                        <option value="<?php echo $sub['id']; ?>" <?php if($filter_subject == $sub['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($sub['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">Filter by Date</label>
                <input type="date" name="date" class="form-select" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="fw-bold mb-0">Attendance Records</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><i class="far fa-calendar me-2"></i>Date</th>
                        <th><i class="fas fa-user me-2"></i>Student Name</th>
                        <th><i class="fas fa-book me-2"></i>Course</th>
                        <th class="text-center"><i class="fas fa-check-circle me-2"></i>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($results) > 0): ?>
                        <?php 
                        mysqli_data_seek($results, 0);
                        while($row = mysqli_fetch_assoc($results)): 
                        ?>
                            <tr>
                                <td class="text-muted"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                <td class="text-center">
                                    <?php 
                                        $status = strtolower($row['status']);
                                        $badge = 'bg-secondary';
                                        $icon = 'fa-question';
                                        if($status == 'present') { 
                                            $badge = 'bg-success'; 
                                            $icon = 'fa-check';
                                        }
                                        if($status == 'absent') { 
                                            $badge = 'bg-danger'; 
                                            $icon = 'fa-times';
                                        }
                                        if($status == 'late') { 
                                            $badge = 'bg-warning text-dark'; 
                                            $icon = 'fa-clock';
                                        }
                                    ?>
                                    <span class="badge <?php echo $badge; ?> px-3">
                                        <i class="fas <?php echo $icon; ?> me-1"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-25 d-block"></i>
                                <p class="text-muted">No attendance records found for these filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Theme persistence
    const savedTheme = localStorage.getItem('theme');
    if(savedTheme) {
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    }
</script>
</body>
</html>