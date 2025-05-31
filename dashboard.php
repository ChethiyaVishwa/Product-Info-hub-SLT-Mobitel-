<?php
session_start();
require_once 'config.php';

// Temporary debug code - Remove after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (isset($_SESSION['is_superadmin'])) {
    echo "<!-- Debug: Logged in as superadmin: " . ($_SESSION['is_superadmin'] ? 'Yes' : 'No') . " -->";
} else {
    echo "<!-- Debug: Not logged in as superadmin -->";
}

// Check if user is logged in and is a superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== true) {
    header("Location: login.php");
    exit();
}

try {
    // Fetch templates from database with proper error handling
    $stmt = $pdo->query("
        SELECT 
            template_id,
            template_name,
            created_dtm,
            has_child,
            root_template_id
        FROM templates 
        WHERE end_dtm IS NULL 
        ORDER BY created_dtm DESC
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch superadmins list if current user is a superadmin
    $stmt = $pdo->query("
        SELECT 
            id,
            username,
            email,
            created_at,
            last_login,
            is_active
        FROM superadmins
        ORDER BY username ASC
    ");
    $superadmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $templates = [];
    $superadmins = [];
    $error = "Error loading data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Product Info Hub</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Responsive CSS -->
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css" rel="stylesheet">
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
            box-shadow: 0 0 15px rgb(0, 0, 0);
            padding: 30px;
            margin: 80px auto;
            max-width: 1200px;
            width: 95%;
        }
        .btn-create {
            background-color: #00A3E0;
            color: white;
            border-radius: 6px;
            padding: 8px 16px;
            text-decoration: none;
            font-weight: 500;
        }
        .btn-create:hover {
            background-color: #0090c7;
            color: white;
        }
        /* Custom button styles to match image */
        .btn-view {
            background-color: #0d6efd;
            color: white;
        }
        .btn-edit-fields {
            background-color: #0dcaf0;
            color: white;
        }
        .btn-add-data {
            background-color: #198754;
            color: white;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn-edit-data {
            background-color: #fd7e14;
            color: white;
        }
        .btn-view:hover, .btn-edit-fields:hover, .btn-add-data:hover, 
        .btn-delete:hover, .btn-edit-data:hover {
            color: white;
        }
        
        .btn-view:hover {
            background-color: #0b5ed7;
        }
        
        .btn-edit-fields:hover {
            background-color: #0bacce;
        }
        
        .btn-add-data:hover {
            background-color: #157347;
        }
        
        .btn-delete:hover {
            background-color: #bb2d3b;
        }
        
        .btn-edit-data:hover {
            background-color: #e56b0e;
        }
        /* Modern Pagination Styling */
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 15px;
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background: white;
            border: 1px solid #e0e0e0;
            color: #333 !important;
            border-radius: 3px;
            padding: 3px 8px;
            margin: 0 2px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            min-width: 25px;
            height: 25px;
            line-height: 17px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: rgb(3, 7, 53) !important;
            border-color: rgb(3, 7, 53);
            color: white !important;
            font-weight: 500;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
            background: #f5f5f5 !important;
            border-color: #ddd;
            color: #333 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .dataTables_wrapper .dataTables_info {
            font-size: 12px;
            color: #666;
            margin-top: 15px;
            text-align: center;
        }

        .dataTables_wrapper .dataTables_length {
            margin-bottom: 15px;
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 3px 25px 3px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 3px;
            font-size: 12px;
            height: 25px;
            background-color: white;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3E%3Cpath fill='%23333' d='M0 2l4 4 4-4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        /* Table styling */
        .table-responsive {
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background: white;
            padding: 15px;
            margin-top: 15px;
            overflow: hidden; /* Ensure content doesn't overflow rounded corners */
        }

        #templatesTable {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        #templatesTable thead th:first-child {
            border-top-left-radius: 10px;
        }

        #templatesTable thead th:last-child {
            border-top-right-radius: 10px;
        }

        #templatesTable tbody tr:last-child td:first-child {
            border-bottom-left-radius: 10px;
        }

        #templatesTable tbody tr:last-child td:last-child {
            border-bottom-right-radius: 10px;
        }

        /* Table header styling */
        #templatesTable thead th {
            white-space: nowrap;
            background: linear-gradient(135deg, rgb(3, 7, 53) 0%, rgb(16, 35, 117) 100%);
            color: white;
            border-bottom: 2px solid #28a745;
            font-size: 0.9rem;
            padding: 12px 8px;
        }

        /* Table cell styling */
        #templatesTable tbody td {
            padding: 10px 8px;
            font-size: 0.9rem;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        #templatesTable tbody tr:last-child td {
            border-bottom: none;
        }

        #templatesTable tbody tr:hover td {
            background-color: #f8f9fa;
        }

        /* Action buttons styling */
        .btn-sm {
            width: 50px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 1px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Template name styling - ensure it doesn't get cut off */
        .template-name-wrapper {
            display: inline-block;
            padding-left: 25px;
            position: relative;
            white-space: normal !important;
            word-break: break-word;
            color: #333;
            font-weight: 500;
        }

        .template-path {
            padding: 4px 8px;
            border-radius: 4px;
            background-color: #f8f9fa;
            display: inline-block;
        }

        .template-path .separator {
            color: #6c757d;
            margin: 0 4px;
        }

        /* User Profile Modal Styles */
        .profile-modal {
            display: none;
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1050;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }
        .profile-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.7);
            background: linear-gradient(145deg,rgb(3, 7, 53),rgb(255, 255, 255));
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: all 0.3s ease-in-out;
        }
        .profile-modal.show .profile-modal-content {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e8eef3;
        }
        .profile-header img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-right: 25px;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .profile-header img:hover {
            transform: scale(1.05);
        }
        .profile-header-info h3 {
            margin: 0;
            color:rgb(255, 255, 255);
            font-size: 1.5rem;
            font-weight: 600;
        }
        .profile-header-info p {
            margin: 8px 0 0;
            color:rgb(209, 243, 255);
            font-size: 1.1rem;
            font-weight: 500;
        }
        .profile-details {
            background: #f8fafc;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .profile-detail-item {
            margin-bottom: 20px;
            transition: transform 0.2s ease;
        }
        .profile-detail-item:last-child {
            margin-bottom: 0;
        }
        .profile-detail-item:hover {
            transform: translateX(5px);
        }
        .profile-detail-item label {
            display: block;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .profile-detail-item span {
            color: #334155;
            font-size: 1.1rem;
            display: block;
            padding: 8px 0;
        }
        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #94a3b8;
            background: #f1f5f9;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            padding: 0;
            line-height: 1;
        }
        .close-modal:hover {
            color: #475569;
            background: #e2e8f0;
            transform: rotate(90deg);
        }
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, -40%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        /* Delete Confirmation Modal Styles */
        .delete-modal {
            display: none;
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1060;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }
        .delete-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.7);
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(220, 53, 69, 0.2);
            opacity: 0;
            transition: all 0.3s ease-in-out;
            text-align: center;
        }
        .delete-modal.show .delete-modal-content {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
        }
        .delete-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #ffe5e7;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .delete-icon i {
            font-size: 40px;
            color: #dc3545;
        }
        .delete-modal h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        .delete-modal p {
            color: #64748b;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }
        .delete-modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .btn-cancel {
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            background: #e9ecef;
            color: #495057;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-cancel:hover {
            background: #dee2e6;
            transform: translateY(-1px);
        }
        .btn-confirm-delete {
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            background: #dc3545;
            color: white;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-confirm-delete:hover {
            background: #bb2d3b;
            transform: translateY(-1px);
        }
        .delete-modal .template-name {
            color: #dc3545;
            font-weight: 600;
        }
        /* DataTables length menu and search styling */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 10px;
            font-size: 0.85rem;
        }

        .dataTables_wrapper .dataTables_length {
            float: left;
        }

        .dataTables_wrapper .dataTables_length label {
            margin-bottom: 0;
            color: #333;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .dataTables_wrapper .dataTables_length select {
            padding: 2px 20px 2px 5px;
            font-size: 0.85rem;
            height: 24px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background-color: #fff;
            cursor: pointer;
            margin: 0 5px;
            min-width: 60px;
        }

        .dataTables_wrapper .dataTables_filter {
            float: right;
            margin-right: 5px;
        }

        .dataTables_wrapper .dataTables_filter label {
            margin-bottom: 0;
            color: #333;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .dataTables_wrapper .dataTables_filter input {
            padding: 2px 8px;
            font-size: 0.85rem;
            height: 24px;
            width: 140px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background-color: #fff;
            margin-left: 5px;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none;
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Clear floats */
        .dataTables_wrapper::after {
            content: "";
            display: table;
            clear: both;
        }
        
        /* Responsive Media Queries */
        @media (max-width: 1199.98px) {
            .content-wrapper {
                padding: 20px;
                margin: 60px auto;
            }
            
            .btn-sm {
                width: 40px;
                height: 28px;
                font-size: 0.8rem;
            }
            
            #templatesTable thead th {
                font-size: 0.85rem;
                padding: 10px 6px;
            }
            
            #templatesTable tbody td {
                padding: 8px 6px;
                font-size: 0.85rem;
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
            
            .profile-modal-content {
                padding: 20px;
                max-width: 400px;
            }
            
            .profile-header img {
                width: 80px;
                height: 80px;
                margin-right: 15px;
            }
            
            .profile-header-info h3 {
                font-size: 1.3rem;
            }
            
            .delete-modal-content {
                padding: 20px;
            }
            
            .action-column {
                white-space: nowrap;
            }
            
            .dataTables_wrapper .dataTables_filter input {
                width: 120px;
            }
        }
        
        @media (max-width: 767.98px) {
            .content-wrapper {
                padding: 15px;
                margin: 40px auto;
            }
            
            .table-responsive {
                padding: 10px;
                border-radius: 10px;
            }
            
            #templatesTable {
                width: 100% !important;
                margin-bottom: 0;
            }
            
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                float: none;
                text-align: center;
                margin-bottom: 10px;
            }
            
            .navbar .nav-link.superadmin-btn,
            .navbar .nav-link.home-btn,
            .navbar .nav-link.logout-btn {
                padding: 4px 8px;
                font-size: 0.8rem;
            }
            
            .delete-modal-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn-cancel, .btn-confirm-delete {
                width: 100%;
                padding: 8px;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-header img {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
        
        @media (max-width: 575.98px) {
            h2 {
                font-size: 1.5rem;
            }
            
            .btn-create {
                font-size: 0.85rem;
                padding: 6px 12px;
            }
            
            .profile-modal-content {
                padding: 15px;
                border-radius: 15px;
            }
            
            .profile-details {
                padding: 15px;
            }
            
            .profile-detail-item label {
                font-size: 0.8rem;
            }
            
            .profile-detail-item span {
                font-size: 0.95rem;
            }
            
            .navbar-brand img {
                height: 30px;
            }
            
            .navbar .profile-img {
                height: 25px;
            }
            
            .close-modal {
                top: 10px;
                right: 10px;
                width: 30px;
                height: 30px;
                font-size: 20px;
            }
            
            /* Make the table more mobile-friendly */
            .table-responsive {
                overflow-x: auto;
            }
            
            #templatesTable thead th {
                min-width: 40px;
                font-size: 0.8rem;
            }
            
            #templatesTable thead th:first-child {
                min-width: 100px;
            }
            
            .action-column {
                min-width: 50px;
            }
            
            .name-column {
                min-width: 150px;
            }
            
            .date-column {
                min-width: 100px;
            }
        }
        
        /* Fix for DataTables on mobile */
        @media (max-width: 650px) {
            table.dataTable {
                width: 100% !important;
            }
            
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 2px 6px;
                font-size: 11px;
                min-width: 22px;
                height: 22px;
            }
            
            .dataTables_wrapper .dataTables_info {
                font-size: 11px;
            }
        }
        
        /* Navbar collapse styles */
        @media (max-width: 991.98px) {
            .navbar .navbar-nav {
                margin-top: 10px;
                gap: 5px;
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
        }

        /* DataTables Responsive Styling */
        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control {
            padding-left: 30px !important;
            position: relative;
        }

        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control:before {
            top: 50% !important;
            left: 6px !important;
            transform: translateY(-50%) !important;
            height: 16px !important;
            width: 16px !important;
            line-height: 16px !important;
            font-size: 14px !important;
            border-radius: 4px !important;
            box-shadow: none !important;
            border: none !important;
            background-color: rgb(3, 7, 53) !important;
            color: white !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
        }

        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > th.dtr-control:before {
            background-color: #28a745 !important;
        }

        /* Fix for template name column spacing */
        .name-column {
            padding-left: 35px !important;
            position: relative;
            min-width: 180px !important;
        }

        /* Template name styling - ensure it doesn't get cut off */
        .template-name-wrapper {
            display: inline-block;
            padding-left: 25px;
            position: relative;
            white-space: normal !important;
            word-break: break-word;
            color: #333;
            font-weight: 500;
        }

        /* Make sure the control button doesn't overlap text on very small screens */
        @media (max-width: 575.98px) {
            table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control,
            table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control {
                padding-left: 25px !important;
            }
            
            table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before,
            table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control:before {
                left: 4px !important;
                height: 14px !important;
                width: 14px !important;
                line-height: 14px !important;
                font-size: 12px !important;
            }
            
            .name-column {
                padding-left: 30px !important;
            }
            
            .template-name-wrapper {
                padding-left: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="delete-icon">
                <i class="bi bi-trash3"></i>
            </div>
            <h3>Delete Template</h3>
            <p>Are you sure you want to delete <span class="template-name">this template</span>?</p>
            <p style="font-size: 0.9rem; color: #dc3545;">This action cannot be undone.</p>
            <div class="delete-modal-buttons">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-confirm-delete" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <!-- User Profile Modal -->
    <div id="userProfileModal" class="profile-modal">
        <div class="profile-modal-content">
            <button class="close-modal" onclick="closeProfileModal()">&times;</button>
            <div class="profile-header">
                <img src="images/profile.png" alt="Profile Picture" id="profileImage">
                <div class="profile-header-info">
                    <h3 id="userName">Loading...</h3>
                    <p id="userRole">Product Info Hub User</p>
                </div>
            </div>
            <div class="profile-details">
                <div class="profile-detail-item">
                    <label><i class="bi bi-envelope"></i> Email Address</label>
                    <span id="userEmail">Loading...</span>
                </div>
                <div class="profile-detail-item">
                    <label><i class="bi bi-calendar-check"></i> Member Since</label>
                    <span id="userJoinDate">Loading...</span>
                </div>
            </div>
        </div>
    </div>

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
                    <a class="nav-link me-2" href="#">HELLO!</a>
                    <?php if ($_SESSION['is_superadmin']): ?>
                    <a class="nav-link me-2 superadmin-btn" href="#superadminSection">
                        <i class="bi bi-shield-lock"></i> Superadmin Management
                    </a>
                    <?php endif; ?>
                    <a class="nav-link me-2 home-btn" href="dashboard.php">HOME</a>
                    <a class="nav-link me-2 logout-btn" href="index.php">LOG OUT</a>
                    <a class="nav-link" href="#" onclick="openProfileModal()">
                        <img src="images/profile.png" alt="Profile" class="profile-img">
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Templates Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">All Templates</h2>
            <a href="newtemplate.php" class="btn btn-create">Create New Template</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover" id="templatesTable">
                <thead>
                    <tr>
                        <th class="name-column">Template Name</th>
                        <th class="date-column">Created Date</th>
                        <th class="action-column">View</th>
                        <th class="action-column">Edit Fields</th>
                        <th class="action-column">Add Data</th>
                        <th class="action-column">Delete</th>
                        <th class="action-column">Edit Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No templates found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                        <tr>
                            <td><?php 
                                $template_parts = explode('_', $template['template_name']);
                                $formatted_parts = array_map(function($part) {
                                    // Split by uppercase letters and trim
                                    $words = preg_split('/(?=[A-Z])/', $part);
                                    $words = array_map('trim', $words);
                                    // Filter out empty strings and join with space
                                    $words = array_filter($words);
                                    return htmlspecialchars(ucwords(strtolower(implode(' ', $words))));
                                }, $template_parts);
                                echo '<span class="template-name-wrapper">' . 
                                     implode(' <i class="bi bi-chevron-right text-muted"></i> ', $formatted_parts) . 
                                     '</span>';
                            ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($template['created_dtm'])); ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-view" onclick="viewData(<?php echo $template['template_id']; ?>)">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </td>
                            <td class="text-center">
                                <a href="edit_fields.php?id=<?php echo $template['template_id']; ?>" class="btn btn-sm btn-edit-fields">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-add-data" onclick="addData(<?php echo $template['template_id']; ?>)">
                                    <i class="bi bi-plus-circle-fill"></i>
                                </button>
                            </td>
                            <td class="text-center">
                                <?php if (!$template['has_child']): ?>
                                <button class="btn btn-sm btn-delete" onclick="deleteTemplate(<?php echo $template['template_id']; ?>)">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-edit-data" onclick="editTemplateData(<?php echo $template['template_id']; ?>)">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add/Edit Superadmin Modal -->
        <div class="modal fade" id="superadminModal" tabindex="-1" aria-labelledby="superadminModalTitle" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: rgb(3, 7, 53); color: white;">
                        <h5 class="modal-title" id="superadminModalTitle">Add New Superadmin</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="superadminForm">
                            <input type="hidden" id="superadminId" name="id">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small class="text-muted">Leave empty to keep existing password when editing</small>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="isActive" name="is_active" checked>
                                    <label class="form-check-label" for="isActive">Active Account</label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveSuperadmin()">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Superadmin Management Modal -->
        <div class="modal fade" id="superadminManagementModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: rgb(3, 7, 53); color: white;">
                        <h5 class="modal-title">Superadmin Management</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-end mb-3">
                            <button class="btn btn-success" onclick="openAddSuperadminModal()">
                                <i class="bi bi-person-plus-fill"></i> Add New Superadmin
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="superadminsTable">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Created Date</th>
                                        <th>Last Login</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($superadmins as $admin): ?>
                                    <tr data-id="<?php echo $admin['id']; ?>">
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($admin['created_at'])); ?></td>
                                        <td><?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'Never'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $admin['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-warning edit-superadmin" 
                                                        data-id="<?php echo $admin['id']; ?>"
                                                        data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                                        data-email="<?php echo htmlspecialchars($admin['email']); ?>"
                                                        data-active="<?php echo $admin['is_active']; ?>">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteSuperadmin(<?php echo $admin['id']; ?>)">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>
                                            <?php else: ?>
                                            <span class="badge bg-info">Current User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable with sorting
            var table = $('#templatesTable').DataTable({
                "order": [[0, "asc"]],
                "columnDefs": [
                    { 
                        "orderable": true, 
                        "targets": [0, 1],
                        "className": "name-column", 
                        "targets": 0
                    },
                    { "orderable": false, "targets": [2, 3, 4, 5, 6] }
                ],
                "pageLength": 10,
                "lengthMenu": [10, 25, 50],
                "searching": true,
                "paging": true,
                "info": true,
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search...",
                    "paginate": {
                        "first": "«",
                        "last": "»",
                        "next": "›",
                        "previous": "‹"
                    },
                    "info": "_START_-_END_ of _TOTAL_",
                    "lengthMenu": "_MENU_ per page",
                    "infoEmpty": "No entries to show",
                    "infoFiltered": " (filtered from _MAX_ total entries)"
                },
                "dom": "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                       "<'row'<'col-sm-12'tr>>" +
                       "<'row'<'col-sm-5'i><'col-sm-7'p>>",
                "responsive": {
                    details: {
                        type: 'column',
                        target: 0,
                        renderer: function(api, rowIdx, columns) {
                            var data = $.map(columns, function(col, i) {
                                return col.hidden ?
                                    '<tr data-dt-row="'+col.rowIndex+'" data-dt-column="'+col.columnIndex+'">'+
                                        '<td>'+col.title+':'+'</td> '+
                                        '<td>'+col.data+'</td>'+
                                    '</tr>' :
                                    '';
                            }).join('');
                            
                            return data ? 
                                $('<table/>').append(data) :
                                false;
                        }
                    }
                },
                "autoWidth": false
            });

            // Make DataTables responsive on window resize
            $(window).resize(function() {
                table.columns.adjust().responsive.recalc();
            });

            // Handle navbar toggler for mobile
            $('.navbar-toggler').on('click', function() {
                $(this).toggleClass('active');
            });

            // Initialize Bootstrap modals
            var modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                new bootstrap.Modal(modal);
            });

            // Handle edit button clicks
            $(document).on('click', '.edit-superadmin', function(e) {
                e.preventDefault();
                const row = $(this).closest('tr');
                const id = row.data('id');
                const username = row.find('td:eq(0)').text();
                const email = row.find('td:eq(1)').text();
                const isActive = row.find('.badge').hasClass('bg-success');

                // Hide management modal
                $('#superadminManagementModal').modal('hide');

                // Reset form
                $('#superadminForm')[0].reset();

                // Set form values
                $('#superadminModalTitle').text('Edit Superadmin');
                $('#superadminId').val(id);
                $('#username').val(username);
                $('#email').val(email);
                $('#isActive').prop('checked', isActive);
                $('#password').val(''); // Clear password field

                // Show edit modal
                setTimeout(() => {
                    const editModal = new bootstrap.Modal(document.getElementById('superadminModal'));
                    editModal.show();
                }, 500);
            });

            // Handle superadmin management button click
            $('.superadmin-btn').click(function(e) {
                e.preventDefault();
                try {
                    const modal = new bootstrap.Modal(document.getElementById('superadminManagementModal'));
                    modal.show();
                } catch(error) {
                    console.error('Error showing modal:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to open superadmin management modal'
                    });
                }
            });
            
            // Initialize superadmins table with responsive features
            if ($('#superadminsTable').length) {
                $('#superadminsTable').DataTable({
                    "responsive": true,
                    "autoWidth": false,
                    "language": {
                        "search": "",
                        "searchPlaceholder": "Search superadmins...",
                        "paginate": {
                            "first": "«",
                            "last": "»",
                            "next": "›",
                            "previous": "‹"
                        }
                    }
                });
            }
        });

        function openAddSuperadminModal() {
            // Hide management modal
            $('#superadminManagementModal').modal('hide');

            // Reset form
            $('#superadminForm')[0].reset();
            $('#superadminId').val('');
            $('#superadminModalTitle').text('Add New Superadmin');
            $('#isActive').prop('checked', true);

            // Show add modal
            setTimeout(() => {
                const addModal = new bootstrap.Modal(document.getElementById('superadminModal'));
                addModal.show();
            }, 500);
        }

        function saveSuperadmin() {
            const formData = new FormData($('#superadminForm')[0]);
            
            // Validate required fields
            if (!formData.get('username') || !formData.get('email')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Username and email are required fields'
                });
                return;
            }

            // Validate email format
            const email = formData.get('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address'
                });
                return;
            }

            // Validate username length
            if (formData.get('username').length < 3) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Username',
                    text: 'Username must be at least 3 characters long'
                });
                return;
            }

            // Validate password for new superadmin
            if (!formData.get('id') && !formData.get('password')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Required',
                    text: 'Password is required for new superadmin'
                });
                return;
            }

            // Show loading state
            Swal.fire({
                title: 'Saving...',
                html: 'Please wait while we save the changes',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: 'save_superadmin.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: formData.get('id') ? 'Superadmin updated successfully' : 'New superadmin added successfully',
                                timer: 1500
                            }).then(() => {
                                $('#superadminModal').modal('hide');
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Error saving superadmin'
                            });
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error processing server response'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Error connecting to server';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        errorMessage = 'Server error: ' + error;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                }
            });
        }

        function deleteSuperadmin(id) {
            // Get the username from the row
            const username = $(`tr[data-id="${id}"]`).find('td:eq(0)').text();
            
            // Show confirmation dialog
            Swal.fire({
                title: 'Are you sure?',
                html: `Do you want to delete superadmin <strong>${username}</strong>?<br>This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel!',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Deleting...',
                        html: 'Please wait while we delete the superadmin',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Proceed with deletion
                    $.ajax({
                        url: 'delete_superadmin.php',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            try {
                                if (typeof response === 'string') {
                                    response = JSON.parse(response);
                                }
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: `${username} has been deleted successfully`,
                                        timer: 1500
                                    }).then(() => {
                                        const table = $('#superadminsTable').DataTable();
                                        table.row(`tr[data-id="${id}"]`).remove().draw();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message || 'Error deleting superadmin'
                                    });
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Error processing server response'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            let errorMessage = 'Error connecting to server';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.message || errorMessage;
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                errorMessage = 'Server error: ' + error;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage
                            });
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // User clicked "No, cancel!"
                    Swal.fire({
                        icon: 'info',
                        title: 'Cancelled',
                        text: 'The superadmin was not deleted',
                        timer: 1500
                    });
                }
            });
        }

        // Profile Modal Functions
        function openProfileModal() {
            const modal = $('#userProfileModal');
            modal.fadeIn(300);
            setTimeout(() => modal.addClass('show'), 50);
            fetchUserDetails();

            // Add loading animation
            $('#userName, #userEmail, #userJoinDate').html(
                '<div class="spinner-border spinner-border-sm text-primary" role="status">' +
                '<span class="visually-hidden">Loading...</span></div>'
            );
        }

        function closeProfileModal() {
            const modal = $('#userProfileModal');
            modal.removeClass('show');
            setTimeout(() => modal.fadeOut(300), 200);
        }

        function fetchUserDetails() {
            $.ajax({
                url: 'get_user_details.php',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Animate the text updates
                        $('#userName').fadeOut(200, function() {
                            $(this).text(response.data.name).fadeIn(200);
                        });
                        $('#userEmail').fadeOut(200, function() {
                            $(this).text(response.data.email).fadeIn(200);
                        });
                        $('#userJoinDate').fadeOut(200, function() {
                            $(this).text(response.data.created_at).fadeIn(200);
                        });
                    } else {
                        $('#userName').text('Error');
                        $('#userEmail').text('Could not load user details');
                        $('#userJoinDate').text('--');
                        console.error('Error loading user details:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#userName').text('Error');
                    $('#userEmail').text('Could not connect to server');
                    $('#userJoinDate').text('--');
                    console.error('Ajax error:', status, error);
                }
            });
        }

        // Close modal when clicking outside
        $(window).click(function(e) {
            if ($(e.target).hasClass('profile-modal')) {
                closeProfileModal();
            }
        });

        // Prevent modal close when clicking modal content
        $('.profile-modal-content').click(function(e) {
            e.stopPropagation();
        });

        // Add escape key support
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                closeProfileModal();
            }
        });

        let templateToDelete = null;

        function deleteTemplate(templateId) {
            // First check if template can be deleted
            $.get('check_template_deletion.php', { id: templateId }, function(response) {
                if (response.can_delete) {
                    // Get template name
                    const templateName = $(`button[onclick="deleteTemplate(${templateId})"]`)
                        .closest('tr')
                        .find('td:first')
                        .text()
                        .trim();
                    
                    // Show delete confirmation modal
                    templateToDelete = templateId;
                    showDeleteModal(templateName);
                } else {
                    showErrorToast('Cannot delete this template: ' + response.message);
                }
            }).fail(function() {
                showErrorToast('Error checking template status');
            });
        }

        function showDeleteModal(templateName) {
            const modal = $('#deleteModal');
            $('.template-name').text(templateName);
            modal.fadeIn(300);
            setTimeout(() => modal.addClass('show'), 50);
        }

        function closeDeleteModal() {
            const modal = $('#deleteModal');
            modal.removeClass('show');
            setTimeout(() => modal.fadeOut(300), 200);
            templateToDelete = null;
        }

        function confirmDelete() {
            if (!templateToDelete) return;

            const templateId = templateToDelete;
            $.post('delete_template.php', { id: templateId }, function(response) {
                if (response.success) {
                    closeDeleteModal();
                    // Reload the page with a small delay for the modal to close
                    setTimeout(() => window.location.reload(), 300);
                } else {
                    showErrorToast(response.message || 'Error deleting template');
                }
            }).fail(function() {
                showErrorToast('Error connecting to server');
            });
        }

        function showErrorToast(message) {
            // You can enhance this with a proper toast notification
            alert(message);
        }

        // Close delete modal when clicking outside
        $(window).click(function(e) {
            if ($(e.target).hasClass('delete-modal')) {
                closeDeleteModal();
            }
        });

        // Prevent delete modal close when clicking modal content
        $('.delete-modal-content').click(function(e) {
            e.stopPropagation();
        });

        // Add escape key support for delete modal
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                if ($('#deleteModal').is(':visible')) {
                    closeDeleteModal();
                }
            }
        });

        function viewData(templateId) {
            window.location.href = 'view_data.php?id=' + templateId;
        }

        function editFields(templateId) {
            window.location.href = 'edit_fields.php?id=' + templateId;
        }

        function addData(templateId) {
            window.location.href = 'add_data.php?id=' + templateId;
        }

        function editTemplateData(templateId) {
            window.location.href = 'edit_template_data.php?id=' + templateId;
        }
    </script>
</body>
</html> 