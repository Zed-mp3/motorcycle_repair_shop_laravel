<?php
require_once __DIR__ . '/../bootstrap.php';
// reports.php - Redesigned with dark theme
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getConnection();

$report_type = $_GET['type'] ?? 'daily';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$revenue_sql = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_bookings,
    SUM(estimated_cost) as total_revenue,
    AVG(estimated_cost) as avg_booking_value
    FROM bookings 
    WHERE status = 'completed' 
    AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC";
$stmt = $conn->prepare($revenue_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$revenue_data = $stmt->get_result();

$service_sql = "SELECT 
    service_type,
    COUNT(*) as total_count,
    SUM(estimated_cost) as total_revenue,
    AVG(estimated_cost) as avg_cost,
    ROUND(AVG(CASE WHEN r.completion_date IS NOT NULL AND r.start_date IS NOT NULL 
        THEN DATEDIFF(r.completion_date, r.start_date) ELSE NULL END), 1) as avg_days
    FROM bookings b
    LEFT JOIN repairs r ON b.id = r.booking_id
    WHERE b.status = 'completed' 
    AND DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY service_type
    ORDER BY total_count DESC";
$stmt = $conn->prepare($service_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$service_data = $stmt->get_result();

$mechanic_sql = "SELECT 
    u.name as mechanic_name,
    u.specialization,
    COUNT(r.id) as total_jobs,
    SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
    SUM(CASE WHEN r.status = 'in_progress' THEN 1 ELSE 0 END) as ongoing_jobs,
    SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_jobs,
    ROUND(AVG(CASE WHEN r.completion_date IS NOT NULL AND r.start_date IS NOT NULL 
        THEN DATEDIFF(r.completion_date, r.start_date) ELSE NULL END), 1) as avg_completion_days,
    COALESCE(SUM(r.labor_cost), 0) as total_earnings
    FROM users u
    LEFT JOIN repairs r ON u.id = r.mechanic_id
    WHERE u.role = 'mechanic'
    GROUP BY u.id
    ORDER BY completed_jobs DESC";
$stmt = $conn->prepare($mechanic_sql);
$stmt->execute();
$mechanic_data = $stmt->get_result();

$customer_sql = "SELECT 
    u.name as customer_name,
    u.email,
    u.contact_number,
    COUNT(b.id) as total_bookings,
    SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
    COALESCE(SUM(b.estimated_cost), 0) as total_spent,
    MAX(b.created_at) as last_booking
    FROM users u
    JOIN bookings b ON u.id = b.user_id
    WHERE u.role = 'customer'
    AND DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY u.id
    HAVING total_bookings > 0
    ORDER BY total_spent DESC
    LIMIT 10";
$stmt = $conn->prepare($customer_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$customer_data = $stmt->get_result();

$inventory_sql = "SELECT 
    part_name,
    stock_quantity,
    unit_price,
    CASE 
        WHEN stock_quantity = 0 THEN 'Out of Stock'
        WHEN stock_quantity < 10 THEN 'Low Stock'
        WHEN stock_quantity < 30 THEN 'Medium Stock'
        ELSE 'Good Stock'
    END as stock_status
    FROM parts
    ORDER BY stock_quantity ASC";
$inventory_data = $conn->query($inventory_sql);

$popular_parts_sql = "SELECT 
    p.part_name,
    SUM(rp.quantity) as total_quantity_used,
    COUNT(DISTINCT rp.repair_id) as times_used,
    p.unit_price,
    SUM(rp.quantity * rp.price_at_time) as total_value
    FROM repair_parts rp
    JOIN parts p ON rp.part_id = p.id
    JOIN repairs r ON rp.repair_id = r.id
    WHERE (r.created_at BETWEEN ? AND ? OR r.created_at IS NULL)
    GROUP BY p.id
    ORDER BY total_quantity_used DESC
    LIMIT 10";
$stmt = $conn->prepare($popular_parts_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$popular_parts = $stmt->get_result();

$summary_sql = "SELECT 
    COUNT(DISTINCT b.id) as total_bookings,
    SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
    SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN b.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    COALESCE(SUM(b.estimated_cost), 0) as total_revenue,
    AVG(b.estimated_cost) as avg_booking_value,
    COUNT(DISTINCT b.user_id) as unique_customers
    FROM bookings b
    WHERE DATE(b.created_at) BETWEEN ? AND ?";
$stmt = $conn->prepare($summary_sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

if (!$summary) {
    $summary = [
        'total_bookings' => 0,
        'completed_bookings' => 0,
        'pending_bookings' => 0,
        'in_progress' => 0,
        'total_revenue' => 0,
        'avg_booking_value' => 0,
        'unique_customers' => 0
    ];
}

$stock_levels = ['Low Stock' => 0, 'Medium Stock' => 0, 'Good Stock' => 0, 'Out of Stock' => 0];
$inventory_data_temp = $conn->query($inventory_sql);
while($row = $inventory_data_temp->fetch_assoc()) {
    $stock_levels[$row['stock_status']]++;
}
$inventory_data->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
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
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="jobs.php" class="menu-item"><i class="fas fa-tasks"></i><span>Job List</span></a>
            <a href="assign-mechanic.php" class="menu-item"><i class="fas fa-user-plus"></i><span>Assign Mechanic</span></a>
            <a href="inventory.php" class="menu-item"><i class="fas fa-boxes"></i><span>Inventory</span></a>
            <a href="mechanics.php" class="menu-item"><i class="fas fa-users"></i><span>Mechanics</span></a>
            <a href="customers.php" class="menu-item"><i class="fas fa-users"></i><span>Customers</span></a>
            <a href="reports.php" class="menu-item active"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div style="border-top: 1px solid var(--border-steel); margin: 20px 25px;"></div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <div class="page-title"><i class="fas fa-chart-bar" style="color: var(--primary-color);"></i> Reports & Analytics</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="filter-bar">
                <form method="GET" class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="form-label" style="color: var(--text-muted);">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-dark" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="color: var(--text-muted);">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-dark" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary-custom w-100">Apply Filter</button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" onclick="window.print()" class="btn btn-secondary-custom w-100">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </form>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">TOTAL BOOKINGS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo number_format($summary['total_bookings']); ?></h2>
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
                                <span class="text-muted-custom small">COMPLETED</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo number_format($summary['completed_bookings']); ?></h2>
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
                                <span class="text-muted-custom small">TOTAL REVENUE</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);">$<?php echo number_format($summary['total_revenue'], 2); ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(13, 202, 240, 0.15); color: #0dcaf0;">
                                <i class="fas fa-dollar-sign fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">UNIQUE CUSTOMERS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo number_format($summary['unique_customers']); ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                                <i class="fas fa-users fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <h5><i class="fas fa-chart-line" style="color: var(--primary-color);"></i> Daily Revenue Report (<?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>)</h5>
                <canvas id="revenueChart" height="100"></canvas>
                <div class="table-responsive mt-3">
                    <table class="table table-custom">
                        <thead>
                            <tr><th>Date</th><th>Bookings</th><th>Revenue</th><th>Avg Booking Value</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $chart_dates = [];
                            $chart_revenues = [];
                            if($revenue_data && $revenue_data->num_rows > 0):
                                while($row = $revenue_data->fetch_assoc()): 
                                    $chart_dates[] = $row['date'];
                                    $chart_revenues[] = $row['total_revenue'];
                            ?>
                                <tr>
                                    <td style="color: var(--text);"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                    <td style="color: var(--text);"><?php echo $row['total_bookings']; ?> bookings</td>
                                    <td style="color: var(--text);">$<?php echo number_format($row['total_revenue'], 2); ?></td>
                                    <td style="color: var(--text);">$<?php echo number_format($row['avg_booking_value'], 2); ?></td>
                                </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <tr><td colspan="4" class="text-center text-muted-custom">No revenue data available for selected period</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card-custom">
                        <h5><i class="fas fa-cogs" style="color: var(--primary-color);"></i> Service Type Performance</h5>
                        <canvas id="serviceChart" height="200"></canvas>
                        <div class="table-responsive mt-3">
                            <table class="table table-custom table-sm">
                                <thead>
                                    <tr><th>Service</th><th>Count</th><th>Revenue</th><th>Avg Days</th></tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $service_names = [];
                                    $service_counts = [];
                                    if($service_data && $service_data->num_rows > 0):
                                        while($row = $service_data->fetch_assoc()): 
                                            $service_names[] = $row['service_type'];
                                            $service_counts[] = $row['total_count'];
                                    ?>
                                        <tr>
                                            <td style="color: var(--text);"><?php echo htmlspecialchars($row['service_type']); ?></td>
                                            <td style="color: var(--text);"><?php echo $row['total_count']; ?> jobs</td>
                                            <td style="color: var(--text);">$<?php echo number_format($row['total_revenue'], 2); ?></td>
                                            <td style="color: var(--text-muted);"><?php echo $row['avg_days'] ?? 'N/A'; ?> days</td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <tr><td colspan="4" class="text-center text-muted-custom">No service data available</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card-custom">
                        <h5><i class="fas fa-wrench" style="color: var(--primary-color);"></i> Mechanic Performance (All Time)</h5>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr><th>Mechanic</th><th>Completed</th><th>Ongoing</th><th>Pending</th><th>Avg Days</th><th>Earnings</th></tr>
                                </thead>
                                <tbody>
                                    <?php if($mechanic_data && $mechanic_data->num_rows > 0): ?>
                                        <?php while($row = $mechanic_data->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong style="color: var(--text);"><?php echo htmlspecialchars($row['mechanic_name']); ?></strong><br>
                                                    <small class="text-muted-custom"><?php echo htmlspecialchars($row['specialization'] ?? 'General'); ?></small>
                                                </td>
                                                <td><span class="badge" style="background: #198754;"><?php echo $row['completed_jobs']; ?></span></td>
                                                <td><span class="badge" style="background: #0d6efd;"><?php echo $row['ongoing_jobs'] ?? 0; ?></span></td>
                                                <td><span class="badge" style="background: #ffc107; color: #000;"><?php echo $row['pending_jobs'] ?? 0; ?></span></td>
                                                <td style="color: var(--text-muted);"><?php echo $row['avg_completion_days'] ?? '0'; ?> days</td>
                                                <td style="color: var(--text);">$<?php echo number_format($row['total_earnings'] ?? 0, 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center text-muted-custom">No mechanic data available</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <h5><i class="fas fa-trophy" style="color: var(--primary-color);"></i> Top Customers</h5>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr><th>Customer</th><th>Contact</th><th>Bookings</th><th>Completed</th><th>Total Spent</th><th>Last Booking</th></tr>
                        </thead>
                        <tbody>
                            <?php if($customer_data && $customer_data->num_rows > 0): ?>
                                <?php while($row = $customer_data->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--text);"><?php echo htmlspecialchars($row['customer_name']); ?></strong><br>
                                            <small class="text-muted-custom"><?php echo htmlspecialchars($row['email']); ?></small>
                                        </td>
                                        <td style="color: var(--text);"><?php echo htmlspecialchars($row['contact_number']); ?></td>
                                        <td style="color: var(--text);"><?php echo $row['total_bookings']; ?></td>
                                        <td style="color: var(--text);"><?php echo $row['completed_bookings']; ?></td>
                                        <td style="color: var(--text);">$<?php echo number_format($row['total_spent'], 2); ?></td>
                                        <td style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($row['last_booking'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted-custom">No customer data available</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card-custom">
                        <h5><i class="fas fa-boxes" style="color: var(--primary-color);"></i> Current Inventory Status</h5>
                        <canvas id="inventoryChart" height="200"></canvas>
                        <div class="table-responsive mt-3">
                            <table class="table table-custom table-sm">
                                <thead>
                                    <tr><th>Part</th><th>Stock</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php if($inventory_data && $inventory_data->num_rows > 0): ?>
                                        <?php while($row = $inventory_data->fetch_assoc()): ?>
                                            <tr>
                                                <td style="color: var(--text);"><?php echo htmlspecialchars($row['part_name']); ?></td>
                                                <td style="color: var(--text);"><?php echo $row['stock_quantity']; ?> units</td>
                                                <td>
                                                    <span class="badge" style="background: <?php 
                                                        echo $row['stock_status'] == 'Low Stock' ? '#dc3545' : 
                                                            ($row['stock_status'] == 'Medium Stock' ? '#ffc107' : 
                                                            ($row['stock_status'] == 'Out of Stock' ? '#6c757d' : '#198754')); 
                                                        ?>; color: <?php echo $row['stock_status'] == 'Medium Stock' ? '#000' : '#fff'; ?>;">
                                                        <?php echo $row['stock_status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted-custom">No inventory data available</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card-custom">
                        <h5><i class="fas fa-fire" style="color: var(--primary-color);"></i> Most Used Parts</h5>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr><th>Part Name</th><th>Times Used</th><th>Total Quantity</th><th>Total Value</th></tr>
                                </thead>
                                <tbody>
                                    <?php if($popular_parts && $popular_parts->num_rows > 0): ?>
                                        <?php while($row = $popular_parts->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong style="color: var(--text);"><?php echo htmlspecialchars($row['part_name']); ?></strong><br>
                                                    <small class="text-muted-custom">$<?php echo number_format($row['unit_price'], 2); ?> each</small>
                                                </td>
                                                <td style="color: var(--text);"><?php echo $row['times_used']; ?>x</td>
                                                <td style="color: var(--text);"><?php echo $row['total_quantity_used']; ?> units</td>
                                                <td style="color: var(--text);">$<?php echo number_format($row['total_value'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center text-muted-custom">No parts usage data available</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        <?php if(!empty($chart_dates) && !empty($chart_revenues)): ?>
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_dates); ?>,
                datasets: [{
                    label: 'Daily Revenue ($)',
                    data: <?php echo json_encode($chart_revenues); ?>,
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
                    legend: { labels: { color: '#edeff1' } },
                    tooltip: { callbacks: { label: function(context) { return '$' + context.parsed.y.toFixed(2); } } }
                },
                scales: {
                    x: { ticks: { color: '#9a9ea5' }, grid: { color: 'rgba(207, 211, 216, 0.08)' } },
                    y: { ticks: { color: '#9a9ea5', callback: function(value) { return '$' + value; } }, grid: { color: 'rgba(207, 211, 216, 0.08)' } }
                }
            }
        });
        <?php endif; ?>

        <?php if(!empty($service_names) && !empty($service_counts)): ?>
        const serviceCtx = document.getElementById('serviceChart').getContext('2d');
        new Chart(serviceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($service_names); ?>,
                datasets: [{
                    data: <?php echo json_encode($service_counts); ?>,
                    backgroundColor: ['#e67e22', '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#1abc9c', '#9b59b6', '#34495e']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { 
                    legend: { position: 'bottom', labels: { color: '#edeff1' } }
                }
            }
        });
        <?php endif; ?>

        const inventoryCtx = document.getElementById('inventoryChart').getContext('2d');
        new Chart(inventoryCtx, {
            type: 'pie',
            data: {
                labels: ['Low Stock', 'Medium Stock', 'Good Stock', 'Out of Stock'],
                datasets: [{
                    data: [<?php echo $stock_levels['Low Stock']; ?>, 
                           <?php echo $stock_levels['Medium Stock']; ?>, 
                           <?php echo $stock_levels['Good Stock']; ?>, 
                           <?php echo $stock_levels['Out of Stock']; ?>],
                    backgroundColor: ['#dc3545', '#ffc107', '#28a745', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { 
                    legend: { position: 'bottom', labels: { color: '#edeff1' } }
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
