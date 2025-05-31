<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['verify_email'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$email = $_SESSION['verify_email'];

try {
    $conn = connectDB();
    
    // Generate new OTP
    $otp = substr(str_shuffle("0123456789"), 0, 6);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Update user with new OTP
    $stmt = $conn->prepare("
        UPDATE users 
        SET otp = ?, 
            otp_expiry = ? 
        WHERE email = ? AND is_verified = 0
    ");
    $stmt->execute([$otp, $otp_expiry, $email]);
    
    // Get user's name
    $stmt = $conn->prepare("SELECT name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $name = $stmt->fetchColumn();
    
    // Send new OTP email
    $to = $email;
    $subject = "New OTP Code - Product Info Hub";
    
    $message = "
    <html>
    <head>
        <title>New OTP Code</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2>New OTP Code</h2>
            <p>Dear " . htmlspecialchars($name) . ",</p>
            <p>Your new OTP code for email verification is:</p>
            <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                " . $otp . "
            </div>
            <p>This code will expire in 15 minutes.</p>
            <p>If you didn't request this code, please ignore this email.</p>
            <p>Best regards,<br>Product Info Hub Team</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Product Info Hub <noreply@productinfohub.com>' . "\r\n";
    
    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => 'New OTP code has been sent to your email']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send new OTP code']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 