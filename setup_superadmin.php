<?php
require_once 'config/database.php';

try {
    $conn = connectDB();
    
    // Generate proper password hash
    $password = 'Admin@123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update superadmin password
    $stmt = $conn->prepare("UPDATE superadmins SET password = ? WHERE email = ?");
    $stmt->execute([$hash, 'admin@productinfohub.com']);
    
    // Verify if superadmin exists, if not create one
    $stmt = $conn->prepare("SELECT id FROM superadmins WHERE email = ?");
    $stmt->execute(['admin@productinfohub.com']);
    
    if (!$stmt->fetch()) {
        $stmt = $conn->prepare("INSERT INTO superadmins (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hash, 'admin@productinfohub.com']);
    }
    
    echo "Superadmin account has been set up successfully!\n";
    echo "Email: admin@productinfohub.com\n";
    echo "Password: Admin@123\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 