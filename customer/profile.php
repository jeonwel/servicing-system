<?php
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is customer
if(!isLoggedIn() || !isCustomer()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user info using prepared statement
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$user_result = executeQuery($conn, $user_sql, [$user_id], "i");
$user = mysqli_fetch_assoc($user_result);

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check which form was submitted
    if(isset($_POST['update_profile'])) {
        // Update personal information
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        
        // Validation
        $errors = [];
        
        if(empty($full_name)) $errors[] = "Full name is required";
        if(empty($email)) $errors[] = "Email is required";
        if(empty($phone)) $errors[] = "Phone number is required";
        if(empty($address)) $errors[] = "Address is required";
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        
        // Check if email already exists (excluding current user)
        $email_check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $email_check_result = executeQuery($conn, $email_check_sql, [$email, $user_id], "si");
        
        if($email_check_result && mysqli_num_rows($email_check_result) > 0) {
            $errors[] = "Email already exists. Please use a different email.";
        }
        
        if(empty($errors)) {
            $update_sql = "UPDATE users SET 
                           full_name = ?, 
                           email = ?, 
                           phone = ?, 
                           address = ? 
                           WHERE user_id = ?";
            
            // Use executeUpdate for UPDATE queries
            if(executeUpdate($conn, $update_sql, [$full_name, $email, $phone, $address, $user_id], "ssssi")) {
                // Update session variables
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                
                // Refresh user data
                $user_result = executeQuery($conn, $user_sql, [$user_id], "i");
                $user = mysqli_fetch_assoc($user_result);
                
                $success = "Profile updated successfully!";
            } else {
                // Check for specific SQL error
                $error = "Error updating profile. Please try again.";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
        // Handle password change
    if(isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        // Verify current password
        if(!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if(empty($new_password)) {
            $errors[] = "New password is required";
        } elseif(strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }
        
        if($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if(empty($errors)) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $password_sql = "UPDATE users SET password = ? WHERE user_id = ?";
            
            // Use executeUpdate for UPDATE queries
            if(executeUpdate($conn, $password_sql, [$hashed_password, $user_id], "si")) {
                $success = "Password changed successfully!";
            } else {
                $error = "Error changing password. Please try again.";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
}

// Get user statistics
$stats_sql = "SELECT 
                COUNT(*) as total_tickets,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tickets,
                SUM(CASE WHEN status IN ('pending', 'assigned', 'in_progress') THEN 1 ELSE 0 END) as active_tickets
              FROM service_requests 
              WHERE customer_id = ?";
$stats_result = executeQuery($conn, $stats_sql, [$user_id], "i");
$stats = mysqli_fetch_assoc($stats_result);

// Get recent tickets
$recent_tickets_sql = "SELECT 
                        sr.request_id,
                        sr.service_id,
                        sr.category,
                        sr.status,
                        sr.request_date,
                        s.service_name,
                        tn.ticket_code
                       FROM service_requests sr
                       JOIN services s ON sr.service_id = s.service_id
                       LEFT JOIN ticket_numbers tn ON sr.request_id = tn.request_id
                       WHERE sr.customer_id = ?
                       ORDER BY sr.request_date DESC 
                       LIMIT 5";
$recent_tickets_result = executeQuery($conn, $recent_tickets_sql, [$user_id], "i");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRAD Servicing : My Profile</title>
    <style>
        @font-face {
            font-family: 'Science Gothic';
            src: url('../assets/fonts/ScienceGothic-Medium.ttf') format('truetype');
            font-weight: normal;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Science Gothic", sans-serif;
            background: linear-gradient(135deg, #0a0a2a 0%, #1a1a3a 100%);
            background-color: black;
            color: #fff;
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(80, 208, 224, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(80, 208, 224, 0.1) 0%, transparent 20%);
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .main-header {
            background: rgba(10, 10, 42, 0.9);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(80, 208, 224, 0.3);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-section img {
            height: 60px;
            width: auto;
        }

        .nav-menu {
            display: flex;
            gap: 25px;
            list-style: none;
        }

        .nav-menu a {
            color: #fff;
            text-decoration: none;
            font-size: 1rem;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-menu a:hover {
            color: #50d0e0;
        }

        .nav-menu a.active {
            color: #50d0e0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #50d0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #1b1a1a;
            font-size: 1.2rem;
        }

        .user-name {
            color: #50d0e0;
            font-weight: bold;
        }

        .logout-btn {
            background: transparent;
            color: #ff6b6b;
            border: 2px solid #ff6b6b;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-family: "Science Gothic", sans-serif;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: rgba(255, 107, 107, 0.1);
        }

        /* Main Content */
        main {
            padding-top: 120px;
            min-height: calc(100vh - 200px);
            background-color: black;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 2.5rem;
            color: #50d0e0;
            margin-bottom: 15px;
        }

        .page-subtitle {
            color: #aee2ff;
            font-size: 1.2rem;
            line-height: 1.6;
        }

        .profile-badge {
            display: inline-block;
            background: linear-gradient(45deg, #50d0e0, #2196F3);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        /* Profile Layout */
        .profile-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }

        @media (max-width: 1024px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Profile Sidebar */
        .profile-sidebar {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 140px;
            height: fit-content;
        }

        .profile-avatar-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            background: rgba(80, 208, 224, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 3px solid #50d0e0;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar-text {
            font-size: 3rem;
            color: #50d0e0;
            font-weight: bold;
        }

        .avatar-upload-form {
            margin-top: 20px;
        }

        .avatar-input {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(80, 208, 224, 0.3);
            border-radius: 8px;
            color: #fff;
            font-family: "Science Gothic", sans-serif;
            font-size: 0.9rem;
        }

        .avatar-upload-btn {
            width: 100%;
            margin-top: 10px;
            padding: 10px;
            background: rgba(80, 208, 224, 0.2);
            color: #50d0e0;
            border: 1px solid #50d0e0;
            border-radius: 8px;
            cursor: pointer;
            font-family: "Science Gothic", sans-serif;
            transition: all 0.3s ease;
        }

        .avatar-upload-btn:hover {
            background: rgba(80, 208, 224, 0.3);
        }

        .profile-stats {
            margin-top: 30px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #aee2ff;
            font-size: 0.95rem;
        }

        .stat-value {
            color: #fff;
            font-weight: bold;
            font-size: 1.1rem;
        }

        /* Profile Content */
        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Profile Card */
        .profile-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
        }

        .card-title {
            font-size: 1.4rem;
            color: #50d0e0;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(80, 208, 224, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            font-size: 1.5rem;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        @media (max-width: 768px) {
            .form-group.full-width {
                grid-column: span 1;
            }
        }

        .form-label {
            display: block;
            color: #aee2ff;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-label .required {
            color: #ff6b6b;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(80, 208, 224, 0.3);
            border-radius: 8px;
            color: #fff;
            font-family: "Science Gothic", sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #50d0e0;
            box-shadow: 0 0 0 3px rgba(80, 208, 224, 0.1);
            background: rgba(255, 255, 255, 0.15);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Recent Activity */
        .recent-activity {
            margin-top: 20px;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: rgba(80, 208, 224, 0.05);
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: rgba(80, 208, 224, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #50d0e0;
            font-size: 1.2rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            color: #fff;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .activity-meta {
            color: #aee2ff;
            font-size: 0.9rem;
        }

        .activity-status {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-pending { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .status-assigned { background: rgba(33, 150, 243, 0.2); color: #2196F3; }
        .status-in_progress { background: rgba(255, 152, 0, 0.2); color: #ff9800; }
        .status-completed { background: rgba(76, 175, 80, 0.2); color: #4CAF50; }
        .status-cancelled { background: rgba(244, 67, 54, 0.2); color: #f44336; }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-family: "Science Gothic", sans-serif;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #50d0e0, #2196F3);
            color: #fff;
            font-weight: bold;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(80, 208, 224, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: #50d0e0;
            border: 2px solid #50d0e0;
        }

        .btn-secondary:hover {
            background: rgba(80, 208, 224, 0.1);
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border-color: #4CAF50;
            color: #4CAF50;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            border-color: #f44336;
            color: #ff6b6b;
        }

        /* Footer */
        .main-footer {
            background: rgba(10, 10, 42, 0.9);
            padding: 60px 0 30px;
            margin-top: 80px;
            border-top: 1px solid rgba(80, 208, 224, 0.3);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h3 {
            color: #50d0e0;
            font-size: 1.3rem;
            margin-bottom: 20px;
        }

        .footer-section p, .footer-section a {
            color: #aee2ff;
            line-height: 1.6;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
        }

        .footer-section a:hover {
            color: #50d0e0;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #aaa;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 20px;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .user-info {
                margin-top: 15px;
            }
            
            main {
                padding-top: 160px;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .profile-sidebar {
                position: static;
                margin-bottom: 30px;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 5px;
            height: 4px;
            border-radius: 2px;
            background: rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .strength-weak { background-color: #f44336; }
        .strength-medium { background-color: #ff9800; }
        .strength-strong { background-color: #4CAF50; }

        .strength-text {
            font-size: 0.85rem;
            margin-top: 5px;
            color: #aaa;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="../assets/images/logo-light-transparent.png" alt="DRAD Servicing Logo">
                </div>
                
                <nav>
                    <ul class="nav-menu">
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="book-service.php">Request Service</a></li>
                        <li><a href="tickets.php">My Tickets</a></li>
                        <li><a href="profile.php" class="active">Profile</a></li>
                    </ul>
                </nav>
                
                <div class="user-info">
                    <div class="user-avatar">
                        <?php 
                        $name_parts = explode(' ', $_SESSION['full_name']);
                        $initials = '';
                        foreach($name_parts as $part) {
                            $initials .= strtoupper(substr($part, 0, 1));
                        }
                        echo substr($initials, 0, 2);
                        ?>
                    </div>
                    <span class="user-name"><?php echo $_SESSION['full_name']; ?></span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Page Header -->
            <div class="page-header animate-fade-in-up">
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">
                    Manage your personal information, security settings, and account preferences.
                    <span class="profile-badge">Account Management</span>
                </p>
            </div>

            <!-- Success/Error Messages -->
            <?php if($success): ?>
                <div class="alert alert-success animate-fade-in-up">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="alert alert-error animate-fade-in-up">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="profile-layout">
                <!-- Left Sidebar: Profile Summary -->
                <div class="profile-sidebar animate-fade-in-up" style="animation-delay: 0.1s;">
                    <div class="profile-avatar-section">
                        <div class="profile-avatar">
                            <?php if(!empty($user['avatar'])): ?>
                                <img src="../assets/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                     alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                            <?php else: ?>
                                <div class="profile-avatar-text">
                                    <?php 
                                    $name_parts = explode(' ', $user['full_name']);
                                    $initials = '';
                                    foreach($name_parts as $part) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                    }
                                    echo substr($initials, 0, 2);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h3 style="color: #fff; margin-bottom: 10px;"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                        <p style="color: #aee2ff; margin-bottom: 15px;"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="profile-badge">Customer Account</span>
                    </div>
                    
                    <!-- Profile Statistics -->
                    <div class="profile-stats">
                        <h4 style="color: #50d0e0; margin-bottom: 20px; font-size: 1.1rem;">Account Statistics</h4>
                        
                        <div class="stat-item">
                            <span class="stat-label">Member Since</span>
                            <span class="stat-value">
                                <?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?>
                            </span>
                        </div>
                        
                        <div class="stat-item">
                            <span class="stat-label">Total Tickets</span>
                            <span class="stat-value"><?php echo $stats['total_tickets'] ?? 0; ?></span>
                        </div>
                        
                        <div class="stat-item">
                            <span class="stat-label">Active Issues</span>
                            <span class="stat-value"><?php echo $stats['active_tickets'] ?? 0; ?></span>
                        </div>
                        
                        <div class="stat-item">
                            <span class="stat-label">Completed</span>
                            <span class="stat-value"><?php echo $stats['completed_tickets'] ?? 0; ?></span>
                        </div>
                        
                        <div class="stat-item">
                            <span class="stat-label">Account Status</span>
                            <span class="stat-value" style="color: #4CAF50;">Active</span>
                        </div>
                    </div>
                </div>

                <!-- Right Content: Profile Forms -->
                <div class="profile-content">
                    <!-- Personal Information Form -->
                    <div class="profile-card animate-fade-in-up" style="animation-delay: 0.2s;">
                        <h3 class="card-title">
                            <span>👤</span> Personal Information
                        </h3>
                        
                        <form method="POST" action="">
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label class="form-label">Full Name <span class="required">*</span></label>
                                    <input type="text" name="full_name" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email Address <span class="required">*</span></label>
                                    <input type="email" name="email" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Phone Number <span class="required">*</span></label>
                                    <input type="tel" name="phone" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label class="form-label">Address <span class="required">*</span></label>
                                    <textarea name="address" class="form-textarea" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <span>💾</span> Save Changes
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <span>🔄</span> Reset Form
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password Form -->
                    <div class="profile-card animate-fade-in-up" style="animation-delay: 0.3s;">
                        <h3 class="card-title">
                            <span>🔒</span> Security Settings
                        </h3>
                        
                        <form method="POST" action="" id="passwordForm">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Current Password <span class="required">*</span></label>
                                    <input type="password" name="current_password" class="form-input" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">New Password <span class="required">*</span></label>
                                    <input type="password" name="new_password" class="form-input" id="newPassword" required>
                                    <div class="password-strength">
                                        <div class="strength-bar" id="strengthBar"></div>
                                    </div>
                                    <div class="strength-text" id="strengthText">Password strength</div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Confirm New Password <span class="required">*</span></label>
                                    <input type="password" name="confirm_password" class="form-input" id="confirmPassword" required>
                                    <div id="passwordMatch" style="font-size: 0.85rem; margin-top: 5px;"></div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <span>🔑</span> Change Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Recent Activity -->
                    <div class="profile-card animate-fade-in-up" style="animation-delay: 0.4s;">
                        <h3 class="card-title">
                            <span>📋</span> Recent Service Requests
                        </h3>
                        
                        <div class="recent-activity">
                            <?php if($recent_tickets_result && mysqli_num_rows($recent_tickets_result) > 0): ?>
                                <div class="activity-list">
                                    <?php while($ticket = mysqli_fetch_assoc($recent_tickets_result)): 
                                        $status_class = str_replace(' ', '_', strtolower($ticket['status']));
                                    ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <?php 
                                                switch($ticket['category']) {
                                                    case 'Computer Repair': echo '💻'; break;
                                                    case 'Network Configuration': echo '🌐'; break;
                                                    case 'IT Consultation': echo '🔍'; break;
                                                    default: echo '🔧';
                                                }
                                                ?>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title">
                                                    <?php echo htmlspecialchars($ticket['service_name']); ?>
                                                </div>
                                                <div class="activity-meta">
                                                    <?php echo $ticket['ticket_code']; ?> • 
                                                    <?php echo date('M d, Y', strtotime($ticket['request_date'])); ?>
                                                </div>
                                            </div>
                                            <span class="activity-status status-<?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                
                                <div style="text-align: center; margin-top: 20px;">
                                    <a href="tickets.php" class="btn btn-secondary">
                                        <span>📋</span> View All Tickets
                                    </a>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 40px 20px; color: #aee2ff;">
                                    <div style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;">📭</div>
                                    <h3 style="color: #fff; margin-bottom: 10px;">No Recent Activity</h3>
                                    <p>You haven't requested any services yet.</p>
                                    <a href="book-service.php" class="btn btn-primary" style="margin-top: 20px;">
                                        <span>🔧</span> Request Your First Service
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>DRAD Servicing</h3>
                    <p>Specialized networking and computer repair services. Describe your issue, get prepared technicians, and experience efficient IT solutions.</p>
                    <p class="tagline" style="color: #50d0e0; margin-top: 10px;">Networking Solutions - Hire, Succeed, Repeat</p>
                </div>
                
                <div class="footer-section">
                    <h3>Customer Menu</h3>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="book-service.php">Request Service</a>
                    <a href="tickets.php">My Tickets</a>
                    <a href="profile.php">My Profile</a>
                    <a href="../logout.php">Logout</a>
                </div>
                
                <div class="footer-section">
                    <h3>Technical Services</h3>
                    <a href="../index.php#services">Computer Diagnostic & Repair</a>
                    <a href="../index.php#services">Basic Network Configuration</a>
                    <a href="../index.php#services">IT Consultation</a>
                </div>
                
                <div class="footer-section">
                    <h3>Technical Support</h3>
                    <a href="mailto:techsupport@dradservicing.com">Email: techsupport@dradservicing.com</a>
                    <a href="tel:+63212345678">Phone: (02) 1234-5678</a>
                    <a href="#">Service Hours</a>
                    <a href="#">Technical FAQ</a>
                    <a href="#">Service Area</a>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> DRAD Servicing System. All rights reserved.</p>
                <p>Database Management System Project - For academic purposes</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password strength indicator
            const newPasswordInput = document.getElementById('newPassword');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const passwordMatch = document.getElementById('passwordMatch');
            
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Check password strength
                if(password.length >= 8) strength++;
                if(/[A-Z]/.test(password)) strength++;
                if(/[0-9]/.test(password)) strength++;
                if(/[^A-Za-z0-9]/.test(password)) strength++;
                
                // Update strength bar
                let width = 0;
                let text = '';
                let color = '';
                
                switch(strength) {
                    case 0:
                        width = 0;
                        text = 'Too weak';
                        color = '#f44336';
                        break;
                    case 1:
                        width = 25;
                        text = 'Weak';
                        color = '#f44336';
                        break;
                    case 2:
                        width = 50;
                        text = 'Fair';
                        color = '#ff9800';
                        break;
                    case 3:
                        width = 75;
                        text = 'Good';
                        color = '#4CAF50';
                        break;
                    case 4:
                        width = 100;
                        text = 'Strong';
                        color = '#4CAF50';
                        break;
                }
                
                strengthBar.style.width = width + '%';
                strengthBar.style.backgroundColor = color;
                strengthBar.className = 'strength-bar';
                
                if(strength <= 1) {
                    strengthBar.classList.add('strength-weak');
                } else if(strength === 2) {
                    strengthBar.classList.add('strength-medium');
                } else {
                    strengthBar.classList.add('strength-strong');
                }
                
                strengthText.textContent = text;
                strengthText.style.color = color;
                
                // Check password match
                checkPasswordMatch();
            });
            
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            
            function checkPasswordMatch() {
                const password = newPasswordInput.value;
                const confirm = confirmPasswordInput.value;
                
                if(confirm === '') {
                    passwordMatch.textContent = '';
                    passwordMatch.style.color = '';
                } else if(password === confirm) {
                    passwordMatch.textContent = '✓ Passwords match';
                    passwordMatch.style.color = '#4CAF50';
                } else {
                    passwordMatch.textContent = '✗ Passwords do not match';
                    passwordMatch.style.color = '#f44336';
                }
            }
            
            // Form validation
            const passwordForm = document.getElementById('passwordForm');
            passwordForm.addEventListener('submit', function(e) {
                const password = newPasswordInput.value;
                const confirm = confirmPasswordInput.value;
                
                if(password !== confirm) {
                    e.preventDefault();
                    alert('Passwords do not match. Please check and try again.');
                    confirmPasswordInput.focus();
                    return false;
                }
                
                if(password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long.');
                    newPasswordInput.focus();
                    return false;
                }
            });
            
            // Avatar upload preview
            const avatarInput = document.querySelector('input[name="avatar"]');
            avatarInput.addEventListener('change', function(e) {
                const file = this.files[0];
                if(file) {
                    // Validate file size
                    if(file.size > 2 * 1024 * 1024) {
                        alert('File size must be less than 2MB.');
                        this.value = '';
                        return;
                    }
                    
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if(!allowedTypes.includes(file.type)) {
                        alert('Only JPG, PNG, and GIF files are allowed.');
                        this.value = '';
                        return;
                    }
                    
                    // Preview image (optional enhancement)
                    /*
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const avatarImg = document.querySelector('.profile-avatar img');
                        if(avatarImg) {
                            avatarImg.src = e.target.result;
                        }
                    }
                    reader.readAsDataURL(file);
                    */
                }
            });
            
            // Add hover effect to activity items
            const activityItems = document.querySelectorAll('.activity-item');
            activityItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
            
            // Update user avatar in header to match profile
            const headerAvatar = document.querySelector('.user-info .user-avatar');
            if(headerAvatar) {
                // Update with initials from current user
                const userName = '<?php echo $_SESSION["full_name"]; ?>';
                const nameParts = userName.split(' ');
                let initials = '';
                nameParts.forEach(part => {
                    initials += part.charAt(0).toUpperCase();
                });
                headerAvatar.textContent = initials.substring(0, 2);
            }
        });
    </script>
</body>
</html>