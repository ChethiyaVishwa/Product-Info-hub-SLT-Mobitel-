<?php
require_once 'config.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_POST['field_id']) || !isset($_POST['value'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$field_id = intval($_POST['field_id']);
$value = trim($_POST['value']);

try {
    // Update the field value in template_fields table
    $stmt = $pdo->prepare("
        UPDATE template_fields 
        SET field_value = ?
        WHERE field_id = ? AND end_dtm IS NULL
    ");
    
    $stmt->execute([$value, $field_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Field value saved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Field not found or no changes made'
        ]);
    }

} catch (Exception $e) {
    error_log("Error saving field value: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error saving field value: ' . $e->getMessage()
    ]);
}
?> 