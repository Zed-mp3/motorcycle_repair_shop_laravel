<?php
require_once __DIR__ . '/../bootstrap.php';
// mechanics.php - Redesigned with dark theme
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getConnection();

if (isset($_POST['add_mechanic'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $specialization = $_POST['specialization'];
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        echo "<script>alert('Password is required for new mechanic');</script>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'mechanic';
        $created_at = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO users (name, email, password, contact_number, address, specialization, role, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $name, $email, $hashed_password, $contact, $address, $specialization, $role, $created_at);
        
        if ($stmt->execute()) {
            echo "<script>alert('Mechanic added successfully'); window.location.href='mechanics.php';</script>";
        } else {
            echo "<script>alert('Error adding mechanic: " . $conn->error . "');</script>";
        }
    }
}

if (isset($_POST['edit_mechanic'])) {
    $id = $_POST['mechanic_id'];
    $name = trim($_POST['edit_name']);
    $email = trim($_POST['edit_email']);
    $contact = trim($_POST['edit_contact_number']);
    $address = trim($_POST['edit_address']);
    $specialization = $_POST['edit_specialization'];
    $password = $_POST['edit_password'] ?? '';
    
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET name=?, email=?, contact_number=?, address=?, specialization=?, password=? WHERE id=? AND role='mechanic'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $name, $email, $contact, $address, $specialization, $hashed_password, $id);
    } else {
        $sql = "UPDATE users SET name=?, email=?, contact_number=?, address=?, specialization=? WHERE id=? AND role='mechanic'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $name, $email, $contact, $address, $specialization, $id);
    }
    
    if ($stmt->execute()) {
        echo "<script>alert('Mechanic updated successfully'); window.location.href='mechanics.php';</script>";
    } else {
        echo "<script>alert('Error updating mechanic: " . $conn->error . "');</script>";
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $check_sql = "SELECT COUNT(*) as count FROM repairs WHERE mechanic_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        echo "<script>alert('Cannot delete mechanic. They have $count assigned repair(s).'); window.location.href='mechanics.php';</script>";
    } else {
        $sql = "DELETE FROM users WHERE id = ? AND role = 'mechanic'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Mechanic deleted successfully'); window.location.href='mechanics.php';</script>";
        }
    }
    exit();
}

$mechanics = $conn->query("SELECT * FROM users WHERE role = 'mechanic' ORDER BY name");

$edit_mechanic = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM users WHERE id = $id AND role = 'mechanic'");
    if ($result && $result->num_rows > 0) {
        $edit_mechanic = $result->fetch_assoc();
    }
}

$total_mechanics = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'mechanic'")->fetch_assoc()['count'];
$active_mechanics = $conn->query("SELECT COUNT(DISTINCT mechanic_id) as count FROM repairs WHERE status IN ('in_progress', 'pending')")->fetch_assoc()['count'];
$completed_jobs = $conn->query("SELECT COUNT(*) as count FROM repairs WHERE status = 'completed'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanics Management - Admin</title>
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
            <div class="page-title"><i class="fas fa-users" style="color: var(--primary-color);"></i> Mechanics Management</div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <?php if($edit_mechanic): ?>
            <div class="edit-mode-banner">
                <div>
                    <i class="fas fa-edit text-warning"></i> 
                    <strong>Editing Mode:</strong> You are currently editing "<?php echo htmlspecialchars($edit_mechanic['name']); ?>"
                </div>
                <a href="mechanics.php" class="btn btn-secondary-custom" style="width: auto; padding: 5px 15px; margin: 0;">
                    <i class="fas fa-times"></i> Cancel Edit
                </a>
            </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted-custom small">TOTAL MECHANICS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $total_mechanics; ?></h2>
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
                                <span class="text-muted-custom small">ACTIVE MECHANICS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $active_mechanics; ?></h2>
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
                                <span class="text-muted-custom small">COMPLETED JOBS</span>
                                <h2 class="mb-0 mt-1" style="color: var(--text);"><?php echo $completed_jobs; ?></h2>
                            </div>
                            <div class="stat-icon" style="background: rgba(13, 202, 240, 0.15); color: #0dcaf0;">
                                <i class="fas fa-wrench fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <?php if(!$edit_mechanic): ?>
                <div class="col-md-5">
                    <div class="card-custom">
                        <h5><i class="fas fa-plus-circle" style="color: var(--primary-color);"></i> Add New Mechanic</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Full Name *</label>
                                <input type="text" name="name" class="form-control form-control-dark" required>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Email Address *</label>
                                <input type="email" name="email" class="form-control form-control-dark" required>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Contact Number *</label>
                                <input type="text" name="contact_number" class="form-control form-control-dark" required>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Specialization</label>
                                <select name="specialization" class="form-control form-select-dark">
                                    <option value="General">General Repairs</option>
                                    <option value="Engine">Engine Specialist</option>
                                    <option value="Electrical">Electrical Systems</option>
                                    <option value="Brakes">Brake Systems</option>
                                    <option value="Suspension">Suspension & Steering</option>
                                    <option value="Transmission">Transmission</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Address</label>
                                <textarea name="address" class="form-control form-control-dark" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Password *</label>
                                <input type="password" name="password" class="form-control form-control-dark" required>
                                <small class="text-muted-custom">Minimum 6 characters</small>
                            </div>
                            <button type="submit" name="add_mechanic" class="btn btn-primary-custom">
                                <i class="fas fa-save"></i> Add Mechanic
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($edit_mechanic): ?>
                <div class="col-md-5">
                    <div class="card-custom">
                        <h5><i class="fas fa-edit" style="color: #ffc107;"></i> Edit Mechanic</h5>
                        <form method="POST">
                            <input type="hidden" name="mechanic_id" value="<?php echo $edit_mechanic['id']; ?>">
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Full Name *</label>
                                <input type="text" name="edit_name" class="form-control form-control-dark" value="<?php echo htmlspecialchars($edit_mechanic['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Email Address *</label>
                                <input type="email" name="edit_email" class="form-control form-control-dark" value="<?php echo htmlspecialchars($edit_mechanic['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Contact Number *</label>
                                <input type="text" name="edit_contact_number" class="form-control form-control-dark" value="<?php echo htmlspecialchars($edit_mechanic['contact_number']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Specialization</label>
                                <select name="edit_specialization" class="form-control form-select-dark">
                                    <option value="General" <?php echo ($edit_mechanic['specialization'] ?? '') == 'General' ? 'selected' : ''; ?>>General Repairs</option>
                                    <option value="Engine" <?php echo ($edit_mechanic['specialization'] ?? '') == 'Engine' ? 'selected' : ''; ?>>Engine Specialist</option>
                                    <option value="Electrical" <?php echo ($edit_mechanic['specialization'] ?? '') == 'Electrical' ? 'selected' : ''; ?>>Electrical Systems</option>
                                    <option value="Brakes" <?php echo ($edit_mechanic['specialization'] ?? '') == 'Brakes' ? 'selected' : ''; ?>>Brake Systems</option>
                                    <option value="Suspension" <?php echo ($edit_mechanic['specialization'] ?? '') == 'Suspension' ? 'selected' : ''; ?>>Suspension & Steering</option>
                                    <option value="Transmission" <?php echo ($edit_mechanic['specialization'] ?? '') == 'Transmission' ? 'selected' : ''; ?>>Transmission</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Address</label>
                                <textarea name="edit_address" class="form-control form-control-dark" rows="2"><?php echo htmlspecialchars($edit_mechanic['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label style="color: var(--text-muted); font-size: 14px;">Password <small class="text-muted-custom">(Leave blank to keep current)</small></label>
                                <input type="password" name="edit_password" class="form-control form-control-dark">
                            </div>
                            <button type="submit" name="edit_mechanic" class="btn btn-warning-custom" style="width: 100%; padding: 8px 20px;">
                                <i class="fas fa-save"></i> Update Mechanic
                            </button>
                            <a href="mechanics.php" class="btn btn-secondary-custom">
                                <i class="fas fa-times"></i> Cancel Edit
                            </a>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <div class="col-md-<?php echo $edit_mechanic ? '7' : '7'; ?>">
                    <div class="card-custom">
                        <h5><i class="fas fa-list" style="color: var(--primary-color);"></i> Mechanics List</h5>
                        <div class="table-responsive table-responsive-custom">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Mechanic</th>
                                        <th>Contact</th>
                                        <th>Specialization</th>
                                        <th>Status</th>
                                        <th>Workload</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($mechanics->num_rows > 0): ?>
                                        <?php while($mechanic = $mechanics->fetch_assoc()): 
                                            $workload_sql = "SELECT COUNT(*) as count FROM repairs WHERE mechanic_id = ? AND status IN ('pending', 'in_progress')";
                                            $workload_stmt = $conn->prepare($workload_sql);
                                            $workload_stmt->bind_param("i", $mechanic['id']);
                                            $workload_stmt->execute();
                                            $workload = $workload_stmt->get_result()->fetch_assoc()['count'];
                                            
                                            $status_class = $workload == 0 ? 'status-idle' : ($workload < 3 ? 'status-active' : 'status-busy');
                                            $status_text = $workload == 0 ? 'Idle' : ($workload < 3 ? 'Active' : 'Busy');
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="mechanic-avatar"><?php echo strtoupper(substr($mechanic['name'], 0, 1)); ?></div>
                                                        <div>
                                                            <strong style="color: var(--text);"><?php echo htmlspecialchars($mechanic['name']); ?></strong><br>
                                                            <small class="text-muted-custom"><?php echo htmlspecialchars($mechanic['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="color: var(--text);"><?php echo htmlspecialchars($mechanic['contact_number']); ?></td>
                                                <td><span class="badge" style="background: var(--surface-raised); color: var(--text-muted);"><?php echo htmlspecialchars($mechanic['specialization'] ?? 'General'); ?></span></td>
                                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                                <td>
                                                    <?php if($workload > 0): ?>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="progress progress-custom">
                                                                <div class="progress-bar bg-<?php echo $workload >= 3 ? 'danger' : 'warning'; ?>" 
                                                                     style="width: <?php echo min($workload * 33, 100); ?>%;"></div>
                                                            </div>
                                                            <small class="text-muted-custom"><?php echo $workload; ?>/3 jobs</small>
                                                        </div>
                                                    <?php else: ?>
                                                        <small class="text-muted-custom">No active jobs</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="?edit=<?php echo $mechanic['id']; ?>" class="btn-warning-custom" title="Edit Mechanic">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $mechanic['id']; ?>" class="btn-danger-custom" 
                                                       onclick="return confirm('Delete this mechanic?')" title="Delete Mechanic">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="mechanic-performance.php?id=<?php echo $mechanic['id']; ?>" class="btn-info-custom" title="View Performance">
                                                        <i class="fas fa-chart-line"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted-custom" style="padding: 40px 0;">
                                                <i class="fas fa-users fa-2x d-block mb-2" style="color: var(--text-muted);"></i>
                                                No mechanics found. Add your first mechanic!
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
