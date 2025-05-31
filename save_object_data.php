<?php
require_once 'config.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_POST['template_id']) || !isset($_POST['object_name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$template_id = intval($_POST['template_id']);
$object_name = trim($_POST['object_name']);
$fields = isset($_POST['fields']) ? $_POST['fields'] : [];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Handle image upload if present
    $image_path = null;
    if (isset($_FILES['object_image']) && $_FILES['object_image']['error'] == 0) {
        // Create upload directory if it doesn't exist
        $upload_dir = 'uploads/objects/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['object_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('obj_') . '.' . $file_extension;
        $target_file = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['object_image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        } else {
            throw new Exception("Failed to upload image");
        }
    }

    // Insert new object with image path
    $stmt = $pdo->prepare("
        INSERT INTO objects (template_id, object_name, image_path)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$template_id, $object_name, $image_path]);
    $object_id = $pdo->lastInsertId();

    // Get fixed field values from template_fields
    $stmt = $pdo->prepare("
        SELECT field_id, field_value 
        FROM template_fields 
        WHERE template_id = ? 
        AND is_fixed = 1 
        AND end_dtm IS NULL
    ");
    $stmt->execute([$template_id]);
    $fixed_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Insert fixed field values
    foreach ($fixed_fields as $field) {
        $stmt = $pdo->prepare("
            INSERT INTO object_field_values (object_id, field_id, field_value)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$object_id, $field['field_id'], $field['field_value']]);
    }

    // Insert dynamic field values
    foreach ($fields as $field_id => $value) {
        // Skip empty values for dynamic fields
        if (trim($value) !== '') {
            $stmt = $pdo->prepare("
                INSERT INTO object_field_values (object_id, field_id, field_value)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$object_id, $field_id, $value]);
        }
    }

    // Commit transaction
    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 