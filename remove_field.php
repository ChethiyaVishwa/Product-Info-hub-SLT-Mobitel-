<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if field_id is provided
$field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;
$template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;

if (!$field_id || !$template_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Field ID and Template ID are required'
    ]);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // First check if the field exists and belongs to the current template (not parent)
    $checkStmt = $pdo->prepare("
        SELECT tf.field_id, tf.field_name, tf.template_id, tf.is_fixed
        FROM template_fields tf
        WHERE tf.field_id = ? 
        AND tf.template_id = ?
        AND tf.end_dtm IS NULL
    ");
    $checkStmt->execute([$field_id, $template_id]);
    $field = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$field) {
        throw new Exception('Field not found or belongs to a parent template');
    }

    // Get current timestamp
    $currentTime = date('Y-m-d H:i:s');

    // Soft delete the field by setting end_dtm and ended_by
    $updateStmt = $pdo->prepare("
        UPDATE template_fields 
        SET end_dtm = ?, 
            ended_by = 'system'
        WHERE field_id = ? 
        AND template_id = ?
        AND end_dtm IS NULL
    ");

    $updateStmt->execute([$currentTime, $field_id, $template_id]);

    if ($updateStmt->rowCount() === 0) {
        throw new Exception('Failed to remove field');
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Field removed successfully',
        'field_name' => $field['field_name']
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error removing field: ' . $e->getMessage()
    ]);
}
?> 