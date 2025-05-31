<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['field_id'])) {
    echo json_encode(['success' => false, 'message' => 'Field ID is required']);
    exit;
}

$field_id = intval($_GET['field_id']);

try {
    $stmt = $pdo->prepare("SELECT field_value FROM template_fields WHERE field_id = ? AND end_dtm IS NULL");
    $stmt->execute([$field_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(['success' => true, 'value' => $result['field_value']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Field not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 