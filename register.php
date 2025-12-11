<?php
include 'includes/config.php';
include 'includes/functions.php';

// Redirect if already logged in
if(isLoggedIn()) {
    redirect('customer/dashboard.php');
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Validation
    $errors = [];
    
    if(empty($username)) {
        $errors[] = "Username is required";
    }
    
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if(strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if(empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    // Check if username or email already exists
    if(empty($errors)) {
        $check_sql = "SELECT * FROM users WHERE username='$username' OR email='$email'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if(mysqli_num_rows($check_result) > 0) {
            $errors[] = "Username or email already exists";
        }
    }
    
    if(empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user into database
        $sql = "INSERT INTO users (username, password, email, full_name, phone, address, role) 
                VALUES ('$username', '$hashed_password', '$email', '$full_name', '$phone', '$address', 'customer')";
        
        if(mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Registration successful! Please login with your email/username and password.";
            redirect('login.php');
        } else {
            $error = "Registration failed: " . mysqli_error($conn);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRAD Servicing : Hire, Succeed, Repeat - Register</title>
    <style>
        @font-face {
            font-family: 'Science Gothic';
            src: url('assets/fonts/ScienceGothic-Medium.ttf') format('truetype');
            font-weight: normal;
        }

        html, body{
            margin: 0;
            height: 100%;
        }

        body{
            background-image: url(assets/images/bg2.jpg);
            background-size: cover;
            backdrop-filter: blur(5px);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-family: "Science Gothic", sans-serif;
        }

        .logo img{
            height: 80px;
            width: 200px;
        }

        .description p{
            margin-top: 32px;
            font-size: 14px;
            line-height: 1.6;
        }

        #view{
            padding: 12px 24px;
            border: none;
            margin-top: 20px;
            border-radius: 6px;
            background: #50d0e0;
            color: #1b1a1a;
            font-size: 16px;
            cursor: pointer;
            font-family: "Science Gothic", sans-serif;
            transition: background 0.3s;
        }

        #view:hover{
            background: #3f8791;  
        }

        .register-container{
            background-image: url(assets/images/bg1.jpg);
            background-repeat: no-repeat;
            background-size: cover;
            display: grid;
            grid-template-columns: 550px 580px;
            gap: 50px;
            padding: 50px;
            border: 1px solid black;
            border-radius: 18px;
            box-shadow: 0 0 20px rgba(163, 142, 142, 0.2);
            max-width: 1160px;
            margin: 20px;
        }

        .signup {
            width: 100%;
            max-width: 500px;
            padding: 25px;
            border-radius: 12px;
            background: rgba(66, 64, 64, 0.3); 
            box-shadow: 0 0 20px rgba(221, 210, 210, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(39, 37, 37, 0.8);
        }

        .signup h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #e0e0e0; 
            font-size: 24px;
        }

        .signup label {
            color: #e0e0e0;
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }

        .signup input[type="text"],
        .signup input[type="email"],
        .signup input[type="password"],
        .signup input[type="tel"],
        .signup textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.2); 
            color: #fff;
            box-sizing: border-box;
        }

        .signup input:focus,
        .signup textarea:focus {
            outline: none;
            border-color: #50d0e0;
            box-shadow: 0 0 5px rgba(80, 208, 224, 0.5);
        }

        .signup a {
            font-size: 14px;
            color: #aee2ff;
            text-decoration: none;
        }

        .signup a:hover {
            text-decoration: underline;
        }

        .signup button {
            width: 100%;
            padding: 12px;
            border: none;
            margin-top: 20px;
            border-radius: 6px;
            background: #50d0e0;
            color: #1b1a1a;
            font-size: 16px;
            cursor: pointer;
            font-family: "Science Gothic", sans-serif;
            transition: background 0.3s;
            font-weight: bold;
        }

        .signup button:hover {
            background: #3f8791;
        }

        .signup p {
            margin-top: 20px;
            color: #e0e0e0;
            text-align: center;
            font-size: 14px;
        }

        .signup p a {
            color: #50d0e0;
            font-weight: bold;
        }

        .error-message {
            background: rgba(255, 0, 0, 0.2);
            color: #ff6b6b;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid rgba(255, 0, 0, 0.3);
        }

        .success-message {
            background: rgba(0, 255, 0, 0.2);
            color: #4CAF50;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid rgba(0, 255, 0, 0.3);
        }

        .brand-tagline {
            color: #50d0e0;
            font-style: italic;
            margin-top: 5px;
        }

        /* Two-Column Form Layout */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 5px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .password-requirements {
            font-size: 12px;
            color: #aaa;
            margin-top: -10px;
            margin-bottom: 10px;
        }

        .welcome {
            padding: 10px;
        }

        .welcome ul {
            padding-left: 20px;
            margin-top: 15px;
        }

        .welcome li {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .register-container {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 40px;
                max-width: 600px;
            }
            
            .signup {
                width: 100%;
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .register-container {
                padding: 20px;
                margin: 10px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .welcome {
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 15px;
            }
            
            .signup {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="register-container">

        <div class="welcome">
            <div class="logo">
                <img src="assets/images/logo-light-transparent.png" alt="DRAD Servicing Logo">
            </div>
            <div class="description">
                <h3>DRAD Servicing System</h3>
                <p class="brand-tagline">Hire, Succeed, Repeat.</p>
                <p>A streamlined platform designed to provide fast, reliable, and efficient service support to our customers.</p>
            </div>
            <a href="index.php">
                <button id="view">Back to Home</button>
            </a>
        </div>

        <div class="signup">
            <h2>CREATE ACCOUNT</h2>
            
            <?php if($error): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="success-message">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- First Row: Full Name and Username -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required placeholder="Enter full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required placeholder="Enter username">
                    </div>
                </div>
                
                <!-- Second Row: Email and Phone -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter phone number" required>
                    </div>
                </div>
                
                <!-- Third Row: Address (Full Width) -->
                <div class="form-group full-width">
                    <label for="address">Address *</label>
                    <textarea id="address" name="address" rows="3" placeholder="Enter your complete address" required></textarea>
                </div>
                
                <!-- Fourth Row: Password and Confirm Password -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required placeholder="Enter Password">
                        <div class="password-requirements">Minimum 6 characters</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm password">
                    </div>
                </div>
                
                <button type="submit">Create Account</button>
                
                <p>Already have an account? <a href="login.php">Login Here</a></p>
            </form>
        </div>
        
    </div>
    
</body>
</html>