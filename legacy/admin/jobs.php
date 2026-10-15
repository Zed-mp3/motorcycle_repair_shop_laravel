<?php
require_once __DIR__ . '/../bootstrap.php';
// jobs.php - Redesigned with dark theme
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getConnection();

if (isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['status'];
    $allowed = ['pending', 'accepted', 'in_progress', 'completed', 'cancelled'];

    if (in_array($new_status, $allowed, true)) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $booking_id);
            $stmt->execute();

            // repairs has no UNIQUE key on booking_id, so "ON DUPLICATE KEY" never fired
            // and every in_progress click inserted another repair row. Check first instead.
            $stmt = $conn->prepare("SELECT id FROM repairs WHERE booking_id = ? ORDER BY (mechanic_id IS NULL), id DESC LIMIT 1");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if ($new_status == 'in_progress') {
                if ($existing) {
                    $stmt = $conn->prepare("UPDATE repairs SET status = 'in_progress', start_date = COALESCE(start_date, CURDATE()), completion_date = NULL WHERE booking_id = ?");
                    $stmt->bind_param("i", $booking_id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO repairs (booking_id, status, start_date) VALUES (?, 'in_progress', CURDATE())");
                    $stmt->bind_param("i", $booking_id);
                }
                $stmt->execute();
            } elseif ($new_status == 'completed') {
                $stmt = $conn->prepare("UPDATE repairs SET status = 'completed', start_date = COALESCE(start_date, CURDATE()), completion_date = CURDATE() WHERE booking_id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
            } elseif ($existing && in_array($new_status, ['pending', 'accepted'], true)) {
                // moving a job back: keep the mechanic's repair row in sync
                $stmt = $conn->prepare("UPDATE repairs SET status = 'pending', completion_date = NULL WHERE booking_id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
        }
    }

    header("Location: jobs.php");
    exit();
}

$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT b.*, u.name as user_name FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.id 
        WHERE 1=1";
if ($status_filter != 'all') {
    $sql .= " AND b.status = '$status_filter'";
}
if ($search) {
    $sql .= " AND (b.full_name LIKE '%$search%' OR b.booking_id LIKE '%$search%' OR b.motorcycle_model LIKE '%$search%')";
}
$sql .= " ORDER BY b.created_at DESC";
$bookings = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Management - Admin</title>
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
            <a href="jobs.php" class="menu-item active"><i class="fas fa-tasks"></i><span>Job List</span></a>
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
            <div class="page-title"><i class="fas fa-tasks" style="color: var(--primary-color);"></i> Job Management</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" class="d-flex gap-2 flex-wrap">
                        <input type="text" name="search" class="form-control form-control-dark" placeholder="Search by name, ID, or model" value="<?php echo $search; ?>" style="flex:1; min-width:150px;">
                        <select name="status" class="form-control form-select-dark" style="width: auto; min-width:120px;">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="accepted" <?php echo $status_filter == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                        <button type="submit" class="btn btn-primary-custom">Filter</button>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <a href="assign-mechanic.php" class="btn btn-primary-custom">
                        <i class="fas fa-user-plus"></i> Assign Mechanic
                    </a>
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
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($bookings->num_rows > 0): ?>
                                <?php while($row = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong style="color: var(--text);"><?php echo $row['booking_id']; ?></strong></td>
                                        <td style="color: var(--text);"><?php echo $row['full_name']; ?></td>
                                        <td style="color: var(--text);"><?php echo $row['motorcycle_model']; ?></td>
                                        <td style="color: var(--text);"><?php echo $row['service_type']; ?></td>
                                        <td><span class="badge" style="background: var(--surface-raised); color: var(--text-muted);">#<?php echo $row['queue_no']; ?></span></td>
                                        <td>
                                            <span class="badge" style="background: <?php 
                                                echo $row['status'] == 'pending' ? '#ffc107' : 
                                                    ($row['status'] == 'accepted' ? '#0dcaf0' : 
                                                    ($row['status'] == 'in_progress' ? '#0d6efd' : '#198754')); 
                                                ?>; color: <?php echo $row['status'] == 'pending' ? '#000' : '#fff'; ?>;">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <button class="btn-info-custom" onclick="viewDetails(<?php echo $row['id']; ?>)"><i class="fas fa-eye"></i></button>
                                            <button class="btn-edit-custom" data-bs-toggle="modal" data-bs-target="#updateModal" onclick="setUpdateData(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')"><i class="fas fa-edit"></i></button>
                                            <a href="repair-details.php?booking_id=<?php echo $row['id']; ?>" class="btn-success-custom"><i class="fas fa-wrench"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted-custom" style="padding: 40px 0;">
                                        <i class="fas fa-tasks fa-2x d-block mb-2" style="color: var(--text-muted);"></i>
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

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content modal-content-custom">
                <div class="modal-header">
                    <h5 class="modal-title" style="color: var(--text);">Update Job Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="update_booking_id">
                        <select name="status" id="update_status" class="form-control form-select-dark" required>
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary-custom">Update</button>
                    </div>
                </form>
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

        function setUpdateData(id, status) {
            document.getElementById('update_booking_id').value = id;
            document.getElementById('update_status').value = status;
        }

        function viewDetails(id) {
            window.location.href = 'repair-details.php?booking_id=' + id;
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
