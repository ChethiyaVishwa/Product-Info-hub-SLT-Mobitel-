<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_POST['object_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing object ID']);
    exit;
}

$object_id = intval($_POST['object_id']);

try {
    $pdo->beginTransaction();

    // Soft delete field values
    $stmt = $pdo->prepare("
        UPDATE object_field_values 
        SET end_dtm = CURRENT_TIMESTAMP
        WHERE object_id = ? AND end_dtm IS NULL
    ");
    $stmt->execute([$object_id]);

    // Soft delete object
    $stmt = $pdo->prepare("
        UPDATE objects 
        SET end_dtm = CURRENT_TIMESTAMP
        WHERE object_id = ? AND end_dtm IS NULL
    ");
    $stmt->execute([$object_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Object deleted successfully']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error deleting object: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting object: ' . $e->getMessage()
    ]);
}
?> 