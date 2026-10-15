<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get all bookings for the logged-in customer
$sql = "SELECT b.*, 
        r.id as repair_id, 
        r.status as repair_status,
        r.labor_cost,
        r.start_date,
        r.completion_date,
        (SELECT SUM(price_at_time * quantity) FROM repair_parts WHERE repair_id = r.id) as parts_cost
        FROM bookings b 
        LEFT JOIN repairs r ON r.id = (SELECT r2.id FROM repairs r2 WHERE r2.booking_id = b.id ORDER BY (r2.mechanic_id IS NULL), r2.id DESC LIMIT 1) 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    COALESCE(SUM(estimated_cost), 0) as total_spent
    FROM bookings 
    WHERE user_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - MotoRepair Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/portal.css">
    
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-motorcycle fa-2x mb-2"></i>
            <h3>MotoRepair Pro</h3>
            <p>Customer Portal</p>
        </div>
        <div class="sidebar-menu">
            <a href="home.php" class="menu-item"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="book-service.php" class="menu-item"><i class="fas fa-calendar-alt"></i><span>Book Service</span></a>
            <a href="track-status.php" class="menu-item"><i class="fas fa-search"></i><span>Track Status</span></a>
            <a href="booking-status.php" class="menu-item active"><i class="fas fa-history"></i><span>My Bookings</span></a>
            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 25px;"></div>
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <div class="page-title"><i class="fas fa-history text-primary"></i> My Bookings</div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <h3 class="text-primary mb-0"><?php echo $stats['total'] ?? 0; ?></h3>
                        <small class="text-muted">Total Bookings</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <h3 class="text-warning mb-0"><?php echo $stats['pending'] ?? 0; ?></h3>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <h3 class="text-info mb-0"><?php echo ($stats['accepted'] ?? 0) + ($stats['in_progress'] ?? 0); ?></h3>
                        <small class="text-muted">In Progress</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <h3 class="text-success mb-0"><?php echo $stats['completed'] ?? 0; ?></h3>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="mb-4">
                <span class="filter-btn active" data-filter="all">All</span>
                <span class="filter-btn" data-filter="pending">Pending</span>
                <span class="filter-btn" data-filter="accepted">Accepted</span>
                <span class="filter-btn" data-filter="in_progress">In Progress</span>
                <span class="filter-btn" data-filter="completed">Completed</span>
                <span class="filter-btn" data-filter="cancelled">Cancelled</span>
            </div>

            <!-- Bookings List -->
            <?php if($bookings->num_rows > 0): ?>
                <?php while($booking = $bookings->fetch_assoc()): 
                    $status_class = '';
                    switch($booking['status']) {
                        case 'pending': $status_class = 'status-pending'; break;
                        case 'accepted': $status_class = 'status-accepted'; break;
                        case 'in_progress': $status_class = 'status-in_progress'; break;
                        case 'completed': $status_class = 'status-completed'; break;
                        case 'cancelled': $status_class = 'status-cancelled'; break;
                    }
                    
                    $progress_percent = 0;
                    switch($booking['status']) {
                        case 'pending': $progress_percent = 25; break;
                        case 'accepted': $progress_percent = 50; break;
                        case 'in_progress': $progress_percent = 75; break;
                        case 'completed': $progress_percent = 100; break;
                    }
                    
                    $total_cost = ($booking['labor_cost'] ?? 0) + ($booking['parts_cost'] ?? 0);
                ?>
                <div class="booking-card" data-status="<?php echo $booking['status']; ?>">
                    <div class="booking-header" onclick="toggleBooking(<?php echo $booking['id']; ?>)">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <strong><i class="fas fa-ticket-alt"></i> <?php echo $booking['booking_id']; ?></strong>
                            </div>
                            <div class="col-md-3">
                                <i class="fas fa-motorcycle"></i> <?php echo htmlspecialchars($booking['motorcycle_model']); ?>
                            </div>
                            <div class="col-md-2">
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                </span>
                            </div>
                            <div class="col-md-2">
                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                            </div>
                            <div class="col-md-2 text-end">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div class="progress">
                                    <div class="progress-bar bg-<?php echo $progress_percent == 100 ? 'success' : 'primary'; ?>" 
                                         style="width: <?php echo $progress_percent; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="booking-body" id="booking-<?php echo $booking['id']; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle"></i> Service Details</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td width="40%"><strong>Service Type:</strong></td>
                                        <td><?php echo htmlspecialchars($booking['service_type']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Problem:</strong></td>
                                        <td><?php echo htmlspecialchars($booking['problem_description']) ?: 'Not specified'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Preferred Date:</strong></td>
                                        <td><?php echo date('F d, Y', strtotime($booking['preferred_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Queue Number:</strong></td>
                                        <td><span class="badge bg-secondary">#<?php echo $booking['queue_no']; ?></span></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-chart-line"></i> Progress Timeline</h6>
                                <div class="timeline small">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>📅 Booked</span>
                                        <span>✅ Accepted</span>
                                        <span>🔧 In Progress</span>
                                        <span>🎉 Completed</span>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $progress_percent; ?>%">
                                            <?php echo $progress_percent; ?>%
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if($booking['start_date']): ?>
                                <div class="mt-3">
                                    <small><i class="fas fa-play-circle text-success"></i> Started: <?php echo date('M d, Y', strtotime($booking['start_date'])); ?></small><br>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($booking['completion_date']): ?>
                                <div>
                                    <small><i class="fas fa-check-circle text-success"></i> Completed: <?php echo date('M d, Y', strtotime($booking['completion_date'])); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-microchip"></i> Parts Used</h6>
                                <?php
                                $parts_sql = "SELECT rp.*, p.part_name 
                                              FROM repair_parts rp 
                                              JOIN parts p ON rp.part_id = p.id 
                                              WHERE rp.repair_id = ?";
                                $parts_stmt = $conn->prepare($parts_sql);
                                $parts_stmt->bind_param("i", $booking['repair_id']);
                                $parts_stmt->execute();
                                $parts_result = $parts_stmt->get_result();
                                
                                if($parts_result->num_rows > 0):
                                ?>
                                <table class="table table-sm">
                                    <thead>
                                        <tr><th>Part</th><th>Qty</th><th>Price</th><th>Total</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while($part = $parts_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($part['part_name']); ?></td>
                                            <td><?php echo $part['quantity']; ?></td>
                                            <td>$<?php echo number_format($part['price_at_time'], 2); ?></td>
                                            <td>$<?php echo number_format($part['quantity'] * $part['price_at_time'], 2); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <p class="text-muted">No parts recorded yet</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6><i class="fas fa-calculator"></i> Cost Summary</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <td width="50%"><strong>Parts Cost:</strong></td>
                                                <td class="text-end">$<?php echo number_format($booking['parts_cost'] ?? 0, 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Labor Cost:</strong></td>
                                                <td class="text-end">$<?php echo number_format($booking['labor_cost'] ?? 0, 2); ?></td>
                                            </tr>
                                            <tr class="table-active">
                                                <td><strong>Total Amount:</strong></td>
                                                <td class="text-end"><strong>$<?php echo number_format($total_cost, 2); ?></strong></td>
                                            </tr>
                                        </table>
                                        <?php if($booking['status'] == 'completed'): ?>
                                        <button onclick="window.open('../generate-invoice.php?booking_id=<?php echo $booking['booking_id']; ?>', '_blank')" 
                                                class="btn btn-success btn-sm w-100">
                                            <i class="fas fa-file-invoice"></i> Download Invoice
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if($booking['status'] == 'pending'): ?>
                        <div class="mt-3 text-end">
                            <button onclick="cancelBooking(<?php echo $booking['id']; ?>, '<?php echo $booking['booking_id']; ?>')" 
                                    class="btn btn-danger btn-sm">
                                <i class="fas fa-times"></i> Cancel Booking
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-4x text-muted mb-3"></i>
                    <h4>No Bookings Yet</h4>
                    <p class="text-muted">You haven't made any service bookings yet.</p>
                    <a href="book-service.php" class="btn btn-primary">Book Your First Service</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle booking details
        function toggleBooking(id) {
            const element = document.getElementById('booking-' + id);
            element.classList.toggle('show');
        }
        
        // Filter bookings
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active state
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter bookings
                document.querySelectorAll('.booking-card').forEach(card => {
                    if (filter === 'all' || card.getAttribute('data-status') === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
        
        // Cancel booking
        function cancelBooking(id, bookingId) {
            if (confirm('Are you sure you want to cancel booking ' + bookingId + '?')) {
                window.location.href = '../cancel-booking.php?id=' + id;
            }
        }
        
        // Mobile sidebar toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        if(menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
        
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
        
        // Expand first booking by default
        const firstBooking = document.querySelector('.booking-body');
        if(firstBooking) {
            firstBooking.classList.add('show');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
