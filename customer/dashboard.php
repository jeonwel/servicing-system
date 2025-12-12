<?php
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is customer
if(!isLoggedIn() || !isCustomer()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Get user info using prepared statement
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$user_result = executeQuery($conn, $user_sql, [$user_id], "i");
$user = mysqli_fetch_assoc($user_result);

// Get ticket counts using prepared statements
$pending_sql = "SELECT COUNT(*) as count FROM service_requests 
                WHERE customer_id = ? AND status IN ('pending', 'assigned', 'in_progress')";
$pending_result = executeQuery($conn, $pending_sql, [$user_id], "i");
$pending_count = mysqli_fetch_assoc($pending_result)['count'];

$completed_sql = "SELECT COUNT(*) as count FROM service_requests 
                  WHERE customer_id = ? AND status = 'completed'";
$completed_result = executeQuery($conn, $completed_sql, [$user_id], "i");
$completed_count = mysqli_fetch_assoc($completed_result)['count'];

$total_sql = "SELECT COUNT(*) as count FROM service_requests WHERE customer_id = ?";
$total_result = executeQuery($conn, $total_sql, [$user_id], "i");
$total_count = mysqli_fetch_assoc($total_result)['count'];

// Get networking services count
$services_sql = "SELECT COUNT(*) as count FROM services WHERE status = 'available'";
$services_result = executeQuery($conn, $services_sql);
$services_count = mysqli_fetch_assoc($services_result)['count'];

// Get recent activity using prepared statement
$recent_sql = "SELECT sr.*, s.service_name, tn.ticket_code 
               FROM service_requests sr 
               JOIN services s ON sr.service_id = s.service_id 
               LEFT JOIN ticket_numbers tn ON sr.request_id = tn.request_id
               WHERE sr.customer_id = ? 
               ORDER BY sr.request_date DESC LIMIT 8";
$recent_result = executeQuery($conn, $recent_sql, [$user_id], "i");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRAD Servicing : Customer Dashboard</title>
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
            max-width: 1400px;
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

        /* Dashboard Content */
        main {
            padding-top: 100px;
            min-height: calc(100vh - 200px);
            background-color: black;
        }

        .dashboard-header {
            margin-bottom: 40px;
        }

        .welcome-message {
            font-size: 2.2rem;
            color: #50d0e0;
            margin-bottom: 10px;
        }

        .welcome-subtitle {
            color: #aee2ff;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .network-focus-badge {
            display: inline-block;
            background: linear-gradient(45deg, #50d0e0, #2196F3);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 15px;
        }

        /* Dashboard Cards Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #50d0e0, #2196F3);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            border-color: #50d0e0;
            box-shadow: 0 10px 30px rgba(80, 208, 224, 0.2);
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 20px;
            display: block;
        }

        .card-title {
            font-size: 1.4rem;
            color: #fff;
            margin-bottom: 15px;
        }

        .card-count {
            font-size: 3rem;
            font-weight: bold;
            color: #50d0e0;
            margin: 20px 0;
        }

        .card-action {
            margin-top: 20px;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 6px;
            font-family: "Science Gothic", sans-serif;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
        }

        .btn-primary {
            background: #50d0e0;
            color: #1b1a1a;
            font-weight: bold;
        }

        .btn-primary:hover {
            background: #3f8791;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            color: #50d0e0;
            border: 2px solid #50d0e0;
        }

        .btn-secondary:hover {
            background: rgba(80, 208, 224, 0.1);
        }

        /* Services Overview */
        .services-overview {
            margin: 50px 0;
        }

        .services-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .service-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .service-item:hover {
            border-color: #50d0e0;
            transform: translateY(-3px);
        }

        .service-item-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .service-icon {
            width: 50px;
            height: 50px;
            background: rgba(80, 208, 224, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #50d0e0;
        }

        .service-item-title {
            font-size: 1.3rem;
            color: #fff;
            margin: 0;
        }

        .service-category {
            display: inline-block;
            background: rgba(80, 208, 224, 0.1);
            color: #50d0e0;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .service-description {
            color: #aee2ff;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .service-features {
            list-style: none;
            padding-left: 0;
            margin: 15px 0;
        }

        .service-features li {
            color: #aee2ff;
            margin-bottom: 8px;
            padding-left: 25px;
            position: relative;
        }

        .service-features li:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #50d0e0;
            font-weight: bold;
        }

        /* Recent Activity Section */
        .section-title {
            font-size: 2rem;
            color: #50d0e0;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(80, 208, 224, 0.3);
        }

        .recent-activity {
            margin-top: 50px;
        }

        .activity-table {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
        }

        .activity-table th {
            background: rgba(80, 208, 224, 0.2);
            color: #50d0e0;
            padding: 18px 20px;
            text-align: left;
            font-weight: bold;
        }

        .activity-table td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .activity-table tr:last-child td {
            border-bottom: none;
        }

        .activity-table tr:hover {
            background: rgba(80, 208, 224, 0.05);
        }

        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
        }

        .status-pending { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .status-assigned { background: rgba(33, 150, 243, 0.2); color: #2196F3; }
        .status-in_progress { background: rgba(255, 152, 0, 0.2); color: #ff9800; }
        .status-completed { background: rgba(76, 175, 80, 0.2); color: #4CAF50; }
        .status-cancelled { background: rgba(244, 67, 54, 0.2); color: #f44336; }

        .action-link {
            color: #50d0e0;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .action-link:hover {
            color: #aee2ff;
            text-decoration: underline;
        }

        /* Quick Actions */
        .quick-actions {
            margin-top: 50px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            text-align: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .action-card:hover {
            border-color: #50d0e0;
            transform: translateY(-3px);
        }

        .action-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            display: block;
            color: #50d0e0;
        }

        .action-title {
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: 10px;
        }

        .action-description {
            color: #aee2ff;
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        /* Process Guide */
        .process-guide {
            margin-top: 60px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
        }

        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .process-step {
            text-align: center;
            position: relative;
        }

        .process-step:not(:last-child):after {
            content: '→';
            position: absolute;
            right: -15px;
            top: 30px;
            color: #50d0e0;
            font-size: 1.5rem;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: #50d0e0;
            color: #1b1a1a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: bold;
            margin: 0 auto 15px;
        }

        .step-title {
            font-size: 1.1rem;
            color: #fff;
            margin-bottom: 10px;
        }

        .step-description {
            color: #aee2ff;
            font-size: 0.9rem;
            line-height: 1.5;
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
        @media (max-width: 1024px) {
            .nav-menu {
                gap: 15px;
            }
            
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .process-step:not(:last-child):after {
                display: none;
            }
            
            .process-steps {
                grid-template-columns: repeat(2, 1fr);
            }
        }

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
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .services-list {
                grid-template-columns: 1fr;
            }
            
            .activity-table {
                display: block;
                overflow-x: auto;
            }
            
            .welcome-message {
                font-size: 1.8rem;
            }
            
            .process-steps {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }
            
            .dashboard-card, .action-card, .service-item {
                padding: 20px;
            }
            
            .card-count {
                font-size: 2.5rem;
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 2px dashed rgba(80, 208, 224, 0.3);
            margin-top: 20px;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #50d0e0;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 1.5rem;
            color: #fff;
            margin-bottom: 15px;
        }

        .empty-state-description {
            color: #aee2ff;
            max-width: 500px;
            margin: 0 auto 30px;
            line-height: 1.6;
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
                        <li><a href="dashboard.php" class="active">Dashboard</a></li>
                        <li><a href="book-service.php">Request Service</a></li>
                        <li><a href="tickets.php">My Tickets</a></li>
                        <li><a href="profile.php">Profile</a></li>
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
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1 class="welcome-message">Welcome back, <?php echo $_SESSION['full_name']; ?>! 👨‍💻</h1>
                <p class="welcome-subtitle">
                    Manage your networking service requests, track technical issues, and get professional IT support.
                    <span class="network-focus-badge">Networking & Computer Solutions</span>
                </p>
            </div>

            <!-- Dashboard Stats Cards -->
            <div class="dashboard-grid">
                <div class="dashboard-card animate-fade-in-up" style="animation-delay: 0.1s;">
                    <span class="card-icon">🔄</span>
                    <h3 class="card-title">Active Technical Issues</h3>
                    <div class="card-count"><?php echo $pending_count; ?></div>
                    <p>Networking/Computer issues in progress</p>
                    <div class="card-action">
                        <a href="tickets.php" class="btn btn-primary">View All Issues</a>
                    </div>
                </div>
                
                <div class="dashboard-card animate-fade-in-up" style="animation-delay: 0.2s;">
                    <span class="card-icon">✅</span>
                    <h3 class="card-title">Resolved Issues</h3>
                    <div class="card-count"><?php echo $completed_count; ?></div>
                    <p>Completed networking services</p>
                    <div class="card-action">
                        <a href="tickets.php?status=completed" class="btn btn-secondary">View History</a>
                    </div>
                </div>
                
                <div class="dashboard-card animate-fade-in-up" style="animation-delay: 0.3s;">
                    <span class="card-icon">💻</span>
                    <h3 class="card-title">Available Services</h3>
                    <div class="card-count"><?php echo $services_count; ?></div>
                    <p>Networking & IT solutions</p>
                    <div class="card-action">
                        <a href="book-service.php" class="btn btn-primary">View Services</a>
                    </div>
                </div>
                
                <div class="dashboard-card animate-fade-in-up" style="animation-delay: 0.4s;">
                    <span class="card-icon">🔧</span>
                    <h3 class="card-title">Request Support</h3>
                    <p>Need networking or computer help?</p>
                    <div class="card-action" style="margin-top: 30px;">
                        <a href="book-service.php" class="btn btn-primary">Request Service</a>
                    </div>
                </div>
            </div>

            <!-- Services Overview -->
            <div class="services-overview">
                <h2 class="section-title">Our Networking Services</h2>
                <p style="color: #aee2ff; margin-bottom: 30px; font-size: 1.1rem;">
                    We specialize in networking and computer solutions. Describe your issue in detail for better service preparation.
                </p>
                
                <div class="services-list">
                    <!-- Service 1: Computer Diagnostic & Repair -->
                    <div class="service-item animate-fade-in-up" style="animation-delay: 0.1s;">
                        <div class="service-item-header">
                            <div class="service-icon">💻</div>
                            <h3 class="service-item-title">Computer Diagnostic & Repair</h3>
                        </div>
                        <span class="service-category">Hardware/Software</span>
                        <p class="service-description">
                            Complete computer troubleshooting including hardware diagnostics, software issues, 
                            virus removal, and system optimization.
                        </p>
                        <ul class="service-features">
                            <li>Hardware testing & diagnostics</li>
                            <li>Software troubleshooting</li>
                            <li>Virus & malware removal</li>
                            <li>System optimization</li>
                        </ul>
                        <a href="book-service.php" class="btn btn-primary" style="width: 100%; margin-top: 15px;">
                            Request This Service
                        </a>
                    </div>
                    
                    <!-- Service 2: Basic Network Configuration -->
                    <div class="service-item animate-fade-in-up" style="animation-delay: 0.2s;">
                        <div class="service-item-header">
                            <div class="service-icon">🌐</div>
                            <h3 class="service-item-title">Basic Network Configuration</h3>
                        </div>
                        <span class="service-category">Networking</span>
                        <p class="service-description">
                            Router setup, WiFi configuration, network security setup, and basic LAN troubleshooting.
                        </p>
                        <ul class="service-features">
                            <li>Router setup & configuration</li>
                            <li>WiFi optimization</li>
                            <li>Network security setup</li>
                            <li>LAN troubleshooting</li>
                        </ul>
                        <a href="book-service.php" class="btn btn-primary" style="width: 100%; margin-top: 15px;">
                            Request This Service
                        </a>
                    </div>
                    
                    <!-- Service 3: IT Consultation & Troubleshooting -->
                    <div class="service-item animate-fade-in-up" style="animation-delay: 0.3s;">
                        <div class="service-item-header">
                            <div class="service-icon">🔍</div>
                            <h3 class="service-item-title">IT Consultation & Troubleshooting</h3>
                        </div>
                        <span class="service-category">Consultation</span>
                        <p class="service-description">
                            Expert advice and troubleshooting for specific IT issues, problem diagnosis, 
                            and solution recommendations.
                        </p>
                        <ul class="service-features">
                            <li>Problem diagnosis</li>
                            <li>Solution recommendations</li>
                            <li>Technical advice</li>
                            <li>Best practices guidance</li>
                        </ul>
                        <a href="book-service.php" class="btn btn-primary" style="width: 100%; margin-top: 15px;">
                            Request Consultation
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <h2 class="section-title">Recent Service Requests</h2>
                <p style="color: #aee2ff; margin-bottom: 20px; font-size: 1.1rem;">
                    Track your networking and computer service tickets
                </p>
                
                <?php
                $recent_sql = "SELECT sr.*, s.service_name, tn.ticket_code 
                               FROM service_requests sr 
                               JOIN services s ON sr.service_id = s.service_id 
                               LEFT JOIN ticket_numbers tn ON sr.request_id = tn.request_id
                               WHERE sr.customer_id='$user_id' 
                               ORDER BY sr.request_date DESC LIMIT 8";
                $recent_result = mysqli_query($conn, $recent_sql);
                
                if(mysqli_num_rows($recent_result) > 0): ?>
                    <div class="activity-table">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Issue Type</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($recent_result)): 
                                    $status_class = str_replace('_', '-', $row['status']);
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['ticket_code'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['request_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['category'] ?? 'Networking'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $row['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="ticket-details.php?id=<?php echo $row['request_id']; ?>" 
                                               class="action-link">View Details</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Process Guide -->
            <div class="process-guide">
                <h2 class="section-title" style="border-bottom: none; margin-bottom: 10px;">How Our Service Works</h2>
                <p style="color: #aee2ff; margin-bottom: 30px;">Efficient networking support process</p>
                
                <div class="process-steps">
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Describe Issue</h3>
                        <p class="step-description">Provide detailed description of your networking or computer problem</p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Technician Review</h3>
                        <p class="step-description">Our networking expert reviews your specific issue</p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Service Preparation</h3>
                        <p class="step-description">Technician prepares tools & solutions based on your issue</p>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <h3 class="step-title">Issue Resolution</h3>
                        <p class="step-description">Expert service delivery with prepared solutions</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="action-card animate-fade-in-up" style="animation-delay: 0.1s;">
                    <span class="action-icon">📝</span>
                    <h4 class="action-title">Update Profile</h4>
                    <p class="action-description">Keep your contact information updated for better service</p>
                    <a href="profile.php" class="btn btn-secondary" style="margin-top: 10px;">Edit Profile</a>
                </div>
                
                <div class="action-card animate-fade-in-up" style="animation-delay: 0.2s;">
                    <span class="action-icon">🔧</span>
                    <h4 class="action-title">Browse Services</h4>
                    <p class="action-description">View all available networking and computer services</p>
                    <a href="../index.php#services" class="btn btn-secondary" style="margin-top: 10px;">View Services</a>
                </div>
                
                <div class="action-card animate-fade-in-up" style="animation-delay: 0.3s;">
                    <span class="action-icon">📋</span>
                    <h4 class="action-title">New Service Request</h4>
                    <p class="action-description">Submit detailed description of your technical issue</p>
                    <a href="book-service.php" class="btn btn-primary" style="margin-top: 10px;">Request Service</a>
                </div>
                
                <div class="action-card animate-fade-in-up" style="animation-delay: 0.4s;">
                    <span class="action-icon">📞</span>
                    <h4 class="action-title">Tech Support</h4>
                    <p class="action-description">Contact our networking specialists for assistance</p>
                    <a href="../index.php#contact" class="btn btn-secondary" style="margin-top: 10px;">Contact Support</a>
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
        // Smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation classes
            const cards = document.querySelectorAll('.dashboard-card, .action-card, .service-item');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${(index + 1) * 0.1}s`;
            });
            
            // Add hover effects to cards
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Update user avatar with random color if needed
            const avatar = document.querySelector('.user-avatar');
            const colors = ['#50d0e0', '#4CAF50', '#2196F3', '#9C27B0', '#FF9800'];
            if(avatar && !avatar.style.backgroundColor) {
                const randomColor = colors[Math.floor(Math.random() * colors.length)];
                avatar.style.backgroundColor = randomColor;
            }
        });
    </script>
</body>
</html>