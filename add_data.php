<?php
require_once 'config.php';

// Get template ID from URL
$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$template_id) {
    header('Location: dashboard.php');
    exit;
}

try {
    // Get template details
    $stmt = $pdo->prepare("
        SELECT template_name 
        FROM templates 
        WHERE template_id = ? AND end_dtm IS NULL
    ");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$template) {
        header('Location: dashboard.php');
        exit;
    }

    // Get all fields including parent fields
    $fields_stmt = $pdo->prepare("
        WITH RECURSIVE template_hierarchy AS (
            -- Base case: start with the current template
            SELECT template_id, template_name, root_template_id, 0 as level
            FROM templates
            WHERE template_id = ? AND end_dtm IS NULL
            
            UNION ALL
            
            -- Recursive case: get parent templates
            SELECT t.template_id, t.template_name, t.root_template_id, th.level + 1
            FROM templates t
            INNER JOIN template_hierarchy th ON t.template_id = th.root_template_id
            WHERE t.end_dtm IS NULL
        ),
        ranked_fields AS (
            SELECT 
                tf.field_id,
                tf.field_name,
                tf.field_type,
                tf.description,
                tf.is_fixed,
                tf.field_value,
                t.template_name as source_template,
                th.level,
                ROW_NUMBER() OVER (PARTITION BY tf.field_name ORDER BY th.level ASC) as rn
            FROM template_hierarchy th
            JOIN templates t ON th.template_id = t.template_id
            JOIN template_fields tf ON t.template_id = tf.template_id
            WHERE tf.end_dtm IS NULL
        )
        SELECT 
            field_id,
            field_name,
            field_type,
            description,
            is_fixed,
            field_value,
            source_template
        FROM ranked_fields
        WHERE rn = 1
        ORDER BY is_fixed DESC, field_name ASC");
    $fields_stmt->execute([$template_id]);
    $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Data - <?php echo htmlspecialchars($template['template_name']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
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
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 60px auto;
            max-width: 1200px;
        }
        .form-label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        .form-control {
            border-radius: 4px;
            padding: 4px 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            height: calc(1.5em + 0.5rem + 2px);
        }
        .form-control.form-control-lg {
            padding: 6px 10px;
            font-size: 0.9rem;
            height: calc(1.5em + 0.75rem + 2px);
        }
        .field-row {
            margin-bottom: 12px;
            padding: 12px 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        .field-row:hover {
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .field-row.fixed-field {
            background-color: #e8f4f8;
            border: 1px solid #bee5eb;
            border-left: 4px solid #0dcaf0;
        }
        .field-row.fixed-field:hover {
            background-color: #f0faff;
            box-shadow: 0 2px 8px rgba(13, 202, 240, 0.2);
        }
        .field-divider {
            position: relative;
            text-align: center;
            height: 40px;
            margin: 30px 0;
        }
        .field-divider:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
            z-index: 1;
        }
        .divider-text {
            display: inline-block;
            position: relative;
            background: #fff;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: #495057;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            z-index: 2;
        }
        .field-source {
            font-size: 0.75em;
            color: #6c757d;
            margin-top: 2px;
        }
        .required-field::after {
            content: "*";
            color: #dc3545;
            margin-left: 4px;
            font-weight: bold;
        }
        .badge {
            padding: 2px 6px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        .alert-info {
            background-color: #e8f4f8;
            border-color: #bee5eb;
            color: #0c5460;
            font-size: 0.85rem;
            padding: 8px 12px;
            margin-bottom: 15px;
        }
        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 6px 20px;
            font-size: 0.9rem;
        }
        .btn-primary:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-1px);
        }
        hr {
            margin: 15px 0;
        }
        .text-muted {
            color: #6c757d !important;
        }
        .alert {
            padding: 8px 12px;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
        small.form-text {
            font-size: 0.75em;
        }
        h2.mb-4 {
            font-size: 1.4rem;
            margin-bottom: 0.75rem !important;
        }
        .bi-plus-circle-fill {
            font-size: 1.6rem !important;
            margin-right: 0.4rem !important;
        }
        .bi-chevron-right {
            font-size: 1rem;
            vertical-align: middle;
        }
        .template-title {
            font-size: 1.4rem;
            font-weight: 500;
            color: #333;
        }
        /* Field icons and badges */
        .bi-tag, .bi-input-cursor {
            font-size: 0.9rem;
        }
        .field-source .badge {
            padding: 2px 4px;
            font-size: 0.7rem;
        }
        .field-source .bi {
            font-size: 0.8rem;
        }
        /* Submit button section */
        .d-flex.justify-content-center.mt-4 {
            margin-top: 1rem !important;
        }
        .btn-lg {
            padding: 6px 20px;
            font-size: 0.9rem;
        }
        /* Save notification */
        #saveNotification {
            font-size: 0.85rem;
            padding: 8px 15px;
            min-width: 250px;
        }
        /* Spacing adjustments */
        .mb-4 {
            margin-bottom: 1rem !important;
        }
        .my-4 {
            margin-top: 1rem !important;
            margin-bottom: 1rem !important;
        }
        .me-1 {
            margin-right: 0.25rem !important;
        }
        .me-2 {
            margin-right: 0.4rem !important;
        }
        .image-preview-container {
            margin-top: 10px;
            border: 1px dashed #ddd;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        .image-preview-container:hover {
            background-color: #e9ecef;
        }
        #image_preview img {
            max-width: 100%;
            max-height: 200px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        .remove-image {
            margin-top: 8px;
            color: #dc3545;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .file-upload-label {
            cursor: pointer;
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
            text-align: center;
            padding: 8px 16px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .file-upload-label:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            transform: translateY(-1px);
        }
        .file-upload-input {
            display: none;
        }
        
        /* Responsive Media Queries */
        @media (max-width: 1199.98px) {
            .content-wrapper {
                margin: 40px auto;
                max-width: 95%;
            }
            
            h2.mb-4 {
                font-size: 1.3rem;
            }
            
            .template-title {
                font-size: 1.3rem;
            }
            
            .field-divider {
                margin: 20px 0;
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
            
            h2.mb-4 {
                font-size: 1.2rem;
            }
            
            .template-title {
                font-size: 1.2rem;
            }
            
            .bi-plus-circle-fill {
                font-size: 1.4rem !important;
            }
            
            .field-row {
                padding: 10px;
            }
        }
        
        @media (max-width: 767.98px) {
            .content-wrapper {
                margin: 20px auto;
                padding: 15px;
                border-radius: 8px;
            }
            
            h2.mb-4 {
                font-size: 1.1rem;
                flex-wrap: wrap;
            }
            
            .template-title {
                font-size: 1.1rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
            }
            
            .bi-plus-circle-fill {
                font-size: 1.3rem !important;
            }
            
            .field-divider {
                margin: 15px 0;
                height: 30px;
            }
            
            .divider-text {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }
            
            .field-row {
                padding: 10px;
                margin-bottom: 10px;
            }
            
            .form-control {
                font-size: 0.8rem;
            }
            
            .form-label {
                font-size: 0.85rem;
            }
            
            .input-group-text {
                font-size: 0.8rem;
                padding: 0.25rem 0.5rem;
            }
            
            .btn-lg {
                font-size: 0.85rem;
                padding: 6px 16px;
            }
            
            .navbar-brand img {
                height: 35px;
            }
            
            .image-preview-container {
                padding: 5px;
            }
            
            #image_preview img {
                max-height: 180px;
            }
        }
        
        @media (max-width: 575.98px) {
            .content-wrapper {
                margin: 10px auto;
                padding: 12px;
                border-radius: 6px;
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            }
            
            h2.mb-4 {
                font-size: 1rem;
                line-height: 1.4;
                margin-bottom: 10px !important;
            }
            
            .template-title {
                font-size: 1rem;
            }
            
            .bi-plus-circle-fill {
                font-size: 1.2rem !important;
            }
            
            .bi-chevron-right {
                font-size: 0.8rem;
                margin: 0 5px !important;
            }
            
            .field-row {
                padding: 8px;
                margin-bottom: 8px;
            }
            
            .field-divider {
                margin: 12px 0;
                height: 26px;
            }
            
            .divider-text {
                font-size: 0.8rem;
                padding: 0.3rem 0.6rem;
            }
            
            .form-label {
                font-size: 0.8rem;
                margin-bottom: 0.2rem;
            }
            
            .form-control {
                font-size: 0.75rem;
                padding: 4px 6px;
            }
            
            .input-group-text {
                font-size: 0.75rem;
                padding: 4px 6px;
            }
            
            .text-muted, small {
                font-size: 0.7rem;
            }
            
            .field-source {
                font-size: 0.7em;
            }
            
            .badge {
                font-size: 0.7rem;
            }
            
            .alert {
                padding: 6px 10px;
                font-size: 0.75rem;
            }
            
            .btn-primary {
                padding: 5px 15px;
                font-size: 0.8rem;
            }
            
            .navbar-brand img {
                height: 30px;
            }
            
            #image_preview img {
                max-height: 150px;
            }
            
            #saveNotification {
                min-width: 200px;
                font-size: 0.8rem;
                padding: 6px 10px;
            }
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
        <?php
        // Format template name for display
        $template_parts = explode('_', $template['template_name']);
        $formatted_parts = array_map(function($part) {
            // Split by uppercase letters and trim
            $words = preg_split('/(?=[A-Z])/', $part);
            $words = array_map('trim', $words);
            // Filter out empty strings and join with space
            $words = array_filter($words);
            return ucwords(strtolower(implode(' ', $words)));
        }, $template_parts);
        ?>
        <h2 class="mb-4 d-flex align-items-center flex-wrap">
            <i class="bi bi-plus-circle-fill me-2 d-inline-block" style="color: #28a745;"></i>
            <span style="color: #333;">
                Add 
                <span class="template-title d-inline-flex flex-wrap align-items-center">
                    <?php 
                    echo implode(' <i class="bi bi-chevron-right text-muted mx-1 mx-md-2"></i> ', array_map('htmlspecialchars', $formatted_parts)); 
                    ?>
                </span>
            </span>
        </h2>
        
        <!-- Add success notification -->
        <div id="saveNotification" class="alert alert-success" style="display: none; position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1050; min-width: 300px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="bi bi-check-circle-fill me-2"></i>
            <span class="message">Data saved successfully!</span>
        </div>

        <div class="alert alert-info mb-4" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            Please fill in the details below. Fields marked with <span class="text-danger">*</span> are required.
        </div>

        <form id="addDataForm" method="POST" action="save_object_data.php" enctype="multipart/form-data">
            <input type="hidden" name="template_id" value="<?php echo $template_id; ?>">
            
            <!-- Object Name Field -->
            <div class="field-row mb-4">
                <label for="object_name" class="form-label required-field">
                    <i class="bi bi-tag me-1"></i>
                    Object Name
                </label>
                <input type="text"
                       class="form-control form-control-lg"
                       id="object_name"
                       name="object_name"
                       required
                       placeholder="Enter Object Name">
                <small class="text-muted">Give your object a unique and descriptive name</small>
            </div>
            
            <!-- Object Image Field -->
            <div class="field-row mb-4">
                <label for="object_image" class="form-label">
                    <i class="bi bi-image me-1"></i>
                    Object Image
                </label>
                <div class="input-group">
                    <input type="file"
                           class="form-control"
                           id="object_image"
                           name="object_image"
                           accept="image/*">
                    <label class="input-group-text" for="object_image">
                        <i class="bi bi-upload me-1 d-none d-sm-inline-block"></i>
                        Upload
                    </label>
                </div>
                <small class="text-muted">Upload an image of your object (JPG, PNG, GIF)</small>
                <div id="image_preview" class="image-preview-container mt-2" style="display: none;">
                    <img src="" alt="Image Preview" class="img-thumbnail">
                    <div class="remove-image">
                        <i class="bi bi-x-circle me-1"></i>
                        Remove image
                    </div>
                </div>
            </div>

            <hr class="my-4">
            
            <?php 
            $previousFieldType = null;
            foreach ($fields as $field): 
                $isFixed = (int)$field['is_fixed'];
                // Insert a divider when transitioning from fixed to dynamic fields
                if ($previousFieldType === 1 && $isFixed === 0) {
                    echo '<div class="field-divider my-4">
                        <h5 class="divider-text">
                            <i class="bi bi-arrow-down-circle me-2 d-none d-sm-inline-block"></i>
                            Dynamic Fields
                        </h5>
                    </div>';
                }
                if ($previousFieldType === null && $isFixed === 1) {
                    echo '<div class="field-divider mb-4">
                        <h5 class="divider-text">
                            <i class="bi bi-lock me-2 d-none d-sm-inline-block"></i>
                            Fixed Fields
                        </h5>
                    </div>';
                }
                $previousFieldType = $isFixed;
            ?>
            <div class="field-row <?php echo $field['is_fixed'] ? 'fixed-field' : ''; ?>">
                <label for="<?php echo htmlspecialchars($field['field_name']); ?>" 
                       class="form-label <?php echo $field['is_fixed'] ? '' : 'required-field'; ?>">
                    <i class="bi bi-input-cursor me-1"></i>
                    <?php 
                        $display_field_name = str_replace('_', ' ', $field['field_name']);
                        $display_field_name = ucwords($display_field_name);
                        echo $display_field_name;
                    ?>
                    <?php if ($field['field_type'] === 'date'): ?>
                    <small class="text-muted fw-normal ms-2">(Date)</small>
                    <?php elseif ($field['field_type'] === 'price'): ?>
                    <small class="text-muted fw-normal ms-2">(Price in LKR)</small>
                    <?php elseif ($field['field_type'] === 'email'): ?>
                    <small class="text-muted fw-normal ms-2">(Email)</small>
                    <?php elseif ($field['field_type'] === 'number'): ?>
                    <small class="text-muted fw-normal ms-2">(Number)</small>
                    <?php endif; ?>
                </label>
                
                <?php if ($field['field_type'] === 'date'): ?>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    <input type="date"
                           class="form-control"
                           id="<?php echo htmlspecialchars($field['field_name']); ?>"
                           name="fields[<?php echo $field['field_id']; ?>]"
                           data-field-id="<?php echo $field['field_id']; ?>"
                           data-is-fixed="<?php echo $field['is_fixed']; ?>"
                           <?php echo $field['is_fixed'] ? 'disabled' : 'required'; ?>
                           value="<?php echo isset($field['field_value']) ? htmlspecialchars($field['field_value']) : ''; ?>"
                           placeholder="Select date">
                </div>
                <?php elseif ($field['field_type'] === 'price'): ?>
                <div class="input-group flex-nowrap">
                    <span class="input-group-text">LKR</span>
                    <input type="number"
                           step="0.01"
                           min="0"
                           class="form-control"
                           id="<?php echo htmlspecialchars($field['field_name']); ?>"
                           name="fields[<?php echo $field['field_id']; ?>]"
                           data-field-id="<?php echo $field['field_id']; ?>"
                           data-is-fixed="<?php echo $field['is_fixed']; ?>"
                           <?php echo $field['is_fixed'] ? 'disabled' : 'required'; ?>
                           value="<?php echo isset($field['field_value']) ? htmlspecialchars($field['field_value']) : ''; ?>"
                           placeholder="Enter price">
                    <span class="input-group-text d-none d-sm-block">.00</span>
                </div>
                <?php elseif ($field['field_type'] === 'email'): ?>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email"
                           class="form-control"
                           id="<?php echo htmlspecialchars($field['field_name']); ?>"
                           name="fields[<?php echo $field['field_id']; ?>]"
                           data-field-id="<?php echo $field['field_id']; ?>"
                           data-is-fixed="<?php echo $field['is_fixed']; ?>"
                           <?php echo $field['is_fixed'] ? 'disabled' : 'required'; ?>
                           value="<?php echo isset($field['field_value']) ? htmlspecialchars($field['field_value']) : ''; ?>"
                           placeholder="Enter email address">
                </div>
                <?php elseif ($field['field_type'] === 'number'): ?>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-123"></i></span>
                    <input type="number"
                           class="form-control"
                           id="<?php echo htmlspecialchars($field['field_name']); ?>"
                           name="fields[<?php echo $field['field_id']; ?>]"
                           data-field-id="<?php echo $field['field_id']; ?>"
                           data-is-fixed="<?php echo $field['is_fixed']; ?>"
                           <?php echo $field['is_fixed'] ? 'disabled' : 'required'; ?>
                           value="<?php echo isset($field['field_value']) ? htmlspecialchars($field['field_value']) : ''; ?>"
                           placeholder="Enter number">
                </div>
                <?php else: ?>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-type"></i></span>
                    <input type="text"
                           class="form-control"
                           id="<?php echo htmlspecialchars($field['field_name']); ?>"
                           name="fields[<?php echo $field['field_id']; ?>]"
                           data-field-id="<?php echo $field['field_id']; ?>"
                           data-is-fixed="<?php echo $field['is_fixed']; ?>"
                           <?php echo $field['is_fixed'] ? 'disabled' : 'required'; ?>
                           value="<?php echo isset($field['field_value']) ? htmlspecialchars($field['field_value']) : ''; ?>"
                           placeholder="Enter <?php echo ucwords(str_replace('_', ' ', $field['field_name'])); ?>">
                </div>
                <?php endif; ?>
                
                <?php if ($field['description']): ?>
                <small class="form-text text-muted mt-1">
                    <i class="bi bi-info-circle me-1"></i>
                    <?php echo htmlspecialchars($field['description']); ?>
                </small>
                <?php endif; ?>
                
                <div class="field-source mt-1">
                    <span class="badge <?php echo $field['is_fixed'] ? 'bg-info text-dark' : 'bg-primary'; ?> me-2">
                        <i class="bi <?php echo $field['is_fixed'] ? 'bi-lock-fill' : 'bi-pencil-fill'; ?> me-1"></i>
                        <?php echo $field['is_fixed'] ? 'Fixed Field' : 'Required Field'; ?>
                    </span>
                    <?php if ($field['source_template'] != $template['template_name']): ?>
                    <small class="text-muted">
                        <i class="bi bi-box-arrow-up-right me-1"></i>
                        From: <?php echo htmlspecialchars($field['source_template']); ?>
                    </small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="d-flex justify-content-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg w-100 w-md-auto px-4 px-md-5">
                    <i class="bi bi-check-circle me-2"></i>
                    Submit Data
                </button>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Adjust UI based on screen size
            function adjustUIForScreenSize() {
                const windowWidth = $(window).width();
                
                // Adjust form fields on smaller screens
                if (windowWidth <= 767) {
                    $('.input-group').addClass('flex-wrap');
                    $('.field-row').addClass('p-2');
                    
                    // Make success notification narrower on mobile
                    $('#saveNotification').css('min-width', '80%');
                } else {
                    $('.input-group').removeClass('flex-wrap');
                    $('.field-row').removeClass('p-2');
                    
                    // Reset success notification width on desktop
                    $('#saveNotification').css('min-width', '300px');
                }
            }
            
            // Call on page load
            adjustUIForScreenSize();
            
            // Call on window resize
            $(window).resize(function() {
                adjustUIForScreenSize();
            });
            
            // Image preview
            $('#object_image').on('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#image_preview').show();
                        $('#image_preview img').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(file);
                } else {
                    $('#image_preview').hide();
                }
            });
            
            // Remove image
            $('.remove-image').on('click', function() {
                $('#object_image').val('');
                $('#image_preview').hide();
                $('#image_preview img').attr('src', '');
            });
            
            // Add input validation visual feedback
            $('input').on('input', function() {
                if ($(this).is(':invalid')) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                }
            });
            
            // Price field formatting
            $('input[type="number"][step="0.01"]').on('blur', function() {
                const value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    $(this).val(value.toFixed(2));
                }
            });
            
            $('#addDataForm').on('submit', function(e) {
                e.preventDefault();
                
                // Validate all required fields
                let isValid = true;
                $('input[required]').each(function() {
                    if (!$(this).val()) {
                        $(this).addClass('is-invalid');
                        isValid = false;
                    } else {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                    }
                });
                
                if (!isValid) {
                    $('html, body').animate({
                        scrollTop: $('.is-invalid').first().offset().top - 100
                    }, 500);
                    return false;
                }
                
                // Create FormData object for file uploads
                const formData = new FormData(this);
                
                // Submit button loading state
                const submitBtn = $('button[type="submit"]');
                const originalBtnHtml = submitBtn.html();
                submitBtn.html('<i class="bi bi-hourglass-split me-2"></i>Saving...').prop('disabled', true);
                
                // Submit data with file upload
                $.ajax({
                    url: 'save_object_data.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success notification with animation
                            $('#saveNotification').fadeIn('fast').delay(2000).fadeOut('slow');
                            
                            // Reset button state
                            submitBtn.html(originalBtnHtml).prop('disabled', false);
                            
                            // Redirect after delay
                            setTimeout(function() {
                                window.location.href = 'dashboard.php';
                            }, 2500);
                        } else {
                            // Reset button state
                            submitBtn.html(originalBtnHtml).prop('disabled', false);
                            alert(response.message || 'Error saving data');
                        }
                    },
                    error: function() {
                        // Reset button state
                        submitBtn.html(originalBtnHtml).prop('disabled', false);
                        alert('Error connecting to server');
                    }
                });
            });
        });
    </script>
</body>
</html> 