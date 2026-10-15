<?php
require_once __DIR__ . '/../bootstrap.php';
// assign-mechanic.php - Redesigned with dark theme
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getConnection();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_mechanic'])) {
    $repair_id = $_POST['repair_id'];
    $mechanic_id = $_POST['mechanic_id'];
    
    $sql = "UPDATE repairs SET mechanic_id = ?, start_date = CURDATE(), status = 'in_progress' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $mechanic_id, $repair_id);
    
    if ($stmt->execute()) {
        $update_booking = "UPDATE bookings SET status = 'in_progress' WHERE id = (SELECT booking_id FROM repairs WHERE id = ?)";
        $stmt2 = $conn->prepare($update_booking);
        $stmt2->bind_param("i", $repair_id);
        $stmt2->execute();
        
        $message = "Mechanic assigned successfully!";
        $message_type = "success";
    } else {
        $message = "Error assigning mechanic: " . $conn->error;
        $message_type = "danger";
    }
}

$mechanics = $conn->query("SELECT id, name, specialization FROM users WHERE role = 'mechanic' ORDER BY name");

$pending_repairs = $conn->query("
    SELECT r.id, b.booking_id, b.full_name, b.motorcycle_model, b.service_type, b.created_at
    FROM repairs r 
    JOIN bookings b ON r.booking_id = b.id 
    WHERE (r.mechanic_id IS NULL OR r.mechanic_id = 0) AND r.status = 'pending'
    ORDER BY b.created_at ASC
");

$pending_count = $pending_repairs->num_rows;
$mechanic_count = $mechanics->num_rows;
$assigned_count = $conn->query("SELECT COUNT(*) as count FROM repairs WHERE mechanic_id IS NOT NULL AND mechanic_id != 0")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Mechanic - Admin</title>
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
            <a href="assign-mechanic.php" class="menu-item active"><i class="fas fa-user-plus"></i><span>Assign Mechanic</span></a>
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
            <div class="page-title"><i class="fas fa-user-plus" style="color: var(--primary-color);"></i> Assign Mechanic to Repairs</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <?php if($message): ?>
                <div class="alert alert-<?php echo $message_type == 'success' ? 'alert-custom-success' : 'alert-custom-danger'; ?> alert-dismissible fade show" role="alert" style="border-radius: 8px; padding: 12px 16px;">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: invert(1);"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">PENDING ASSIGNMENTS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $pending_count; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">AVAILABLE MECHANICS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $mechanic_count; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(25, 135, 84, 0.15); color: #198754;">
                                <i class="fas fa-user-cog fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">ASSIGNED REPAIRS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $assigned_count; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                                <i class="fas fa-check-circle fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <h5 style="color: var(--text);"><i class="fas fa-tools" style="color: var(--primary-color);"></i> Pending Repairs</h5>
                
                <?php if($pending_repairs->num_rows > 0): ?>
                    <div class="table-responsive table-responsive-custom">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Motorcycle</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Assign To</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($repair = $pending_repairs->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong style="color: var(--text);"><?php echo $repair['booking_id']; ?></strong></td>
                                        <td style="color: var(--text);"><?php echo htmlspecialchars($repair['full_name']); ?></td>
                                        <td style="color: var(--text);"><?php echo htmlspecialchars($repair['motorcycle_model']); ?></td>
                                        <td style="color: var(--text);"><?php echo $repair['service_type']; ?></td>
                                        <td style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($repair['created_at'])); ?></td>
                                        <td style="min-width: 200px;">
                                            <form method="POST" class="d-flex gap-2 flex-wrap">
                                                <input type="hidden" name="repair_id" value="<?php echo $repair['id']; ?>">
                                                <select name="mechanic_id" class="form-control form-select-dark" required style="width: 150px; font-size: 13px;">
                                                    <option value="">Select Mechanic</option>
                                                    <?php 
                                                    $mechanics->data_seek(0);
                                                    while($mechanic = $mechanics->fetch_assoc()): 
                                                    ?>
                                                        <option value="<?php echo $mechanic['id']; ?>">
                                                            <?php echo $mechanic['name']; ?> (<?php echo $mechanic['specialization'] ?? 'General'; ?>)
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <button type="submit" name="assign_mechanic" class="btn-assign">
                                                    <i class="fas fa-check"></i> Assign
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="repair-details.php?booking_id=<?php echo $repair['id']; ?>" class="btn-info-custom">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-4x" style="color: #198754;"></i>
                        <h5 style="color: var(--text); margin-top: 15px;">No Pending Repairs</h5>
                        <p class="text-muted-custom">All repairs have been assigned to mechanics.</p>
                        <a href="jobs.php" class="btn btn-primary-custom">View All Jobs</a>
                    </div>
                <?php endif; ?>
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
