<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$conn = getConnection();
$booking_id = $_GET['booking_id'] ?? 0;

// Get booking details
$sql = "SELECT * FROM bookings WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: jobs.php');
    exit();
}

// Get or create repair record
$sql = "SELECT * FROM repairs WHERE booking_id = ? ORDER BY (mechanic_id IS NULL), id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$repair = $stmt->get_result()->fetch_assoc();

if (!$repair) {
    $sql = "INSERT INTO repairs (booking_id, status) VALUES (?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $repair_id = $conn->insert_id;
} else {
    $repair_id = $repair['id'];
}

// Handle adding multiple parts via AJAX
if (isset($_POST['add_parts_ajax'])) {
    $parts_data = json_decode($_POST['parts_data'], true);
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    foreach ($parts_data as $item) {
        $part_id = $item['part_id'];
        $quantity = $item['quantity'];
        
        $sql = "SELECT * FROM parts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $part_id);
        $stmt->execute();
        $part = $stmt->get_result()->fetch_assoc();
        
        if ($part && $part['stock_quantity'] >= $quantity) {
            $sql = "INSERT INTO repair_parts (repair_id, part_id, quantity, price_at_time) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $repair_id, $part_id, $quantity, $part['unit_price']);
            $stmt->execute();
            
            $new_stock = $part['stock_quantity'] - $quantity;
            $sql = "UPDATE parts SET stock_quantity = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $new_stock, $part_id);
            $stmt->execute();
            
            $success_count++;
        } else {
            $error_count++;
            $errors[] = $part['part_name'] . " (Insufficient stock)";
        }
    }
    
    updateTotalCost($conn, $booking_id, $repair_id);
    
    $response = ['success' => $success_count, 'error' => $error_count, 'errors' => $errors];
    echo json_encode($response);
    exit();
}

// Handle adding single part (backward compatibility)
if (isset($_POST['add_part'])) {
    $part_id = $_POST['part_id'];
    $quantity = $_POST['quantity'];
    
    $sql = "SELECT * FROM parts WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $part_id);
    $stmt->execute();
    $part = $stmt->get_result()->fetch_assoc();
    
    if ($part && $part['stock_quantity'] >= $quantity) {
        $sql = "INSERT INTO repair_parts (repair_id, part_id, quantity, price_at_time) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiid", $repair_id, $part_id, $quantity, $part['unit_price']);
        $stmt->execute();
        
        $new_stock = $part['stock_quantity'] - $quantity;
        $sql = "UPDATE parts SET stock_quantity = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $new_stock, $part_id);
        $stmt->execute();
        
        updateTotalCost($conn, $booking_id, $repair_id);
        
        echo "<script>showNotification('Part added successfully!', 'success'); setTimeout(function() { window.location.href='repair-details.php?booking_id=$booking_id'; }, 1500);</script>";
    } else {
        echo "<script>showNotification('Insufficient stock for this part!', 'error');</script>";
    }
}

// Handle updating labor cost via AJAX
if (isset($_POST['update_labor_ajax'])) {
    $labor_cost = $_POST['labor_cost'];
    $sql = "UPDATE repairs SET labor_cost = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $labor_cost, $repair_id);
    $stmt->execute();
    
    updateTotalCost($conn, $booking_id, $repair_id);
    
    // Get updated totals
    $parts_total = getPartsTotal($conn, $repair_id);
    $labor = getLaborCost($conn, $repair_id);
    $total = $parts_total + $labor;
    
    echo json_encode(['parts_total' => $parts_total, 'labor_cost' => $labor, 'total_cost' => $total]);
    exit();
}

// Handle removing part
if (isset($_GET['remove_part'])) {
    $repair_part_id = $_GET['remove_part'];
    
    $sql = "SELECT rp.*, p.part_name FROM repair_parts rp 
            JOIN parts p ON rp.part_id = p.id 
            WHERE rp.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $repair_part_id);
    $stmt->execute();
    $rp = $stmt->get_result()->fetch_assoc();
    
    if ($rp) {
        $sql = "UPDATE parts SET stock_quantity = stock_quantity + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $rp['quantity'], $rp['part_id']);
        $stmt->execute();
        
        $sql = "DELETE FROM repair_parts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $repair_part_id);
        $stmt->execute();
        
        updateTotalCost($conn, $booking_id, $repair_id);
    }
    
    header("Location: repair-details.php?booking_id=$booking_id");
    exit();
}

function updateTotalCost($conn, $booking_id, $repair_id) {
    $parts_total = getPartsTotal($conn, $repair_id);
    $labor_cost = getLaborCost($conn, $repair_id);
    $total = $parts_total + $labor_cost;
    
    $sql = "UPDATE bookings SET estimated_cost = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $total, $booking_id);
    $stmt->execute();
}

function getPartsTotal($conn, $repair_id) {
    $sql = "SELECT COALESCE(SUM(price_at_time * quantity), 0) as parts_total FROM repair_parts WHERE repair_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $repair_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['parts_total'];
}

function getLaborCost($conn, $repair_id) {
    $sql = "SELECT COALESCE(labor_cost, 0) as labor_cost FROM repairs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $repair_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['labor_cost'];
}

// Get used parts
$sql = "SELECT rp.*, p.part_name, p.unit_price FROM repair_parts rp 
        JOIN parts p ON rp.part_id = p.id 
        WHERE rp.repair_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $repair_id);
$stmt->execute();
$used_parts = $stmt->get_result();

// Get available parts for dropdown
$available_parts = $conn->query("SELECT * FROM parts WHERE stock_quantity > 0 ORDER BY part_name");

// Calculate totals
$parts_total = 0;
$parts_list = [];
while($part = $used_parts->fetch_assoc()) {
    $total = $part['quantity'] * $part['price_at_time'];
    $parts_total += $total;
    $parts_list[] = $part;
}
$used_parts->data_seek(0);
$labor_cost = $repair['labor_cost'] ?? 0;
$total_cost = $parts_total + $labor_cost;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repair Details - <?php echo $booking['booking_id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-motorcycle fa-2x mb-2"></i>
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
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 25px;"></div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <div class="page-title">
                <i class="fas fa-wrench text-primary"></i> Repair Details - <?php echo $booking['booking_id']; ?>
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="jobs.php" class="btn btn-back">
                    <i class="fas fa-arrow-left me-2"></i> Back to Job List
                </a>
            </div>

            <!-- Customer & Service Info -->
            <div class="info-card">
                <div class="info-card-header bg-primary text-white">
                    <i class="fas fa-info-circle me-2"></i> Booking Information
                </div>
                <div class="info-card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Customer Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($booking['full_name']); ?></p>
                            <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($booking['contact_number']); ?></p>
                            <p class="mb-1"><strong>Motorcycle:</strong> <?php echo htmlspecialchars($booking['motorcycle_model']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Service Information</h6>
                            <p class="mb-1"><strong>Service Type:</strong> <?php echo htmlspecialchars($booking['service_type']); ?></p>
                            <p class="mb-1"><strong>Queue #:</strong> <span class="badge bg-secondary">#<?php echo $booking['queue_no']; ?></span></p>
                            <p class="mb-1"><strong>Status:</strong> 
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-light rounded">
                        <strong>Problem Description:</strong><br>
                        <?php echo nl2br(htmlspecialchars($booking['problem_description'])); ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Parts Used Section -->
                <div class="col-md-7">
                    <div class="info-card">
                        <div class="info-card-header bg-success text-white">
                            <i class="fas fa-microchip me-2"></i> Parts Used
                        </div>
                        <div class="info-card-body">
                            <!-- Multiple Part Selection -->
                            <div class="mb-4">
                                <label class="fw-bold mb-2">Add Multiple Parts</label>
                                <div id="parts-list">
                                    <div class="part-row d-flex gap-2">
                                        <select class="form-control form-control-sm part-select" style="flex: 2;">
                                            <option value="">Select Part</option>
                                            <?php 
                                            $all_parts = $conn->query("SELECT * FROM parts WHERE stock_quantity > 0 ORDER BY part_name");
                                            while($part = $all_parts->fetch_assoc()): ?>
                                                <option value="<?php echo $part['id']; ?>" data-price="<?php echo $part['unit_price']; ?>" data-stock="<?php echo $part['stock_quantity']; ?>" data-name="<?php echo $part['part_name']; ?>">
                                                    <?php echo $part['part_name']; ?> (Stock: <?php echo $part['stock_quantity']; ?>) - $<?php echo $part['unit_price']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="number" class="form-control form-control-sm part-quantity" placeholder="Qty" style="width: 80px;" min="1" value="1">
                                        <button type="button" class="btn btn-sm btn-danger remove-part-row">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-secondary" id="add-part-row">
                                        <i class="fas fa-plus"></i> Add Another Part
                                    </button>
                                    <button type="button" class="btn btn-add-multiple ms-2" id="submit-multiple-parts">
                                        <i class="fas fa-check"></i> Add Selected Parts
                                    </button>
                                </div>
                            </div>

                            <hr>

                            <div class="table-responsive">
                                <table class="table table-sm" id="parts-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Part</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($parts_list) > 0): ?>
                                            <?php foreach($parts_list as $part): 
                                                $total = $part['quantity'] * $part['price_at_time'];
                                            ?>
                                                <tr data-part-id="<?php echo $part['id']; ?>" data-part-name="<?php echo $part['part_name']; ?>">
                                                    <td><?php echo htmlspecialchars($part['part_name']); ?></td>
                                                    <td><?php echo $part['quantity']; ?></td>
                                                    <td>$<?php echo number_format($part['price_at_time'], 2); ?></td>
                                                    <td class="part-total">$<?php echo number_format($total, 2); ?></td>
                                                    <td>
                                                        <a href="?booking_id=<?php echo $booking_id; ?>&remove_part=<?php echo $part['id']; ?>" 
                                                           class="btn btn-sm btn-danger remove-part-link" onclick="return confirm('Remove this part?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr class="no-parts-row">
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    <i class="fas fa-box-open mb-2 d-block"></i>No parts added yet
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot class="table-info">
                                        <tr>
                                            <th colspan="3">Parts Total</th>
                                            <th id="parts-total-display">$<?php echo number_format($parts_total, 2); ?></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Labor & Cost Section -->
                <div class="col-md-5">
                    <div class="info-card">
                        <div class="info-card-header bg-warning text-dark">
                            <i class="fas fa-clock me-2"></i> Labor & Cost
                        </div>
                        <div class="info-card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Labor Cost ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" id="labor-cost-input" class="form-control" step="0.01" 
                                           value="<?php echo $labor_cost; ?>">
                                    <button type="button" id="update-labor-btn" class="btn btn-update">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                </div>
                            </div>

                            <hr>

                            <div class="bg-light p-3 rounded">
                                <h6 class="mb-3">Cost Summary</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Parts Cost:</span>
                                    <strong id="summary-parts-cost">$<?php echo number_format($parts_total, 2); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Labor Cost:</span>
                                    <strong id="summary-labor-cost">$<?php echo number_format($labor_cost, 2); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between pt-2 border-top">
                                    <span class="fw-bold">Total Cost:</span>
                                    <span class="fw-bold text-success fs-5" id="summary-total-cost">$<?php echo number_format($total_cost, 2); ?></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <a href="../generate-invoice.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                                   class="btn btn-success w-100" target="_blank">
                                    <i class="fas fa-file-invoice-dollar me-2"></i> Generate Invoice
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Update Status Card -->
                    <div class="info-card">
                        <div class="info-card-header bg-info text-white">
                            <i class="fas fa-tasks me-2"></i> Update Status
                        </div>
                        <div class="info-card-body">
                            <form method="POST" action="jobs.php">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <div class="row g-2">
                                    <div class="col-md-8">
                                        <select name="status" class="form-control">
                                            <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="accepted" <?php echo $booking['status'] == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                            <option value="in_progress" <?php echo $booking['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" name="update_status" class="btn btn-info w-100 text-white">
                                            <i class="fas fa-save"></i> Update
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <?php if($booking['status'] != 'completed'): ?>
                            <div class="progress mt-3">
                                <div class="progress-bar bg-info" style="width: <?php 
                                    echo $booking['status'] == 'pending' ? '25' : ($booking['status'] == 'accepted' ? '50' : '75'); 
                                ?>%;">
                                    <?php echo ucfirst($booking['status']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast Container -->
    <div id="notification-container"></div>

    <script>
        // Show notification function
        function showNotification(message, type = 'success') {
            const container = document.getElementById('notification-container');
            const toast = document.createElement('div');
            toast.className = 'notification-toast alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Update totals on page
        function updateTotals() {
            let partsTotal = 0;
            document.querySelectorAll('#parts-table tbody tr:not(.no-parts-row)').forEach(row => {
                const totalCell = row.querySelector('.part-total');
                if (totalCell) {
                    let total = parseFloat(totalCell.textContent.replace('$', '').replace(',', ''));
                    partsTotal += isNaN(total) ? 0 : total;
                }
            });
            
            const laborCost = parseFloat(document.getElementById('labor-cost-input').value) || 0;
            const totalCost = partsTotal + laborCost;
            
            document.getElementById('parts-total-display').textContent = '$' + partsTotal.toFixed(2);
            document.getElementById('summary-parts-cost').textContent = '$' + partsTotal.toFixed(2);
            document.getElementById('summary-labor-cost').textContent = '$' + laborCost.toFixed(2);
            document.getElementById('summary-total-cost').textContent = '$' + totalCost.toFixed(2);
        }

        // Add new part row
        document.getElementById('add-part-row')?.addEventListener('click', function() {
            const partsList = document.getElementById('parts-list');
            const newRow = document.createElement('div');
            newRow.className = 'part-row d-flex gap-2';
            newRow.innerHTML = `
                <select class="form-control form-control-sm part-select" style="flex: 2;">
                    <option value="">Select Part</option>
                    <?php 
                    $all_parts2 = $conn->query("SELECT * FROM parts WHERE stock_quantity > 0 ORDER BY part_name");
                    while($part = $all_parts2->fetch_assoc()): ?>
                        <option value="<?php echo $part['id']; ?>" data-price="<?php echo $part['unit_price']; ?>" data-stock="<?php echo $part['stock_quantity']; ?>" data-name="<?php echo $part['part_name']; ?>">
                            <?php echo addslashes($part['part_name']); ?> (Stock: <?php echo $part['stock_quantity']; ?>) - $<?php echo $part['unit_price']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="number" class="form-control form-control-sm part-quantity" placeholder="Qty" style="width: 80px;" min="1" value="1">
                <button type="button" class="btn btn-sm btn-danger remove-part-row">
                    <i class="fas fa-times"></i>
                </button>
            `;
            partsList.appendChild(newRow);
            
            newRow.querySelector('.remove-part-row').addEventListener('click', function() {
                newRow.remove();
            });
        });

        // Remove part row event listener
        document.querySelectorAll('.remove-part-row').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.part-row').remove();
            });
        });

        // Submit multiple parts
        document.getElementById('submit-multiple-parts')?.addEventListener('click', function() {
            const partsData = [];
            const partRows = document.querySelectorAll('#parts-list .part-row');
            
            for (let row of partRows) {
                const select = row.querySelector('.part-select');
                const quantity = row.querySelector('.part-quantity').value;
                
                if (select.value && quantity > 0) {
                    partsData.push({
                        part_id: select.value,
                        quantity: quantity
                    });
                }
            }
            
            if (partsData.length === 0) {
                showNotification('Please select at least one part', 'error');
                return;
            }
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'add_parts_ajax=1&parts_data=' + JSON.stringify(partsData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success > 0) {
                    showNotification(data.success + ' part(s) added successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                }
                if (data.error > 0) {
                    showNotification(data.error + ' part(s) failed: ' + data.errors.join(', '), 'error');
                }
            });
        });

        // Update labor cost via AJAX
        document.getElementById('update-labor-btn')?.addEventListener('click', function() {
            const laborCost = document.getElementById('labor-cost-input').value;
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'update_labor_ajax=1&labor_cost=' + laborCost
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('summary-parts-cost').textContent = '$' + parseFloat(data.parts_total).toFixed(2);
                document.getElementById('summary-labor-cost').textContent = '$' + parseFloat(data.labor_cost).toFixed(2);
                document.getElementById('summary-total-cost').textContent = '$' + parseFloat(data.total_cost).toFixed(2);
                document.getElementById('parts-total-display').textContent = '$' + parseFloat(data.parts_total).toFixed(2);
                showNotification('Labor cost updated!', 'success');
            });
        });

        // Initial update
        updateTotals();

        // Mobile sidebar toggle
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
