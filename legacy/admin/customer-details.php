<?php
require_once __DIR__ . '/../bootstrap.php';
// customer-details.php - Redesigned with dark theme
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getConnection();
$customer_id = $_GET['id'] ?? 0;

$sql = "SELECT * FROM users WHERE id = ? AND role = 'customer'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) {
    header('Location: customers.php');
    exit();
}

$bookings_sql = "SELECT * FROM bookings WHERE user_id = ? OR (user_id IS NULL AND email = ?) ORDER BY created_at DESC";
$bookings_stmt = $conn->prepare($bookings_sql);
$bookings_stmt->bind_param("is", $customer_id, $customer['email']);
$bookings_stmt->execute();
$bookings = $bookings_stmt->get_result();

$stats_sql = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(estimated_cost) as total_spent
    FROM bookings WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $customer_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - <?php echo htmlspecialchars($customer['name']); ?></title>
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
            <a href="mechanics.php" class="menu-item"><i class="fas fa-users"></i><span>Mechanics</span></a>
            <a href="customers.php" class="menu-item active"><i class="fas fa-users"></i><span>Customers</span></a>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div style="border-top: 1px solid var(--border-steel); margin: 20px 25px;"></div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <div class="page-title"><i class="fas fa-user" style="color: var(--primary-color);"></i> Customer Details - <?php echo htmlspecialchars($customer['name']); ?></div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card-custom text-center">
                        <div class="customer-avatar-large mx-auto mb-3"><?php echo strtoupper(substr($customer['name'], 0, 1)); ?></div>
                        <h4 style="color: var(--text);"><?php echo htmlspecialchars($customer['name']); ?></h4>
                        <p class="text-muted-custom"><?php echo htmlspecialchars($customer['email']); ?></p>
                        <p style="color: var(--text);"><i class="fas fa-phone" style="color: var(--primary-color);"></i> <?php echo htmlspecialchars($customer['contact_number'] ?? 'N/A'); ?></p>
                        <p style="color: var(--text-muted);"><i class="fas fa-map-marker-alt" style="color: var(--primary-color);"></i> <?php echo htmlspecialchars($customer['address'] ?? 'No address provided'); ?></p>
                        <p style="color: var(--text-muted);"><i class="fas fa-calendar" style="color: var(--primary-color);"></i> Registered: <?php echo date('F d, Y', strtotime($customer['created_at'])); ?></p>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <h3 style="color: #0d6efd;"><?php echo $stats['total_bookings'] ?? 0; ?></h3>
                                <p class="text-muted-custom mb-0">Total Bookings</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <h3 style="color: #198754;"><?php echo $stats['completed'] ?? 0; ?></h3>
                                <p class="text-muted-custom mb-0">Completed</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <h3 style="color: var(--primary-color);">$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></h3>
                                <p class="text-muted-custom mb-0">Total Spent</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-custom mt-3">
                <h5><i class="fas fa-history" style="color: var(--primary-color);"></i> Booking History</h5>
                <div class="table-responsive table-responsive-custom">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Motorcycle</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Cost</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($bookings->num_rows > 0): ?>
                                <?php while($booking = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong style="color: var(--text);"><?php echo $booking['booking_id']; ?></strong></td>
                                        <td style="color: var(--text);"><?php echo htmlspecialchars($booking['motorcycle_model']); ?></td>
                                        <td style="color: var(--text);"><?php echo $booking['service_type']; ?></td>
                                        <td>
                                            <span class="badge" style="background: <?php 
                                                echo $booking['status'] == 'completed' ? '#198754' : 
                                                    ($booking['status'] == 'in_progress' ? '#0d6efd' : 
                                                    ($booking['status'] == 'pending' ? '#ffc107' : '#6c757d')); 
                                                ?>; color: <?php echo $booking['status'] == 'pending' ? '#000' : '#fff'; ?>;">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                                        <td style="color: var(--text);">$<?php echo number_format($booking['estimated_cost'], 2); ?></td>
                                        <td>
                                            <a href="repair-details.php?booking_id=<?php echo $booking['id']; ?>" class="btn-primary-custom">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted-custom" style="padding: 40px 0;">
                                        <i class="fas fa-calendar fa-2x d-block mb-2" style="color: var(--text-muted);"></i>
                                        No bookings found
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
