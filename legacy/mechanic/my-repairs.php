<?php
require_once __DIR__ . '/../bootstrap.php';
// my-repairs.php - Updated to hide completed jobs
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || $_SESSION['role'] != 'mechanic') {
    header('Location: ../customer/login.php');
    exit();
}

$conn = getConnection();
$mechanic_id = $_SESSION['user_id'];

$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query - EXCLUDE completed jobs by default
$sql = "SELECT r.*, b.booking_id, b.full_name, b.motorcycle_model, b.service_type, b.queue_no
        FROM repairs r 
        JOIN bookings b ON r.booking_id = b.id 
        WHERE r.mechanic_id = ?";

// Exclude completed jobs unless specifically filtered
if ($status_filter == 'all') {
    $sql .= " AND r.status != 'completed'";
} elseif ($status_filter == 'completed') {
    $sql .= " AND r.status = 'completed'";
} else {
    $sql .= " AND r.status = ?";
}

if ($search) {
    $sql .= " AND (b.booking_id LIKE ? OR b.full_name LIKE ? OR b.motorcycle_model LIKE ?)";
}
$sql .= " ORDER BY r.start_date DESC, b.created_at ASC";

$stmt = $conn->prepare($sql);

// Bind parameters based on filter
if ($status_filter == 'all' && $search) {
    $search_param = "%$search%";
    $stmt->bind_param("isss", $mechanic_id, $search_param, $search_param, $search_param);
} elseif ($status_filter == 'all') {
    $stmt->bind_param("i", $mechanic_id);
} elseif ($status_filter == 'completed' && $search) {
    $search_param = "%$search%";
    $stmt->bind_param("isss", $mechanic_id, $search_param, $search_param, $search_param);
} elseif ($status_filter == 'completed') {
    $stmt->bind_param("i", $mechanic_id);
} elseif ($search) {
    $search_param = "%$search%";
    $stmt->bind_param("isss", $mechanic_id, $status_filter, $search_param, $search_param, $search_param);
} else {
    $stmt->bind_param("is", $mechanic_id, $status_filter);
}
$stmt->execute();
$repairs = $stmt->get_result();

// Get counts for filter badges
$total_active = $conn->query("SELECT COUNT(*) as count FROM repairs WHERE mechanic_id = $mechanic_id AND status != 'completed'")->fetch_assoc()['count'];
$total_completed = $conn->query("SELECT COUNT(*) as count FROM repairs WHERE mechanic_id = $mechanic_id AND status = 'completed'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Repairs - Mechanic Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="my-repairs.php" class="menu-item active"><i class="fas fa-tools"></i><span>My Repairs</span></a>
            <div style="border-top: 1px solid var(--border-steel); margin: 20px 25px;"></div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <div class="page-title"><i class="fas fa-tools" style="color: var(--primary-color);"></i> My Repairs</div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h4 style="color: var(--text);">
                        <i class="fas fa-list-ul" style="color: var(--primary-color);"></i> 
                        <?php if($status_filter == 'completed'): ?>
                            Completed Repairs
                            <span class="filter-badge filter-badge-completed"><?php echo $total_completed; ?></span>
                        <?php else: ?>
                            Active Repairs
                            <span class="filter-badge filter-badge-active"><?php echo $total_active; ?></span>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="d-flex flex-wrap gap-2">
                        <input type="text" name="search" class="form-control form-control-custom" placeholder="Search by ID, customer, or motorcycle" value="<?php echo htmlspecialchars($search); ?>" style="flex:1; min-width:150px;">
                        <select name="status" class="form-control form-control-custom" style="width: auto; min-width:120px;">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        </select>
                        <button type="submit" class="btn btn-primary-custom">Filter</button>
                    </form>
                </div>
            </div>

            <div class="table-container">
                <div class="table-responsive table-responsive-custom">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Motorcycle</th>
                                <th>Service</th>
                                <th>Queue</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($repairs->num_rows > 0): ?>
                                <?php while($repair = $repairs->fetch_assoc()): 
                                    $is_completed = ($repair['status'] == 'completed');
                                ?>
                                    <tr>
                                        <td><strong style="color: var(--text);"><?php echo $repair['booking_id']; ?></strong></td>
                                        <td style="color: var(--text);"><?php echo htmlspecialchars($repair['full_name']); ?></td>
                                        <td style="color: var(--text);"><?php echo htmlspecialchars($repair['motorcycle_model']); ?></td>
                                        <td style="color: var(--text);"><?php echo $repair['service_type']; ?></td>
                                        <td><span class="badge" style="background: var(--surface-raised); color: var(--text-muted);">#<?php echo $repair['queue_no']; ?></span></td>
                                        <td>
                                            <span class="badge-custom badge-<?php echo $repair['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $repair['status'])); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted);"><?php echo $repair['start_date'] ? date('M d', strtotime($repair['start_date'])) : '-'; ?></td>
                                        <td>
                                            <?php if($is_completed): ?>
                                                <span class="btn-sm-disabled">
                                                    <i class="fas fa-check-circle me-1"></i> Done
                                                </span>
                                            <?php else: ?>
                                                <a href="work-on-repair.php?id=<?php echo $repair['id']; ?>" class="btn-sm-custom">
                                                    <i class="fas fa-wrench"></i> Work
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center" style="color: var(--text-muted); padding: 40px 0;">
                                        <?php if($status_filter == 'completed'): ?>
                                            <i class="fas fa-check-circle fa-2x d-block mb-2" style="color: var(--text-muted);"></i>
                                            No completed repairs found
                                        <?php else: ?>
                                            <i class="fas fa-tools fa-2x d-block mb-2" style="color: var(--text-muted);"></i>
                                            No active repairs found
                                        <?php endif; ?>
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
