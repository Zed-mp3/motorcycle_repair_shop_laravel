<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MotoRepair Pro - Professional Motorcycle Repair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-motorcycle fa-2x mb-2"></i>
            <h3>MotoRepair Pro</h3>
            <p>Customer Portal</p>
        </div>
        <div class="sidebar-menu">
            <a href="home.php" class="menu-item active">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="book-service.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Book Service</span>
            </a>
            <a href="track-status.php" class="menu-item">
                <i class="fas fa-search"></i>
                <span>Track Status</span>
            </a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="booking-status.php" class="menu-item">
                    <i class="fas fa-history"></i>
                    <span>My Bookings</span>
                </a>
                <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 25px;"></div>
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 25px;"></div>
                <a href="../login.php" class="menu-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login / Register</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="page-title">
                <i class="fas fa-motorcycle text-primary"></i> Welcome to MotoRepair Pro
            </div>
            <div class="user-info">
                <span>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    <?php else: ?>
                        Guest User
                    <?php endif; ?>
                </span>
                <div class="user-avatar" onclick="toggleDropdown()">
                    <?php echo isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'G'; ?>
                </div>
            </div>
        </div>

        <!-- Hero Section -->
        <div class="hero-section">
            <div class="container">
                <h1>Expert Motorcycle Repair Services</h1>
                <p class="lead">Fast, Reliable, and Affordable repairs for all motorcycle brands</p>
                <a href="book-service.php" class="btn btn-light btn-lg mt-3">
                    <i class="fas fa-calendar-alt"></i> Book a Service Now
                </a>
            </div>
        </div>

        <div class="container mt-5">
            <div class="row">
                <div class="col-md-12 text-center mb-4">
                    <h2>Our Services</h2>
                    <p>Professional repair and maintenance services for your motorcycle</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-engine"></i>
                        </div>
                        <h5>Engine Repair</h5>
                        <p>Complete engine overhaul, top-end rebuild, and performance tuning</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-oil-can"></i>
                        </div>
                        <h5>Oil Change</h5>
                        <p>Regular oil change and filter replacement for engine longevity</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h5>Electrical Fix</h5>
                        <p>Wiring repair, battery replacement, and lighting system fixes</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-car-side"></i>
                        </div>
                        <h5>Brake Repair</h5>
                        <p>Brake pad replacement, caliper repair, and brake fluid service</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <h5>Tire Change</h5>
                        <p>New tire fitting, balancing, and tube replacement</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h5>General Maintenance</h5>
                        <p>Complete inspection, chain adjustment, and preventive maintenance</p>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="service-card" style="background: linear-gradient(135deg, #e67e22, #f39c12); color: white;">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h3>Why Choose MotoRepair Pro?</h3>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check-circle"></i> Certified mechanics with 10+ years experience</li>
                                    <li><i class="fas fa-check-circle"></i> Genuine spare parts guaranteed</li>
                                    <li><i class="fas fa-check-circle"></i> Quick turnaround time</li>
                                    <li><i class="fas fa-check-circle"></i> Competitive pricing</li>
                                    <li><i class="fas fa-check-circle"></i> 30-day warranty on all repairs</li>
                                </ul>
                            </div>
                            <div class="col-md-6 text-center">
                                <h3>Opening Hours</h3>
                                <p>
                                    Monday - Friday: 8:00 AM - 7:00 PM<br>
                                    Saturday: 9:00 AM - 5:00 PM<br>
                                    Sunday: Closed<br>
                                    <strong>Emergency repairs available 24/7</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="container text-center">
                <p>&copy; 2024 MotoRepair Pro. All rights reserved.</p>
                <p>
                    <small>
                        <i class="fas fa-phone"></i> (555) 123-4567 &nbsp;|&nbsp;
                        <i class="fas fa-envelope"></i> info@motorepair.com
                    </small>
                </p>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const avatar = document.querySelector('.user-avatar');
            if (dropdown && !avatar.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Mobile sidebar toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
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
