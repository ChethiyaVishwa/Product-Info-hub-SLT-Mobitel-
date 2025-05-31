<?php
session_start();
require_once 'config/database.php';

// Get the credential from the POST request
$credential = $_POST['credential'] ?? null;

if (!$credential) {
    echo json_encode(['success' => false, 'message' => 'No credential provided']);
    exit;
}

// Your Google OAuth 2.0 Client Secret
$client_secret = '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';

// Verify the token with Google
$client_id = '335818055558-fsl1bjs4tqag5084astem9eb3hro4mon.apps.googleusercontent.com';
$url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $credential;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status != 200) {
    echo json_encode(['success' => false, 'message' => 'Failed to verify token']);
    exit;
}

$payload = json_decode($response, true);

// Verify that the token is intended for your application
if ($payload['aud'] !== $client_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid client ID']);
    exit;
}

try {
    $conn = connectDB();
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->execute([$payload['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Create new user without google_id
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        
        // Generate a strong random password for Google users
        $randomPassword = bin2hex(random_bytes(16));
        $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
        
        $stmt->execute([
            $payload['name'],
            $payload['email'],
            $hashedPassword // Store a secure random password
        ]);
        
        $userId = $conn->lastInsertId();
        $userName = $payload['name'];
        $userEmail = $payload['email'];
    } else {
        $userId = $user['id'];
        $userName = $user['name'];
        $userEmail = $user['email'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $userEmail;
    
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 