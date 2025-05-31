<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_POST['template_id']) || !isset($_POST['values'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$template_id = intval($_POST['template_id']);
$values = $_POST['values'];

try {
    $pdo->beginTransaction();

    // First, clear existing values for this template
    $clearStmt = $pdo->prepare("
        UPDATE template_field_values 
        SET end_dtm = CURRENT_TIMESTAMP 
        WHERE template_id = ? AND end_dtm IS NULL
    ");
    $clearStmt->execute([$template_id]);

    // Insert new values
    $insertStmt = $pdo->prepare("
        INSERT INTO template_field_values (
            template_id,
            field_id,
            field_value,
            created_by
        ) VALUES (?, ?, ?, ?)
    ");

    foreach ($values as $key => $value) {
        if (preg_match('/field_(\d+)/', $key, $matches)) {
            $field_id = $matches[1];
            $insertStmt->execute([
                $template_id,
                $field_id,
                $value,
                'system' // Replace with actual user ID when you have authentication
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Template values saved successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error saving template values: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error saving template values'
    ]);
}
?> 