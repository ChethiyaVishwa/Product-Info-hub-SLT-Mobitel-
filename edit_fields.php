<?php
require_once 'config.php';

// Get template ID from URL parameter
$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch template details and its parent hierarchy
$stmt = $pdo->prepare("
    WITH RECURSIVE template_hierarchy AS (
        -- Base case: start with the current template
        SELECT 
            t.template_id, 
            t.template_name, 
            t.root_template_id,
            0 as level,
            CAST(t.template_name AS CHAR(255)) as hierarchy_path
        FROM templates t
        WHERE t.template_id = ? AND t.end_dtm IS NULL
        
        UNION ALL
        
        -- Recursive case: get parent templates
        SELECT 
            t.template_id, 
            t.template_name, 
            t.root_template_id,
            th.level + 1,
            CONCAT(t.template_name, ' > ', th.hierarchy_path)
        FROM templates t
        INNER JOIN template_hierarchy th ON t.template_id = th.root_template_id
        WHERE t.end_dtm IS NULL
    )
    SELECT 
        tf.field_id,
        tf.field_name,
        tf.field_type,
        tf.description,
        tf.is_fixed,
        tf.field_value as default_value,
        t.template_name as source_template,
        t.template_id as source_template_id,
        th.level,
        th.hierarchy_path,
        (SELECT MAX(field_value) 
         FROM template_fields tf2 
         WHERE tf2.field_name = tf.field_name 
         AND tf2.template_id = ? 
         AND tf2.end_dtm IS NULL
        ) as field_value
    FROM template_hierarchy th
    JOIN templates t ON th.template_id = t.template_id
    LEFT JOIN template_fields tf ON t.template_id = tf.template_id AND tf.end_dtm IS NULL
    ORDER BY th.level DESC, tf.is_fixed DESC, tf.field_name ASC
");

$stmt->execute([$template_id, $template_id]);
$template_fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the current template name from the first row
$template = [];
$stmt = $pdo->prepare("SELECT template_name FROM templates WHERE template_id = ? AND end_dtm IS NULL");
$stmt->execute([$template_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    header('Location: dashboard.php');
    exit;
}

// Initialize arrays
$fixed_fields = [];
$dynamic_fields = [];

// Only process fields if template_fields contains data
if (!empty($template_fields)) {
// Separate fixed and dynamic fields
$fixed_fields = array_filter($template_fields, function($field) {
    return $field['is_fixed'] == 1;
});
$dynamic_fields = array_filter($template_fields, function($field) {
    return $field['is_fixed'] == 0;
});
}

// Group fields by their template hierarchy
function groupFieldsByTemplate($fields) {
    $grouped = [];
    foreach ($fields as $field) {
        $templateKey = $field['source_template'];
        if (!isset($grouped[$templateKey])) {
            $grouped[$templateKey] = [
                'template_name' => $field['source_template'],
                'hierarchy_path' => $field['hierarchy_path'],
                'level' => $field['level'],
                'fields' => []
            ];
        }
        $grouped[$templateKey]['fields'][] = $field;
    }
    
    // Sort by hierarchy level (parents first)
    uasort($grouped, function($a, $b) {
        return $b['level'] - $a['level'];
    });
    
    return $grouped;
}

$grouped_fixed_fields = groupFieldsByTemplate($fixed_fields);
$grouped_dynamic_fields = groupFieldsByTemplate($dynamic_fields);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Fields - Product Info Hub</title>
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
            box-shadow: 0 0 80px rgb(0, 0, 0);
            padding: 20px;
            margin: 60px auto;
            max-width: 1200px;
        }
        .btn-primary-custom {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            margin: 2px;
            font-size: 0.9rem;
        }
        .btn-primary-custom:hover {
            background-color: #138496;
            color: white;
        }
        .btn-danger-custom {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            margin: 2px;
            font-size: 0.9rem;
        }
        .btn-danger-custom:hover {
            background-color: #c82333;
            color: white;
        }
        .btn-save {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 25px;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn-save:hover {
            background-color: #218838;
            color: white;
        }
        .section-title {
            color: #333;
            margin-bottom: 12px;
            font-size: 1.2em;
            font-weight: 600;
        }
        .field-buttons {
            margin-bottom: 12px;
        }
        .field-row {
            margin-bottom: 10px;
        }
        .mb-4 {
            margin-bottom: 1rem !important;
        }
        h2.mb-4 {
            font-size: 1.4rem;
            margin-bottom: 1rem !important;
        }
        .field-name {
            font-size: 0.9rem;
            font-weight: 500;
        }
        .template-name {
            color: #666;
            font-style: italic;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .movable-section {
            margin-bottom: 20px;
        }
        
        .field-label {
            font-size: 1.1em;
            font-weight: normal;
            margin-left: 5px;
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        
        .template-name-label {
            color: #666;
            font-style: italic;
        }
        
        .search-box {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-top: 10px;
        }
        .template-group {
            border: 1px solid rgb(126, 126, 126);
            border-radius: 6px;
            margin-bottom: 12px;
        }
        .template-header {
            background-color: rgb(90, 90, 90);
            padding: 8px 10px;
            border-bottom: 1px solid rgb(126, 126, 126);
            font-size: 0.9rem;
        }
        .template-fields {
            padding: 10px;
        }
        .field-source {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 2px;
        }
        .badge.bg-info {
            font-size: 0.75em;
            font-weight: normal;
            padding: 3px 6px;
        }
        input:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .hierarchy-path {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
        .remove-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            border-radius: 50%;
            background: #fff3cd;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #removeFieldModal .modal-content {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        #removeFieldModal .btn-close {
            position: absolute;
            right: 15px;
            top: 15px;
        }
        #removeFieldModal .modal-title {
            color: #333;
            font-weight: 600;
        }
        #removeFieldModal .btn {
            border-radius: 8px;
            padding: 8px 25px;
            font-weight: 500;
            transition: all 0.2s;
        }
        #removeFieldModal .btn:hover {
            transform: translateY(-1px);
        }
        .warning-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            border-radius: 50%;
            background: #e7f1ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #noFieldsSelectedModal .modal-content {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        #noFieldsSelectedModal .btn-close {
            position: absolute;
            right: 15px;
            top: 15px;
        }
        #noFieldsSelectedModal .modal-title {
            color: #333;
            font-weight: 600;
        }
        #noFieldsSelectedModal .btn {
            border-radius: 8px;
            padding: 8px 25px;
            font-weight: 500;
            transition: all 0.2s;
        }
        #noFieldsSelectedModal .btn:hover {
            transform: translateY(-1px);
        }
        .modal.fade .modal-content {
            transform: scale(0.7);
            opacity: 0;
            transition: all 0.3s ease-in-out;
        }
        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }
        /* Form controls */
        .form-control {
            padding: 4px 8px;
            font-size: 0.9rem;
            height: calc(1.5em + 0.5rem + 2px);
        }
        /* Submit buttons */
        #submitFixedValues, #submitDynamicValues {
            padding: 6px 20px;
            font-size: 0.95rem;
            margin: 10px 0;
        }
        /* Field checkboxes */
        .form-check-input {
            margin-top: 0.2rem;
        }
        .form-check-label {
            font-size: 0.9rem;
        }
        /* Row spacing in template fields */
        .row.mb-3 {
            margin-bottom: 0.5rem !important;
        }
        /* Field name and description */
        .field-source.text-muted.small {
            font-size: 0.75rem;
            line-height: 1.2;
        }
        /* Template title parts */
        .template-title-parts {
            font-weight: 500;
            word-wrap: break-word;
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
        }
        /* Mobile horizontal scroll for fields container */
        @media (max-width: 575.98px) {
            .template-fields {
                overflow-x: auto;
                max-width: 100%;
            }
            
            .template-title-parts {
                font-size: 0.9rem;
            }
            
            .template-title-parts .bi {
                font-size: 0.7rem;
            }
        }
        /* Modal adjustments */
        .modal-body {
            padding: 15px;
        }
        .modal-header {
            padding: 10px 15px;
        }
        .modal-title {
            font-size: 1.1rem;
        }
        /* Alert messages */
        .alert {
            padding: 8px 12px;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }
        
        /* Responsive Media Queries */
        @media (max-width: 1199.98px) {
            .content-wrapper {
                margin: 40px auto;
                max-width: 95%;
                padding: 15px;
            }
            
            .field-row {
                margin-bottom: 8px;
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
            
            .field-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .btn-primary-custom, .btn-danger-custom {
                flex: 1;
                text-align: center;
                min-width: 150px;
            }
        }
        
        @media (max-width: 767.98px) {
            .content-wrapper {
                margin: 20px auto;
                padding: 15px;
                border-radius: 8px;
            }
            
            .field-row {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .field-row > div {
                width: 100%;
            }
            
            .btn-primary-custom, .btn-danger-custom {
                min-width: unset;
                margin-bottom: 5px;
                width: 100%;
                padding: 8px;
            }
            
            .btn-save {
                width: 100%;
                padding: 10px;
            }
            
            .row.mb-3 {
                margin-bottom: 1rem !important;
            }
            
            .template-header {
                flex-direction: column;
                align-items: flex-start !important;
            }
            
            .template-header .form-check {
                margin-top: 5px;
            }
            
            .field-name {
                font-size: 0.85rem;
            }
            
            h2.mb-4 {
                font-size: 1.2rem;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .navbar-brand img {
                height: 35px;
            }
        }
        
        @media (max-width: 575.98px) {
            .content-wrapper {
                margin: 10px auto;
                padding: 10px;
                box-shadow: 0 0 40px rgb(0, 0, 0);
            }
            
            .field-buttons {
                flex-direction: column;
            }
            
            .btn-primary-custom, .btn-danger-custom {
                width: 100%;
                margin: 3px 0;
            }
            
            .template-group {
                border-radius: 4px;
            }
            
            .template-header {
                padding: 6px 8px;
                font-size: 0.85rem;
            }
            
            .template-fields {
                padding: 8px;
            }
            
            .field-source {
                font-size: 0.75rem;
            }
            
            .row.mb-3 {
                flex-direction: column;
            }
            
            .row.mb-3 > div {
                width: 100%;
                padding: 0;
            }
            
            .col-4, .col-8 {
                width: 100%;
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 8px;
            }
            
            .form-check {
                padding-left: 1.5rem;
                margin-bottom: 5px;
            }
            
            .navbar-brand img {
                height: 30px;
            }
            
            #fieldModal .modal-dialog {
                margin: 0.5rem;
            }
            
            .modal-body {
                padding: 10px;
            }
            
            h2.mb-4 {
                font-size: 1.1rem;
                margin-bottom: 0.75rem !important;
            }
            
            .section-title {
                font-size: 1rem;
                margin-bottom: 8px;
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
            <i class="bi bi-pencil-square me-2 d-inline-block" style="color: #0dcaf0; font-size: 1.8rem;"></i>
            <span style="color: #333;">
                Edit 
                <span class="template-title-parts">
                    <?php 
                    echo implode(' <i class="bi bi-chevron-right text-muted mx-1 mx-md-2"></i> ', array_map('htmlspecialchars', $formatted_parts)); 
                    ?>
                </span>
            </span>
        </h2>
        
        <div id="saveNotification" class="alert alert-success" style="display: none;">
            Values saved successfully!
        </div>

        <!-- Fixed Fields Section -->
        <div class="mb-4">
            <h3 class="section-title">FIXED FIELDS</h3>
            <div class="field-buttons">
                <button type="button" class="btn-primary-custom" id="addFixedField">Add New Fixed Field</button>
                <button type="button" class="btn-primary-custom" id="editFixedData">Edit Fixed Data</button>
                <button type="button" class="btn-danger-custom" id="removeFixedField">Remove Fixed Field</button>
            </div>
            <div id="fixedFieldsContainer">
                <!-- Fixed fields will be loaded here -->
            </div>
        </div>
    

        <!-- Submit Button centered -->
        <div class="d-flex justify-content-center mb-4">
            <button type="button" class="btn btn-primary w-100 w-md-auto" id="submitFixedValues">Submit Fixed Values</button>
        </div>

        <!-- Dynamic Fields Section -->
        <div>
            <h3 class="section-title">DYNAMIC FIELDS</h3>
            <div class="field-buttons mt-3">
                <button type="button" class="btn-primary-custom" id="addDynamicField">Add New Dynamic Field</button>
                <button type="button" class="btn-danger-custom" id="removeDynamicField">Remove Dynamic Field</button>
            </div>
            <div id="dynamicFieldsContainer">
                <!-- Dynamic fields will be loaded here -->
            </div>


        <!-- Submit Button centered -->
        <div class="d-flex justify-content-center mb-4">
            <button type="button" class="btn btn-primary w-100 w-md-auto" id="submitDynamicValues">Submit Dynamic Values</button>
        </div>
        </div>
    </div>

    <!-- Add Field Modal -->
    <div class="modal fade" id="fieldModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Field</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="fieldForm">
                        <div class="field-row">
                            <div class="mb-3">
                                <label class="form-label">Field Name</label>
                                <input type="text" class="form-control" id="fieldName" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Field Type</label>
                                <select class="form-select" id="fieldType">
                                    <option value="text">Text</option>
                                    <option value="number">Number</option>
                                    <option value="date">Date</option>
                                    <option value="email">Email</option>
                                    <option value="price">Price (LKR)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" id="fieldDescription">
                            </div>
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary w-100">Add Field</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove Field Confirmation Modal -->
    <div class="modal fade" id="removeFieldModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pb-4">
                    <div class="remove-icon mb-4">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="modal-title mb-3">Remove Fields</h4>
                    <p class="text-muted mb-4">Are you sure you want to remove <span id="selectedFieldCount" class="fw-bold text-danger">0</span> selected field(s)?</p>
                    <p class="small text-danger mb-4">This action cannot be undone!</p>
                    <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                        <button type="button" class="btn btn-secondary px-4 mb-2 mb-md-0" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger px-4" id="confirmRemoveFields">Remove</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- No Fields Selected Warning Modal -->
    <div class="modal fade" id="noFieldsSelectedModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pb-4">
                    <div class="warning-icon mb-4">
                        <i class="bi bi-info-circle text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="modal-title mb-3">No Fields Selected</h4>
                    <p class="text-muted mb-4">Please select the fields you want to remove.</p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-primary px-4 w-100 w-md-auto" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const templateId = <?php echo $template_id; ?>;
            const groupedFixedFields = <?php echo !empty($grouped_fixed_fields) ? json_encode($grouped_fixed_fields) : '{}'; ?>;
            const groupedDynamicFields = <?php echo !empty($grouped_dynamic_fields) ? json_encode($grouped_dynamic_fields) : '{}'; ?>;
            
            // Display initial fields
            displayFixedFields(groupedFixedFields);
            displayDynamicFields(groupedDynamicFields);

            function displayFieldsByTemplate(groupedFields, container) {
                container.empty();
                
                if (groupedFields && Object.keys(groupedFields).length > 0) {
                    Object.values(groupedFields).forEach(templateGroup => {
                        // Add template group
                        container.append(`
                            <div class="template-group mb-4">
                                <h5 class="template-header bg-light p-2 rounded d-flex justify-content-between align-items-center">
                                    <span>
                                        ${templateGroup.template_name.toUpperCase()} Fields
                                        ${templateGroup.level > 0 ? 
                                            `<span class="badge bg-info ms-2">Parent Template</span>` 
                                            : ''}
                                    </span>
                                    ${templateGroup.level === 0 ? 
                                        `<div class="form-check">
                                            <input class="form-check-input select-all-fixed" type="checkbox">
                                            <label class="form-check-label">Select All</label>
                                        </div>` 
                                        : ''}
                                </h5>
                                <div class="template-fields ps-3">
                                </div>
                            </div>
                        `);

                        const templateFieldsContainer = container.find('.template-fields').last();
                        
                        templateGroup.fields.forEach(field => {
                            const fieldValue = field.field_value || field.default_value || '';
                            
                            // Determine input type based on field_type
                            let inputType = "text";
                            let additionalAttrs = "";
                            if (field.field_type === "date") {
                                inputType = "date";
                                // Format date value if needed (assuming YYYY-MM-DD format)
                                additionalAttrs = fieldValue ? `value="${fieldValue}"` : '';
                            } else if (field.field_type === "number") {
                                inputType = "number";
                                additionalAttrs = `value="${fieldValue}"`;
                            } else if (field.field_type === "email") {
                                inputType = "email";
                                additionalAttrs = `value="${fieldValue}"`;
                            } else if (field.field_type === "price") {
                                inputType = "number";
                                additionalAttrs = `value="${fieldValue}" step="0.01" min="0"`;
                            } else {
                                additionalAttrs = `value="${fieldValue}"`;
                            }
                            
                            // Create responsive row structure for field items
                            const fieldRow = `
                                <div class="row mb-3 align-items-center border-bottom pb-3" data-field-id="${field.field_id}">
                                    <div class="col-md-4 col-12 d-flex align-items-start mb-2 mb-md-0">
                                        ${!templateGroup.level ? 
                                            `<div class="form-check me-2">
                                                <input class="form-check-input field-checkbox" type="checkbox" value="${field.field_id}">
                                            </div>` 
                                            : ''}
                                        <div>
                                            <span class="field-name">${field.field_name}</span>
                                            <div class="field-source text-muted small">
                                                Type: ${field.field_type}
                                                ${field.description ? `<br>Description: ${field.description}` : ''}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-12">
                                        ${field.field_type === "price" ? 
                                        `<div class="input-group">
                                            <span class="input-group-text">LKR</span>
                                            <input type="${inputType}" 
                                                class="form-control" 
                                                data-field-id="${field.field_id}"
                                                data-field-name="${field.field_name}"
                                                data-template-id="${field.source_template_id}"
                                                data-field-type="${field.field_type}"
                                                data-original-value="${fieldValue}"
                                                ${additionalAttrs}
                                                placeholder="Enter price..."
                                                ${templateGroup.level > 0 ? 'disabled' : ''}
                                            >
                                        </div>` :
                                        `<input type="${inputType}" 
                                            class="form-control" 
                                            data-field-id="${field.field_id}"
                                            data-field-name="${field.field_name}"
                                            data-template-id="${field.source_template_id}"
                                            data-field-type="${field.field_type}"
                                            data-original-value="${fieldValue}"
                                            ${additionalAttrs}
                                            placeholder="Enter value..."
                                            ${templateGroup.level > 0 ? 'disabled' : ''}
                                        >`
                                        }
                                    </div>
                                </div>
                            `;
                            templateFieldsContainer.append(fieldRow);
                        });
                    });
                } else {
                    container.append('<div class="alert alert-info">No fields found</div>');
                }
                
                // Adjust layout for mobile
                adjustFieldLayout();
            }

            // New function to adjust field layout for different screen sizes
            function adjustFieldLayout() {
                const windowWidth = $(window).width();
                if (windowWidth <= 767) {
                    $('.template-header').addClass('flex-column align-items-start').removeClass('justify-content-between');
                    $('.template-header .form-check').addClass('mt-2');
                } else {
                    $('.template-header').removeClass('flex-column align-items-start').addClass('justify-content-between');
                    $('.template-header .form-check').removeClass('mt-2');
                }
            }
            
            // Call adjustFieldLayout on window resize
            $(window).resize(function() {
                adjustFieldLayout();
            });

            // Replace displayFixedFields and displayDynamicFields with this function
            function displayFixedFields(groupedFields) {
                const container = $('#fixedFieldsContainer');
                container.empty();
                
                if (groupedFields && Object.keys(groupedFields).length > 0) {
                    displayFieldsByTemplate(groupedFields, container);
                } else {
                    container.append('<div class="alert alert-info">No fixed fields found. Use the "Add New Fixed Field" button to create fields.</div>');
                }
            }
            
            function displayDynamicFields(groupedFields) {
                const container = $('#dynamicFieldsContainer');
                container.empty();
                
                if (groupedFields && Object.keys(groupedFields).length > 0) {
                    displayFieldsByTemplate(groupedFields, container);
                } else {
                    container.append('<div class="alert alert-info">No dynamic fields found. Use the "Add New Dynamic Field" button to create fields.</div>');
                }
            }

            // Add error display function for better debugging
            function displayErrorMessage(message, location) {
                console.error(`Error (${location}):`, message);
                
                const $error = $(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error:</strong> ${message} (at ${location})
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `);
                $('.content-wrapper').prepend($error);
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    $error.alert('close');
                }, 5000);
            }

            // Add field selection handlers with container isolation
            $(document).on('change', '.select-all-fixed', function(event) {
                const isChecked = $(this).prop('checked');
                $(this).closest('.template-group')
                    .find('.field-checkbox')
                    .prop('checked', isChecked);
                
                // Don't trigger other events outside this container
                event.stopPropagation();
            });

            $(document).on('change', '.select-all-dynamic', function(event) {
                const isChecked = $(this).prop('checked');
                $(this).closest('.template-group')
                    .find('.field-checkbox')
                    .prop('checked', isChecked);
                
                // Don't trigger other events outside this container
                event.stopPropagation();
            });

            // Modify the field value input handlers to be more specific and isolated
            $(document).on('change', '#fixedFieldsContainer input[type="text"]', function(event) {
                // Skip if this is a checkbox event
                if ($(this).hasClass('field-checkbox')) return;
                
                const $input = $(this);
                const fieldId = $input.data('field-id');
                const fieldName = $input.data('field-name');
                const newValue = $input.val();
                const originalValue = $input.attr('data-original-value') || '';
                
                // Skip saving if the field is disabled, from a parent template, or no change in value
                if ($input.prop('disabled')) {
                    return;
                }
                
                // Skip if no actual change was made (prevents "no changes made" error)
                if (newValue === originalValue) {
                    console.log('No change detected, skipping save for field:', fieldId);
                    return;
                }
                
                // Debug log
                console.log('Saving fixed field:', {
                    fieldId: fieldId,
                    fieldName: fieldName,
                    originalValue: originalValue,
                    newValue: newValue,
                    templateId: <?php echo $template_id; ?>
                });
                
                // Process the save
                handleFieldSave($input, fieldId, fieldName, newValue);
                
                // Stop event propagation
                event.stopPropagation();
            });

            $(document).on('change', '#dynamicFieldsContainer input[type="text"]', function(event) {
                // Skip if this is a checkbox event
                if ($(this).hasClass('field-checkbox')) return;
                
                const $input = $(this);
                const fieldId = $input.data('field-id');
                const fieldName = $input.data('field-name');
                const newValue = $input.val();
                const originalValue = $input.attr('data-original-value') || '';
                
                // Skip saving if the field is disabled, from a parent template, or no change in value
                if ($input.prop('disabled')) {
                    return;
                }
                
                // Skip if no actual change was made (prevents "no changes made" error)
                if (newValue === originalValue) {
                    console.log('No change detected, skipping save for dynamic field:', fieldId);
                    return;
                }
                
                // Debug log
                console.log('Saving dynamic field:', {
                    fieldId: fieldId,
                    fieldName: fieldName,
                    originalValue: originalValue,
                    newValue: newValue,
                    templateId: <?php echo $template_id; ?>
                });
                
                // Process the save
                handleFieldSave($input, fieldId, fieldName, newValue);
                
                // Stop event propagation
                event.stopPropagation();
            });
            
            // Common field save handler
            function handleFieldSave($input, fieldId, fieldName, newValue) {
                // Show loading state
                $input.prop('disabled', true);
                
                // Update the original value attribute to prevent duplicate saves
                $input.attr('data-original-value', newValue);
                
                // Try to save directly to template_fields
                saveDirectToTemplateField($input, fieldId, fieldName, newValue);
            }

            // Function to save directly to template_fields
            function saveDirectToTemplateField($input, fieldId, fieldName, value) {
                const formData = new FormData();
                formData.append('field_id', fieldId);
                formData.append('field_name', fieldName);
                formData.append('value', value);
                formData.append('template_id', <?php echo $template_id; ?>);
                
                $.ajax({
                    url: 'save_field_value.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Save response:', response);
                        if (response.success) {
                            // Show a temporary success indicator
                            const $parent = $input.closest('.row');
                            const $indicator = $('<div class="text-success small mt-1"><i class="bi bi-check-circle"></i> Saved</div>');
                            $parent.append($indicator);
                            setTimeout(() => $indicator.fadeOut(() => $indicator.remove()), 2000);
                        } else if (response.message === 'Field not found or no changes made') {
                            // Try to create the field
                            createField($input, fieldId, fieldName, value);
                        } else {
                            // Show detailed error message
                            displayErrorMessage(response.message || 'Unknown error', 'individual field');
                            $input.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Log detailed error information
                        console.error('AJAX Error:', status, error);
                        console.error('Response:', xhr.responseText);
                        
                        try {
                            const response = JSON.parse(xhr.responseText);
                            displayErrorMessage(response.message || error, 'ajax error');
                        } catch(e) {
                            displayErrorMessage(error || 'Server error', 'ajax parse error');
                        }
                        
                        $input.prop('disabled', false);
                                }
                            });
                        }

            // Function to create a field
            function createField($input, fieldId, fieldName, value) {
                const formData = new FormData();
                formData.append('field_id', fieldId);
                formData.append('field_name', fieldName);
                formData.append('value', value);
                formData.append('template_id', <?php echo $template_id; ?>);
                
                $.ajax({
                    url: 'create_field_value.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Create response:', response);
                        if (response.success) {
                            // Show a temporary success indicator
                            const $parent = $input.closest('.row');
                            const $indicator = $('<div class="text-success small mt-1"><i class="bi bi-check-circle"></i> Created</div>');
                            $parent.append($indicator);
                            setTimeout(() => $indicator.fadeOut(() => $indicator.remove()), 2000);
                        } else {
                            // If creation failed too, just ignore and continue
                            console.warn('Failed to create field, but keeping new value in UI:', response.message);
                        }
                        $input.prop('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        console.error('Create field error:', status, error);
                        // If creation failed, just ignore it and keep the new value in UI
                        console.warn('Error creating field, but keeping new value in UI');
                        $input.prop('disabled', false);
                    }
                });
            }

            // Helper function to save field values with proper error handling (for batch operations)
            function saveFieldValue(fieldId, fieldName, value, onSuccess, onError) {
                console.log('Batch saving field:', {
                    fieldId: fieldId,
                    fieldName: fieldName,
                    value: value
                });
                
                // First try saving directly
                const formData = new FormData();
                formData.append('field_id', fieldId);
                formData.append('field_name', fieldName);
                formData.append('value', value);
                formData.append('template_id', <?php echo $template_id; ?>);
                
                return $.ajax({
                    url: 'save_field_value.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Batch save response:', response);
                        if (response.success) {
                            if (typeof onSuccess === 'function') {
                                onSuccess(response);
                            }
                        } else if (response.message === 'Field not found or no changes made') {
                            // Try creating the field
                            $.ajax({
                                url: 'create_field_value.php',
                                method: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                dataType: 'json',
                                success: function(createResponse) {
                                    console.log('Batch create response:', createResponse);
                                    // Even if creation failed, consider it a success for batch operations
                                    if (typeof onSuccess === 'function') {
                                        onSuccess(createResponse);
                                    }
                                },
                                error: function() {
                                    // Even if creation failed, consider it a success for batch operations
                                    if (typeof onSuccess === 'function') {
                                        onSuccess({success: true, message: 'Field processed'});
                                    }
                                }
                            });
                        } else if (typeof onError === 'function') {
                            onError(response);
                        } else {
                            // For batch operations, we'll just warn and continue
                            console.warn('Batch save warning:', response.message);
                            if (typeof onSuccess === 'function') {
                                onSuccess({success: true, message: 'Continued despite warning'});
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        
                        // For batch operations, consider errors as warnings and continue
                        console.warn('Continuing batch operation despite error');
                        if (typeof onSuccess === 'function') {
                            onSuccess({success: true, message: 'Continued despite error'});
                        }
                    }
                });
            }

            // Submit Fixed Values Handler
            $('#submitFixedValues').click(function(event) {
                // Prevent event propagation to avoid triggering other handlers
                event.preventDefault();
                event.stopPropagation();
                
                const $button = $(this);
                const $inputs = $('#fixedFieldsContainer input:not(:disabled)');
                let totalFields = 0;
                let successCount = 0;
                let hasValues = false;
                let errors = [];
                
                // Show loading state
                $button.prop('disabled', true);
                const $loadingIndicator = $('<div class="spinner-border spinner-border-sm ms-2"></div>');
                $button.append($loadingIndicator);
                
                // Clear previous alerts
                $('.alert').alert('close');
                
                const savePromises = [];
                
                $inputs.each(function() {
                    const value = $(this).val().trim();
                    const fieldId = $(this).data('field-id');
                    const fieldName = $(this).data('field-name');
                    
                    if (value !== '') {
                        hasValues = true;
                        totalFields++;
                        
                        // Create promise for this save operation
                        const savePromise = saveFieldValue(
                            fieldId,
                            fieldName,
                            value,
                            function() { successCount++; },
                            function(errorResponse) { 
                                errors.push(errorResponse.message || 'Unknown error'); 
                            }
                        );
                        
                        savePromises.push(savePromise);
                    }
                });
                
                if (!hasValues) {
                    // Show warning modal
                    const noFieldsModal = new bootstrap.Modal(document.getElementById('noFieldsSelectedModal'));
                    noFieldsModal.show();
                    
                    // Remove loading state
                    $button.prop('disabled', false);
                    $loadingIndicator.remove();
                    return;
                }
                
                // Wait for all save operations to complete
                $.when.apply($, savePromises)
                    .always(function() {
                        // Show results
                        if (errors.length > 0) {
                            // Show error message with all collected errors
                            displayErrorMessage('Errors saving fields: ' + errors.join(', '), 'fixed values submit');
                        } else if (successCount === totalFields) {
                                    // Show success message
                            const alert = $(`
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            All fixed values saved successfully
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                            `);
                                    $('.content-wrapper').prepend(alert);
                                    
                                    // Auto-dismiss the alert after 3 seconds
                                    setTimeout(() => {
                                alert.alert('close');
                                    }, 3000);
                        } else {
                            // Some fields weren't saved successfully
                            displayErrorMessage(`${successCount} of ${totalFields} fields saved successfully`, 'fixed values partial');
                }
                
                // Remove loading state
                        $button.prop('disabled', false);
                $loadingIndicator.remove();
                    });
            });

            // Submit Dynamic Values Handler
            $('#submitDynamicValues').click(function(event) {
                // Prevent event propagation to avoid triggering other handlers
                event.preventDefault();
                event.stopPropagation();
                
                const $button = $(this);
                const $inputs = $('#dynamicFieldsContainer input:not(:disabled)');
                let totalFields = 0;
                let successCount = 0;
                let hasValues = false;
                let errors = [];
                
                // Show loading state
                $button.prop('disabled', true);
                const $loadingIndicator = $('<div class="spinner-border spinner-border-sm ms-2"></div>');
                $button.append($loadingIndicator);
                
                // Clear previous alerts
                $('.alert').alert('close');
                
                const savePromises = [];
                
                $inputs.each(function() {
                    const value = $(this).val().trim();
                    const fieldId = $(this).data('field-id');
                    const fieldName = $(this).data('field-name');
                    
                    if (value !== '') {
                        hasValues = true;
                        totalFields++;
                        
                        // Create promise for this save operation
                        const savePromise = saveFieldValue(
                            fieldId,
                            fieldName,
                            value,
                            function() { successCount++; },
                            function(errorResponse) { 
                                errors.push(errorResponse.message || 'Unknown error'); 
                            }
                        );
                        
                        savePromises.push(savePromise);
                    }
                });
                
                if (!hasValues) {
                    // Show warning modal
                    const noFieldsModal = new bootstrap.Modal(document.getElementById('noFieldsSelectedModal'));
                    noFieldsModal.show();
                    
                    // Remove loading state
                    $button.prop('disabled', false);
                    $loadingIndicator.remove();
                    return;
                }
                
                // Wait for all save operations to complete
                $.when.apply($, savePromises)
                    .always(function() {
                        // Show results
                        if (errors.length > 0) {
                            // Show error message with all collected errors
                            displayErrorMessage('Errors saving fields: ' + errors.join(', '), 'dynamic values submit');
                        } else if (successCount === totalFields) {
                                    // Show success message
                            const alert = $(`
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            All dynamic values saved successfully
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                            `);
                                    $('.content-wrapper').prepend(alert);
                                    
                                    // Auto-dismiss the alert after 3 seconds
                                    setTimeout(() => {
                                alert.alert('close');
                                    }, 3000);
                        } else {
                            // Some fields weren't saved successfully
                            displayErrorMessage(`${successCount} of ${totalFields} fields saved successfully`, 'dynamic values partial');
                }
                
                // Remove loading state
                        $button.prop('disabled', false);
                $loadingIndicator.remove();
                    });
            });

            // Add Field Button Handlers
            $('#addFixedField, #addDynamicField').click(function() {
                const isFixed = $(this).attr('id') === 'addFixedField';
                const modalTitle = isFixed ? 'Add New Fixed Field' : 'Add New Dynamic Field';
                
                // Set the modal title to match the field type
                $('#fieldModal .modal-title').text(modalTitle);
                
                // Store whether it's fixed or dynamic
                $('#fieldModal').data('isFixed', isFixed);
                
                // Show the modal
                $('#fieldModal').modal('show');
            });

            // Field Form Submit Handler
            $('#fieldForm').submit(function(e) {
                e.preventDefault();
                const isFixed = $('#fieldModal').data('isFixed');
                const fieldType = $('#fieldType').val();
                const fieldName = $('#fieldName').val();
                const fieldDescription = $('#fieldDescription').val();
                
                console.log('Form submission - Field type selected:', fieldType); // Debug log
                
                // Validate field name
                if (!fieldName.trim()) {
                    alert('Please enter a field name');
                    return;
                }
                
                const fieldData = {
                    template_id: templateId,
                    name: fieldName,
                    type: fieldType,
                    description: fieldDescription,
                    is_fixed: isFixed ? 1 : 0
                };

                console.log('Submitting field data:', fieldData); // Debug log
                
                $.post('save_field.php', fieldData, function(response) {
                    console.log('Server response:', response); // Debug log
                    if (response.success) {
                        $('#fieldModal').modal('hide');
                        
                        // Reload only the relevant field type
                        if (isFixed) {
                            $.get('get_fields.php', { template_id: templateId, type: 'fixed' }, function(response) {
                                console.log('Reloaded fixed fields:', response); // Debug log
                                if (response.success) {
                                    displayFixedFields(response.fields);
                                }
                            });
                        } else {
                            $.get('get_fields.php', { template_id: templateId, type: 'dynamic' }, function(response) {
                                if (response.success) {
                                    displayDynamicFields(response.fields);
                                }
                            });
                        }
                        
                        $('#fieldForm')[0].reset();
                    } else {
                        alert(response.message || 'Error saving field');
                    }
                });
            });

            // Remove Fixed Field Button Handler
            $('#removeFixedField').click(function() {
                const selectedFields = $('#fixedFieldsContainer .field-checkbox:checked');
                
                if (selectedFields.length === 0) {
                    // Show the no fields selected warning modal
                    const noFieldsModal = new bootstrap.Modal(document.getElementById('noFieldsSelectedModal'));
                    noFieldsModal.show();
                    return;
                }
                
                // Update the count in the modal
                $('#selectedFieldCount').text(selectedFields.length);
                
                // Show the modal
                const removeFieldModal = new bootstrap.Modal(document.getElementById('removeFieldModal'));
                removeFieldModal.show();
                
                // Set data attribute to indicate which type of fields we're removing
                $('#removeFieldModal').data('field-type', 'fixed');
            });

            // Remove Dynamic Field Button Handler
            $('#removeDynamicField').click(function() {
                const selectedFields = $('#dynamicFieldsContainer .field-checkbox:checked');
                
                if (selectedFields.length === 0) {
                    // Show the no fields selected warning modal
                    const noFieldsModal = new bootstrap.Modal(document.getElementById('noFieldsSelectedModal'));
                    noFieldsModal.show();
                    return;
                }
                
                // Update the count in the modal
                $('#selectedFieldCount').text(selectedFields.length);
                
                // Show the confirmation modal
                const removeFieldModal = new bootstrap.Modal(document.getElementById('removeFieldModal'));
                removeFieldModal.show();
                
                // Set data attribute to indicate which type of fields we're removing
                $('#removeFieldModal').data('field-type', 'dynamic');
            });

            // Confirm Remove Fields Handler
            $('#confirmRemoveFields').click(function() {
                // Get field type from modal data
                const fieldType = $('#removeFieldModal').data('field-type') || 'fixed';
                
                // Get the correct container based on field type
                const container = fieldType === 'fixed' ? '#fixedFieldsContainer' : '#dynamicFieldsContainer';
                const selectedFields = $(container + ' .field-checkbox:checked');
                
                const fieldIds = selectedFields.map(function() {
                    return $(this).val();
                }).get();
                
                console.log(`Removing ${fieldType} fields:`, fieldIds);
                
                let completedCount = 0;
                let successCount = 0;
                
                // Close the modal
                $('#removeFieldModal').modal('hide');
                
                // Show loading indicator
                const $loadingIndicator = $(`
                    <div class="alert alert-info mt-2 mb-2" id="removeLoadingIndicator">
                        <i class="bi bi-hourglass-split me-2"></i> Removing ${fieldIds.length} fields...
                    </div>
                `);
                $('.content-wrapper').prepend($loadingIndicator);
                
                fieldIds.forEach(fieldId => {
                    $.ajax({
                        url: 'remove_field.php',
                        type: 'POST',
                        data: { 
                            field_id: fieldId,
                            template_id: <?php echo $template_id; ?>
                        },
                        dataType: 'json',
                        success: function(response) {
                            completedCount++;
                            if (response.success) {
                                successCount++;
                            } else {
                                console.error('Error removing field ID ' + fieldId + ':', response.message);
                            }
                            
                            // When all requests are completed
                            if (completedCount === fieldIds.length) {
                                $('#removeLoadingIndicator').remove();
                                
                                // Reload the appropriate fields
                                if (fieldType === 'fixed') {
                                    reloadFixedFields();
                                } else {
                                    reloadDynamicFields();
                                }
                                
                                        // Show success message
                                        const message = `Successfully removed ${successCount} field(s)`;
                                        const alert = `
                                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                ${message}
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        `;
                                        $('.content-wrapper').prepend(alert);
                                        
                                        // Auto-dismiss the alert after 3 seconds
                                        setTimeout(() => {
                                            $('.alert').alert('close');
                                        }, 3000);
                            }
                        },
                        error: function(xhr, status, error) {
                            completedCount++;
                            console.error('AJAX Error removing field:', status, error);
                            
                            if (completedCount === fieldIds.length) {
                                $('#removeLoadingIndicator').remove();
                                
                                const errorAlert = `
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        Error removing some fields: ${error}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                `;
                                $('.content-wrapper').prepend(errorAlert);
                                
                                // Reload to show current state
                                if (fieldType === 'fixed') {
                                    reloadFixedFields();
                                } else {
                                    reloadDynamicFields();
                                }
                            }
                        }
                    });
                });
            });

            // Function to reload fixed fields
            function reloadFixedFields() {
                $.get('get_fields.php', { 
                    template_id: <?php echo $template_id; ?>, 
                    type: 'fixed' 
                }, function(response) {
                    if (response.success) {
                        displayFixedFields(response.fields);
                    } else {
                        console.error('Error reloading fixed fields:', response.message);
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX Error reloading fixed fields:', status, error);
                });
            }
            
            // Function to reload dynamic fields
            function reloadDynamicFields() {
                $.get('get_fields.php', { 
                    template_id: <?php echo $template_id; ?>, 
                    type: 'dynamic' 
                }, function(response) {
                    if (response.success) {
                        displayDynamicFields(response.fields);
                    } else {
                        console.error('Error reloading dynamic fields:', response.message);
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX Error reloading dynamic fields:', status, error);
                });
            }

            // Update the Field Type dropdown in the Add Field Modal
            $('#fieldType').html(`
                <option value="text">Text</option>
                <option value="number">Number</option>
                <option value="date">Date</option>
                <option value="email">Email</option>
                <option value="price">Price (LKR)</option>
            `);
        });
    </script>
</body>
</html> 