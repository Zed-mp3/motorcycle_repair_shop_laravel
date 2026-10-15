<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$conn = getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Status - MotoRepair Pro</title>
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
            <a href="track-status.php" class="menu-item active"><i class="fas fa-search"></i><span>Track Status</span></a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="booking-status.php" class="menu-item"><i class="fas fa-history"></i><span>My Bookings</span></a>
                <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 25px;"></div>
                <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            <?php else: ?>
                <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 25px;"></div>
                <a href="../login.php" class="menu-item"><i class="fas fa-sign-in-alt"></i><span>Login / Register</span></a>
            <?php endif; ?>
        </div>
    </div>

    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>

    <div class="main-content" id="mainContent">
        <div class="top-navbar">
            <div class="page-title"><i class="fas fa-search text-primary"></i> Track Your Repair</div>
            <div class="user-info">
                <span><?php echo isset($_SESSION['user_id']) ? 'Welcome, ' . htmlspecialchars($_SESSION['user_name']) : 'Guest User'; ?></span>
                <div class="user-avatar"><?php echo isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'G'; ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="track-card">
                        <h4 class="text-center mb-4">
                            <i class="fas fa-motorcycle text-primary"></i> Track Your Repair Status
                        </h4>
                        
                        <form method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" name="booking_id" class="form-control" placeholder="Enter your Booking ID (e.g., BK202412011234)" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Track
                                </button>
                            </div>
                        </form>

                        <?php if(isset($_GET['booking_id'])): 
                            $booking_id = mysqli_real_escape_string($conn, $_GET['booking_id']);
                            $sql = "SELECT b.*, r.labor_cost, r.id as repair_id,
                                    (SELECT SUM(price_at_time * quantity) FROM repair_parts WHERE repair_id = r.id) as parts_cost
                                    FROM bookings b 
                                    LEFT JOIN repairs r ON b.id = r.booking_id 
                                    WHERE b.booking_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("s", $booking_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $booking = $result->fetch_assoc();
                            
                            if($booking):
                                $statuses = ['pending' => 25, 'accepted' => 50, 'in_progress' => 75, 'completed' => 100];
                                $progress = $statuses[$booking['status']] ?? 0;
                                $total = ($booking['labor_cost'] ?? 0) + ($booking['parts_cost'] ?? 0);
                        ?>
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Booking ID: <?php echo htmlspecialchars($booking['booking_id']); ?></h5>
                                        <p class="mb-0">Queue Number: <strong>#<?php echo $booking['queue_no']; ?></strong></p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <p class="mb-0">Booking Date: <?php echo date('F d, Y', strtotime($booking['created_at'])); ?></p>
                                        <p class="mb-0">Preferred Date: <?php echo date('F d, Y', strtotime($booking['preferred_date'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="fw-bold mb-2">Repair Progress:</label>
                                <div class="progress">
                                    <div class="progress-bar bg-<?php echo $progress == 100 ? 'success' : ($progress >= 50 ? 'info' : 'warning'); ?>" 
                                         role="progressbar" style="width: <?php echo $progress; ?>%">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="bg-light p-3 rounded">
                                        <h6><i class="fas fa-user"></i> Customer Details</h6>
                                        <p class="mb-0"><strong>Name:</strong> <?php echo htmlspecialchars($booking['full_name']); ?></p>
                                        <p class="mb-0"><strong>Contact:</strong> <?php echo htmlspecialchars($booking['contact_number']); ?></p>
                                        <p class="mb-0"><strong>Motorcycle:</strong> <?php echo htmlspecialchars($booking['motorcycle_model']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="bg-light p-3 rounded">
                                        <h6><i class="fas fa-wrench"></i> Repair Details</h6>
                                        <p class="mb-0"><strong>Service:</strong> <?php echo htmlspecialchars($booking['service_type']); ?></p>
                                        <p class="mb-0"><strong>Issue:</strong> <?php echo htmlspecialchars($booking['problem_description']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if($total > 0): ?>
                            <div class="alert alert-success">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-0">Total Amount: $<?php echo number_format($total, 2); ?></h5>
                                        <small>Parts: $<?php echo number_format($booking['parts_cost'] ?? 0, 2); ?> | Labor: $<?php echo number_format($booking['labor_cost'] ?? 0, 2); ?></small>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <button onclick="window.open('../generate-invoice.php?booking_id=<?php echo $booking['booking_id']; ?>', '_blank')" class="btn btn-success">
                                            <i class="fas fa-file-invoice"></i> Invoice
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="status-timeline">
                                <div class="timeline-item">
                                    <div class="timeline-dot <?php echo $booking['status'] != 'pending' ? 'completed' : ($booking['status'] == 'pending' ? 'active' : ''); ?>"></div>
                                    <div class="timeline-content">
                                        <strong>Booking Confirmed</strong>
                                        <p class="text-muted mb-0 small">Queue #<?php echo $booking['queue_no']; ?> assigned</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-dot <?php echo $booking['status'] == 'accepted' || $booking['status'] == 'in_progress' || $booking['status'] == 'completed' ? 'completed' : ''; ?>"></div>
                                    <div class="timeline-content">
                                        <strong>Job Accepted</strong>
                                        <p class="text-muted mb-0 small">Your repair has been accepted by our shop</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-dot <?php echo $booking['status'] == 'in_progress' || $booking['status'] == 'completed' ? 'completed' : ''; ?>"></div>
                                    <div class="timeline-content">
                                        <strong>Repair In Progress</strong>
                                        <p class="text-muted mb-0 small">Our mechanics are working on your motorcycle</p>
                                    </div>
                                </div>
                                <div class="timeline-item">
                                    <div class="timeline-dot <?php echo $booking['status'] == 'completed' ? 'completed' : ''; ?>"></div>
                                    <div class="timeline-content">
                                        <strong>Repair Completed</strong>
                                        <p class="text-muted mb-0 small">Your motorcycle is ready for pickup!</p>
                                    </div>
                                </div>
                            </div>
                            
                        <?php else: ?>
                            <div class="alert alert-danger text-center">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                                <strong>Booking ID not found!</strong><br>
                                Please check your Booking ID and try again.
                            </div>
                        <?php endif; ?>
                        <?php endif; ?>
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
