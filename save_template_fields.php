<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_POST['template_id']) || !isset($_POST['fields'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    $pdo->beginTransaction();

    $template_id = $_POST['template_id'];
    $fields = json_decode($_POST['fields'], true);

    if (!is_array($fields)) {
        throw new Exception('Invalid fields data');
    }

    // Prepare the insert statement
    $stmt = $pdo->prepare("
        INSERT INTO template_fields 
        (template_id, field_name, field_type, description, is_fixed, created_by) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($fields as $field) {
        $stmt->execute([
            $template_id,
            $field['name'],
            $field['type'],
            $field['description'],
            $field['fixed'] ? 1 : 0,
            'system' // Replace with actual user ID when you have authentication
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Fields saved successfully']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error saving fields: ' . $e->getMessage()]);
}
?> 