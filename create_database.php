<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Create connection without database
    $conn = new PDO("mysql:host=$host", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS product_info_hub");
    echo "Database created or already exists successfully\n";
    
    // Select the database
    $conn->exec("USE product_info_hub");
    
    // Create superadmins table
    $conn->exec("CREATE TABLE IF NOT EXISTS superadmins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE
    )");
    echo "Superadmins table created successfully\n";
    
    // Create users table
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE
    )");
    echo "Users table created successfully\n";
    
    // Create remember_tokens table
    $conn->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        is_superadmin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Remember tokens table created successfully\n";
    
    // Create default superadmin account
    $password = 'Admin@123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO superadmins (username, password, email, is_active) 
                           VALUES (?, ?, ?, 1) 
                           ON DUPLICATE KEY UPDATE 
                           password = VALUES(password),
                           is_active = 1");
    $stmt->execute(['admin', $hash, 'admin@productinfohub.com']);
    echo "Superadmin account created/updated successfully\n";
    echo "\nYou can now log in with:\n";
    echo "Email: admin@productinfohub.com\n";
    echo "Password: Admin@123\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 