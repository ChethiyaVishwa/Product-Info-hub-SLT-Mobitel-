<?php
require_once 'config.php';

header('Content-Type: application/json');

$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
$include_parent_fields = isset($_GET['include_parent_fields']) && $_GET['include_parent_fields'] === 'true';

if (!$template_id) {
    echo json_encode(['success' => false, 'message' => 'Template ID is required']);
    exit;
}

try {
    // Get all fields for this template and its parent hierarchy
    $fields = getTemplateFields($pdo, $template_id, $include_parent_fields);
    
    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);

} catch (Exception $e) {
    error_log("Error fetching template fields: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching template fields: ' . $e->getMessage()
    ]);
}

function getTemplateFields($pdo, $templateId, $includeParentFields = false) {
    $fields = [];
    
    // Get the current template info
    $templateQuery = "
        SELECT t.template_name, t.root_template_id, t.has_parent
        FROM templates t 
        WHERE t.template_id = ? AND t.end_dtm IS NULL
    ";
    $templateStmt = $pdo->prepare($templateQuery);
    $templateStmt->execute([$templateId]);
    $templateData = $templateStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$templateData) {
        return $fields;
    }
    
    // Get current template's fields
    $query = "
        SELECT 
            field_name,
            field_type,
            description,
            is_fixed
        FROM template_fields 
        WHERE template_id = ? 
            AND end_dtm IS NULL
        ORDER BY field_name ASC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$templateId]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['source_template'] = $templateData['template_name'];
        $fields[] = $row;
    }
    
    // If we need parent fields and this template has a parent
    if ($includeParentFields && $templateData['has_parent'] && $templateData['root_template_id']) {
        // Recursively get parent fields
        $parentFields = getTemplateFields($pdo, $templateData['root_template_id'], true);
        $fields = array_merge($parentFields, $fields); // Note: parent fields first, then current template fields
    }
    
    return $fields;
}
?> 