<?php
require_once 'config.php';

// Get template ID from URL parameter
$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$template_id) {
    header('Location: dashboard.php');
    exit;
}

try {
    // Get template name
    $stmt = $pdo->prepare("
        SELECT template_name
        FROM templates 
        WHERE template_id = ? AND end_dtm IS NULL
    ");
    $stmt->execute([$template_id]);
    $template_name = $stmt->fetchColumn();
    
    // Convert array to string
    $formatted_template = preg_split('/(?=[A-Z])/', $template_name);
    $formatted_template = array_filter($formatted_template);
    $formatted_template = ucwords(str_replace('_', ' ', implode(' ', $formatted_template)));

    // Get template hierarchy path
    $stmt = $pdo->prepare("
        WITH RECURSIVE template_hierarchy AS (
            -- Base case: start with the current template
            SELECT 
                template_id, 
                template_name,
                root_template_id,
                CAST(template_name AS CHAR(255)) as path,
                1 as level
            FROM templates 
            WHERE template_id = ? AND end_dtm IS NULL
            
            UNION ALL
            
            -- Recursive case: get parent templates
            SELECT 
                t.template_id, 
                t.template_name,
                t.root_template_id,
                CONCAT(t.template_name, '_', th.path),
                th.level + 1
            FROM templates t
            INNER JOIN template_hierarchy th ON t.template_id = th.root_template_id
            WHERE t.end_dtm IS NULL
        )
        SELECT DISTINCT template_name 
        FROM template_hierarchy 
        ORDER BY level DESC
    ");
    $stmt->execute([$template_id]);
    $template_path_parts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Convert array to string
    $template_path = implode('_', $template_path_parts);
    
    // Get objects for this template
    $stmt = $pdo->prepare("
        SELECT object_id, object_name, image_path 
        FROM objects 
        WHERE template_id = ? AND end_dtm IS NULL 
        ORDER BY object_name
    ");
    $stmt->execute([$template_id]);
    $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all fields and their values for each object
    $fields_query = "
        WITH RECURSIVE template_hierarchy AS (
            SELECT template_id, template_name, root_template_id, 0 as level
            FROM templates
            WHERE template_id = ? AND end_dtm IS NULL
            
            UNION ALL
            
            SELECT t.template_id, t.template_name, t.root_template_id, th.level + 1
            FROM templates t
            INNER JOIN template_hierarchy th ON t.template_id = th.root_template_id
            WHERE t.end_dtm IS NULL
        ),
        ranked_fields AS (
            SELECT 
                tf.field_id,
                tf.field_name,
                tf.is_fixed,
                ofv.field_value,
                tf.field_value as default_value,
                t.template_name as source_template,
                ROW_NUMBER() OVER (PARTITION BY tf.field_name ORDER BY th.level ASC) as rn
            FROM template_hierarchy th
            JOIN templates t ON th.template_id = t.template_id
            JOIN template_fields tf ON t.template_id = tf.template_id
            LEFT JOIN object_field_values ofv ON tf.field_id = ofv.field_id AND ofv.object_id = ? AND ofv.end_dtm IS NULL
            WHERE tf.end_dtm IS NULL
        )
        SELECT 
            field_id,
            field_name,
            is_fixed,
            field_value,
            default_value,
            source_template
        FROM ranked_fields
        WHERE rn = 1
        ORDER BY is_fixed DESC, field_name ASC";

    $fields_by_object = [];
    foreach ($objects as $object) {
        $stmt = $pdo->prepare($fields_query);
        $stmt->execute([$template_id, $object['object_id']]);
        $fields_by_object[$object['object_id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Template Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-image: url('https://cdn.pixabay.com/photo/2020/10/21/01/56/digital-5671888_1280.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            margin: 0;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, rgb(3, 7, 53) 0%, rgb(16, 35, 117) 100%) !important;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
            position: relative;
            padding: 8px 0;
            min-height: 60px;
            border-bottom: 2px solid #28a745;
        }
        .navbar .container-fluid {
            padding-left: 30px;
            padding-right: 30px;
            position: relative;
            z-index: 1;
        }
        .navbar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(3, 7, 53, 0.95), rgba(40, 167, 69, 0.1));
            pointer-events: none;
        }
        .navbar::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(to right, #28a745, #0dcaf0);
        }
        .navbar .nav-link {
            color: #ffffff !important;
            font-weight: 500;
            padding: 6px 12px;
            font-size: 0.9rem;
            margin: 0 6px;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }
        .navbar .nav-link:hover {
            color: #90caf9 !important;
            transform: translateY(-1px);
        }
        .navbar .nav-link.home-btn {
            background: linear-gradient(135deg, #0dcaf0, #0891b2);
            color: #ffffff !important;
            font-weight: bold;
            border-radius: 6px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .navbar .nav-link.home-btn:hover {
            background: linear-gradient(135deg, #0891b2, #0dcaf0);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(13, 202, 240, 0.3);
        }
        .navbar .nav-link.logout-btn {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            color: #ffffff !important;
            font-weight: bold;
            border-radius: 6px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .navbar .nav-link.logout-btn:hover {
            background: linear-gradient(135deg, #b02a37, #dc3545);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }
        .navbar-brand {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            padding: 4px 12px;
            border-radius: 6px;
            margin-left: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .navbar-brand:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .navbar-brand img {
            height: 40px;
        }
        .navbar .navbar-nav {
            align-items: center;
            gap: 12px;
        }
        .navbar .profile-img {
            height: 32px;
            width: auto;
            filter: brightness(0) invert(1);
            margin-left: 8px;
            transition: transform 0.3s ease;
        }
        .navbar .profile-img:hover {
            transform: scale(1.1);
        }
        /* Add styles for the superadmin management button */
        .navbar .nav-link.superadmin-btn {
            background: linear-gradient(135deg, #28a745, #198754);
            color: #ffffff !important;
            font-weight: 500;
            border-radius: 4px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 6px 12px;
            font-size: 0.9rem;
        }
        .navbar .nav-link.home-btn,
        .navbar .nav-link.logout-btn {
            padding: 6px 12px;
            font-size: 0.9rem;
            border-radius: 4px;
        }
        .content-wrapper {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 60px auto;
            max-width: 1200px;
        }
        .template-path {
            color: #666;
            font-size: 1rem;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #28a745;
        }
        .template-path span {
            color: #333;
            font-weight: 500;
        }
        .template-path i {
            color: #6c757d;
            margin: 0 10px;
        }
        .object-name {
            padding: 8px 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin: 6px 0;
            transition: all 0.2s ease;
        }
        .object-name:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .object-name.active {
            background-color: #e7f5ff;
            border-color: #007bff;
            box-shadow: 0 2px 6px rgba(0,123,255,0.1);
        }
        .object-name.editing {
            background-color: #fff3cd;
            border-color: #ffc107;
            box-shadow: 0 2px 6px rgba(255,193,7,0.2);
        }
        .object-name.active .btn-group .btn {
            opacity: 1;
        }
        .btn-group {
            display: flex;
            gap: 8px;
        }
        .field-list {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
        .field-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        .field-item:last-child {
            border-bottom: none;
        }
        .field-item:hover {
            background-color: #f8f9fa;
        }
        .field-item.editing {
            background-color: #fff3cd;
            border-radius: 4px;
            border-left: 3px solid #ffc107;
        }
        .field-name {
            font-size: 0.9rem;
            font-weight: 500;
            color: #333;
            flex: 1;
        }
        .field-value {
            flex: 2;
        }
        .field-value input {
            width: 100%;
            padding: 4px 8px;
            font-size: 0.9rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            height: 32px;
            transition: all 0.3s ease;
        }
        .field-value input:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
            outline: 0;
        }
        .field-value input[disabled] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .fixed-badge, .parent-badge {
            font-size: 0.75rem;
            padding: 1px 6px;
            border-radius: 3px;
            margin-left: 6px;
        }
        .fixed-badge {
            background-color: #e2f2ff;
            color: #0275d8;
            border: 1px solid #b8daff;
        }
        .parent-badge {
            background-color: #f2e7ff;
            color: #6f42c1;
            border: 1px solid #d6c7ed;
        }
        .locked-field {
            opacity: 0.9;
            background-color: #f8f9fa;
        }
        .btn-group .btn {
            padding: 4px 10px;
            font-size: 0.85rem;
            margin: 0 2px;
            border-radius: 4px;
            height: 28px;
            min-width: 70px;
        }
        .btn-group .btn i {
            font-size: 0.85rem;
            margin-right: 3px;
        }
        .btn-edit {
            color: #fff;
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-edit:hover {
            background-color: #218838;
            border-color: #1e7e34;
            color: #fff;
        }
        .btn-delete {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #c82333;
            border-color: #bd2130;
            color: #fff;
        }
        .btn-save {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
            display: none;
        }
        .btn-save:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            color: #fff;
        }
        .edit-name-input {
            height: 32px;
            padding: 4px 8px;
            font-size: 0.9rem;
        }
        /* Image styles */
        .object-image-container {
            margin-top: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px dashed #dee2e6;
            text-align: center;
        }
        .current-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .image-actions {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .image-placeholder {
            width: 150px;
            height: 150px;
            background-color: #e9ecef;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            margin: 0 auto;
        }
        .image-placeholder i {
            font-size: 3rem;
        }
        .upload-new-image {
            display: none;
            margin-top: 10px;
        }
        .btn-remove-image {
            color: #dc3545;
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }
        .btn-remove-image:hover {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        /* Responsive Media Queries */
        @media (max-width: 1199.98px) {
            .content-wrapper {
                margin: 40px auto;
                max-width: 95%;
                padding: 15px;
            }
            
            .btn-group .btn {
                min-width: 60px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 991.98px) {
            .navbar .container-fluid {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .navbar-brand {
                margin-left: 0;
            }
            
            .navbar .nav-link {
                margin: 0 3px;
                padding: 5px 8px;
                font-size: 0.85rem;
            }
            
            .navbar-collapse {
                background: rgba(3, 7, 53, 0.95);
                border-radius: 0 0 10px 10px;
                padding: 10px;
                margin: 0 -15px;
            }
            
            .navbar-toggler {
                border: 2px solid rgba(255, 255, 255, 0.5);
                padding: 0.25rem 0.5rem;
                background: rgba(255, 255, 255, 0.1);
                transition: all 0.3s ease;
            }
            
            .navbar-toggler:focus {
                box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.25);
                outline: none;
            }
            
            .navbar-toggler:hover {
                background: rgba(255, 255, 255, 0.2);
            }
            
            .navbar-toggler-icon {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
            }
            
            .content-wrapper {
                margin: 30px auto;
                padding: 15px;
            }
            
            .field-list {
                padding: 12px;
            }
            
            .object-name {
                padding: 7px 12px;
            }
            
            .btn-group {
                gap: 6px;
            }
            
            .btn-group .btn {
                padding: 3px 8px;
                min-width: 50px;
                font-size: 0.75rem;
            }
            
            .current-image {
                max-height: 180px;
            }
        }
        
        @media (max-width: 767.98px) {
            .content-wrapper {
                margin: 20px auto;
                padding: 15px;
                border-radius: 8px;
            }
            
            .template-path {
                font-size: 0.9rem;
                padding: 8px;
                margin-bottom: 15px;
            }
            
            .object-name {
                padding: 6px 10px;
                margin: 5px 0;
                display: flex;
                flex-direction: column;
            }
            
            .object-title {
                margin-bottom: 8px;
                width: 100%;
            }
            
            .btn-group {
                display: flex;
                width: 100%;
                gap: 5px;
                justify-content: center;
            }
            
            .btn-group .btn {
                flex: 1;
                padding: 4px 5px;
                font-size: 0.75rem;
                min-width: auto;
            }
            
            .field-item {
                flex-direction: column;
                align-items: flex-start;
                padding: 8px;
            }
            
            .field-name {
                margin-bottom: 5px;
                width: 100%;
            }
            
            .field-value {
                width: 100%;
            }
            
            .fixed-badge, .parent-badge {
                font-size: 0.7rem;
                padding: 1px 4px;
            }
            
            .current-image {
                max-height: 160px;
            }
            
            .navbar-brand img {
                height: 35px;
            }
        }
        
        @media (max-width: 575.98px) {
            .content-wrapper {
                margin: 10px auto;
                padding: 12px;
                border-radius: 6px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            
            .template-path {
                font-size: 0.85rem;
                padding: 6px;
                margin-bottom: 10px;
            }
            
            .template-path i {
                margin: 0 6px;
            }
            
            .object-name {
                padding: 5px 8px;
                margin: 4px 0;
            }
            
            .object-title {
                font-size: 0.9rem;
                margin-bottom: 6px;
            }
            
            .btn-group {
                gap: 3px;
            }
            
            .btn-group .btn {
                padding: 3px 5px;
                font-size: 0.7rem;
                height: 26px;
            }
            
            .btn-group .btn i {
                margin-right: 2px;
                font-size: 0.75rem;
            }
            
            .field-list {
                padding: 8px;
                margin-top: 15px;
            }
            
            .field-item {
                padding: 6px;
            }
            
            .field-name {
                font-size: 0.85rem;
            }
            
            .field-value input {
                font-size: 0.85rem;
                height: 30px;
                padding: 2px 6px;
            }
            
            .fixed-badge, .parent-badge {
                font-size: 0.65rem;
                padding: 1px 3px;
                margin-left: 4px;
            }
            
            .object-image-container {
                padding: 8px;
                margin: 10px 0;
            }
            
            .current-image {
                max-height: 140px;
            }
            
            .image-actions {
                margin-top: 8px;
                gap: 5px;
            }
            
            .image-placeholder {
                width: 120px;
                height: 120px;
            }
            
            .image-placeholder i {
                font-size: 2.5rem;
            }
            
            .upload-new-image .form-label {
                font-size: 0.8rem;
            }
            
            .navbar-brand img {
                height: 30px;
            }
        }
        
        /* Add animation for updates */
        @keyframes highlight {
            0% { background-color: rgba(255, 193, 7, 0.1); }
            50% { background-color: rgba(255, 193, 7, 0.3); }
            100% { background-color: transparent; }
        }
        
        .field-item.updated {
            animation: highlight 1.5s ease;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="images/logo.png" alt="Product Info Hub">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link me-3 home-btn" href="dashboard.php">HOME</a>
                    <a class="nav-link me-3" href="#">HELLO!</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="template-path">
            <i class="bi bi-diagram-3 me-2"></i>
            <span><?php echo htmlspecialchars($template_name); ?></span>
        </div>

        <!-- Object Selection -->
        <div class="object-selection container-fluid px-0">
            <div class="row">
                <?php foreach ($objects as $object): ?>
                <div class="col-lg-12">
                    <div class="object-name" data-object-id="<?php echo $object['object_id']; ?>">
                        <div class="d-flex justify-content-between flex-wrap">
                            <div class="object-title">
                                <span class="display-name"><?php echo htmlspecialchars($object['object_name']); ?></span>
                                <input type="text" class="form-control edit-name-input" style="display:none;"
                                    value="<?php echo htmlspecialchars($object['object_name']); ?>">
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-edit" onclick="editObject(<?php echo $object['object_id']; ?>)" title="Edit Object">
                                    <i class="bi bi-pencil-square"></i> <span class="d-none d-sm-inline-block">Edit</span>
                                </button>
                                <button class="btn btn-save" onclick="saveObject(<?php echo $object['object_id']; ?>)" title="Save Changes" style="display:none;">
                                    <i class="bi bi-check-circle"></i> <span class="d-none d-sm-inline-block">Save</span>
                                </button>
                                <button class="btn btn-delete" onclick="deleteObject(<?php echo $object['object_id']; ?>)" title="Delete Object">
                                    <i class="bi bi-trash3"></i> <span class="d-none d-sm-inline-block">Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Field Values -->
        <?php foreach ($objects as $object): ?>
        <div class="field-list" id="fields-<?php echo $object['object_id']; ?>" style="display: none;">
            <form id="form-<?php echo $object['object_id']; ?>" enctype="multipart/form-data">
                <!-- Object Image -->
                <div class="object-image-container">
                    <h5 class="mb-3">Object Image</h5>
                    <?php if (!empty($object['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($object['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($object['object_name']); ?>" 
                             class="current-image">
                        <div class="image-actions">
                            <button type="button" class="btn btn-sm btn-remove-image" 
                                    onclick="removeImage(<?php echo $object['object_id']; ?>)" disabled>
                                <i class="bi bi-trash3"></i> Remove Image
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="image-placeholder">
                            <i class="bi bi-image"></i>
                        </div>
                        <p class="text-muted small mt-2">No image available</p>
                    <?php endif; ?>
                    <div class="upload-new-image">
                        <label for="new_image_<?php echo $object['object_id']; ?>" class="form-label">Upload new image:</label>
                        <input type="file" class="form-control" 
                               id="new_image_<?php echo $object['object_id']; ?>" 
                               name="new_image" 
                               accept="image/*" disabled>
                        <div class="preview-container mt-2" style="display: none;">
                            <img src="" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    </div>
                    <input type="hidden" name="remove_image" value="0">
                </div>

                <div class="row">
                    <div class="col-12">
                        <?php foreach ($fields_by_object[$object['object_id']] as $field): ?>
                        <div class="field-item <?php echo ($field['is_fixed'] || $field['source_template'] !== $template_name) ? 'locked-field' : ''; ?>">
                            <div class="field-name">
                                <?php 
                                    $words = explode('_', $field['field_name']);
                                    echo ucwords(implode(' ', $words));
                                ?>
                                <?php if ($field['is_fixed']): ?>
                                    <span class="fixed-badge">Fixed</span>
                                <?php endif; ?>
                                <?php if ($field['source_template'] !== $template_name): ?>
                                    <span class="parent-badge">Parent Template</span>
                                <?php endif; ?>
                            </div>
                            <div class="field-value">
                                <input type="text" 
                                    name="field_<?php echo $field['field_id']; ?>" 
                                    value="<?php echo htmlspecialchars($field['field_value'] ?? $field['default_value'] ?? ''); ?>"
                                    class="form-control"
                                    <?php if ($field['is_fixed'] || $field['source_template'] !== $template_name): ?>
                                    readonly
                                    <?php endif; ?>
                                    disabled>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Function to adjust UI based on screen size
            function adjustForMobile() {
                const windowWidth = $(window).width();
                
                if (windowWidth <= 767) {
                    $('.field-item').addClass('mb-3');
                    $('.object-image-container').addClass('mb-4');
                }
                else {
                    $('.field-item').removeClass('mb-3');
                    $('.object-image-container').removeClass('mb-4');
                }
            }
            
            // Call on page load and window resize
            adjustForMobile();
            $(window).resize(adjustForMobile);
        
            // Show first object's fields by default
            const firstObjectId = $('.object-name').first().data('object-id');
            $('.object-name').first().addClass('active');
            $('#fields-' + firstObjectId).show();

            // Handle object selection
            $('.object-name').click(function(e) {
                // Only trigger if not clicking on button or input
                if (!$(e.target).is('button, input, i, span.d-none')) {
                    const objectId = $(this).data('object-id');
                    
                    // Update active state
                    $('.object-name').removeClass('active');
                    $(this).addClass('active');
                    
                    // Show selected object's fields
                    $('.field-list').hide();
                    $('#fields-' + objectId).fadeIn(300);
                    
                    // On mobile, scroll to the fields
                    if ($(window).width() < 768) {
                        $('html, body').animate({
                            scrollTop: $('#fields-' + objectId).offset().top - 20
                        }, 300);
                    }
                }
            });
        });

        function editObject(objectId) {
            const objectDiv = $(`.object-name[data-object-id="${objectId}"]`);
            const form = $(`#form-${objectId}`);
            
            // Enable only editable field inputs (not fixed or from parent templates)
            form.find('input').not('[readonly]').prop('disabled', false);
            form.find('.field-item').not('.locked-field').addClass('editing');
            
            // Enable image actions
            form.find('.btn-remove-image').prop('disabled', false);
            form.find('.upload-new-image').show();
            form.find('.upload-new-image input').prop('disabled', false);
            
            // Show edit name input
            objectDiv.find('.display-name').hide();
            objectDiv.find('.edit-name-input').show();
            
            // Toggle buttons
            objectDiv.find('.btn-edit').hide();
            objectDiv.find('.btn-save').show();
            
            // Add visual indicator that the object is being edited
            objectDiv.addClass('editing');
        }

        function saveObject(objectId) {
            const objectDiv = $(`.object-name[data-object-id="${objectId}"]`);
            const form = $(`#form-${objectId}`);
            const newName = objectDiv.find('.edit-name-input').val();
            
            // Create FormData for file upload
            const formData = new FormData(form[0]);
            formData.append('object_id', objectId);
            formData.append('object_name', newName);

            // Save button loading state
            const saveBtn = objectDiv.find('.btn-save');
            const originalBtnHtml = saveBtn.html();
            saveBtn.html('<i class="bi bi-hourglass-split"></i>').prop('disabled', true);

            // Save changes
            $.ajax({
                url: 'save_object.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Update display name
                        objectDiv.find('.display-name').text(newName).show();
                        objectDiv.find('.edit-name-input').hide();
                        
                        // Disable inputs and toggle buttons
                        form.find('input').prop('disabled', true);
                        form.find('.field-item').removeClass('editing');
                        form.find('.btn-remove-image').prop('disabled', true);
                        form.find('.upload-new-image').hide();
                        
                        // If image was updated, refresh the page
                        if (formData.get('new_image') && formData.get('new_image').size > 0 || 
                            formData.get('remove_image') === '1') {
                            
                            // Show loading indicator
                            const loadingAlert = $('<div class="alert alert-info alert-dismissible fade show mt-3" role="alert">')
                                .text('Image updated. Refreshing page...')
                                .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                            form.prepend(loadingAlert);
                            
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                            return;
                        }
                        
                        // Reset button states and remove editing indicator
                        objectDiv.removeClass('editing');
                        saveBtn.html(originalBtnHtml).prop('disabled', false);
                        saveBtn.hide();
                        objectDiv.find('.btn-edit').show();
                        
                        // Show success message
                        const alert = $('<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">')
                            .text('Changes saved successfully!')
                            .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                        
                        form.prepend(alert);
                        setTimeout(() => alert.alert('close'), 3000);
                        
                        // Scroll to top of form on mobile
                        if ($(window).width() < 768) {
                            $('html, body').animate({
                                scrollTop: form.offset().top - 20
                            }, 300);
                        }
                    } else {
                        // Reset button state
                        saveBtn.html(originalBtnHtml).prop('disabled', false);
                        
                        const alert = $('<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">')
                            .text(response.message || 'Error saving changes')
                            .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                        
                        form.prepend(alert);
                    }
                },
                error: function() {
                    // Reset button state
                    saveBtn.html(originalBtnHtml).prop('disabled', false);
                    
                    const alert = $('<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">')
                        .text('Error connecting to server')
                        .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                    
                    form.prepend(alert);
                }
            });
        }

        function deleteObject(objectId) {
            if (confirm('Are you sure you want to delete this object? This action cannot be undone.')) {
                const objectDiv = $(`.object-name[data-object-id="${objectId}"]`);
                
                // Delete button loading state
                const deleteBtn = objectDiv.find('.btn-delete');
                const originalBtnHtml = deleteBtn.html();
                deleteBtn.html('<i class="bi bi-hourglass-split"></i>').prop('disabled', true);
                
                $.post('delete_object.php', { object_id: objectId }, function(response) {
                    if (response.success) {
                        const fieldList = $(`#fields-${objectId}`);
                        
                        // Add fade-out animation
                        objectDiv.fadeOut(400, function() { $(this).remove(); });
                        fieldList.fadeOut(400, function() { $(this).remove(); });
                        
                        // If this was the active object, show the first remaining object
                        if (objectDiv.hasClass('active')) {
                            const firstRemaining = $('.object-name').first();
                            if (firstRemaining.length) {
                                firstRemaining.addClass('active');
                                $(`#fields-${firstRemaining.data('object-id')}`).fadeIn(400);
                                
                                // Scroll to the selected object on mobile
                                if ($(window).width() < 768) {
                                    $('html, body').animate({
                                        scrollTop: firstRemaining.offset().top - 20
                                    }, 300);
                                }
                            }
                        }
                    } else {
                        // Reset button state
                        deleteBtn.html(originalBtnHtml).prop('disabled', false);
                        alert(response.message || 'Error deleting object');
                    }
                })
                .fail(function() {
                    // Reset button state
                    deleteBtn.html(originalBtnHtml).prop('disabled', false);
                    alert('Error connecting to server');
                });
            }
        }
        
        // Handle image upload preview
        $('input[type="file"]').on('change', function() {
            const file = this.files[0];
            const previewContainer = $(this).siblings('.preview-container');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.show();
                    previewContainer.find('img').attr('src', e.target.result);
                    
                    // On mobile, scroll to show the preview
                    if ($(window).width() < 768) {
                        setTimeout(() => {
                            $('html, body').animate({
                                scrollTop: previewContainer.offset().top - 50
                            }, 300);
                        }, 100);
                    }
                }
                reader.readAsDataURL(file);
            } else {
                previewContainer.hide();
            }
        });
        
        // Handle remove image
        function removeImage(objectId) {
            const form = $(`#form-${objectId}`);
            form.find('input[name="remove_image"]').val('1');
            form.find('.current-image').css('opacity', '0.5');
            form.find('.btn-remove-image').text('Image will be removed').prop('disabled', true).addClass('text-muted');
            
            // Add visual indicator
            form.find('.object-image-container').addClass('bg-light border border-danger border-opacity-25');
            
            // Add restore option on mobile
            if ($(window).width() < 768) {
                const restoreBtn = $('<button type="button" class="btn btn-sm btn-outline-secondary mt-2">Cancel remove</button>');
                restoreBtn.on('click', function() {
                    form.find('input[name="remove_image"]').val('0');
                    form.find('.current-image').css('opacity', '1');
                    form.find('.btn-remove-image').text('Remove Image').prop('disabled', false).removeClass('text-muted');
                    form.find('.object-image-container').removeClass('bg-light border border-danger border-opacity-25');
                    $(this).remove();
                });
                form.find('.image-actions').append(restoreBtn);
            }
        }
    </script>
</body>
</html> 