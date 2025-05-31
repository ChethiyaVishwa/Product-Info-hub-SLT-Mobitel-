<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_POST['object_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing object ID']);
    exit;
}

$object_id = intval($_POST['object_id']);
$object_name = $_POST['object_name'] ?? '';
$remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';

try {
    $pdo->beginTransaction();
    
    // Get current image path
    $stmt = $pdo->prepare("SELECT image_path FROM objects WHERE object_id = ? AND end_dtm IS NULL");
    $stmt->execute([$object_id]);
    $current_image = $stmt->fetchColumn();
    
    // Handle image upload if present
    $image_path = $current_image;
    if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0) {
        // Create upload directory if it doesn't exist
        $upload_dir = 'uploads/objects/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('obj_') . '.' . $file_extension;
        $target_file = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['new_image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
            
            // Delete old image if exists
            if ($current_image && file_exists($current_image)) {
                unlink($current_image);
            }
        } else {
            throw new Exception("Failed to upload image");
        }
    }
    
    // Handle image removal
    if ($remove_image) {
        // Delete the image file
        if ($current_image && file_exists($current_image)) {
            unlink($current_image);
        }
        $image_path = null;
    }

    // Update object name and image path
    $stmt = $pdo->prepare("
        UPDATE objects 
        SET object_name = ?, image_path = ?
        WHERE object_id = ? AND end_dtm IS NULL
    ");
    $stmt->execute([$object_name, $image_path, $object_id]);

    // Update field values
    foreach ($_POST as $key => $value) {
        if (preg_match('/field_(\d+)/', $key, $matches)) {
            $field_id = $matches[1];
            
            // Check if a value already exists for this field
            $stmt = $pdo->prepare("
                SELECT id 
                FROM object_field_values 
                WHERE object_id = ? AND field_id = ? AND end_dtm IS NULL
            ");
            $stmt->execute([$object_id, $field_id]);
            $existing_value_id = $stmt->fetchColumn();

            if ($existing_value_id) {
                // Soft delete old value
                $stmt = $pdo->prepare("
                    UPDATE object_field_values 
                    SET end_dtm = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$existing_value_id]);
            }

            // Insert new value
            $stmt = $pdo->prepare("
                INSERT INTO object_field_values (
                    object_id,
                    field_id,
                    field_value,
                    created_dtm
                ) VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$object_id, $field_id, $value]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Changes saved successfully']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error saving object changes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error saving changes: ' . $e->getMessage()
    ]);
}
?> 