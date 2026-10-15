<?php
require_once __DIR__ . '/../bootstrap.php';
// dashboard.php - Updated with no work button for completed jobs
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || $_SESSION['role'] != 'mechanic') {
    header('Location: ../customer/login.php');
    exit();
}

$conn = getConnection();
$mechanic_id = $_SESSION['user_id'];

// Get mechanic's assigned repairs - show all including completed
$sql = "SELECT b.*, r.id as repair_id, r.status as repair_status, r.start_date, r.labor_cost,
        (SELECT COUNT(*) FROM repair_parts WHERE repair_id = r.id) as parts_count
        FROM bookings b 
        JOIN repairs r ON b.id = r.booking_id 
        WHERE r.mechanic_id = ?
        ORDER BY FIELD(r.status, 'in_progress', 'pending', 'completed'), r.start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$my_repairs = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
    COUNT(CASE WHEN r.status = 'in_progress' THEN 1 END) as ongoing,
    COUNT(CASE WHEN r.status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN r.status = 'pending' THEN 1 END) as pending
    FROM repairs r WHERE r.mechanic_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent completed work
$recent_sql = "SELECT b.booking_id, b.motorcycle_model, r.completion_date, r.labor_cost
               FROM repairs r 
               JOIN bookings b ON r.booking_id = b.id 
               WHERE r.mechanic_id = ? AND r.status = 'completed' 
               ORDER BY r.completion_date DESC LIMIT 5";
$stmt = $conn->prepare($recent_sql);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$recent_work = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanic Dashboard - MotoRepair Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-motorcycle fa-2x mb-2" style="color: var(--primary-color);"></i>
            <h3>MotoRepair Pro</h3>
            <p>Mechanic Portal</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="my-repairs.php" class="menu-item"><i class="fas fa-tools"></i><span>My Repairs</span></a>
            <div style="border-top: 1px solid var(--border-steel); margin: 20px 25px;"></div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <div class="page-title"><i class="fas fa-wrench" style="color: var(--primary-color);"></i> Mechanic Dashboard</div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">ONGOING REPAIRS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $stats['ongoing'] ?? 0; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                                <i class="fas fa-tools fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">COMPLETED</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $stats['completed'] ?? 0; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(25, 135, 84, 0.15); color: #198754;">
                                <i class="fas fa-check-circle fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">PENDING</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $stats['pending'] ?? 0; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="table-container">
                        <div class="card-header">
                            <i class="fas fa-list" style="color: var(--primary-color);"></i> My Assigned Repairs
                        </div>
                        <div>
                            <?php if($my_repairs->num_rows > 0): ?>
                                <?php while($repair = $my_repairs->fetch_assoc()): 
                                    $status_class = '';
                                    $status_text = ucfirst(str_replace('_', ' ', $repair['repair_status']));
                                    switch($repair['repair_status']) {
                                        case 'pending': $status_class = 'status-pending'; break;
                                        case 'in_progress': $status_class = 'status-in_progress'; break;
                                        case 'completed': $status_class = 'status-completed'; break;
                                    }
                                    $is_completed = ($repair['repair_status'] == 'completed');
                                ?>
                                <div class="repair-row p-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <div style="min-width: 90px;">
                                                <div class="text-muted-custom small">Booking ID</div>
                                                <strong style="color: var(--text);"><?php echo $repair['booking_id']; ?></strong>
                                            </div>
                                            <div style="min-width: 110px;">
                                                <div class="text-muted-custom small">Customer</div>
                                                <div style="color: var(--text);"><?php echo htmlspecialchars($repair['full_name']); ?></div>
                                            </div>
                                            <div style="min-width: 120px;">
                                                <div class="text-muted-custom small">Motorcycle</div>
                                                <div style="color: var(--text);"><?php echo htmlspecialchars($repair['motorcycle_model']); ?></div>
                                            </div>
                                            <div>
                                                <div class="text-muted-custom small">Status</div>
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </div>
                                        </div>
                                        <div class="mt-2 mt-md-0">
                                            <?php if($is_completed): ?>
                                                <span class="btn-work-disabled">
                                                    <i class="fas fa-check-circle me-1"></i> Completed
                                                </span>
                                            <?php else: ?>
                                                <a href="work-on-repair.php?id=<?php echo $repair['repair_id']; ?>" class="btn-work">
                                                    <i class="fas fa-wrench me-1"></i> Work
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if($repair['repair_status'] == 'in_progress'): ?>
                                    <div class="mt-2">
                                        <div class="progress" style="height: 4px; background: var(--surface-raised);">
                                            <div class="progress-bar" style="width: 65%; background: var(--primary-color);"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x" style="color: var(--text-muted);"></i>
                                    <p class="text-muted-custom mb-0 mt-2">No repairs assigned yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="table-container mb-4">
                        <div class="card-header">
                            <i class="fas fa-chart-pie" style="color: var(--primary-color);"></i> My Performance
                        </div>
                        <div class="p-3 text-center">
                            <canvas id="performanceChart" height="180" style="max-height: 180px;"></canvas>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="card-header">
                            <i class="fas fa-history" style="color: var(--primary-color);"></i> Recently Completed
                        </div>
                        <div>
                            <?php if($recent_work->num_rows > 0): ?>
                                <?php while($work = $recent_work->fetch_assoc()): ?>
                                <div class="repair-row p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div style="color: var(--text); font-weight: 600;"><?php echo $work['booking_id']; ?></div>
                                            <div class="text-muted-custom small"><?php echo htmlspecialchars($work['motorcycle_model']); ?></div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge" style="background: #198754;">$<?php echo number_format($work['labor_cost'], 2); ?></span>
                                            <div class="text-muted-custom small"><?php echo $work['completion_date'] ? date('M d', strtotime($work['completion_date'])) : '-'; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-tools fa-2x" style="color: var(--text-muted);"></i>
                                    <p class="text-muted-custom small mb-0">No completed repairs yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('performanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Ongoing', 'Completed', 'Pending'],
                datasets: [{
                    data: [<?php echo $stats['ongoing'] ?? 0; ?>, 
                           <?php echo $stats['completed'] ?? 0; ?>, 
                           <?php echo $stats['pending'] ?? 0; ?>],
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107'],
                    borderWidth: 0,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '60%',
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            boxWidth: 12, 
                            font: { size: 11, color: '#edeff1' },
                            color: '#edeff1'
                        } 
                    },
                    tooltip: { 
                        callbacks: { 
                            label: function(context) { 
                                return context.label + ': ' + context.raw; 
                            } 
                        } 
                    }
                }
            }
        });

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
