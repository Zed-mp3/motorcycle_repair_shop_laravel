<?php
require_once __DIR__ . '/../bootstrap.php';
// dashboard.php - Redesigned with dark theme
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getConnection();

$today = date('Y-m-d');
$today_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = '$today'")->fetch_assoc()['count'];
$pending_repairs = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];
$completed_today = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed' AND DATE(created_at) = '$today'")->fetch_assoc()['count'];
$daily_income = $conn->query("SELECT SUM(estimated_cost) as total FROM bookings WHERE status = 'completed' AND DATE(created_at) = '$today'")->fetch_assoc()['total'] ?? 0;

$weekly_revenue = [];
$weekly_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weekly_labels[] = date('D', strtotime($date));
    $result = $conn->query("SELECT COALESCE(SUM(estimated_cost), 0) as total FROM bookings WHERE status = 'completed' AND DATE(created_at) = '$date'");
    $weekly_revenue[] = $result->fetch_assoc()['total'];
}

$recent = $conn->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5");

$low_stock = $conn->query("SELECT COUNT(*) as count FROM parts WHERE stock_quantity < 10");
$low_stock_count = $low_stock->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MotoRepair Pro</title>
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
            <p>Admin Panel</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="jobs.php" class="menu-item"><i class="fas fa-tasks"></i><span>Job List</span></a>
            <a href="assign-mechanic.php" class="menu-item"><i class="fas fa-user-plus"></i><span>Assign Mechanic</span></a>
            <a href="inventory.php" class="menu-item"><i class="fas fa-boxes"></i><span>Inventory</span></a>
            <a href="mechanics.php" class="menu-item"><i class="fas fa-users"></i><span>Mechanics</span></a>
            <a href="customers.php" class="menu-item"><i class="fas fa-users"></i><span>Customers</span></a>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div style="border-top: 1px solid var(--border-steel); margin: 20px 25px;"></div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <div class="page-title"><i class="fas fa-tachometer-alt" style="color: var(--primary-color);"></i> Dashboard</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">TODAY'S BOOKINGS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $today_bookings; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                                <i class="fas fa-calendar fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">PENDING REPAIRS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $pending_repairs; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">COMPLETED TODAY</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $completed_today; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(25, 135, 84, 0.15); color: #198754;">
                                <i class="fas fa-check-circle fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">DAILY INCOME</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);">$<?php echo number_format($daily_income, 2); ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                                <i class="fas fa-dollar-sign fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-7">
                    <div class="card-custom">
                        <div class="card-header"><i class="fas fa-history" style="color: var(--primary-color);"></i> Recent Bookings</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Customer</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($recent && $recent->num_rows > 0): ?>
                                            <?php while($row = $recent->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong style="color: var(--text);"><?php echo htmlspecialchars($row['booking_id']); ?></strong></td>
                                                    <td style="color: var(--text);"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                    <td>
                                                        <span class="badge" style="background: <?php 
                                                            echo $row['status'] == 'pending' ? '#ffc107' : 
                                                                ($row['status'] == 'completed' ? '#198754' : 
                                                                ($row['status'] == 'in_progress' ? '#0d6efd' : '#6c757d')); 
                                                        ?>; color: #fff;">
                                                            <?php echo ucfirst($row['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted-custom">No bookings found</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <div class="card-custom">
                        <div class="card-header"><i class="fas fa-chart-line" style="color: var(--primary-color);"></i> Weekly Revenue</div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($low_stock_count > 0): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert-warning-custom alert alert-dismissible fade show" role="alert" style="padding: 12px 16px;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Low Stock Alert!</strong> There are <?php echo $low_stock_count; ?> part(s) with low inventory. 
                            <a href="inventory.php" style="color: #ffc107;">Check inventory now</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: invert(1);"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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

        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($weekly_labels); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($weekly_revenue); ?>,
                    borderColor: '#e67e22',
                    backgroundColor: 'rgba(230, 126, 34, 0.15)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#e67e22'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        labels: { color: '#edeff1' }
                    },
                    tooltip: { 
                        callbacks: { 
                            label: function(context) { 
                                return '$' + context.parsed.y.toFixed(2); 
                            } 
                        } 
                    }
                },
                scales: { 
                    x: { 
                        ticks: { color: '#9a9ea5' },
                        grid: { color: 'rgba(207, 211, 216, 0.08)' }
                    },
                    y: { 
                        beginAtZero: true, 
                        ticks: { color: '#9a9ea5', callback: function(value) { return '$' + value; } },
                        grid: { color: 'rgba(207, 211, 216, 0.08)' }
                    } 
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
