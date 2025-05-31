<?php
session_start();
require_once 'config/database.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    try {
        $conn = connectDB();
        
        // Get user from database
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Handle Remember Me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $token, $expires]);
                
                // Set cookie
                setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
            }
            
            header("Location: user_dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } catch(PDOException $e) {
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
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.email 
            FROM users u 
            JOIN remember_tokens rt ON u.id = rt.user_id 
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ");
        $stmt->execute([$_COOKIE['remember_token']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            header("Location: user_dashboard.php");
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
    <!-- Google Sign-In API -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        body {
            min-height: 100vh;
            height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            margin: 0;
            padding: 15px 0;
            box-sizing: border-box;
        }
        .wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100%;
            position: relative;
            z-index: 1;
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
            padding: 1rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
            width: 90%;
            max-width: 450px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin: 0.5rem auto;
            transition: all 0.3s ease;
        }
        .login-container:hover {
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }
        .logo {
            max-width: 120px;
            margin-bottom: 0;
        }
        .text-center .mb-3{
            color:rgb(255, 255, 255);
        }
        .mb-3 .form-label{
            color:rgb(255, 255, 255);
            margin-bottom: 0.15rem;
            font-size: 0.9rem;
        }
        .form-check .form-check-label{
            color:rgb(255, 255, 255);
            font-size: 0.85rem;
        }
        .form-check {
            margin-bottom: 0.5rem;
            padding-top: 0;
        }
        .btn-primary {
            background-color: #004B93;
            border-color: #004B93;
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 0.35rem 0.75rem;
        }
        .btn-primary:hover {
            background-color: #003B73;
            border-color: #003B73;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 0.5rem;
            background-color: rgba(220, 53, 69, 0.1);
            padding: 6px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .success-message {
            color: #198754;
            margin-bottom: 0.5rem;
            background-color: rgba(25, 135, 84, 0.1);
            padding: 6px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .back-link {
            color:rgb(255, 255, 255);
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-block;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #004B93;
            transform: translateX(-3px);
        }
        
        /* Input group styles */
        .input-group {
            transition: all 0.3s ease;
            margin-bottom: 0.75rem;
        }
        .input-group:focus-within {
            box-shadow: 0 0 0 0.15rem rgba(0, 75, 147, 0.25);
            border-radius: 0.25rem;
        }
        .input-group-text {
            background-color: #004B93;
            color: white;
            border-color: #004B93;
            padding: 0.375rem 0.5rem;
        }
        .form-control {
            padding: 0.35rem 0.5rem;
            font-size: 0.9rem;
        }
        .form-control:focus {
            border-color: #004B93;
            box-shadow: none;
        }
        .form-check-input:checked {
            background-color: #004B93;
            border-color: #004B93;
        }
        
        /* Google sign-in styles */
        .google-container {
            width: 100%;
            margin-bottom: 0.75rem;
        }
        .g_id_signin {
            width: 100%;
            display: flex;
            justify-content: center;
            transform: scale(0.95);
        }
        .or-divider {
            text-align: center;
            margin: 0.5rem 0;
            position: relative;
            color:rgb(255, 255, 255);
            font-size: 0.8rem;
        }
        .or-divider::before,
        .or-divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background-color: #dee2e6;
        }
        .or-divider::before {
            left: 0;
        }
        .or-divider::after {
            right: 0;
        }
        
        /* Responsive Media Queries */
        @media (max-width: 768px) {
            body {
                align-items: flex-start;
                padding: 10px 0 20px;
            }
            .wrapper {
                align-items: flex-start;
            }
            .login-container {
                padding: 0.8rem;
                max-width: 90%;
                margin: 0.25rem auto;
            }
            
            h2.mb-3 {
                font-size: 1.3rem;
                margin-bottom: 0.25rem !important;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 8px 0 15px;
                min-height: auto;
            }
            .login-container {
                padding: 0.8rem;
                max-width: 95%;
                margin: 0.25rem auto;
            }
            
            .logo {
                max-width: 90px;
            }
            
            h2.mb-3 {
                font-size: 1.1rem;
                margin-bottom: 0.25rem !important;
                margin-top: 0;
            }
            
            .form-label {
                font-size: 0.8rem;
                margin-bottom: 0.1rem;
            }
            
            .form-control {
                font-size: 0.85rem;
                padding: 0.275rem 0.4rem;
            }
            
            .btn-primary {
                padding: 0.3rem 0.6rem;
                font-size: 0.85rem;
            }
            
            .back-link {
                font-size: 0.75rem;
                margin-top: 0.25rem;
            }
            .mb-3 {
                margin-bottom: 0.4rem !important;
            }
            .mt-3 {
                margin-top: 0.4rem !important;
            }
            .mt-2 {
                margin-top: 0.2rem !important;
            }
            .input-group {
                margin-bottom: 0.5rem;
            }
        }
        
        @media (max-height: 650px) {
            body {
                padding: 10px 0 20px;
                min-height: auto;
                height: auto;
            }
            .login-container {
                padding: 0.8rem;
                margin-top: 0;
                margin-bottom: 0.5rem;
            }
            
            .logo {
                max-width: 80px;
                margin-bottom: 0;
            }
            
            h2.mb-3 {
                font-size: 1.2rem;
                margin-bottom: 0.3rem !important;
            }
            
            .mb-3 {
                margin-bottom: 0.4rem !important;
            }
            
            .form-label {
                margin-bottom: 0.2rem;
            }
            .google-container {
                margin-bottom: 0.5rem;
            }
            .or-divider {
                margin: 0.5rem 0;
            }
            .mt-3 {
                margin-top: 0.4rem !important;
            }
            .mt-2 {
                margin-top: 0.2rem !important;
            }
        }
        
        /* Ensure video background covers properly on all devices */
        @media (max-aspect-ratio: 16/9) {
            #bg-video {
                width: 100%;
                height: auto;
            }
            body {
                padding: 10px 0 20px;
                min-height: auto;
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
    <video autoplay muted loop id="bg-video" playsinline>
        <source src="images/bg-5.mp4" type="video/mp4">
    </video>

    <div class="wrapper">
        <div class="login-container">
            <div class="text-center mb-2">
                <img src="images/logo.png" alt="Product Info Hub Logo" class="logo img-fluid">
                <h2 class="mb-2">Login</h2>
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

            <!-- Google Sign-In Button -->
            <div class="google-container">
                <div id="g_id_onload"
                    data-client_id="335818055558-fsl1bjs4tqag5084astem9eb3hro4mon.apps.googleusercontent.com"
                    data-context="signin"
                    data-callback="handleCredentialResponse"
                    data-auto_prompt="false">
                </div>
                <div class="g_id_signin"
                    data-type="standard"
                    data-size="large"
                    data-theme="outline"
                    data-text="signin_with"
                    data-shape="rectangular"
                    data-logo_alignment="left"
                    data-width="100%">
                </div>
            </div>

            <div class="or-divider">
                <span>OR</span>
            </div>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-2">
                    <label for="email" class="form-label">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
                    </div>
                </div>
                <div class="mb-2">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    </div>
                </div>
                <div class="mb-2 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            
            <div class="text-center mt-2">
                <a href="#" class="text-decoration-none text-white small">Forgot password?</a>
            </div>

            <div class="text-center mt-2">
                <a href="register.php" class="text-decoration-none text-white small">Don't have an account? Register</a>
            </div>
            
            <div class="text-center mt-2">
                <a href="index.php" class="back-link">← Back to home</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function handleCredentialResponse(response) {
            // Send the ID token to your server
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'verify_google_token.php');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        window.location.href = 'user_dashboard.php';
                    } else {
                        alert('Login failed: ' + response.message);
                    }
                }
            };
            xhr.send('credential=' + response.credential);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Focus management for better mobile experience
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const submitButton = document.querySelector('button[type="submit"]');
            
            // Skip auto-focus on mobile
            if (window.innerWidth > 768) {
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
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Login';
                submitButton.disabled = true;
            });
            
            // Scale form correctly for all screen sizes
            function scaleFormForDevice() {
                const loginContainer = document.querySelector('.login-container');
                const windowHeight = window.innerHeight;
                
                if (windowHeight < 550) {
                    loginContainer.style.transform = 'scale(0.9)';
                    loginContainer.style.transformOrigin = 'center top';
                } else if (windowHeight < 650) {
                    loginContainer.style.transform = 'scale(0.95)';
                    loginContainer.style.transformOrigin = 'center top';
                } else {
                    loginContainer.style.transform = 'scale(1)';
                }
                
                // Ensure bottom elements are visible
                const body = document.body;
                const formHeight = loginContainer.offsetHeight;
                
                if (formHeight > windowHeight) {
                    body.style.height = 'auto';
                    body.style.alignItems = 'flex-start';
                    window.scrollTo(0, 0);
                }
            }
            
            // Call on page load and resize
            scaleFormForDevice();
            window.addEventListener('resize', scaleFormForDevice);
        });
    </script>
</body>
</html> 