<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_POST['template_name'])) {
    echo json_encode(['success' => false, 'message' => 'Template name not provided']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM templates WHERE template_name = ? AND end_dtm IS NULL");
    $stmt->execute([$_POST['template_name']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'available' => $result['count'] == 0,
        'message' => $result['count'] == 0 ? 'Template name is available' : 'Template name already exists'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 