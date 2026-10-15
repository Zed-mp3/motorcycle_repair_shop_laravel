<?php
require_once __DIR__ . '/../bootstrap.php';
// customers.php - Redesigned with dark theme
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getConnection();

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $check_sql = "SELECT COUNT(*) as count FROM bookings WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        echo "<script>alert('Cannot delete customer. They have $count booking(s).'); window.location.href='customers.php';</script>";
    } else {
        $sql = "DELETE FROM users WHERE id = ? AND role = 'customer'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Customer deleted successfully'); window.location.href='customers.php';</script>";
        }
    }
    exit();
}

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM users WHERE role = 'customer'";
if ($search) {
    $sql .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR contact_number LIKE '%$search%')";
}
$sql .= " ORDER BY created_at DESC";
$customers = $conn->query($sql);

$total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];
$active_customers = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'];
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers Management - Admin</title>
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
            <div class="page-title"><i class="fas fa-users" style="color: var(--primary-color);"></i> Customers Management</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">TOTAL CUSTOMERS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $total_customers; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                                <i class="fas fa-users fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">ACTIVE (30 DAYS)</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $active_customers; ?></h2>
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
                                <span class="text-muted-custom small">TOTAL BOOKINGS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $total_bookings; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                                <i class="fas fa-calendar fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 style="color: var(--text); margin: 0;"><i class="fas fa-list" style="color: var(--primary-color);"></i> All Customers</h5>
                    <form method="GET" class="d-flex gap-2 flex-wrap">
                        <input type="text" name="search" class="form-control form-control-dark" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>" style="width: 250px;">
                        <button type="submit" class="btn btn-primary-custom"><i class="fas fa-search"></i> Search</button>
                        <?php if($search): ?>
                            <a href="customers.php" class="btn btn-secondary-custom">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="table-responsive table-responsive-custom">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Registered</th>
                                <th>Total Bookings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($customers && $customers->num_rows > 0): ?>
                                <?php while($customer = $customers->fetch_assoc()): 
                                    $booking_sql = "SELECT COUNT(*) as count, SUM(estimated_cost) as total_spent FROM bookings WHERE user_id = ?";
                                    $booking_stmt = $conn->prepare($booking_sql);
                                    $booking_stmt->bind_param("i", $customer['id']);
                                    $booking_stmt->execute();
                                    $booking_stats = $booking_stmt->get_result()->fetch_assoc();
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="customer-avatar"><?php echo strtoupper(substr($customer['name'], 0, 1)); ?></div>
                                                <div>
                                                    <strong style="color: var(--text);"><?php echo htmlspecialchars($customer['name']); ?></strong><br>
                                                    <small class="text-muted-custom"><?php echo htmlspecialchars($customer['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="color: var(--text);"><?php echo htmlspecialchars($customer['contact_number'] ?? 'N/A'); ?></td>
                                        <td style="color: var(--text-muted);"><?php echo htmlspecialchars(substr($customer['address'] ?? '', 0, 50)) . (strlen($customer['address'] ?? '') > 50 ? '...' : ''); ?></td>
                                        <td style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                        <td>
                                            <span class="badge" style="background: #0d6efd;"><?php echo $booking_stats['count']; ?> bookings</span><br>
                                            <small class="text-muted-custom">Spent: $<?php echo number_format($booking_stats['total_spent'] ?? 0, 2); ?></small>
                                        </td>
                                        <td>
                                            <a href="customer-details.php?id=<?php echo $customer['id']; ?>" class="btn-info-custom">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?delete=<?php echo $customer['id']; ?>" class="btn-danger-custom" 
                                               onclick="return confirm('Delete this customer? This will also delete all their bookings.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted-custom" style="padding: 40px 0;">
                                        <i class="fas fa-users fa-2x d-block mb-2" style="color: var(--text-muted);"></i>
                                        No customers found<?php echo $search ? ' matching "' . htmlspecialchars($search) . '"' : ''; ?>.
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
