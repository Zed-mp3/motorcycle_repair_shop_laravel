<?php
require_once __DIR__ . '/../bootstrap.php';
// inventory.php - Redesigned with dark theme
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getConnection();

if (isset($_POST['save_part'])) {
    $id = $_POST['part_id'] ?? 0;
    $part_name = $_POST['part_name'];
    $description = $_POST['description'];
    $stock = $_POST['stock_quantity'];
    $price = $_POST['unit_price'];
    
    if ($id > 0) {
        $sql = "UPDATE parts SET part_name=?, description=?, stock_quantity=?, unit_price=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssidi", $part_name, $description, $stock, $price, $id);
    } else {
        $sql = "INSERT INTO parts (part_name, description, stock_quantity, unit_price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssid", $part_name, $description, $stock, $price);
    }
    
    if ($stmt->execute()) {
        echo "<script>alert('Part saved successfully'); window.location.href='inventory.php';</script>";
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM parts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: inventory.php");
    exit();
}

$parts = $conn->query("SELECT * FROM parts ORDER BY part_name");
$edit_part = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM parts WHERE id = $id");
    $edit_part = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Admin</title>
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
            <a href="inventory.php" class="menu-item active"><i class="fas fa-boxes"></i><span>Inventory</span></a>
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
            <div class="page-title"><i class="fas fa-boxes" style="color: var(--primary-color);"></i> Inventory Management</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card-custom">
                        <h5><i class="fas fa-plus" style="color: var(--primary-color);"></i> <?php echo $edit_part ? 'Edit' : 'Add'; ?> Part</h5>
                        <form method="POST">
                            <input type="hidden" name="part_id" value="<?php echo $edit_part['id'] ?? 0; ?>">
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Part Name *</label>
                                <input type="text" name="part_name" class="form-control form-control-dark" value="<?php echo $edit_part['part_name'] ?? ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Description</label>
                                <textarea name="description" class="form-control form-control-dark" rows="2"><?php echo $edit_part['description'] ?? ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Stock Quantity *</label>
                                <input type="number" name="stock_quantity" class="form-control form-control-dark" value="<?php echo $edit_part['stock_quantity'] ?? 0; ?>" required min="0">
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Unit Price ($) *</label>
                                <input type="number" name="unit_price" class="form-control form-control-dark" step="0.01" value="<?php echo $edit_part['unit_price'] ?? 0; ?>" required>
                            </div>
                            <button type="submit" name="save_part" class="btn btn-primary-custom"><i class="fas fa-save"></i> <?php echo $edit_part ? 'Update' : 'Save'; ?> Part</button>
                            <?php if($edit_part): ?>
                                <a href="inventory.php" class="btn btn-secondary-custom">Cancel Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card-custom">
                        <h5><i class="fas fa-list" style="color: var(--primary-color);"></i> Parts Inventory</h5>
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Part Name</th>
                                        <th>Stock</th>
                                        <th>Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($part = $parts->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: var(--text);"><?php echo $part['part_name']; ?></strong><br>
                                                <small class="text-muted-custom"><?php echo $part['description']; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: <?php echo $part['stock_quantity'] < 10 ? '#dc3545' : ($part['stock_quantity'] < 30 ? '#ffc107' : '#198754'); ?>; color: <?php echo $part['stock_quantity'] < 30 ? '#000' : '#fff'; ?>;">
                                                    <?php echo $part['stock_quantity']; ?> units
                                                </span>
                                            </td>
                                            <td style="color: var(--text);">$<?php echo number_format($part['unit_price'], 2); ?></td>
                                            <td>
                                                <a href="?edit=<?php echo $part['id']; ?>" class="btn-warning-custom"><i class="fas fa-edit"></i></a>
                                                <a href="?delete=<?php echo $part['id']; ?>" class="btn-danger-custom" onclick="return confirm('Delete this part?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if($parts->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted-custom" style="padding: 40px 0;">
                                                <i class="fas fa-boxes fa-2x d-block mb-2" style="color: var(--text-muted);"></i>
                                                No parts in inventory
                                            </td>
                                        </tr>
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
