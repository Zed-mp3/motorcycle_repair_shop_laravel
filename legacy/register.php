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
$form_data = [];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $form_data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'contact' => trim($_POST['contact'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'motorcycle_model' => trim($_POST['motorcycle_model'] ?? '')
    ];
    
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']) ? true : false;
    
    // Validation
    if (empty($form_data['name'])) {
        $error = "Please enter your full name";
    } elseif (empty($form_data['email'])) {
        $error = "Please enter your email address";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (empty($form_data['contact'])) {
        $error = "Please enter your contact number";
    } elseif (empty($password)) {
        $error = "Please enter a password";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (!$terms) {
        $error = "Please accept the terms and conditions";
    } else {
        $conn = getConnection();
        
        // Check if email already exists
        $check_sql = "SELECT id, email FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $form_data['email']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email address is already registered. Please <a href='login.php'>login</a> instead.";
        } else {
            // Hash password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'customer';
            $created_at = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO users (name, email, password, contact_number, address, role, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", 
                $form_data['name'], 
                $form_data['email'], 
                $hashed_password, 
                $form_data['contact'], 
                $form_data['address'], 
                $role, 
                $created_at
            );
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Auto login after registration
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $form_data['name'];
                $_SESSION['user_email'] = $form_data['email'];
                $_SESSION['role'] = 'customer';
                
                // Redirect to home page with welcome message
                header('Location: customer/home.php?welcome=1');
                exit();
            } else {
                $error = "Registration failed. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MotoRepair Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .register-container {
            margin: 20px auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .card-header h3 {
            margin: 0;
            font-weight: 600;
        }
        .card-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        .brand-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: #e67e22;
            box-shadow: 0 0 0 0.2rem rgba(230,126,34,0.25);
        }
        .input-group-text {
            background: #f8f9fa;
            border-radius: 8px 0 0 8px;
        }
        .btn-register {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            border: none;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #d35400, #e67e22);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(230,126,34,0.3);
        }
        .alert {
            border-radius: 10px;
        }
        .strength-meter {
            height: 5px;
            background: #e0e0e0;
            border-radius: 5px;
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 5px;
        }
        .strength-text {
            font-size: 12px;
            margin-top: 5px;
        }
        .password-requirement {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .password-requirement i {
            margin-right: 5px;
        }
        .requirement-met {
            color: #28a745;
        }
        .requirement-unmet {
            color: #dc3545;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #e67e22;
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .card-header h3 {
                font-size: 1.5rem;
            }
            .register-container {
                margin: 10px auto;
            }
        }
    </style>
</head>
<body>
    <div class="container register-container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-motorcycle brand-icon"></i>
                        <h3>Create Account</h3>
                        <p>Join MotoRepair Pro today</p>
                    </div>
                    <div class="card-body p-4">
                        <!-- Alert Messages -->
                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Registration Form -->
                        <form method="POST" id="registerForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" 
                                           placeholder="Enter your full name" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                                           placeholder="Enter your email address" required>
                                </div>
                                <small class="text-muted">We'll never share your email with anyone else.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Contact Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="contact" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['contact'] ?? ''); ?>" 
                                           placeholder="Enter your contact number" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <textarea name="address" class="form-control" rows="2" 
                                              placeholder="Enter your complete address"><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Motorcycle Model (Optional)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-motorcycle"></i></span>
                                    <input type="text" name="motorcycle_model" class="form-control" 
                                           value="<?php echo htmlspecialchars($form_data['motorcycle_model'] ?? ''); ?>" 
                                           placeholder="e.g., Honda Wave 100, Yamaha Mio">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" id="password" class="form-control" 
                                           placeholder="Create a password (min 6 characters)" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="strength-meter">
                                    <div class="strength-bar" id="strengthBar"></div>
                                </div>
                                <div class="strength-text" id="strengthText"></div>
                                <div class="password-requirement" id="passwordRequirements">
                                    <span id="lengthReq" class="requirement-unmet">
                                        <i class="fas fa-circle"></i> At least 6 characters
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-check"></i></span>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                                           placeholder="Confirm your password" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="confirm_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordMatch" class="password-requirement"></div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" name="terms" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                                    <span class="text-danger">*</span>
                                </label>
                            </div>

                            <button type="submit" name="register" class="btn btn-register w-100 text-white">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>
                        </form>

                        <div class="login-link">
                            Already have an account? <a href="login.php">Login here</a>
                        </div>

                        <hr class="my-4">

                        <div class="text-center">
                            <small class="text-muted">By registering, you'll be able to:</small>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <small><i class="fas fa-check-circle text-success"></i> Book services online</small>
                                </div>
                                <div class="col-6">
                                    <small><i class="fas fa-check-circle text-success"></i> Track repair status</small>
                                </div>
                                <div class="col-6">
                                    <small><i class="fas fa-check-circle text-success"></i> View repair history</small>
                                </div>
                                <div class="col-6">
                                    <small><i class="fas fa-check-circle text-success"></i> Download invoices</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Service Agreement</h6>
                    <p>By using our services, you agree to allow our mechanics to perform necessary repairs on your motorcycle.</p>
                    
                    <h6>2. Payment Terms</h6>
                    <p>Payment is due upon completion of repairs. We accept cash, credit cards, and bank transfers.</p>
                    
                    <h6>3. Warranty</h6>
                    <p>We provide a 30-day warranty on all parts and labor. Warranty does not cover misuse or accidents.</p>
                    
                    <h6>4. Cancellation Policy</h6>
                    <p>Bookings can be cancelled up to 2 hours before the scheduled time without any penalty.</p>
                    
                    <h6>5. Data Privacy</h6>
                    <p>Your personal information will be kept confidential and used only for service-related communications.</p>
                    
                    <h6>6. Liability</h6>
                    <p>We are not responsible for any pre-existing issues or damage caused by normal wear and tear.</p>
                    
                    <h6>7. Service Guarantee</h6>
                    <p>We guarantee quality workmanship and use only genuine or OEM parts unless specified otherwise.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Agree</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
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

        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const lengthReq = document.getElementById('lengthReq');

        function checkPasswordStrength() {
            const password = passwordInput.value;
            let strength = 0;
            let message = '';
            let color = '';
            
            // Length check
            if (password.length >= 6) {
                strength += 25;
                lengthReq.innerHTML = '<i class="fas fa-check-circle"></i> At least 6 characters';
                lengthReq.className = 'requirement-met';
            } else {
                lengthReq.innerHTML = '<i class="fas fa-circle"></i> At least 6 characters';
                lengthReq.className = 'requirement-unmet';
            }
            
            // Has numbers
            if (password.match(/[0-9]/)) strength += 25;
            
            // Has lowercase and uppercase
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
            
            // Has special characters
            if (password.match(/[^a-zA-Z0-9]/)) strength += 25;
            
            if (password.length === 0) {
                message = '';
                color = '#e0e0e0';
            } else if (strength <= 25) {
                message = 'Weak';
                color = '#dc3545';
            } else if (strength <= 50) {
                message = 'Fair';
                color = '#ffc107';
            } else if (strength <= 75) {
                message = 'Good';
                color = '#17a2b8';
            } else {
                message = 'Strong';
                color = '#28a745';
            }
            
            strengthBar.style.width = strength + '%';
            strengthBar.style.backgroundColor = color;
            strengthText.innerHTML = message;
            strengthText.style.color = color;
        }

        // Check password match
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirm.length === 0) {
                matchDiv.innerHTML = '';
            } else if (password === confirm) {
                matchDiv.innerHTML = '<i class="fas fa-check-circle text-success"></i> Passwords match';
                matchDiv.className = 'password-requirement text-success';
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Passwords do not match';
                matchDiv.className = 'password-requirement text-danger';
            }
        }
        
        passwordInput.addEventListener('keyup', checkPasswordStrength);
        passwordInput.addEventListener('keyup', checkPasswordMatch);
        document.getElementById('confirm_password').addEventListener('keyup', checkPasswordMatch);
        
        // Form validation before submit
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Please accept the Terms and Conditions');
                return false;
            }
            
            return true;
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
    </script>
</body>
</html>
