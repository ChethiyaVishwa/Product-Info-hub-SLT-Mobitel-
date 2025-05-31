<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $template_id = $_POST['template_id'] ?? 0;
    $fields = $_POST['fields'] ?? [];

    if (!$template_id || empty($fields)) {
        throw new Exception('Missing required data');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Create new template data entry
    $stmt = $pdo->prepare("
        INSERT INTO template_data (
            template_id,
            created_dtm
        ) VALUES (?, NOW())
    ");
    $stmt->execute([$template_id]);
    $template_data_id = $pdo->lastInsertId();

    // Insert field values
    $field_stmt = $pdo->prepare("
        INSERT INTO template_field_values (
            template_data_id,
            field_name,
            field_value,
            created_dtm
        ) VALUES (?, ?, ?, NOW())
    ");

    foreach ($fields as $field_name => $field_value) {
        // Skip empty values for non-required fields
        if ($field_value !== '') {
            $field_stmt->execute([
                $template_data_id,
                $field_name,
                $field_value
            ]);
        }
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Data saved successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error saving data: ' . $e->getMessage()
    ]);
} 