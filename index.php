<?php
session_start();
$errors = ['login' => $_SESSION['login_error'] ?? ''];
session_unset();
function showError($error){
    return !empty($error) ? "<p class='error-message' color = 'red'>$error</p>" : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .bg-primary-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .library-icon {
            font-size: 4rem;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Left Side - Branding -->
            <div class="col-md-6 d-none d-md-flex bg-primary-gradient text-white align-items-center justify-content-center">
                <div class="text-center p-5">
                    <div class="library-icon mb-4">📚</div>
                    <h1 class="display-3 fw-bold mb-4">Library Portal</h1>
                    <p class="lead fs-4">Welcome to Vibecoders Library Management System</p>
                    <p class="mt-4">Manage books, members, and loans efficiently</p>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="col-md-6 d-flex align-items-center justify-content-center">
                <div class="w-100 p-4" style="max-width: 450px;">
                    <div class="card border-0">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4 fw-bold">Sign In</h2>
                            <?= showError($errors['login']); ?> 
                            <p class="text-center text-muted mb-4">Enter your credentials to access the system</p>
                            
                            <form id="loginForm" action = "login.php" method ="post" novalidate>
                                

                                <div class="mb-4">
                                    <label for="email" class="form-label fw-semibold">Email Address</label>
                                    <input type="email" class="form-control form-control-lg" name="email" 
                                           placeholder="Enter your email" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label fw-semibold">Password</label>
                                    <input type="password" class="form-control form-control-lg" name="password" 
                                           placeholder="Enter your password" required>
                                    <div class="invalid-feedback">Please enter your password.</div>
                                </div>



                                <button type="submit" name = "login" class="btn btn-primary btn-lg w-100 mb-3">
                                    Sign In
                                </button>

                                <div class="text-center">
                                    <a href="member_registration.php" class="text-decoration-none">
                                        New member? Register here
                                    </a>
                                </div>

                                <div class="text-center mt-2">
                                    <a href="#" class="text-muted text-decoration-none small">
                                        Forgot password?
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Info Section -->
                    <div class="mt-4 text-center text-muted small">
                        <p class="mb-1">© 2025 Vibecoders Library Management System</p>
                        <p>CS 4347.001 Database Systems - Team 9</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>