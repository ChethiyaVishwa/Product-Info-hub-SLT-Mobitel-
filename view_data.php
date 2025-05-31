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
                tf.field_type,
                ofv.field_value,
                tf.field_value as default_value,
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
            field_type,
            is_fixed,
            field_value,
            default_value
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
    <title>View Template Data</title>
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
            border-radius: 8px;
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
            margin: 0 8px;
            font-size: 0.9rem;
        }
        .object-name {
            display: inline-block;
            padding: 6px 14px;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            margin: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
            color: #475569;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .object-name:hover {
            background-color: #f1f5f9;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .object-name.active {
            background-color: #0ea5e9;
            color: white;
            border-color: #0284c7;
            box-shadow: 0 2px 4px rgba(14, 165, 233, 0.3);
        }
        .field-list {
            margin-top: 20px;
        }
        .field-item {
            padding: 10px 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            background-color: #f8fafc;
            border-left: 3px solid #0ea5e9;
            transition: all 0.2s ease;
        }
        .field-item:hover {
            background-color: #f1f5f9;
            transform: translateX(2px);
        }
        .field-item:last-child {
            margin-bottom: 0;
        }
        .field-name {
            font-weight: 600;
            color: #1e293b;
            min-width: 150px;
            max-width: 200px;
            font-size: 0.9rem;
        }
        .field-value {
            color: #0ea5e9;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .field-separator {
            margin: 0 6px;
            color: #94a3b8;
        }
        .fixed-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            background-color: #94a3b8;
            color: white;
            border-radius: 4px;
            margin-left: 6px;
            vertical-align: middle;
        }
        /* Object selection section */
        .object-selection {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        /* Spacing utilities */
        .me-2 {
            margin-right: 0.4rem !important;
        }
        .me-3 {
            margin-right: 0.6rem !important;
        }
        /* Icon sizes */
        .bi {
            font-size: 0.9rem;
        }
        .bi-diagram-3 {
            font-size: 1rem;
        }
        /* Responsive adjustments */
        @media (max-width: 1199.98px) {
            .content-wrapper {
                max-width: 95%;
                margin: 40px auto;
            }
            
            .field-name {
                min-width: 120px;
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
            
            .navbar-brand img {
                height: 35px;
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
            
            .object-image {
                max-height: 250px;
            }
        }
        
        @media (max-width: 767.98px) {
            .content-wrapper {
                margin: 30px auto;
                padding: 15px;
            }
            
            .template-path {
                font-size: 0.85rem;
                padding: 8px;
            }
            
            .object-name {
                padding: 5px 12px;
                font-size: 0.8rem;
                margin: 3px;
            }
            
            .object-selection {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                padding: 12px;
            }
            
            .field-name {
                min-width: 100px;
                font-size: 0.85rem;
            }
            
            .field-value {
                font-size: 0.85rem;
            }
            
            .object-title {
                font-size: 1.1rem;
                padding: 10px 14px;
            }
            
            .field-items-title {
                font-size: 1rem;
            }
            
            .object-image {
                max-height: 200px;
            }
            
            .field-items-container {
                padding: 15px;
            }
            
            .field-item {
                padding: 8px 10px;
            }
        }
        
        @media (max-width: 575.98px) {
            .content-wrapper {
                margin: 20px auto;
                padding: 12px;
                border-radius: 6px;
            }
            
            .template-path {
                font-size: 0.8rem;
                padding: 6px;
                margin-bottom: 12px;
            }
            
            .object-selection {
                padding: 10px;
                margin-bottom: 12px;
                overflow-x: auto;
                white-space: nowrap;
                display: block;
            }
            
            .object-selection::-webkit-scrollbar {
                height: 3px;
            }
            
            .object-selection::-webkit-scrollbar-thumb {
                background-color: rgba(14, 165, 233, 0.3);
                border-radius: 3px;
            }
            
            .object-name {
                padding: 4px 10px;
                font-size: 0.75rem;
                display: inline-block;
                margin: 2px;
            }
            
            .object-name i {
                font-size: 0.7rem;
                margin-right: 4px;
            }
            
            .field-item {
                padding: 8px;
                margin-bottom: 6px;
            }
            
            .field-item .d-flex {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .field-name {
                margin-bottom: 2px;
                font-size: 0.8rem;
            }
            
            .field-separator {
                display: none;
            }
            
            .field-value {
                padding-left: 8px;
                font-size: 0.8rem;
                width: 100%;
            }
            
            .object-title {
                font-size: 1rem;
                padding: 8px 12px;
                margin-bottom: 15px;
            }
            
            .field-items-container {
                padding: 12px;
                border-radius: 8px;
            }
            
            .field-items-title {
                font-size: 0.9rem;
                margin-bottom: 12px;
                padding-bottom: 8px;
            }
            
            .object-image-container {
                padding: 10px;
                margin-bottom: 15px;
            }
            
            .no-image-placeholder {
                width: 150px;
                height: 100px;
            }
            
            .no-image-placeholder i {
                font-size: 2rem;
            }
            
            .navbar-brand img {
                height: 30px;
            }
            
            .fixed-badge {
                font-size: 0.65rem;
                padding: 1px 4px;
            }
        }
        /* Object image styles */
        .object-image-container {
            margin-bottom: 20px;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            border: 1px solid #dee2e6;
        }
        .object-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .no-image-placeholder {
            width: 200px;
            height: 150px;
            margin: 0 auto;
            background-color: #e9ecef;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        .no-image-placeholder i {
            font-size: 3rem;
        }
        /* Object title styles */
        .object-title {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: white;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 1.25rem;
            margin-bottom: 20px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(14, 165, 233, 0.2);
            display: flex;
            align-items: center;
        }
        .object-title i {
            margin-right: 10px;
            font-size: 1.3rem;
        }
        /* Field list styles */
        .field-items-container {
            background-color: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .field-items-title {
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            color: #0f172a;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .field-items-title i {
            color: #0ea5e9;
            margin-right: 8px;
            font-size: 1.2rem;
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
                    <a class="nav-link" href="#">
                        <img src="images/profile.png" alt="Profile" class="profile-img">
                    </a>
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
        <div class="object-selection">
            <div class="row">
                <div class="col-12">
                    <?php foreach ($objects as $object): ?>
                    <span class="object-name" data-object-id="<?php echo $object['object_id']; ?>">
                        <i class="bi bi-box me-2"></i>
                        <?php echo htmlspecialchars($object['object_name']); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Field Values -->
        <?php foreach ($objects as $object): ?>
        <div class="field-list" id="fields-<?php echo $object['object_id']; ?>" style="display: none;">
            <!-- Object Title -->
            <h4 class="object-title">
                <i class="bi bi-info-circle"></i>
                <?php echo htmlspecialchars($object['object_name']); ?>
            </h4>
            
            <!-- Object Image Display -->
            <div class="object-image-container">
                <?php if (!empty($object['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($object['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($object['object_name']); ?>" 
                         class="object-image">
                <?php else: ?>
                    <div class="no-image-placeholder">
                        <i class="bi bi-image"></i>
                    </div>
                    <p class="text-muted small mt-2">No image available</p>
                <?php endif; ?>
            </div>
            
            <!-- Field Items -->
            <div class="field-items-container">
                <h5 class="field-items-title">
                    <i class="bi bi-list-check"></i>Object Details
                </h5>
                
                <div class="row">
                    <div class="col-12">
                        <?php foreach ($fields_by_object[$object['object_id']] as $field): ?>
                        <div class="field-item">
                            <div class="d-flex align-items-center">
                                <span class="field-name">
                                    <?php 
                                        $words = explode('_', $field['field_name']);
                                        echo ucwords(implode(' ', $words));
                                    ?>
                                    <?php if ($field['is_fixed']): ?>
                                        <span class="fixed-badge">Fixed</span>
                                    <?php endif; ?>
                                </span>
                                <span class="field-separator">:</span>
                                <span class="field-value">
                                    <?php 
                                        $value = $field['field_value'] ?? $field['default_value'] ?? '';
                                        
                                        if (!empty($value)) {
                                            if (isset($field['field_type']) && $field['field_type'] === 'date') {
                                                // Format date for display
                                                $date = new DateTime($value);
                                                echo $date->format('d M Y');
                                            } elseif (isset($field['field_type']) && $field['field_type'] === 'price') {
                                                // Format price with LKR
                                                echo 'LKR ' . number_format((float)$value, 2);
                                            } else {
                                                // Regular value display
                                                echo htmlspecialchars($value);
                                            }
                                        } else {
                                            echo '—'; // Em dash for empty values
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle navbar toggler for mobile
            $('.navbar-toggler').on('click', function() {
                $(this).toggleClass('active');
            });
            
            // Show first object's fields by default
            const firstObjectId = $('.object-name').first().data('object-id');
            $('.object-name').first().addClass('active');
            $('#fields-' + firstObjectId).show();

            // Handle object selection
            $('.object-name').click(function() {
                const objectId = $(this).data('object-id');
                
                // Update active state
                $('.object-name').removeClass('active');
                $(this).addClass('active');
                
                // Show selected object's fields
                $('.field-list').hide();
                $('#fields-' + objectId).fadeIn(300);
                
                // Scroll to top of fields on mobile
                if (window.innerWidth < 768) {
                    $('html, body').animate({
                        scrollTop: $('#fields-' + objectId).offset().top - 10
                    }, 300);
                }
                
                // If in mobile view, scroll object name to center
                if (window.innerWidth < 576) {
                    const container = document.querySelector('.object-selection');
                    const element = this;
                    const containerRect = container.getBoundingClientRect();
                    const elementRect = element.getBoundingClientRect();
                    const offset = elementRect.left - containerRect.left - (containerRect.width - elementRect.width) / 2;
                    
                    container.scrollTo({
                        left: container.scrollLeft + offset,
                        behavior: 'smooth'
                    });
                }
            });
            
            // Handle window resize
            $(window).resize(function() {
                adjustLayout();
            });
            
            // Initial layout adjustment
            adjustLayout();
            
            // Function to adjust layout based on screen size
            function adjustLayout() {
                const windowWidth = $(window).width();
                
                // Adjust field items for mobile
                if (windowWidth < 576) {
                    $('.field-item .d-flex').addClass('flex-column align-items-start');
                    $('.field-item .d-flex').removeClass('align-items-center');
                    $('.field-separator').hide();
                } else {
                    $('.field-item .d-flex').removeClass('flex-column align-items-start');
                    $('.field-item .d-flex').addClass('align-items-center');
                    $('.field-separator').show();
                }
            }
        });
    </script>
</body>
</html> 