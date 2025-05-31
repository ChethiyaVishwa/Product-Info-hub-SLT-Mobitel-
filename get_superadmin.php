<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, username, email, is_active FROM superadmins WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $superadmin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($superadmin) {
        echo json_encode(['success' => true, 'data' => $superadmin]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Superadmin not found']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 