<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['template_name']) || trim($input['template_name']) === '') {
    echo json_encode(['success' => false, 'message' => 'Template name is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // First check if template name already exists
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM templates WHERE template_name = ? AND end_dtm IS NULL");
    $checkStmt->execute([trim($input['template_name'])]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Template name already exists']);
        exit;
    }

    // Insert the template
    $stmt = $pdo->prepare("
        INSERT INTO templates (
            template_name, 
            has_parent, 
            root_template_id, 
            created_by
        ) VALUES (?, ?, ?, ?)
    ");
    
    $templateName = trim($input['template_name']);
    $hasParent = !empty($input['parent_template_id']);
    $rootTemplateId = $hasParent ? $input['parent_template_id'] : null;
    
    $stmt->execute([
        $templateName,
        $hasParent,
        $rootTemplateId,
        'system' // Replace with actual user ID when you have authentication
    ]);

    $templateId = $pdo->lastInsertId();

    // Insert template fields if any
    if (isset($input['fields']) && is_array($input['fields']) && !empty($input['fields'])) {
        $fieldStmt = $pdo->prepare("
            INSERT INTO template_fields (
                template_id, 
                field_name, 
                field_type, 
                description, 
                is_fixed, 
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($input['fields'] as $field) {
            $fieldStmt->execute([
                $templateId,
                $field['name'],
                $field['type'],
                $field['description'],
                $field['fixed'] ? 1 : 0,
                'system' // Replace with actual user ID when you have authentication
            ]);
        }
    }

    // If this template has a parent, update the parent's has_child flag
    if ($hasParent) {
        $updateParentStmt = $pdo->prepare("UPDATE templates SET has_child = TRUE WHERE template_id = ?");
        $updateParentStmt->execute([$rootTemplateId]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Template and fields saved successfully',
        'template_id' => $templateId
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error saving template: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error saving template: ' . $e->getMessage()
    ]);
}
?> 