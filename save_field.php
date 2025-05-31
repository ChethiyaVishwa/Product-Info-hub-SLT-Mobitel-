<?php
require_once 'config.php';

header('Content-Type: application/json');

// Debug log
error_log("Received field data: " . print_r($_POST, true));

// Validate input
if (!isset($_POST['template_id']) || !isset($_POST['name']) || !isset($_POST['type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$template_id = intval($_POST['template_id']);
$field_name = trim($_POST['name']);
$field_type = trim($_POST['type']);
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$is_fixed = isset($_POST['is_fixed']) ? (int)$_POST['is_fixed'] : 0;

// Debug log
error_log("Processing field: name={$field_name}, type={$field_type}, is_fixed={$is_fixed}");

try {
    // Check if field name already exists for this template
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM template_fields 
        WHERE template_id = ? AND field_name = ? AND end_dtm IS NULL
    ");
    $checkStmt->execute([$template_id, $field_name]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Field name already exists for this template']);
        exit;
    }

    // Insert new field
    $stmt = $pdo->prepare("
        INSERT INTO template_fields (
            template_id,
            field_name,
            field_type,
            description,
            is_fixed,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $template_id,
        $field_name,
        $field_type,
        $description,
        $is_fixed,
        'system' // Replace with actual user ID when you have authentication
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Field added successfully',
        'field_id' => $pdo->lastInsertId(),
        'is_fixed' => $is_fixed ? true : false
    ]);

} catch (Exception $e) {
    error_log("Error saving field: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error saving field: ' . $e->getMessage()
    ]);
}
?> 