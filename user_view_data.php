<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: userlogin.php");
    exit();
}

// Get template ID and object ID from URL parameters
$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$object_id = isset($_GET['object_id']) ? intval($_GET['object_id']) : 0;

if (!$template_id || !$object_id) {
    header('Location: user_dashboard.php');
    exit;
}

try {
    $conn = connectDB();
    
    // Get template name
    $stmt = $conn->prepare("
        SELECT template_name
        FROM templates 
        WHERE template_id = ? AND end_dtm IS NULL
    ");
    $stmt->execute([$template_id]);
    $template_name = $stmt->fetchColumn();
    
    // Format template name for display
    $formatted_template = preg_split('/(?=[A-Z])/', $template_name);
    $formatted_template = array_filter($formatted_template);
    $formatted_template = ucwords(str_replace('_', ' ', implode(' ', $formatted_template)));

    // Get object details
    $stmt = $conn->prepare("
        SELECT object_id, object_name, image_path 
        FROM objects 
        WHERE object_id = ? AND template_id = ? AND end_dtm IS NULL
    ");
    $stmt->execute([$object_id, $template_id]);
    $object = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$object) {
        header('Location: user_dashboard.php');
        exit;
    }

    // Get all fields and their values for the object
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

    $stmt = $conn->prepare($fields_query);
    $stmt->execute([$template_id, $object_id]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($object['object_name']); ?> | Product Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2dd4bf;
            --primary-dark: #14b8a6;
            --secondary-color: #f43f5e;
            --dark-color: #0f172a;
            --light-color: #f8fafc;
            --gray-color: #64748b;
            --border-radius: 12px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('https://cdn.pixabay.com/photo/2020/10/21/01/56/digital-5671888_1280.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            margin: 0;
            min-height: 100vh;
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg, 
                rgba(15, 23, 42, 0.3), 
                rgba(15, 23, 42, 0.2)
            );
            z-index: -1;
        }

        .navbar {
            background: linear-gradient(135deg, rgb(3, 7, 53) 0%, rgb(16, 35, 117) 100%) !important;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
            position: relative;
            padding: 8px 0;
            min-height: 60px;
            border-bottom: 2px solid #28a745;
        }

        .navbar-brand img {
            height: 60px;
            transition: transform 0.3s ease;
            margin-right: 3rem;
        }

        .navbar-brand img:hover {
            transform: scale(1.05);
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 1rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-decoration: none;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
        }

        .page-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 1.5rem;
            position: relative;
        }

        .navigation-bar {
            background: white;
            padding: 10px 15px;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .back-link {
            color: var(--dark-color);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            padding: 5px 12px;
            border-radius: 16px;
            background: #e2e8f0;
        }

        .back-link:hover {
            background: #cbd5e1;
            transform: translateX(-2px);
        }

        .object-meta {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .template-pill {
            display: inline-block;
            padding: 4px 12px;
            background-color: #e2e8f0;
            border-radius: 16px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-color);
            margin-bottom: 10px;
        }

        .object-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .object-image-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .image-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .image-container {
            padding: 20px;
            text-align: center;
            background: #f8fafc;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .object-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
        }

        .no-image {
            width: 100%;
            height: 250px;
            background: #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-color);
            flex-direction: column;
            gap: 10px;
        }

        .no-image i {
            font-size: 3rem;
            opacity: 0.5;
        }

        .specs-container {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .specs-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            padding: 16px;
            max-height: 300px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        
        .specs-grid::-webkit-scrollbar {
            width: 6px;
        }
        
        .specs-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .specs-grid::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        .spec-card {
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .spec-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .spec-name {
            font-weight: 500;
            color: var(--dark-color);
            padding: 10px;
            background-color: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .spec-name i {
            color: var(--primary-color);
            font-size: 0.75rem;
        }

        .spec-value {
            color: var(--primary-dark);
            font-weight: 500;
            padding: 10px;
            font-size: 0.9rem;
            flex-grow: 1;
        }
        
        .empty-specs {
            padding: 30px;
            text-align: center;
            color: var(--gray-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .empty-specs i {
            font-size: 2rem;
            opacity: 0.6;
        }
        
        .empty-specs p {
            margin: 0;
        }



        .empty-value {
            color: #94a3b8;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .object-title {
                font-size: 1.25rem;
            }
            
            .navigation-bar {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .fields-table td {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="images/logo.png" alt="Logo">
            </a>
            <div class="navbar-nav ms-auto d-flex align-items-center">
                <span class="nav-item nav-link text-white me-3">
                    <i class="bi bi-person-circle me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                </span>
                <a href="logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="page-container">
        <!-- Navigation Bar -->
        <div class="navigation-bar">
            <a href="user_dashboard.php" class="back-link">
                <i class="bi bi-chevron-left"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Object Meta -->
        <div class="object-meta">
            <div class="template-pill">
                <i class="bi bi-folder2"></i>
                <?php echo htmlspecialchars($formatted_template); ?>
            </div>
            <h1 class="object-title">
                <i class="bi bi-box-seam text-secondary small"></i>
                <?php echo htmlspecialchars($object['object_name']); ?>
            </h1>
        </div>

        <div class="row g-4">
            <!-- Object Image Column -->
            <div class="col-lg-5">
                <div class="object-image-card">
                    <div class="image-header">
                        <i class="bi bi-image me-2"></i>
                        Product Image
                    </div>
                    <div class="image-container">
                        <?php if (!empty($object['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($object['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($object['object_name']); ?>" 
                                 class="object-image">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="bi bi-card-image"></i>
                                <span>No image available</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Object Fields Column -->
            <div class="col-lg-7">
                <div class="specs-container">
                    <div class="specs-header">
                        <i class="bi bi-list-check me-2"></i>
                        Product Specifications
                    </div>
                    
                    <?php if (count($fields) > 0): ?>
                        <div class="specs-grid">
                            <?php foreach ($fields as $field): ?>
                                <div class="spec-card">
                                    <div class="spec-name">
                                        <i class="bi bi-tag-fill"></i>
                                        <?php 
                                            $words = explode('_', $field['field_name']);
                                            echo ucwords(implode(' ', $words));
                                        ?>

                                    </div>
                                    <div class="spec-value">
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
                                                echo '<span class="empty-value">Not specified</span>';
                                            }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-specs">
                            <i class="bi bi-exclamation-circle"></i>
                            <p>No specifications available for this product</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
