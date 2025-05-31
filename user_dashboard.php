<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: userlogin.php");
    exit();
}

try {
    $conn = connectDB();
    
    // Get all templates with their objects and fields
    $stmt = $conn->prepare("
        SELECT 
            t.template_id,
            t.template_name,
            o.object_id,
            o.object_name,
            GROUP_CONCAT(DISTINCT tf.field_name) as field_names,
            COUNT(DISTINCT o.object_id) as object_count
        FROM templates t
        LEFT JOIN objects o ON t.template_id = o.template_id AND o.end_dtm IS NULL
        LEFT JOIN template_fields tf ON t.template_id = tf.template_id AND tf.end_dtm IS NULL
        WHERE t.end_dtm IS NULL
        GROUP BY t.template_id, t.template_name, o.object_id, o.object_name
        ORDER BY t.template_name, o.object_name
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize data by template
    $templates = [];
    foreach ($results as $row) {
        if (!isset($templates[$row['template_id']])) {
            $templates[$row['template_id']] = [
                'name' => $row['template_name'],
                'objects' => [],
                'object_count' => $row['object_count']
            ];
        }
        if ($row['object_id']) {
            $templates[$row['template_id']]['objects'][] = [
                'id' => $row['object_id'],
                'name' => $row['object_name'],
                'fields' => $row['field_names'] ? explode(',', $row['field_names']) : []
            ];
        }
    }

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Product Info Hub</title>
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

        .dashboard-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 1.5rem;
            position: relative;
        }

        .welcome-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-color);
        }

        .welcome-card h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .welcome-card p {
            font-size: 0.9rem;
            color: var(--gray-color);
            margin-bottom: 0;
        }

        .search-wrapper {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .search-input {
            position: relative;
        }
        
        .search-input input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .search-input input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(45, 212, 191, 0.1);
        }
        
        .search-input i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
        }
        
        .template-container {
            margin-bottom: 25px;
        }
        
        .template-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .template-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .template-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .objects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            padding: 20px;
            background: white;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .object-card {
            background-color: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
        }
        
        .object-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-3px);
            border-color: var(--primary-color);
        }
        
        .object-header {
            background-color: #f1f5f9;
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .object-name {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .object-name i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .object-content {
            padding: 15px;
        }
        
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 15px;
            min-height: 60px;
        }
        
        .tag {
            background: rgba(45, 212, 191, 0.1);
            color: var(--primary-dark);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .tag i {
            font-size: 0.6rem;
        }
        
        .view-btn {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-align: center;
            width: 100%;
        }
        
        .view-btn:hover {
            background: var(--primary-dark);
            color: white;
        }
        
        .empty-state {
            padding: 30px;
            text-align: center;
            color: var(--gray-color);
            background: white;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .objects-grid {
                grid-template-columns: 1fr;
            }
            
            .template-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .welcome-card {
                padding: 20px;
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
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>
                <a href="logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="welcome-card">
            <h2>Welcome to Your Dashboard</h2>
            <p>Explore and manage your product information templates and objects.</p>
        </div>

        <div class="search-wrapper">
            <div class="search-input">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search templates and objects..." class="form-control">
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($templates)): ?>
            <div class="empty-state">
                <i class="bi bi-folder-x"></i>
                <h3>No Templates Found</h3>
                <p>There are no templates available at the moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($templates as $template_id => $template): ?>
                <div class="template-container template-item">
                    <div class="template-header">
                        <div class="template-title">
                            <i class="bi bi-folder2"></i>
                            <?php echo htmlspecialchars($template['name']); ?>
                        </div>
                        <div class="template-count">
                            <i class="bi bi-box me-1"></i>
                            <?php echo count($template['objects']); ?> Objects
                        </div>
                    </div>
                    
                    <div class="objects-grid">
                        <?php if (empty($template['objects'])): ?>
                            <div class="p-3 text-center text-muted">
                                <i class="bi bi-info-circle me-2"></i>
                                No objects in this template
                            </div>
                        <?php else: ?>
                            <?php foreach ($template['objects'] as $object): ?>
                                <div class="object-card object-item">
                                    <div class="object-header">
                                        <h5 class="object-name">
                                            <i class="bi bi-box-seam"></i>
                                            <?php echo htmlspecialchars($object['name']); ?>
                                        </h5>
                                    </div>
                                    <div class="object-content">
                                        <div class="tags-container">
                                            <?php foreach (array_slice($object['fields'], 0, 5) as $field): ?>
                                                <span class="tag">
                                                    <i class="bi bi-tag-fill"></i>
                                                    <?php echo htmlspecialchars($field); ?>
                                                </span>
                                            <?php endforeach; ?>
                                            
                                            <?php if (count($object['fields']) > 5): ?>
                                                <span class="tag">
                                                    +<?php echo (count($object['fields']) - 5); ?> more
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="user_view_data.php?id=<?php echo $template_id; ?>&object_id=<?php echo $object['id']; ?>" 
                                           class="view-btn">
                                            <i class="bi bi-eye-fill me-1"></i>
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Search Functionality -->
    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const objectItems = document.querySelectorAll('.object-item');
            const templateItems = document.querySelectorAll('.template-item');

            templateItems.forEach(section => {
                let hasVisibleObjects = false;
                const objects = section.querySelectorAll('.object-item');
                
                objects.forEach(item => {
                    const objectName = item.querySelector('.object-name').textContent.toLowerCase();
                    const tags = Array.from(item.querySelectorAll('.tag'))
                        .map(tag => tag.textContent.toLowerCase());
                    
                    if (objectName.includes(searchValue) || 
                        tags.some(tag => tag.includes(searchValue))) {
                        item.style.display = '';
                        hasVisibleObjects = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                section.style.display = hasVisibleObjects ? '' : 'none';
            });
        });
    </script>
</body>
</html> 