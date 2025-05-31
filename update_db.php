<?php
require_once 'config.php';

try {
    // Check if the column already exists
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = ? 
        AND TABLE_NAME = 'objects' 
        AND COLUMN_NAME = 'image_path'
    ");
    $stmt->execute([$dbname]);
    
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $pdo->exec("
            ALTER TABLE objects
            ADD COLUMN image_path VARCHAR(255) NULL
        ");
        echo "Database updated successfully: Added image_path column to objects table.";
    } else {
        echo "Column image_path already exists in objects table.";
    }
} catch(PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}
?> 