<?php
require_once __DIR__ . '/../bootstrap.php';
// mechanic-performance.php - Redesigned with dark theme
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getConnection();
$mechanic_id = $_GET['id'] ?? 0;

$sql = "SELECT * FROM users WHERE id = ? AND role = 'mechanic'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$mechanic = $stmt->get_result()->fetch_assoc();

if (!$mechanic) {
    header('Location: mechanics.php');
    exit();
}

$stats_sql = "SELECT 
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_jobs,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as ongoing_jobs,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_jobs,
    COALESCE(SUM(labor_cost), 0) as total_earnings,
    ROUND(AVG(CASE WHEN completion_date IS NOT NULL AND start_date IS NOT NULL 
        THEN DATEDIFF(completion_date, start_date) ELSE NULL END), 1) as avg_completion_days
    FROM repairs WHERE mechanic_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$recent_sql = "SELECT r.*, b.booking_id, b.motorcycle_model, b.service_type, b.full_name
               FROM repairs r 
               JOIN bookings b ON r.booking_id = b.id 
               WHERE r.mechanic_id = ? 
               ORDER BY r.created_at DESC LIMIT 10";
$stmt = $conn->prepare($recent_sql);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$recent_jobs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanic Performance - <?php echo htmlspecialchars($mechanic['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-motorcycle fa-2x mb-2" style="color: var(--primary-color);"></i>
            <h3>MotoRepair Pro</h3>
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="jobs.php" class="menu-item"><i class="fas fa-tasks"></i><span>Job List</span></a>
            <a href="assign-mechanic.php" class="menu-item"><i class="fas fa-user-plus"></i><span>Assign Mechanic</span></a>
            <a href="inventory.php" class="menu-item"><i class="fas fa-boxes"></i><span>Inventory</span></a>
            <a href="mechanics.php" class="menu-item active"><i class="fas fa-users"></i><span>Mechanics</span></a>
            <a href="customers.php" class="menu-item"><i class="fas fa-users"></i><span>Customers</span></a>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div style="border-top: 1px solid var(--border-steel); margin: 20px 25px;"></div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <div class="page-title"><i class="fas fa-chart-line" style="color: var(--primary-color);"></i> Mechanic Performance - <?php echo htmlspecialchars($mechanic['name']); ?></div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="mb-3">
                <a href="mechanics.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i> Back to Mechanics List
                </a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <h3 style="color: #198754;"><?php echo $stats['completed_jobs'] ?? 0; ?></h3>
                        <p class="text-muted-custom mb-0">Completed Jobs</p>
                        <i class="fas fa-check-circle" style="color: #198754;"></i>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <h3 style="color: #0d6efd;"><?php echo $stats['ongoing_jobs'] ?? 0; ?></h3>
                        <p class="text-muted-custom mb-0">Ongoing Jobs</p>
                        <i class="fas fa-tools" style="color: #0d6efd;"></i>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <h3 style="color: #ffc107;"><?php echo $stats['pending_jobs'] ?? 0; ?></h3>
                        <p class="text-muted-custom mb-0">Pending Jobs</p>
                        <i class="fas fa-clock" style="color: #ffc107;"></i>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <h3 style="color: #198754;">$<?php echo number_format($stats['total_earnings'] ?? 0, 2); ?></h3>
                        <p class="text-muted-custom mb-0">Total Earnings</p>
                        <i class="fas fa-dollar-sign" style="color: #198754;"></i>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="stat-card">
                        <h3 style="color: #0dcaf0;"><?php echo round($stats['avg_completion_days'] ?? 0, 1); ?> days</h3>
                        <p class="text-muted-custom mb-0">Average Completion Time</p>
                        <i class="fas fa-hourglass-half" style="color: #0dcaf0;"></i>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <h3 style="color: #0d6efd;"><?php echo ($stats['completed_jobs'] ?? 0) + ($stats['ongoing_jobs'] ?? 0) + ($stats['pending_jobs'] ?? 0); ?></h3>
                        <p class="text-muted-custom mb-0">Total Assigned Jobs</p>
                        <i class="fas fa-briefcase" style="color: #0d6efd;"></i>
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <h5><i class="fas fa-history" style="color: var(--primary-color);"></i> Recent Jobs</h5>
                <div class="table-responsive table-responsive-custom">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Motorcycle</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Labor Cost</th>
                                <th>Start Date</th>
                                <th>Completion Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_jobs->num_rows > 0): ?>
                                <?php while($job = $recent_jobs->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong style="color: var(--text);"><?php echo $job['booking_id']; ?></strong></td>
                                        <td style="color: var(--text);"><?php echo htmlspecialchars($job['full_name']); ?></td>
                                        <td style="color: var(--text);"><?php echo htmlspecialchars($job['motorcycle_model']); ?></td>
                                        <td style="color: var(--text);"><?php echo $job['service_type']; ?></td>
                                        <td>
                                            <span class="badge" style="background: <?php 
                                                echo $job['status'] == 'completed' ? '#198754' : 
                                                    ($job['status'] == 'in_progress' ? '#0d6efd' : '#ffc107'); 
                                                ?>; color: <?php echo $job['status'] == 'pending' ? '#000' : '#fff'; ?>;">
                                                <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text);">$<?php echo number_format($job['labor_cost'] ?? 0, 2); ?></td>
                                        <td style="color: var(--text-muted);"><?php echo $job['start_date'] ? date('M d, Y', strtotime($job['start_date'])) : '-'; ?></td>
                                        <td style="color: var(--text-muted);"><?php echo $job['completion_date'] ? date('M d, Y', strtotime($job['completion_date'])) : '-'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted-custom" style="padding: 40px 0;">
                                        <i class="fas fa-tools fa-2x d-block mb-2" style="color: var(--text-muted);"></i>
                                        No jobs found for this mechanic
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        menuToggle.addEventListener('click', function() { sidebar.classList.toggle('active'); });
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
