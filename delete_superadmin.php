<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in and is a superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Validate input
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid superadmin ID']);
    exit();
}

$id = (int)$_POST['id'];

// Prevent deleting own account
if ($id === (int)$_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Check if this is the last active superadmin
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM superadmins WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] <= 1) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete the last active superadmin account']);
        exit();
    }

    // Delete the superadmin
    $stmt = $pdo->prepare("DELETE FROM superadmins WHERE id = ? AND id != ?");
    $result = $stmt->execute([$id, $_SESSION['user_id']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Superadmin not found or cannot be deleted');
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch(Exception $e) {
    $pdo->rollBack();
    error_log("Error deleting superadmin: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 