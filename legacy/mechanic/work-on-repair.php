<?php
require_once __DIR__ . '/../bootstrap.php';
// work-on-repair.php - Redesigned with dark theme
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || $_SESSION['role'] != 'mechanic') {
    header('Location: ../customer/login.php');
    exit();
}

$conn = getConnection();
$repair_id = (int)($_GET['id'] ?? 0);
$mechanic_id = (int)$_SESSION['user_id'];

// NOTE: r.booking_id (int FK -> bookings.id) and b.booking_id (the "BK2026..." code)
// have the same column name. Selecting both with r.* / b.booking_id made the code
// string overwrite the numeric id, so the bookings UPDATE below matched nothing.
// Alias them explicitly so each one keeps its own meaning.
$sql = "SELECT r.*, r.booking_id AS booking_pk, b.booking_id AS booking_code,
        b.full_name, b.contact_number, b.motorcycle_model,
        b.problem_description, b.service_type, b.queue_no
        FROM repairs r
        JOIN bookings b ON r.booking_id = b.id
        WHERE r.id = ? AND r.mechanic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $repair_id, $mechanic_id);
$stmt->execute();
$repair = $stmt->get_result()->fetch_assoc();

if (!$repair) {
    header('Location: dashboard.php');
    exit();
}
$repair['booking_id'] = $repair['booking_code']; // keep the template below working (displays the BK code)
$booking_pk = (int)$repair['booking_pk'];

if (isset($_POST['update_status'])) {
    $allowed = ['pending', 'in_progress', 'completed'];
    $new_status = $_POST['status'] ?? '';
    if (!in_array($new_status, $allowed, true)) {
        echo "<script>alert('Invalid status.'); window.location.href='work-on-repair.php?id=$repair_id';</script>";
        exit();
    }

    $booking_status = ($new_status == 'completed') ? 'completed'
                    : (($new_status == 'in_progress') ? 'in_progress' : 'accepted');

    $conn->begin_transaction();
    try {
        // Update EVERY repair row of this booking (guards against duplicate rows), and
        // keep start/completion dates in sync with the status.
        $stmt = $conn->prepare("UPDATE repairs
            SET status = ?,
                start_date = CASE WHEN ? IN ('in_progress','completed') THEN COALESCE(start_date, CURDATE()) ELSE start_date END,
                completion_date = CASE WHEN ? = 'completed' THEN CURDATE() ELSE NULL END
            WHERE booking_id = ?");
        $stmt->bind_param("sssi", $new_status, $new_status, $new_status, $booking_pk);
        $stmt->execute();

        // Booking status is what the customer and admin pages read.
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $booking_status, $booking_pk);
        $stmt->execute();

        // Keep the booking total (used by admin dashboard/reports + customer) in sync.
        $stmt = $conn->prepare("UPDATE bookings SET estimated_cost =
            COALESCE((SELECT SUM(rp.price_at_time * rp.quantity) FROM repair_parts rp WHERE rp.repair_id = ?), 0)
            + COALESCE((SELECT labor_cost FROM repairs WHERE id = ?), 0)
            WHERE id = ?");
        $stmt->bind_param("iii", $repair_id, $repair_id, $booking_pk);
        $stmt->execute();

        $conn->commit();
        echo "<script>alert('Status updated successfully!'); window.location.href='work-on-repair.php?id=$repair_id';</script>";
    } catch (Throwable $e) {
        $conn->rollback();
        echo "<script>alert('Could not update status. Please try again.'); window.location.href='work-on-repair.php?id=$repair_id';</script>";
    }
    exit();
}

if (isset($_POST['add_note'])) {
    $note = $_POST['note'];
    $update_sql = "UPDATE repairs SET notes = CONCAT(IFNULL(notes, ''), ?) WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $date_note = "\n[" . date('Y-m-d H:i') . "] " . $note;
    $update_stmt->bind_param("si", $date_note, $repair_id);
    $update_stmt->execute();
    echo "<script>alert('Note added successfully!'); window.location.href='work-on-repair.php?id=$repair_id';</script>";
    exit();
}

$parts_sql = "SELECT rp.*, p.part_name, p.unit_price 
              FROM repair_parts rp 
              JOIN parts p ON rp.part_id = p.id 
              WHERE rp.repair_id = ?";
$stmt = $conn->prepare($parts_sql);
$stmt->bind_param("i", $repair_id);
$stmt->execute();
$used_parts = $stmt->get_result();

$parts_total = 0;
$parts_list = [];
while($part = $used_parts->fetch_assoc()) {
    $total = $part['quantity'] * $part['price_at_time'];
    $parts_total += $total;
    $parts_list[] = $part;
}
$used_parts->data_seek(0);

$grand_total = $parts_total + ($repair['labor_cost'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work on Repair - <?php echo $repair['booking_id']; ?></title>
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
            <a href="my-repairs.php" class="menu-item"><i class="fas fa-tools"></i><span>My Repairs</span></a>
            <div style="border-top: 1px solid var(--border-steel); margin: 20px 25px;"></div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <div class="page-title"><i class="fas fa-wrench" style="color: var(--primary-color);"></i> Working on #<?php echo $repair['booking_id']; ?></div>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row">
                <div class="col-md-12">
                    <nav class="breadcrumb-custom">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="my-repairs.php">My Repairs</a></li>
                            <li class="breadcrumb-item active">Repair #<?php echo $repair['booking_id']; ?></li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <!-- Repair Info -->
                    <div class="info-card">
                        <h5 class="mb-3"><i class="fas fa-info-circle" style="color: var(--primary-color);"></i> Repair Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p style="color: var(--text);"><strong style="color: var(--text-muted);">Booking ID:</strong> <?php echo $repair['booking_id']; ?></p>
                                <p style="color: var(--text);"><strong style="color: var(--text-muted);">Customer:</strong> <?php echo htmlspecialchars($repair['full_name']); ?></p>
                                <p style="color: var(--text);"><strong style="color: var(--text-muted);">Contact:</strong> <?php echo htmlspecialchars($repair['contact_number']); ?></p>
                                <p style="color: var(--text);"><strong style="color: var(--text-muted);">Queue #:</strong> <?php echo $repair['queue_no']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p style="color: var(--text);"><strong style="color: var(--text-muted);">Motorcycle:</strong> <?php echo htmlspecialchars($repair['motorcycle_model']); ?></p>
                                <p style="color: var(--text);"><strong style="color: var(--text-muted);">Service Type:</strong> <?php echo $repair['service_type']; ?></p>
                                <p style="color: var(--text);"><strong style="color: var(--text-muted);">Started:</strong> <?php echo $repair['start_date'] ?? 'Not started'; ?></p>
                            </div>
                        </div>
                        <div class="alert-info-custom mt-2">
                            <strong style="color: #6ea8fe;">Problem Description:</strong><br>
                            <?php echo nl2br(htmlspecialchars($repair['problem_description'])); ?>
                        </div>
                    </div>

                    <!-- Update Status -->
                    <div class="info-card">
                        <h5 class="mb-3"><i class="fas fa-tasks" style="color: #ffc107;"></i> Update Status</h5>
                        <form method="POST">
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <select name="status" class="form-control form-select-dark" required>
                                        <option value="pending" <?php echo $repair['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="in_progress" <?php echo $repair['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo $repair['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="update_status" class="btn btn-warning-custom w-100">Update</button>
                                </div>
                            </div>
                        </form>
                        <?php if($repair['status'] != 'completed'): ?>
                        <div class="progress mt-3 progress-custom">
                            <div class="progress-bar" style="width: <?php echo $repair['status'] == 'in_progress' ? '50%' : '25%'; ?>;">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Notes -->
                    <div class="info-card">
                        <h5 class="mb-3"><i class="fas fa-sticky-note" style="color: var(--text-muted);"></i> Repair Notes</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <textarea name="note" class="form-control form-control-dark" rows="3" placeholder="Add work notes, issues found, or special instructions..."></textarea>
                            </div>
                            <button type="submit" name="add_note" class="btn btn-secondary-custom">Add Note</button>
                        </form>
                        <?php if($repair['notes']): ?>
                            <hr style="border-color: var(--border-steel);">
                            <h6 style="color: var(--text-muted);">Notes History:</h6>
                            <div class="notes-history">
                                <?php echo nl2br(htmlspecialchars($repair['notes'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <!-- Parts Used -->
                    <div class="info-card">
                        <h5 class="mb-3"><i class="fas fa-microchip" style="color: #198754;"></i> Parts Used</h5>
                        <div class="table-responsive">
                            <table class="table table-parts">
                                <thead>
                                    <tr><th>Part</th><th>Qty</th><th>Price</th><th>Total</th></tr>
                                </thead>
                                <tbody>
                                    <?php while($part = $used_parts->fetch_assoc()): ?>
                                        <tr>
                                            <td style="color: var(--text);"><?php echo htmlspecialchars($part['part_name']); ?></td>
                                            <td style="color: var(--text);"><?php echo $part['quantity']; ?></td>
                                            <td style="color: var(--text-muted);">$<?php echo number_format($part['price_at_time'], 2); ?></td>
                                            <td style="color: var(--text);">$<?php echo number_format($part['quantity'] * $part['price_at_time'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if(count($parts_list) == 0): ?>
                                        <tr><td colspan="4" class="text-center" style="color: var(--text-muted); padding: 20px 0;">No parts added yet</td></tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" style="color: var(--text);">Parts Total</th>
                                        <th style="color: var(--text);">$<?php echo number_format($parts_total, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="alert-success-custom mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong style="color: #75b798;">Labor Cost:</strong>
                                    <h4 style="color: var(--text);">$<?php echo number_format($repair['labor_cost'] ?? 0, 2); ?></h4>
                                </div>
                                <div class="col-md-6 text-end">
                                    <strong style="color: #75b798;">Total Cost:</strong>
                                    <h4 style="color: var(--primary-color);">$<?php echo number_format($grand_total, 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="info-card">
                        <h5 class="mb-3"><i class="fas fa-lightbulb" style="color: #0dcaf0;"></i> Quick Tips</h5>
                        <ul class="list-unstyled mb-0 list-tips">
                            <li><i class="fas fa-check-circle"></i> Keep detailed notes for customer reference</li>
                            <li><i class="fas fa-check-circle"></i> Update status regularly</li>
                            <li><i class="fas fa-check-circle"></i> Mark as completed only when fully done</li>
                            <li><i class="fas fa-check-circle"></i> Review parts used before finishing</li>
                        </ul>
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
