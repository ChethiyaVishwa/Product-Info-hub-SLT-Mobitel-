<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Log the incoming request data
error_log("Received POST data: " . print_r($_POST, true));

// Validate input
if (!isset($_POST['username']) || !isset($_POST['email'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$id = isset($_POST['id']) ? trim($_POST['id']) : null;
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = isset($_POST['password']) ? trim($_POST['password']) : null;
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Log the processed data
error_log("Processed data - ID: $id, Username: $username, Email: $email, Active: $is_active");

// Validate username and email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

if (strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters long']);
    exit();
}

try {
    // Check if username or email already exists
    $checkStmt = $pdo->prepare("
        SELECT id FROM superadmins 
        WHERE (username = ? OR email = ?) 
        AND id != ?
    ");
    $checkStmt->execute([$username, $email, $id ?: 0]);
    if ($checkStmt->rowCount() > 0) {
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit();
    }

    $pdo->beginTransaction();
    
    if ($id) {
        // Update existing superadmin
        if ($password) {
            // Update with new password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE superadmins 
                SET username = ?, email = ?, password = ?, is_active = ?
                WHERE id = ? AND id != ?
            ");
            $stmt->execute([$username, $email, $hash, $is_active, $id, $_SESSION['user_id']]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("
                UPDATE superadmins 
                SET username = ?, email = ?, is_active = ?
                WHERE id = ? AND id != ?
            ");
            $stmt->execute([$username, $email, $is_active, $id, $_SESSION['user_id']]);
        }
    } else {
        // Create new superadmin
        if (!$password) {
            echo json_encode(['success' => false, 'message' => 'Password is required for new superadmin']);
            exit();
        }
        
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
            exit();
        }
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO superadmins (username, email, password, is_active)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $hash, $is_active]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    $pdo->rollBack();
    $error = $e->getMessage();
    error_log("Database error: " . $error);
    
    // Check for duplicate entry
    if (strpos($error, 'Duplicate entry') !== false) {
        if (strpos($error, 'username') !== false) {
            $error = 'Username already exists';
        } else if (strpos($error, 'email') !== false) {
            $error = 'Email already exists';
        }
    }
    echo json_encode(['success' => false, 'message' => $error]);
}
?> 