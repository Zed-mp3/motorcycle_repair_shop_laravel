<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Service - MotoRepair Pro</title>
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
            <a href="book-service.php" class="menu-item active"><i class="fas fa-calendar-alt"></i><span>Book Service</span></a>
            <a href="track-status.php" class="menu-item"><i class="fas fa-search"></i><span>Track Status</span></a>
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
            <div class="page-title"><i class="fas fa-calendar-alt text-primary"></i> Book a Service</div>
            <div class="user-info">
                <span><?php echo isset($_SESSION['user_id']) ? 'Welcome, ' . htmlspecialchars($_SESSION['user_name']) : 'Guest User'; ?></span>
                <div class="user-avatar"><?php echo isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'G'; ?></div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="booking-card">
                        <h4 class="text-center mb-4">
                            <i class="fas fa-motorcycle text-primary"></i> Schedule Your Repair
                        </h4>
                        <p class="text-center text-muted mb-4">Fill out the form below and we'll contact you to confirm your appointment</p>
                        
                        <form action="../process-booking.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number *</label>
                                    <input type="text" name="contact_number" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Motorcycle Model *</label>
                                <input type="text" name="motorcycle_model" class="form-control" placeholder="e.g., Honda Wave 100, Yamaha Mio i125, Kawasaki Barako" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Service Type *</label>
                                <select name="service_type" class="form-select" required>
                                    <option value="">Select Service</option>
                                    <option value="Engine Repair">ðŸ”§ Engine Repair</option>
                                    <option value="Oil Change">ðŸ›¢ï¸ Oil Change</option>
                                    <option value="Electrical Fix">âš¡ Electrical Fix</option>
                                    <option value="Brake Repair">ðŸ›‘ Brake Repair</option>
                                    <option value="Tire Change">ðŸš² Tire Change</option>
                                    <option value="General Maintenance">ðŸ”© General Maintenance</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Problem Description</label>
                                <textarea name="problem_description" class="form-control" rows="3" placeholder="Describe the issue with your motorcycle in detail..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Preferred Date *</label>
                                <input type="date" name="preferred_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> You will receive a confirmation email with your booking ID and queue number after submission.
                            </div>
                            
                            <button type="submit" class="btn btn-submit btn-primary w-100">
                                <i class="fas fa-paper-plane"></i> Submit Booking
                            </button>
                        </form>
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
