<?php
session_start();
require_once 'config/database.php';

// Function to generate OTP
function generateOTP($length = 6) {
    return substr(str_shuffle("0123456789"), 0, $length);
}

// Function to send OTP email
function sendOTPEmail($email, $otp, $name) {
    $to = $email;
    $subject = "Email Verification - Product Info Hub";
    
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2>Welcome to Product Info Hub!</h2>
            <p>Dear " . htmlspecialchars($name) . ",</p>
            <p>Thank you for registering. To verify your email address, please use the following OTP code:</p>
            <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                " . $otp . "
            </div>
            <p>This code will expire in 15 minutes.</p>
            <p>If you didn't request this verification, please ignore this email.</p>
            <p>Best regards,<br>Product Info Hub Team</p>
        </div>
    </body>
    </html>
    ";

    // Headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Product Info Hub <noreply@productinfohub.com>' . "\r\n";

    return mail($to, $subject, $message, $headers);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = array();
    
    // Validate input
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $conn = connectDB();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email already exists";
            } else {
                // Generate OTP
                $otp = generateOTP();
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user with verification status as 0 (unverified)
                $stmt = $conn->prepare("
                    INSERT INTO users (name, email, password, otp, otp_expiry, is_verified) 
                    VALUES (?, ?, ?, ?, ?, 0)
                ");
                $stmt->execute([$name, $email, $hashed_password, $otp, $otp_expiry]);
                
                // Send OTP email
                if (sendOTPEmail($email, $otp, $name)) {
                    $_SESSION['verify_email'] = $email;
                    header("Location: verify_email.php");
                    exit();
                } else {
                    $errors[] = "Failed to send verification email. Please try again.";
                }
            }
        } catch(PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Register - Product Info Hub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .register-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.64), rgba(200, 200, 255, 0.1));
            padding: 0.9rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgb(255, 255, 255);
            width: 90%;
            max-width: 400px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin: 0 15px;
        }
        .register-container:hover {
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }
        .logo {
            max-width: 120px;
            margin-bottom: 0.2rem;
            width: 60%;
            height: auto;
        }
        .btn-primary {
            background-color: #004B93;
            box-shadow: 0 8px 32px rgb(255, 255, 255);
            border-color: #004B93;
            padding: 0.35rem 0.75rem;
        }
        .btn-primary:hover {
            background-color: rgb(162, 210, 255);
            border-color: rgb(162, 210, 255);
            color: rgb(0, 0, 0);
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .back-link {
            color: #ffffff;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 0.6rem;
            transition: color 0.3s;
        }
        .back-link:hover {
            color: rgb(162, 210, 255);
        }
        .password-requirements {
            font-size: 0.75rem;
            color: #ffffff;
            margin-top: 0.1rem;
        }
        .background-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        .shape {
            position: absolute;
            background-color: rgba(16, 17, 77, 0);
            transform: rotate(45deg);
        }
        
        /* Responsive Media Queries */
        @media (max-width: 576px) {
            .register-container {
                width: 95%;
                padding: 1.2rem;
                margin: 0 10px;
            }
            
            .logo {
                max-width: 150px;
            }
            
            .btn-primary {
                font-size: 0.85rem;
                padding: 7px;
            }
            
            .back-link, .password-requirements {
                font-size: 0.8rem;
            }
        }
        
        @media (max-height: 650px) {
            .register-container {
                padding: 1rem;
            }
            
            .logo {
                max-width: 120px;
                margin-bottom: 0.3rem;
            }
            
            .btn-primary {
                padding: 5px;
                margin-bottom: 0.5rem;
                font-size: 0.8rem;
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
        
        /* Additional form styling for compact size */
        .form-label {
            margin-bottom: 0.15rem;
            font-size: 0.85rem;
        }
        .form-control {
            padding: 0.25rem 0.65rem;
            font-size: 0.85rem;
            height: calc(1.5em + 0.5rem + 2px);
        }
        .mb-3 {
            margin-bottom: 0.5rem !important;
        }
        .form-check {
            margin-bottom: 0.4rem !important;
            font-size: 0.85rem;
        }
        h2 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem !important;
        }
        .text-center.mb-3 {
            margin-bottom: 0.5rem !important;
        }
        .text-center.mt-3 {
            margin-top: 0.4rem !important;
        }
    </style>
</head>
<body>

    <video autoplay muted loop id="bg-video">
        <source src="images/bg-5.mp4" type="video/mp4">
    </video>
    
    <div class="background-shapes">
        <!-- Background shapes will be added by JavaScript -->
    </div>

    <div class="register-container">
        <div class="text-center mb-3">
            <img src="images/logo.png" alt="Product Info Hub Logo" class="logo">
            <h2 class="mb-3">Register</h2>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="password-requirements">
                    Password must be at least 6 characters long
                </div>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">I agree to the Terms and Conditions</label>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
        </form>
        
        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none text-white">Already have an account? Login</a>
        </div>
        
        <div class="text-center">
            <a href="index.php" class="back-link">← Back to home</a>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add background shapes
        function createShapes() {
            const container = document.querySelector('.background-shapes');
            // Create fewer shapes on mobile for better performance
            const isMobile = window.innerWidth <= 768;
            const numShapes = isMobile ? 8 : 15;
            
            for (let i = 0; i < numShapes; i++) {
                const shape = document.createElement('div');
                shape.className = 'shape';
                const size = Math.random() * (isMobile ? 80 : 100) + 50;
                shape.style.width = size + 'px';
                shape.style.height = size + 'px';
                shape.style.left = Math.random() * 100 + '%';
                shape.style.top = Math.random() * 100 + '%';
                container.appendChild(shape);
            }
        }
        
        // Function to handle screen resize
        function handleResize() {
            // Add any specific resize handlers needed
        }
        
        // Initialize
        createShapes();
        handleResize();
        
        // Add event listeners
        window.addEventListener('resize', handleResize);
    </script>
</body>
</html> 