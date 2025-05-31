<?php
session_start();
require_once 'config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    $error = null;
    
    try {
        $conn = connectDB();
        
        // First check if it's a superadmin login
        $stmt = $conn->prepare("SELECT * FROM superadmins WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        $superadmin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug information
        error_log("Login attempt for email: " . $email);
        
        if ($superadmin) {
            error_log("Superadmin found with ID: " . $superadmin['id']);
            error_log("Stored hash: " . $superadmin['password']);
            error_log("Input password: " . $password);
            
            if (password_verify($password, $superadmin['password'])) {
                error_log("Password verified successfully");
                // Set session variables for superadmin
                $_SESSION['user_id'] = 'sa_' . $superadmin['id'];
                $_SESSION['user_name'] = $superadmin['username'];
                $_SESSION['user_email'] = $superadmin['email'];
                $_SESSION['is_superadmin'] = true;
                
                // Update last login
                $stmt = $conn->prepare("UPDATE superadmins SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$superadmin['id']]);
                
                // Handle Remember Me for superadmin
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at, is_superadmin) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$superadmin['id'], $token, $expires]);
                    
                    setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
                    setcookie('user_type', 'superadmin', strtotime('+30 days'), '/', '', true, true);
                }
                
                header("Location: dashboard.php");
                exit();
            } else {
                error_log("Password verification failed");
                $error = "Invalid password for superadmin account. Please try again.";
            }
        } else {
            error_log("No superadmin found with email: " . $email);
            // If not superadmin, check regular users
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                error_log("Regular user found with ID: " . $user['id']);
                if (password_verify($password, $user['password'])) {
                    error_log("User password verified successfully");
                    // Set session variables for regular user
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['is_superadmin'] = false;
                    
                    // Update last login
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Handle Remember Me for regular user
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at, is_superadmin) VALUES (?, ?, ?, 0)");
                        $stmt->execute([$user['id'], $token, $expires]);
                        
                        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
                        setcookie('user_type', 'user', strtotime('+30 days'), '/', '', true, true);
                    }
                    
                    header("Location: dashboard.php");
                    exit();
                } else {
                    error_log("User password verification failed");
                    $error = "Invalid password for user account. Please try again.";
                }
            } else {
                error_log("No user found with email: " . $email);
                $error = "No account found with this email address. Please try again.";
            }
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "Login failed: " . $e->getMessage();
    }
}

// Check for registration success message
if (isset($_SESSION['registration_success'])) {
    $success = "Registration successful! Please login with your credentials.";
    unset($_SESSION['registration_success']);
}

// Check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    try {
        $conn = connectDB();
        
        // Check if it's a superadmin token
        if (isset($_COOKIE['user_type']) && $_COOKIE['user_type'] === 'superadmin') {
            $stmt = $conn->prepare("
                SELECT s.id, s.username as name, s.email 
                FROM superadmins s 
                JOIN remember_tokens rt ON s.id = rt.user_id 
                WHERE rt.token = ? AND rt.expires_at > NOW() AND rt.is_superadmin = 1
            ");
            $stmt->execute([$_COOKIE['remember_token']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $_SESSION['user_id'] = 'sa_' . $user['id'];
                $_SESSION['is_superadmin'] = true;
            }
        } else {
            // Regular user token
            $stmt = $conn->prepare("
                SELECT u.id, u.name, u.email 
                FROM users u 
                JOIN remember_tokens rt ON u.id = rt.user_id 
                WHERE rt.token = ? AND rt.expires_at > NOW() AND rt.is_superadmin = 0
            ");
            $stmt->execute([$_COOKIE['remember_token']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_superadmin'] = false;
            }
        }
        
        if ($user) {
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            header("Location: dashboard.php");
            exit();
        }
    } catch(PDOException $e) {
        // Silent fail - just continue to login page
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Product Info Hub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        #bg-video {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: -2;
            object-fit: cover;
        }
        .login-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(200, 200, 255, 0.1));
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            width: 90%;
            max-width: 500px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin: 0 auto;
            transition: all 0.3s ease;
        }
        .login-container:hover {
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }
        .logo {
            max-width: 200px;
            margin-bottom: 0.1rem;
        }
        .text-center .mb-3{
            color:rgb(255, 255, 255);
        }
        .mb-3 .form-label{
            color:rgb(255, 255, 255);
        }
        .form-check .form-check-label{
            color:rgb(255, 255, 255);
        }
        .btn-primary {
            background-color: #004B93;
            border-color: #004B93;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #003B73;
            border-color: #003B73;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 1rem;
            background-color: rgba(220, 53, 69, 0.1);
            padding: 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .success-message {
            color: #198754;
            margin-bottom: 1rem;
            background-color: rgba(25, 135, 84, 0.1);
            padding: 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .back-link {
            color:rgb(255, 255, 255);
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #004B93;
            transform: translateX(-3px);
        }
        
        /* Input group styles */
        .input-group {
            transition: all 0.3s ease;
        }
        .input-group:focus-within {
            box-shadow: 0 0 0 0.25rem rgba(0, 75, 147, 0.25);
            border-radius: 0.25rem;
        }
        .input-group-text {
            background-color: #004B93;
            color: white;
            border-color: #004B93;
        }
        .form-control:focus {
            border-color: #004B93;
            box-shadow: none;
        }
        .form-check-input:checked {
            background-color: #004B93;
            border-color: #004B93;
        }
        
        /* Responsive Media Queries */
        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
                max-width: 90%;
                margin: 0 15px;
            }
            
            .logo {
                max-width: 150px;
            }
            
            h2.mb-3 {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 1.2rem;
                max-width: 95%;
                margin: 0 10px;
            }
            
            .logo {
                max-width: 120px;
            }
            
            h2.mb-3 {
                font-size: 1.3rem;
                margin-bottom: 0.5rem !important;
            }
            
            .form-label {
                font-size: 0.9rem;
            }
            
            .form-control {
                font-size: 0.9rem;
                padding: 0.375rem 0.5rem;
            }
            
            .btn-primary {
                padding: 0.375rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .back-link {
                font-size: 0.8rem;
            }
        }
        
        @media (max-height: 650px) {
            .login-container {
                padding: 1rem;
            }
            
            .logo {
                max-width: 100px;
                margin-bottom: 0;
            }
            
            h2.mb-3 {
                font-size: 1.2rem;
                margin-bottom: 0.3rem !important;
            }
            
            .mb-3 {
                margin-bottom: 0.5rem !important;
            }
            
            .form-label {
                margin-bottom: 0.2rem;
            }
        }
        
        /* Ensure video background covers properly on all devices */
        @media (max-aspect-ratio: 16/9) {
            #bg-video {
                width: 100%;
                height: auto;
            }
        }
        
        @media (min-aspect-ratio: 16/9) {
            #bg-video {
                width: auto;
                height: 100%;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="bg-video">
        <source src="images/bg-5.mp4" type="video/mp4">
    </video>

    <div class="login-container">
        <div class="text-center mb-3">
            <img src="images/logo.png" alt="Product Info Hub Logo" class="logo img-fluid">
            <h2 class="mb-3">Login</h2>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message text-center mb-3">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="success-message text-center mb-3">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
        
        <div class="text-center mt-3">
            <a href="#" class="text-decoration-none text-white">Forgot password?</a>
        </div>
        
        <div class="text-center mt-2">
            <a href="index.php" class="back-link">← Back to home</a>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus management for better mobile experience
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const submitButton = document.querySelector('button[type="submit"]');
            
            // Auto-focus on email field on page load
            if (window.innerWidth > 768) {  // Only on desktop
                emailInput.focus();
            }
            
            // Add active state visual feedback
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.closest('.input-group').classList.add('shadow-sm');
                });
                
                input.addEventListener('blur', function() {
                    this.closest('.input-group').classList.remove('shadow-sm');
                });
            });
            
            // Add form submission loading state
            const form = document.querySelector('form');
            form.addEventListener('submit', function() {
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Logging in...';
                submitButton.disabled = true;
            });
            
            // Adjust container height on smaller mobile screens
            function adjustForMobile() {
                const loginContainer = document.querySelector('.login-container');
                if (window.innerHeight < 600) {
                    loginContainer.style.transform = 'scale(0.95)';
                } else {
                    loginContainer.style.transform = 'scale(1)';
                }
            }
            
            // Call on page load and resize
            adjustForMobile();
            window.addEventListener('resize', adjustForMobile);
        });
    </script>
</body>
</html> 