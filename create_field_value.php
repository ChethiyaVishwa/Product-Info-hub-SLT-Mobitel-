<?php
require_once 'config.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_POST['field_id']) || !isset($_POST['template_id']) || !isset($_POST['value'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$field_id = intval($_POST['field_id']);
$template_id = intval($_POST['template_id']);
$value = trim($_POST['value']);
$field_name = isset($_POST['field_name']) ? trim($_POST['field_name']) : '';

try {
    // Check if field exists
    $checkStmt = $pdo->prepare("
        SELECT field_id, field_name, field_type 
        FROM template_fields 
        WHERE field_id = ? AND end_dtm IS NULL
    ");
    $checkStmt->execute([$field_id]);
    $field = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($field) {
        // Field exists, update the value
        $updateStmt = $pdo->prepare("
            UPDATE template_fields 
            SET field_value = ?
            WHERE field_id = ? AND end_dtm IS NULL
        ");
        
        $updateStmt->execute([$value, $field_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Field value updated successfully',
            'action' => 'updated'
        ]);
    } else if ($field_name) {
        // Field doesn't exist, create it if name provided
        // Get the database schema to determine available columns
        $schemaStmt = $pdo->prepare("DESCRIBE template_fields");
        $schemaStmt->execute();
        $columns = $schemaStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Build the SQL dynamically based on available columns
        $sql = "INSERT INTO template_fields (template_id, field_name, field_type, field_value, is_fixed";
        $params = [$template_id, $field_name, 'text', $value, 0];
        
        // Close the column list
        $sql .= ") VALUES (?, ?, ?, ?, ?";
        
        // Close the values list
        $sql .= ")";
        
        $createStmt = $pdo->prepare($sql);
        $createStmt->execute($params);
        $newFieldId = $pdo->lastInsertId();
        
        // Log what was done for debugging
        error_log("Created new field: ID=$newFieldId, Name=$field_name, Template=$template_id, Value=$value");
        
        echo json_encode([
            'success' => true,
            'message' => 'New field created successfully',
            'field_id' => $newFieldId,
            'action' => 'created'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Field not found and insufficient information to create it'
        ]);
    }

} catch (Exception $e) {
    error_log("Error creating/updating field value: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 