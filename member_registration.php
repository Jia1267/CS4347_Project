
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Registration - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .registration-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 30px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            color: #667eea !important;
            font-weight: bold;
            font-size: 1.3rem;
        }
        .progress-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #e9ecef;
            margin: 0 5px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .step.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                📚 Library System
            </a>

        </div>
    </nav>

    <div class="container registration-container">
        <div class="card">
            <div class="card-header text-center">
                <h2 class="mb-2 fw-bold">📝 Member Registration</h2>
                <p class="mb-0">Join our library community today!</p>
            </div>
            <div class="card-body p-5">
              
                <form id="memberRegistrationForm" action = "register.php" method ="post">
                    <!-- Personal Information Section -->
                    <div class="mb-4">
                        <h5 class="text-primary mb-3">👤 Personal Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="firstName" class="form-label required-field">First Name</label>
                                <input type="text" class="form-control form-control-lg" name="fname" 
                                       placeholder="Enter first name" required>
                                <div class="invalid-feedback">Please enter your first name.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lastName" class="form-label required-field">Last Name</label>
                                <input type="text" class="form-control form-control-lg" name="lname" 
                                       placeholder="Enter last name" required>
                                <div class="invalid-feedback">Please enter your last name.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="dob" class="form-label required-field">Date of Birth</label>
                            <input type="date" class="form-control form-control-lg" name="dob" required>
                            <div class="invalid-feedback">Please enter your date of birth.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Contact Information Section -->
                    <div class="mb-4">
                        <h5 class="text-primary mb-3">📧 Contact Information</h5>
                        <div class="mb-3">
                            <label for="email" class="form-label required-field">Email Address</label>
                            <input type="email" class="form-control form-control-lg" name="email" 
                                   placeholder="your.email@example.com" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                            <small class="text-muted">This will be used for your login</small>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label required-field">Phone Number</label>
                            <input type="tel" class="form-control form-control-lg" name="phone" 
                                   placeholder="(123) 456-7890" required>
                            <div class="invalid-feedback">Please enter a valid phone number.</div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label required-field">Address</label>
                            <textarea class="form-control form-control-lg" name="address" rows="3" 
                                      placeholder="Enter your full address" required></textarea>
                            <div class="invalid-feedback">Please enter your address.</div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label required-field">Role</label>
                            <select class="form-select form-select-lg" id="role" name="role" required>
                                <option value="">Select role</option>
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Account Setup Section -->
                    <div class="mb-4">
                        <h5 class="text-primary mb-3">🔐 Account Setup</h5>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="password" class="form-label required-field">Password</label>
                                <input type="password" class="form-control form-control-lg" name="password" 
                                       placeholder="Create a password" required>
                            </div>

                    </div>



                    <!-- Submit Button -->
                    <button type="submit" name = "register" class="btn btn-primary w-100 mb-3">
                        Create Account
                    </button>

                    <div class="text-center">
                        <p class="mb-0">Already have an account? 
                            <a href="index.php" class="text-decoration-none fw-semibold">Sign in here</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 text-white">
            <p class="mb-1">© 2025 Vibecoders Library Management System</p>
            <p class="small">CS 4347.001 Database Systems - Team 9</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   
</body>
</html>