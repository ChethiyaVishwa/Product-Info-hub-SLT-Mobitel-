<?php
require_once 'config.php';

header('Content-Type: application/json');

$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Debug log
error_log("get_fields.php: Fetching {$type} fields for template_id {$template_id}");

if (!$template_id) {
    echo json_encode(['success' => false, 'message' => 'Template ID is required']);
    exit;
}

try {
    $is_fixed = ($type === 'fixed') ? 1 : 0;
    
    $stmt = $pdo->prepare("
        SELECT field_id, field_name, field_type, description, field_value 
        FROM template_fields 
        WHERE template_id = ? AND is_fixed = ? AND end_dtm IS NULL
        ORDER BY field_id
    ");
    
    $stmt->execute([$template_id, $is_fixed]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($fields) . " {$type} fields");
    
    echo json_encode(['success' => true, 'fields' => $fields]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 