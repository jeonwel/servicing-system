<?php
include 'includes/config.php';
include 'includes/functions.php';

// Redirect if already logged in
if(isLoggedIn()) {
    if(isCustomer()) {
        redirect('customer/dashboard.php');
    } elseif(isAdmin()) {
        redirect('admin/dashboard.php');
    } elseif(isTechnician()) {
        redirect('technician/dashboard.php');
    }
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = sanitize($_POST['login_input']);
    $password = $_POST['pass'];
    
    // Check if input is email or username
    if(filter_var($login_input, FILTER_VALIDATE_EMAIL)) {
        // Input is email - use prepared statement
        $sql = "SELECT * FROM users WHERE email=? AND status='active'";
        $param_type = "s";
    } else {
        // Input is username - use prepared statement
        $sql = "SELECT * FROM users WHERE username=? AND status='active'";
        $param_type = "s";
    }
    
    // Use prepared statement with your executeQuery function
    $result = executeQuery($conn, $sql, [$login_input], $param_type);
    
    if($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if(password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            
            // Redirect based on role
            switch($user['role']) {
                case 'customer':
                    redirect('customer/dashboard.php');
                    break;
                case 'admin':
                    redirect('admin/dashboard.php');
                    break;
                case 'technician':
                    redirect('technician/dashboard.php');
                    break;
                default:
                    redirect('index.php');
            }
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Email/Username not found or account is inactive!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRAD Servicing : Hire, Succeed, Repeat - Login</title>
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

        .login-container{
            background-image: url(assets/images/bg1.jpg);
            background-repeat: no-repeat;
            background-size: cover;
            display: grid;
            grid-template-columns: 550px 415px;
            gap: 50px;
            padding: 75px;
            border: 1px solid black;
            border-radius: 18px;
            box-shadow: 0 0 20px rgba(163, 142, 142, 0.2);
        }

        .signin {
            width: 350px;
            padding: 25px;
            border-radius: 12px;
            background: rgba(66, 64, 64, 0.3); 
            box-shadow: 0 0 20px rgba(221, 210, 210, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(39, 37, 37, 0.8);
        }

        .signin h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #e0e0e0; 
            font-size: 24px;
        }

        .signin label {
            color: #e0e0e0;
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }

        .signin input[type="text"],
        .signin input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.2); 
            color: #fff;
            box-sizing: border-box;
        }

        .signin input:focus {
            outline: none;
            border-color: #50d0e0;
            box-shadow: 0 0 5px rgba(80, 208, 224, 0.5);
        }

        .signin a {
            font-size: 14px;
            color: #aee2ff;
            text-decoration: none;
        }

        .signin a:hover {
            text-decoration: underline;
        }

        .signin button {
            width: 100%;
            padding: 12px;
            border: none;
            margin-top: 15px;
            border-radius: 6px;
            background: #50d0e0;
            color: #1b1a1a;
            font-size: 16px;
            cursor: pointer;
            font-family: "Science Gothic", sans-serif;
            transition: background 0.3s;
            font-weight: bold;
        }

        .signin button:hover {
            background: #3f8791;
        }

        .signin p {
            margin-top: 20px;
            color: #e0e0e0;
            text-align: center;
            font-size: 14px;
        }

        .signin p a {
            color: #50d0e0;
            font-weight: bold;
        }

        #forgot{
            margin-left: 60px;
            color: #50d0e0;
            float: right;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }

        .remember-me input[type="checkbox"] {
            margin-right: 8px;
            width: auto;
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

        @media (max-width: 1024px) {
            .login-container {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 40px;
            }
            
            .signin {
                width: 100%;
                max-width: 400px;
            }
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 20px;
                margin: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">

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
                <button id="view">View Here</button>
            </a>
        </div>

        <div class="signin">
            <h2>LOGIN</h2>
            
            <?php if($error): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="success-message">
                    <?php echo $_SESSION['success']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-field">
                    <label for="login_input">Email or Username *</label>
                    <input type="text" id="login_input" name="login_input" required placeholder="Enter your email or username">
                    
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="pass" required placeholder="Enter your password">
                </div>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                    <a id="forgot" href="forgot-password.php">Forgot Password?</a>
                </div>
                
                <button type="submit">Login</button>
                
                <p>Don't have an account? <a href="register.php">Register Here</a></p>
            </form>
        </div>
        
    </div>
    
</body>
</html>