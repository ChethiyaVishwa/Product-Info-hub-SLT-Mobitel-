<?php
session_start();
require_once 'config/database.php';

// Redirect if email is not set
if (!isset($_SESSION['verify_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['verify_email'];
$message = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = trim($_POST['otp']);
    
    if (empty($otp)) {
        $message = "Please enter the OTP code";
    } else {
        try {
            $conn = connectDB();
            
            // First, check if user exists and get their current status
            $stmt = $conn->prepare("
                SELECT id, otp, otp_expiry, is_verified 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $message = "User not found";
            } elseif ($user['is_verified'] == 1) {
                $message = "Email is already verified";
                $success = true;
            } else {
                // Check if OTP matches and not expired
                if ($user['otp'] === $otp) {
                    if (strtotime($user['otp_expiry']) >= time()) {
                        // Update user as verified
                        $stmt = $conn->prepare("
                            UPDATE users 
                            SET is_verified = 1, 
                                otp = NULL, 
                                otp_expiry = NULL 
                            WHERE email = ?
                        ");
                        $stmt->execute([$email]);
                        
                        $success = true;
                        $message = "Email verified successfully! You can now login.";
                        
                        // Clear session
                        unset($_SESSION['verify_email']);
                    } else {
                        $message = "OTP has expired. Please request a new one.";
                    }
                } else {
                    $message = "Invalid OTP code. Please try again.";
                }
            }
        } catch(PDOException $e) {
            $message = "Verification failed: " . $e->getMessage();
        }
    }
}

// Get remaining time for OTP if it exists
try {
    $conn = connectDB();
    $stmt = $conn->prepare("
        SELECT otp_expiry 
        FROM users 
        WHERE email = ? AND is_verified = 0
    ");
    $stmt->execute([$email]);
    $expiry = $stmt->fetchColumn();
    
    if ($expiry) {
        $remaining_time = max(0, strtotime($expiry) - time());
    }
} catch(PDOException $e) {
    // Ignore expiry check errors
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Product Info Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
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
        .verify-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgb(255, 255, 255);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 1rem;
        }
        .otp-input {
            letter-spacing: 0.5em;
            text-align: center;
            font-size: 1.5em;
            padding: 0.5em;
        }
        .btn-primary {
            background-color: #004B93;
            border-color: #004B93;
            width: 100%;
            padding: 0.8em;
            margin-top: 1em;
        }
        .btn-primary:hover {
            background-color: #003B73;
            border-color: #003B73;
        }
        .resend-link {
            color: #6c757d;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 1rem;
        }
        .resend-link:hover {
            color: #004B93;
        }
        .success-message {
            color: #28a745;
            margin: 1em 0;
        }
        .error-message {
            color: #dc3545;
            margin: 1em 0;
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="bg-video">
        <source src="images/bg-5.mp4" type="video/mp4">
    </video>

    <div class="verify-container">
        <img src="images/logo-1.png" alt="Product Info Hub Logo" class="logo">
        <h2 class="mb-4">Email Verification</h2>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo $message; ?>
                <div class="mt-3">
                    <a href="userlogin.php" class="btn btn-primary">Proceed to Login</a>
                </div>
            </div>
        <?php else: ?>
            <p class="mb-4">We've sent a verification code to:<br>
                <strong><?php echo htmlspecialchars($email); ?></strong>
            </p>

            <?php if (!empty($message)): ?>
                <div class="error-message"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-3">
                    <input type="text" class="form-control otp-input" 
                           name="otp" maxlength="6" placeholder="Enter OTP"
                           pattern="[0-9]{6}" title="Please enter 6 digits"
                           required>
                    <?php if (isset($remaining_time) && $remaining_time > 0): ?>
                        <div class="small text-muted mt-2">
                            OTP expires in: <span id="countdown"><?php echo ceil($remaining_time/60); ?></span> minutes
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary">Verify Email</button>
            </form>

            <a href="#" class="resend-link" id="resendOTP">Didn't receive the code? Resend OTP</a>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto format OTP input
        document.querySelector('.otp-input').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Handle resend OTP
        document.getElementById('resendOTP').addEventListener('click', function(e) {
            e.preventDefault();
            const button = this;
            button.style.pointerEvents = 'none';
            button.textContent = 'Sending...';

            fetch('resend_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show mt-3';
                    alert.innerHTML = `
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.querySelector('form').insertAdjacentElement('beforebegin', alert);
                    
                    // Disable resend button for 60 seconds
                    let timeLeft = 60;
                    button.textContent = `Resend OTP (${timeLeft}s)`;
                    
                    const timer = setInterval(() => {
                        timeLeft--;
                        if (timeLeft <= 0) {
                            clearInterval(timer);
                            button.textContent = 'Resend OTP';
                            button.style.pointerEvents = 'auto';
                        } else {
                            button.textContent = `Resend OTP (${timeLeft}s)`;
                        }
                    }, 1000);
                } else {
                    // Show error message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                    alert.innerHTML = `
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.querySelector('form').insertAdjacentElement('beforebegin', alert);
                    button.textContent = 'Resend OTP';
                    button.style.pointerEvents = 'auto';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                alert.innerHTML = `
                    Error connecting to server
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('form').insertAdjacentElement('beforebegin', alert);
                button.textContent = 'Resend OTP';
                button.style.pointerEvents = 'auto';
            });
        });

        // Add countdown timer if expiry exists
        const countdownElement = document.getElementById('countdown');
        if (countdownElement) {
            let timeLeft = parseInt(countdownElement.textContent) * 60;
            const timer = setInterval(() => {
                timeLeft--;
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    countdownElement.parentElement.innerHTML = 'OTP has expired. Please request a new one.';
                } else {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            }, 1000);
        }
    </script>
</body>
</html> 