<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['role'] == 'mechanic') {
        header('Location: mechanic/dashboard.php');
    } else {
        header('Location: customer/home.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        $conn = getConnection();
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Remember me functionality (7 days)
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 7), "/");
                // Store token in database (you'll need to add remember_token column)
                $update_sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $token, $user['id']);
                $update_stmt->execute();
            }
            
            // Redirect based on role
            if ($user['role'] == 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($user['role'] == 'mechanic') {
                header('Location: mechanic/dashboard.php');
            } else {
                header('Location: customer/home.php');
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
    }
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $contact = trim($_POST['contact']);
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($contact)) {
        $error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $conn = getConnection();
        
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email already registered. Please login instead.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, email, password, contact_number, role) VALUES (?, ?, ?, ?, 'customer')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $contact);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Please login with your credentials.";
                // Clear form fields
                $_POST = array();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

// Handle forgot password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['reset_email']);
    
    if (empty($email)) {
        $error = "Please enter your email address";
    } else {
        $conn = getConnection();
        $sql = "SELECT id, name FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
           // Generate reset token
$token = bin2hex(random_bytes(32));

// Let MySQL compute the expiry so PHP and MySQL timezones can never disagree
$update_sql = "UPDATE users SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $token, $user['id']);
$update_stmt->execute();
            
            // Build the reset link using Laravel's URL so it works with the wrap
$reset_link = url('/reset-password.php') . '?token=' . urlencode($token);

// Send the email
require_once __DIR__ . '/send-notification.php';
if (sendPasswordReset($email, $user['name'], $reset_link)) {
    $success = "A password reset link has been sent to <strong>" . htmlspecialchars($email) . "</strong>. Please check your inbox.";
} else {
    $error = "We couldn't send the reset email right now. Please try again later.";
}
        } else {
            $error = "Email address not found";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - MotoRepair Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-image: url('assets/pic/Screenshot 2026-07-20 154440.png');
            background-repeat: repeat;
            background-size: 400px 400px;
            background-attachment: fixed;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(20, 20, 20, 0.55);
            z-index: -1;
        }
        .auth-container {
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            text-align: center;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 12px 24px;
        }
        .nav-tabs .nav-link.active {
            color: #e67e22;
            border-bottom: 3px solid #e67e22;
            background: transparent;
        }
        .btn-primary {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #d35400, #e67e22);
            transform: translateY(-2px);
            transition: all 0.3s;
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
        }
        .form-control:focus {
            border-color: #e67e22;
            box-shadow: 0 0 0 0.2rem rgba(230,126,34,0.25);
        }
        .alert {
            border-radius: 10px;
        }
        .brand-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .role-badge {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 1000;
        }
        .demo-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 12px;
        }
        @media (max-width: 768px) {
            .auth-container {
                margin-top: 20px;
            }
            .card-header h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container auth-container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-motorcycle brand-icon"></i>
                        <h3 class="mb-0">MotoRepair Pro</h3>
                        <p class="mb-0">Professional Motorcycle Repair Shop</p>
                    </div>
                    <div class="card-body">
                        <!-- Tabs -->
                        <ul class="nav nav-tabs mb-4" id="authTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                                    <i class="fas fa-user-plus"></i> Register
                                </button>
                            </li>
                        </ul>

                        <!-- Alert Messages -->
                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Tab Content -->
                        <div class="tab-content">
                            <!-- Login Form -->
                            <div class="tab-pane fade show active" id="login" role="tabpanel">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" name="email" class="form-control" 
                                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                                   placeholder="Enter your email" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" name="password" class="form-control" 
                                                   placeholder="Enter your password" required>
                                            <button type="button" class="btn btn-outline-secondary toggle-password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                                        <label class="form-check-label" for="remember">Remember Me</label>
                                        <a href="#" class="float-end" data-bs-toggle="modal" data-bs-target="#forgotModal">
                                            Forgot Password?
                                        </a>
                                    </div>
                                    <button type="submit" name="login" class="btn btn-primary w-100">
                                        <i class="fas fa-sign-in-alt"></i> Login
                                    </button>
                                </form>
                            </div>

                            <!-- Register Form -->
                            <div class="tab-pane fade" id="register" role="tabpanel">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" name="name" class="form-control" 
                                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                                   placeholder="Enter your full name" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" name="email" class="form-control" 
                                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                                   placeholder="Enter your email" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="tel" name="contact" class="form-control" 
                                                   value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>" 
                                                   placeholder="Enter your contact number" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" name="password" class="form-control" 
                                                   placeholder="Create a password (min 6 characters)" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-check"></i></span>
                                            <input type="password" name="confirm_password" class="form-control" 
                                                   placeholder="Confirm your password" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="register" class="btn btn-primary w-100">
                                        <i class="fas fa-user-plus"></i> Register
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Demo Credentials -->
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <p>Enter your email address and we'll send you a link to reset your password.</p>
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="reset_email" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="forgot_password" class="btn btn-primary">Send Reset Link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Role Badge -->
    <div class="role-badge">
        <i class="fas fa-users"></i> Customer / Admin / Mechanic Portal
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.closest('.input-group').querySelector('input');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Preserve tab state after form submission
        const hash = window.location.hash;
        if (hash === '#register') {
            const registerTab = document.querySelector('#register-tab');
            if (registerTab) {
                bootstrap.Tab.getInstance(registerTab) || new bootstrap.Tab(registerTab);
                registerTab.click();
            }
        }
    </script>
</body>
</html>
