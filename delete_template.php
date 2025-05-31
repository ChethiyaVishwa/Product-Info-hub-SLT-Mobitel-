<?php
require_once 'config.php';

header('Content-Type: application/json');

$templateId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$templateId) {
    echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check again if template has child templates (double check for safety)
    $stmt = $pdo->prepare("SELECT COUNT(*) as child_count FROM templates WHERE root_template_id = ? AND end_dtm IS NULL");
    $stmt->execute([$templateId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['child_count'] > 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete: template has child templates'
        ]);
        exit;
    }

    // Get template info for updating parent's has_child status
    $stmt = $pdo->prepare("SELECT root_template_id FROM templates WHERE template_id = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    // Soft delete the template by setting end_dtm
    $stmt = $pdo->prepare("UPDATE templates SET end_dtm = NOW(), ended_by = 'system' WHERE template_id = ?");
    $stmt->execute([$templateId]);

    // If this was a child template, check if parent has other children
    if ($template['root_template_id']) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as remaining_children 
            FROM templates 
            WHERE root_template_id = ? AND end_dtm IS NULL
        ");
        $stmt->execute([$template['root_template_id']]);
        $childCount = $stmt->fetch(PDO::FETCH_ASSOC);

        // Update parent's has_child status if this was the last child
        if ($childCount['remaining_children'] == 0) {
            $stmt = $pdo->prepare("UPDATE templates SET has_child = FALSE WHERE template_id = ?");
            $stmt->execute([$template['root_template_id']]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 