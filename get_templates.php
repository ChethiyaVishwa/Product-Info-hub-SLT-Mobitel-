<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Get all active templates
    $stmt = $pdo->query("
        SELECT template_id, template_name, created_dtm 
        FROM templates 
        WHERE end_dtm IS NULL 
        ORDER BY created_dtm DESC
    ");
    
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates
    foreach ($templates as &$template) {
        $template['created_dtm'] = date('Y-m-d H:i', strtotime($template['created_dtm']));
    }

    echo json_encode([
        'success' => true,
        'templates' => $templates
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading templates: ' . $e->getMessage()
    ]);
}
?> 