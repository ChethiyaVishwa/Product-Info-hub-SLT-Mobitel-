<?php
header('Content-Type: application/json');
require_once 'config.php';

$templateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$templateId) {
    echo json_encode(['can_delete' => false, 'message' => 'Invalid template ID']);
    exit;
}

try {
    // Check if template has child templates
    $stmt = $pdo->prepare("SELECT COUNT(*) as child_count FROM templates WHERE root_template_id = ? AND end_dtm IS NULL");
    $stmt->execute([$templateId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['child_count'] > 0) {
        echo json_encode([
            'can_delete' => false, 
            'message' => 'This template has child templates. Please delete all child templates first.'
        ]);
        exit;
    }

    // If we get here, the template can be deleted
    echo json_encode(['can_delete' => true]);

} catch(PDOException $e) {
    echo json_encode([
        'can_delete' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 