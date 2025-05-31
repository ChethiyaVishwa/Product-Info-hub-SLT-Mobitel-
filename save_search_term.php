<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_POST['template_id']) || !isset($_POST['search_term'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$template_id = intval($_POST['template_id']);
$search_term = trim($_POST['search_term']);

if (empty($search_term)) {
    echo json_encode(['success' => false, 'message' => 'Search term cannot be empty']);
    exit;
}

try {
    // Save the search term in the database
    // This is a placeholder implementation - you may need to adjust this
    // to fit your actual database schema and requirements
    $stmt = $pdo->prepare("
        INSERT INTO template_search_terms (
            template_id,
            search_term,
            created_by,
            created_dtm
        ) VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $template_id,
        $search_term,
        'system' // Replace with actual user ID when you have authentication
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Search term saved successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Error saving search term: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error saving search term: ' . $e->getMessage()
    ]);
}
?> 