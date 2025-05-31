<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Product Info Hub - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        #bg-video {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;    
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: -2;
            object-fit: cover;
        }
        .login-container {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.64), rgba(200, 200, 255, 0.1));
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgb(255, 255, 255);
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin: 0 15px;
        }

        .login-container:hover {
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }

        .logo {
            max-width: 180px;
            margin-bottom: 0.5rem;
            width: 80%;
            height: auto;
        }
        .btn-login {
            background-color: #004B93;
            box-shadow: 0 8px 32px rgb(255, 255, 255);
            font-size: 0.95rem;
            color: white;
            width: 100%;
            padding: 8px;
            margin-bottom: 0.75rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-login:hover {
            background-color: rgb(162, 210, 255);
            color: rgb(0, 0, 0);
        }
        .btn-super-admin, .btn-admin, .btn-user {
            font-size: 0.9rem;
            color: white;
            padding: 6px 4px;
            margin-bottom: 0.75rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            flex: 1;
        }
        .btn-super-admin {
            background-color: #dc3545;
        }
        .btn-super-admin:hover {
            background-color: #c82333;
            color: white;
        }
        .btn-admin {
            background-color: #fd7e14;
        }
        .btn-admin:hover {
            background-color: #e66c07;
            color: white;
        }
        .btn-user {
            background-color: #28a745;
        }
        .btn-user:hover {
            background-color: #218838;
            color: white;
        }
        .login-buttons-row {
            display: none;
            gap: 6px;
            margin-bottom: 0.75rem;
        }
        .login-buttons-row.show {
            display: flex;
        }
        .role-selection-title {
            color: #004B93;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 1.1rem;
            display: none;
        }
        .role-selection-title.show {
            display: block;
        }
        .btn-google {
            background-color: #004B93;
            box-shadow: 0 8px 32px rgb(255, 255, 255);
            font-size: 0.95rem;
            color: rgb(255, 255, 255);
            width: 100%;
            padding: 8px;
            margin-bottom: 0.75rem;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        .btn-google:hover {
            background-color:rgb(162, 210, 255);
            color:rgb(0, 0, 0);
            text-decoration: none;
        }
        .btn-google img {
            width: 16px;
            height: 16px;
        }
        .background-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        .shape {
            position: absolute;
            background-color: rgba(16, 17, 77, 0);
            transform: rotate(45deg);
        }
        .text-muted {
            font-size: 0.9rem;
        }
        .mt-2 {
            margin-top: 0.5rem !important;
        }
        .d-grid.gap-3 {
            gap: 0.75rem !important;
        }
        .role-selection-title{
            color:rgb(255, 255, 255);
        }

        /* Responsive Media Queries */
        @media (max-width: 576px) {
            .login-container {
                width: 95%;
                padding: 1.2rem;
                margin: 0 10px;
            }
            
            .logo {
                max-width: 150px;
            }
            
            .btn-login, .btn-google {
                font-size: 0.85rem;
                padding: 7px;
            }
            
            .login-buttons-row {
                flex-direction: column;
                gap: 4px;
            }
            
            .btn-super-admin, .btn-admin, .btn-user {
                padding: 7px;
                margin-bottom: 4px;
            }
            
            .role-selection-title {
                font-size: 1rem;
            }
            
            .text-muted {
                font-size: 0.8rem;
            }
        }
        
        @media (max-height: 650px) {
            .login-container {
                padding: 1rem;
            }
            
            .logo {
                max-width: 120px;
                margin-bottom: 0.3rem;
            }
            
            .btn-login, .btn-google, .btn-super-admin, .btn-admin, .btn-user {
                padding: 5px;
                margin-bottom: 0.5rem;
                font-size: 0.8rem;
            }
            
            .d-grid.gap-3 {
                gap: 0.5rem !important;
            }
        }
        
        /* Ensure video background covers properly on all devices */
        @media (max-aspect-ratio: 16/9) {
            #bg-video {
                width: 100%;
                height: auto;
            }
        }
        
        @media (min-aspect-ratio: 16/9) {
            #bg-video {
                width: auto;
                height: 100%;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="bg-video">
        <source src="images/bg-9.mp4" type="video/mp4">
    </video>
    
    <div class="background-shapes">
        <!-- Background shapes will be added by JavaScript -->
    </div>
    
    <div class="login-container">
        <img src="images/logo.png" alt="Product Info Hub Logo" class="logo img-fluid">
        
        <div class="d-grid gap-3">
            <h4 class="role-selection-title">Select Your Role</h4>
            <div class="login-buttons-row">
                <a href="login.php" class="btn btn-super-admin d-flex justify-content-center align-items-center">
                    <span class="d-none d-sm-inline">Super</span> Admin
                </a>
                <a href="adminlogin.php" class="btn btn-admin d-flex justify-content-center align-items-center">Admin</a>
                <a href="userlogin.php" class="btn btn-user d-flex justify-content-center align-items-center">User</a>
            </div>
            <a href="azure_login.php" class="btn btn-login d-flex justify-content-center align-items-center">
                <span class="d-none d-sm-inline-block me-1">Login with</span> Azure
            </a>
            <a href="#" class="btn btn-google" id="googleLoginBtn">
                <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0OCA0OCI+PHBhdGggZmlsbD0iI0VBNDMzNSIgZD0iTTI0IDkuNWMzLjU0IDAgNi43MSAxLjIyIDkuMjEgMy42bDYuODUtNi44NUMzNS45IDIuMzggMzAuNDcgMCAyNCAwIDEyLjY2IDAgMy4yMyA1LjUyLjM2IDE0LjRsMy4wNiAyLjNjLjAxLS4wMS4wMi0uMDIuMDMtLjAyQzYuMjEgOC45NSAxNC42NiA0LjUgMjQgNC41eiIvPjxwYXRoIGZpbGw9IiM0Mjg1RjQiIGQ9Ik00NiAyNC4xMkM0NiAyMi45MyA0NS44NyAyMS44MyA0NS42MSAyMEgyNHY4LjVoMTIuMTRjLS41IDIuNy0yLjA2IDQuOTktNC4zMyA2LjUzbDYuNDMgNWM0LjI0LTMuOTMgNi43Ni05LjQ2IDYuNzYtMTUuOTF6Ii8+PHBhdGggZmlsbD0iI0ZCQkMwNSIgZD0iTTEwLjM5IDE0LjQxQzExLjQ3IDEyLjA1IDEzIDEwLjA0IDE0Ljg0IDguNDlsLTMuMDYtMi4zQzkuMzYgOC4zNiA2LjI5IDExLjA2IDQuMTEgMTQuNDFjLTIuMTQgMy4zMy0zLjM5IDcuMjctMy4zOSAxMS41OXMxLjI1IDguMjYgMy4zOSAxMS41OWMyLjE4IDMuMzUgNS4yNSA2LjA1IDguNjcgNy4yMmwzLjA2LTIuM2MtMS44NC0xLjU1LTMuMzctMy41Ni00LjQ1LTUuOTItMS4wNy0yLjM1LTEuNjctNC45Mi0xLjY3LTcuNTlzLjYtNS4yNCAxLjY3LTcuNTl6Ii8+PHBhdGggZmlsbD0iIzM0QTg1MyIgZD0iTTI0IDQ4YzYuNDcgMCAxMS45LTIuMzggMTUuODctNi4zNWwtNi40My01Yy0yLjEgMS40NS00LjcyIDIuMy03LjQ0IDIuM0MxNC42NiAzOC45NSA2LjIxIDM0LjUgMy4wNiAyNi44NmwtMy4wNiAyLjNDMy4yMyA0Mi40OCAxMi42NiA0OCAyNCA0OHoiLz48L3N2Zz4=" alt="Google Icon">
                <span class="d-none d-sm-inline-block">Login with</span> Google
            </a>
            <div class="text-center mt-2">
                <span class="text-muted">Don't have an account?</span>
                <a href="register.php" class="btn btn-login mt-2">Register Now</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add background shapes
        function createShapes() {
            const container = document.querySelector('.background-shapes');
            // Create fewer shapes on mobile for better performance
            const isMobile = window.innerWidth <= 768;
            const numShapes = isMobile ? 8 : 15;
            
            for (let i = 0; i < numShapes; i++) {
                const shape = document.createElement('div');
                shape.className = 'shape';
                const size = Math.random() * (isMobile ? 80 : 100) + 50;
                shape.style.width = size + 'px';
                shape.style.height = size + 'px';
                shape.style.left = Math.random() * 100 + '%';
                shape.style.top = Math.random() * 100 + '%';
                container.appendChild(shape);
            }
        }
        
        // Function to handle screen resize
        function handleResize() {
            const isMobile = window.innerWidth <= 576;
            const loginButtonsRow = document.querySelector('.login-buttons-row');
            
            // If it's mobile and the buttons are showing, make them stack vertically
            if (isMobile && loginButtonsRow.classList.contains('show')) {
                loginButtonsRow.style.flexDirection = 'column';
            } else {
                loginButtonsRow.style.flexDirection = 'row';
            }
        }
        
        // Initialize
        createShapes();
        handleResize();
        
        // Add event listeners
        window.addEventListener('resize', handleResize);

        document.getElementById('googleLoginBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('.role-selection-title').classList.add('show');
            document.querySelector('.login-buttons-row').classList.add('show');
            
            // Apply mobile styling immediately if needed
            handleResize();
            
            // Scroll to ensure buttons are visible on mobile
            if (window.innerWidth <= 576) {
                setTimeout(() => {
                    this.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        });
    </script>
</body>
</html>
