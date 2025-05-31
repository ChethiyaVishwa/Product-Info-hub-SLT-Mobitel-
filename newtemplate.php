<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Template - Product Info Hub</title>
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
            box-shadow: 0 0 15px rgb(0, 0, 0);
            padding: 20px;
            margin: 60px auto;
            max-width: 1200px;
        }
        .btn-check-availability {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .btn-select-parent {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px;
            border-radius: 4px;
            width: 100%;
            margin-top: 8px;
            font-size: 0.9rem;
        }
        .btn-add-new {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 6px;
            border-radius: 4px;
            width: 100%;
            margin-top: 8px;
            font-size: 0.9rem;
        }
        .btn-save-data {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 30px;
            border-radius: 4px;
            font-size: 1rem;
            display: block;
            margin: 20px auto 0;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        .form-control {
            border-radius: 4px;
            padding: 6px 8px;
            border: 1px solid #ced4da;
            font-size: 0.9rem;
            height: calc(1.5em + 0.75rem + 2px);
        }
        /* Add new modal styles */
        .modal-content {
            border-radius: 8px;
        }
        .modal-header {
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
        }
        .modal-header .close {
            font-size: 1.5rem;
            padding: 1rem;
            margin: -1rem -1rem -1rem auto;
        }
        .modal-body {
            padding: 1rem;
        }
        .field-row {
            display: grid;
            grid-template-columns: 2fr 1fr 2fr 0.5fr 0.5fr;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }
        .btn-add {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 6px 20px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .btn-add-to-main {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 8px 25px;
            border-radius: 5px;
            float: right;
            margin-top: 20px;
        }
        /* Add these styles to match the dashboard button styling */
        .btn-sm {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 2px;
            border-radius: 4px;
        }
        
        .btn-sm i {
            font-size: 1rem;
        }
        
        .table th, .table td {
            padding: 0.5rem;
            font-size: 0.9rem;
            vertical-align: middle;
        }
        
        .table th.text-center, .table td.text-center {
            text-align: center;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #bb2d3b;
            color: white;
        }

        /* Form spacing */
        .mb-4 {
            margin-bottom: 1rem !important;
        }

        .mt-4 {
            margin-top: 1rem !important;
        }

        .gap-2 {
            gap: 0.5rem !important;
        }

        /* Headings */
        h2.mb-4 {
            font-size: 1.4rem;
            margin-bottom: 1rem !important;
        }

        h4 {
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }

        h5 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        /* Badge styling */
        .badge {
            padding: 0.25em 0.5em;
            font-size: 0.75rem;
        }

        /* Select styling */
        .form-select {
            padding: 6px 24px 6px 8px;
            font-size: 0.9rem;
            height: calc(1.5em + 0.75rem + 2px);
            border-radius: 4px;
        }

        /* Checkbox styling */
        .form-check {
            margin-top: 0 !important;
        }

        .form-check-input {
            margin-top: 0.2rem;
        }

        /* Notification */
        #saveNotification {
            font-size: 0.9rem;
            padding: 8px 15px;
        }

        /* Template feedback */
        #templateNameFeedback {
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        /* Table container */
        .fields-table-container {
            margin-top: 1rem;
        }

        .table-responsive {
            margin-bottom: 1rem;
        }

        /* Parent template section */
        #templateFieldsSection {
            margin-top: 1rem;
        }

        .btn-info.btn-sm.view-fields,
        .btn-primary.btn-sm.select-template {
            width: auto;
            height: auto;
            padding: 6px 16px;
            font-size: 0.9rem;
            margin: 0 4px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-info.btn-sm.view-fields:hover,
        .btn-primary.btn-sm.select-template:hover {
            transform: translateY(-1px);
            transition: transform 0.2s;
        }

        #parentTemplatesTable td {
            vertical-align: middle;
            padding: 8px 12px;
        }

        #parentTemplatesTable .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }

        #parentTemplatesTable .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .modal-dialog.modal-xl {
            max-width: 800px;
            margin: 1.75rem auto;
        }

        #parentTemplatesTable {
            width: 100%;
            margin-bottom: 1rem;
        }

        #parentTemplatesTable th {
            padding: 8px 12px;
            font-size: 0.9rem;
            font-weight: 500;
            border-bottom: 2px solid #dee2e6;
        }

        #parentTemplatesTable td {
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        #parentTemplatesTable th:first-child {
            width: 40%;
        }

        #parentTemplatesTable th:nth-child(2) {
            width: 25%;
        }

        #parentTemplatesTable th:last-child {
            width: 35%;
        }
        
        /* Responsive Media Queries */
        @media (max-width: 1199.98px) {
            .content-wrapper {
                padding: 20px;
                margin: 50px auto;
                max-width: 95%;
            }
            
            .field-row {
                grid-template-columns: 2fr 1fr 2fr 0.5fr 0.5fr;
                gap: 8px;
            }
            
            .modal-dialog.modal-xl {
                max-width: 95%;
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
            
            .field-row {
                grid-template-columns: 1fr 1fr 1fr 0.5fr 0.5fr;
            }
        }
        
        @media (max-width: 767.98px) {
            .content-wrapper {
                padding: 15px;
                margin: 40px auto;
                max-width: 95%;
            }
            
            h2.mb-4 {
                font-size: 1.3rem;
            }
            
            .field-row {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: auto auto auto;
            }
            
            .field-row > div:nth-child(3) {
                grid-column: 1 / -1;
            }
            
            .field-row > div:nth-child(4),
            .field-row > div:nth-child(5) {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .btn-check-availability {
                margin-top: 0.5rem;
                width: 100%;
            }
            
            .d-flex.gap-2 {
                flex-direction: column;
            }
            
            .table {
                font-size: 0.85rem;
            }
            
            #saveNotification {
                width: 90%;
            }
        }
        
        @media (max-width: 575.98px) {
            .content-wrapper {
                margin: 20px auto;
                padding: 12px;
            }
            
            h2.mb-4 {
                font-size: 1.2rem;
            }
            
            h4, h5 {
                font-size: 1rem;
            }
            
            .btn-save-data {
                width: 100%;
                padding: 8px;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
            
            .table {
                min-width: 500px;
            }
            
            .modal-dialog {
                margin: 0.5rem;
            }
            
            .modal-content {
                padding: 0.5rem;
            }
            
            .field-row {
                grid-template-columns: 1fr;
            }
            
            .field-row > div {
                grid-column: 1 / -1;
                margin-bottom: 8px;
            }
            
            .btn-add {
                width: 100%;
            }
            
            .btn-sm {
                width: 30px;
                height: 30px;
            }
            
            .navbar-brand img {
                height: 30px;
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
        <h2 class="mb-4">Create New Template</h2>
        
        <!-- Add success notification -->
        <div id="saveNotification" class="alert alert-success" style="display: none; position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1050;">
            <i class="bi bi-check-circle-fill me-2"></i>
            <span class="message">Template saved successfully!</span>
        </div>

        <form id="newTemplateForm">
            <div class="mb-4">
                <label for="templateName" class="form-label">New Template Name:</label>
                <div class="input-group mb-2">
                    <input type="text" class="form-control" id="templateName" name="template_name" placeholder="Template Name" required>
                    <button type="button" class="btn-check-availability">Check</button>
                </div>
                <div id="templateNameFeedback" class="mt-2"></div>
            </div>

            <div class="mb-4">
                <label for="parentTemplate" class="form-label">Parent Template Name:</label>
                <input type="text" class="form-control mb-2" id="parentTemplate" readonly>
                <input type="hidden" id="parent_template_id" name="parent_template_id">
                <div class="row g-2">
                    <div class="col-md-6 col-12 mb-2">
                        <button type="button" class="btn-select-parent w-100">Select parent template</button>
                    </div>
                    <div class="col-md-6 col-12">
                        <button type="button" class="btn-add-new w-100">Add New Field</button>
                    </div>
                </div>
            </div>

            <!-- Fields Table -->
            <div class="fields-table-container mt-4">
                <h4>Template Fields</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Field Name</th>
                                <th>Field Type</th>
                                <th>Description</th>
                                <th>Fixed/Dynamic</th>
                                <th>Source</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="parentFieldsTableBody">
                            <!-- Parent template fields will be added here -->
                        </tbody>
                        <tbody id="fieldsTableBody">
                            <!-- New fields will be added here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <button type="submit" class="btn-save-data">Save Data</button>
        </form>
    </div>

    <!-- New Template Field Modal -->
    <div class="modal fade" id="newFieldModal" tabindex="-1" aria-labelledby="newFieldModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newFieldModalLabel">New Template Field</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-lg-5 col-md-6 col-12 mb-3">
                            <label class="form-label">Field Name</label>
                            <input type="text" class="form-control" id="fieldName" placeholder="Enter Field Name">
                        </div>
                        <div class="col-lg-3 col-md-6 col-12 mb-3">
                            <label class="form-label">Select Type</label>
                            <select class="form-select" id="fieldType">
                                <option value="text">text</option>
                                <option value="number">number</option>
                                <option value="date">date</option>
                                <option value="price">price</option>
                            </select>
                        </div>
                        <div class="col-lg-4 col-12 mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" id="fieldDescription" placeholder="Description">
                        </div>
                        <div class="col-lg-6 col-md-6 col-6 d-flex align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="fixedField">
                                <label class="form-check-label" for="fixedField">Fixed Field</label>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-6 mb-3">
                            <button class="btn-add w-100">Add Field</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Parent Template Selection Modal -->
    <div class="modal fade" id="parentTemplateModal" tabindex="-1" aria-labelledby="parentTemplateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="parentTemplateModalLabel">Select Parent Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Template List -->
                    <div class="table-responsive mb-4">
                        <table class="table table-hover" id="parentTemplatesTable">
                            <thead>
                                <tr>
                                    <th>Template Name</th>
                                    <th>Created Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Template Fields Section -->
                    <div id="templateFieldsSection" style="display: none;">
                        <h5 class="mb-3">Template Fields</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Field Name</th>
                                        <th>Field Type</th>
                                        <th>Description</th>
                                        <th>Fixed/Dynamic</th>
                                    </tr>
                                </thead>
                                <tbody id="templateFieldsBody">
                                    <!-- Will be populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Move fields array to global scope
        let fields = [];
        let parentFields = [];

        // Move removeField function to global scope
        function removeField(index) {
            fields.splice(index, 1);
            updateFieldsTable();
        }

        function updateFieldsTable() {
            const tbody = $('#fieldsTableBody');
            tbody.empty();

            fields.forEach((field, index) => {
                const row = `
                    <tr>
                        <td>${field.name}</td>
                        <td>${field.type}</td>
                        <td>${field.description}</td>
                        <td>${field.fixed ? 'Fixed' : 'Dynamic'}</td>
                        <td><span class="badge bg-success">New Field</span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeField(${index})">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        $(document).ready(function() {
            // Handle navbar toggler for mobile
            $('.navbar-toggler').on('click', function() {
                $(this).toggleClass('active');
            });
            
            // Adjust modal max height on mobile
            function adjustModalMaxHeight() {
                const windowHeight = $(window).height();
                $('.modal-body').css('max-height', (windowHeight * 0.7) + 'px');
                $('.modal-body').css('overflow-y', 'auto');
            }
            
            // Call on page load and window resize
            adjustModalMaxHeight();
            $(window).resize(function() {
                adjustModalMaxHeight();
            });
            
            // Initialize all modals
            $('.modal').on('show.bs.modal', function() {
                adjustModalMaxHeight();
            });
            
            // Function to load parent template fields recursively
            function loadParentTemplateFields(templateId) {
                $.get('get_template_fields.php', { 
                    template_id: templateId,
                    include_parent_fields: true
                }, function(response) {
                    if (response.success) {
                        parentFields = response.fields.map(field => ({
                            ...field,
                            source_template: field.source_template || 'Current Parent'
                        }));
                        updateParentFieldsTable();
                        $('.fields-table-container').show();
                    }
                });
            }

            // Function to update parent fields table
            function updateParentFieldsTable() {
                const tbody = $('#parentFieldsTableBody');
                tbody.empty();

                // Group fields by source template
                const groupedFields = {};
                parentFields.forEach((field) => {
                    if (!groupedFields[field.source_template]) {
                        groupedFields[field.source_template] = [];
                    }
                    groupedFields[field.source_template].push(field);
                });

                // Add fields group by group
                Object.entries(groupedFields).forEach(([templateName, templateFields]) => {
                    // Add template header
                    tbody.append(`
                        <tr class="table-secondary">
                            <td colspan="6"><strong>From Template: ${templateName}</strong></td>
                        </tr>
                    `);

                    // Add fields
                    templateFields.forEach((field) => {
                        const row = `
                            <tr class="table-light">
                                <td>${field.field_name}</td>
                                <td>${field.field_type}</td>
                                <td>${field.description || '-'}</td>
                                <td>${field.is_fixed ? 'Fixed' : 'Dynamic'}</td>
                                <td><span class="badge bg-info">Inherited</span></td>
                                <td class="text-center">-</td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                });
            }

            // Add Field Button Click Handler
            $('.btn-add').click(function() {
                const fieldName = $('#fieldName').val().trim();
                const fieldType = $('#fieldType').val();
                const description = $('#fieldDescription').val().trim();
                const isFixed = $('#fixedField').is(':checked');

                if (!fieldName) {
                    alert('Please enter a field name');
                    return;
                }

                // Add field to array
                fields.push({
                    name: fieldName,
                    type: fieldType,
                    description: description || '-',
                    fixed: isFixed
                });

                // Update fields table
                updateFieldsTable();

                // Clear the form
                $('#fieldName').val('');
                $('#fieldDescription').val('');
                $('#fixedField').prop('checked', false);
                $('#fieldType').val('text');

                // Close the modal
                $('#newFieldModal').modal('hide');

                // Show the fields table container
                $('.fields-table-container').show();
            });

            // Handle template selection
            $(document).on('click', '.select-template', function() {
                const templateId = $(this).data('id');
                const templateName = $(this).data('name');
                const currentTemplateName = $('#templateName').val().trim();

                $('#parentTemplate').val(templateName);
                $('#parent_template_id').val(templateId);
                
                // Update template name to include parent template name
                if (currentTemplateName) {
                    const newTemplateName = templateName + '_' + currentTemplateName;
                    $('#templateName').val(newTemplateName);
                }
                
                // Load parent template fields
                loadParentTemplateFields(templateId);
                
                $('#parentTemplateModal').modal('hide');
                $('#templateFieldsSection').hide();
            });

            // Form Submit Handler
            $('#newTemplateForm').on('submit', function(e) {
                e.preventDefault();
                
                const templateName = $('#templateName').val().trim();
                const parentTemplateName = $('#parentTemplate').val();
                
                if (!templateName) {
                    $('#templateNameFeedback').html('<div class="text-danger">Please enter a template name</div>');
                    return;
                }

                // Ensure template name includes parent template prefix
                let finalTemplateName = templateName;
                if (parentTemplateName && !templateName.startsWith(parentTemplateName + '_')) {
                    finalTemplateName = parentTemplateName + '_' + templateName;
                }

                // Get form data including both parent and new fields
                const formData = {
                    template_name: finalTemplateName,
                    parent_template_id: $('#parent_template_id').val(),
                    parent_fields: parentFields,
                    fields: fields
                };

                // Submit form data
                $.ajax({
                    url: 'save_template.php',
                    type: 'POST',
                    data: JSON.stringify(formData),
                    contentType: 'application/json',
                    success: function(response) {
                        if (response.success) {
                            // Show success notification
                            $('#saveNotification').fadeIn('fast').delay(2000).fadeOut('slow');
                            
                            // Redirect after a short delay
                            setTimeout(function() {
                                window.location.href = 'dashboard.php';
                            }, 2500);
                        } else {
                            alert(response.message || 'Error creating template');
                        }
                    },
                    error: function() {
                        alert('Error connecting to server');
                    }
                });
            });

            // Check Availability Button Click Handler
            $('.btn-check-availability').click(function() {
                const templateName = $('#templateName').val().trim();
                if (!templateName) {
                    $('#templateNameFeedback').html('<div class="text-danger">Please enter a template name</div>');
                    return;
                }

                $.post('check_template.php', { template_name: templateName }, function(response) {
                    if (response.success) {
                        $('#templateNameFeedback').html(
                            response.available ? 
                            '<div class="text-success">Template name is available</div>' : 
                            '<div class="text-danger">Template name already exists</div>'
                        );
                    } else {
                        $('#templateNameFeedback').html('<div class="text-danger">' + response.message + '</div>');
                    }
                }).fail(function() {
                    $('#templateNameFeedback').html('<div class="text-danger">Error checking template name</div>');
                });
            });

            // Select Parent Template Button Click Handler
            $('.btn-select-parent').click(function() {
                // Load available templates
                $.get('get_templates.php', function(response) {
                    if (response.success) {
                        let tableBody = $('#parentTemplatesTable tbody');
                        tableBody.empty();

                        response.templates.forEach(function(template) {
                            let row = `
                                <tr>
                                    <td>${template.template_name}</td>
                                    <td>${template.created_dtm}</td>
                                    <td>
                                        <button class="btn btn-info btn-sm view-fields" 
                                                data-id="${template.template_id}" 
                                                data-name="${template.template_name}">
                                            <i class="bi bi-eye me-1"></i> View Fields
                                        </button>
                                        <button class="btn btn-primary btn-sm select-template" 
                                                data-id="${template.template_id}" 
                                                data-name="${template.template_name}">
                                            <i class="bi bi-check2 me-1"></i> Select
                                        </button>
                                    </td>
                                </tr>
                            `;
                            tableBody.append(row);
                        });

                        $('#parentTemplateModal').modal('show');
                    } else {
                        alert('Error loading templates');
                    }
                }).fail(function() {
                    alert('Error connecting to server');
                });
            });

            // View Fields Button Click Handler
            $(document).on('click', '.view-fields', function() {
                const templateId = $(this).data('id');
                const templateName = $(this).data('name');

                // Load template fields including parent fields
                $.get('get_template_fields.php', { 
                    template_id: templateId,
                    include_parent_fields: true
                }, function(response) {
                    if (response.success) {
                        let fieldsBody = $('#templateFieldsBody');
                        fieldsBody.empty();

                        // Group fields by source template
                        const groupedFields = {};
                        response.fields.forEach((field) => {
                            const source = field.source_template || templateName;
                            if (!groupedFields[source]) {
                                groupedFields[source] = [];
                            }
                            groupedFields[source].push(field);
                        });

                        // Add fields group by group
                        Object.entries(groupedFields).forEach(([source, templateFields]) => {
                            // Add template header
                            fieldsBody.append(`
                                <tr class="table-secondary">
                                    <td colspan="4"><strong>From Template: ${source}</strong></td>
                                </tr>
                            `);

                            // Add fields
                            templateFields.forEach((field) => {
                                const row = `
                                    <tr>
                                        <td>${field.field_name}</td>
                                        <td>${field.field_type}</td>
                                        <td>${field.description || '-'}</td>
                                        <td>${field.is_fixed ? 'Fixed' : 'Dynamic'}</td>
                                    </tr>
                                `;
                                fieldsBody.append(row);
                            });
                        });

                        $('#templateFieldsSection').show();
                    } else {
                        alert('Error loading template fields');
                    }
                }).fail(function() {
                    alert('Error connecting to server');
                });
            });

            // Add New Button Click Handler - Show the field modal
            $('.btn-add-new').click(function() {
                $('#newFieldModal').modal('show');
            });
        });
    </script>
</body>
</html> 