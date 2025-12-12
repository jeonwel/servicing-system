<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is technician
if(!isLoggedIn() || !isTechnician()) {
    redirect('../login.php');
}

$technician_id = $_SESSION['user_id'];

// Get technician info
$tech_sql = "SELECT * FROM users WHERE user_id = ?";
$tech_result = executeQuery($conn, $tech_sql, [$technician_id], "i");
$technician = mysqli_fetch_assoc($tech_result);

// Get job counts
$pending_sql = "SELECT COUNT(*) as count FROM service_requests 
                WHERE status = 'pending'";
$pending_result = executeQuery($conn, $pending_sql);
$pending_count = mysqli_fetch_assoc($pending_result)['count'];

$assigned_sql = "SELECT COUNT(DISTINCT a.request_id) as count 
                 FROM assignments a 
                 JOIN service_requests sr ON a.request_id = sr.request_id
                 WHERE a.technician_id = ? AND sr.status IN ('assigned', 'in_progress')";
$assigned_result = executeQuery($conn, $assigned_sql, [$technician_id], "i");
$assigned_count = mysqli_fetch_assoc($assigned_result)['count'];

$completed_sql = "SELECT COUNT(DISTINCT a.request_id) as count 
                  FROM assignments a 
                  JOIN service_requests sr ON a.request_id = sr.request_id
                  WHERE a.technician_id = ? AND sr.status = 'completed'";
$completed_result = executeQuery($conn, $completed_sql, [$technician_id], "i");
$completed_count = mysqli_fetch_assoc($completed_result)['count'];

$today = date('Y-m-d');
$today_jobs_sql = "SELECT COUNT(DISTINCT a.request_id) as count 
                   FROM assignments a 
                   JOIN service_requests sr ON a.request_id = sr.request_id
                   WHERE a.technician_id = ? AND DATE(sr.preferred_date) = ? 
                   AND sr.status IN ('assigned', 'in_progress')";
$today_jobs_result = executeQuery($conn, $today_jobs_sql, [$technician_id, $today], "is");
$today_jobs_count = mysqli_fetch_assoc($today_jobs_result)['count'];

// Get pending payments
$pending_payments_sql = "SELECT COUNT(*) as count FROM payments 
                         WHERE technician_id = ? AND remittance_status = 'not_remitted'";
$pending_payments_result = executeQuery($conn, $pending_payments_sql, [$technician_id], "i");
$pending_payments_count = mysqli_fetch_assoc($pending_payments_result)['count'];

// Get today's schedule
$today_schedule_sql = "SELECT 
                        sr.request_id,
                        sr.first_name,
                        sr.last_name,
                        sr.service_id,
                        sr.preferred_time,
                        sr.address,
                        s.service_name
                       FROM assignments a
                       JOIN service_requests sr ON a.request_id = sr.request_id
                       JOIN services s ON sr.service_id = s.service_id
                       WHERE a.technician_id = ? 
                       AND DATE(sr.preferred_date) = ?
                       AND sr.status IN ('assigned', 'in_progress')
                       ORDER BY sr.preferred_time ASC";
$today_schedule_result = executeQuery($conn, $today_schedule_sql, [$technician_id, $today], "is");

// Get pending jobs for acceptance
$pending_jobs_sql = "SELECT 
                      sr.request_id,
                      sr.first_name,
                      sr.last_name,
                      sr.category,
                      sr.problem_description,
                      sr.preferred_date,
                      sr.request_date,
                      s.service_name,
                      s.base_price
                     FROM service_requests sr
                     JOIN services s ON sr.service_id = s.service_id
                     LEFT JOIN assignments a ON sr.request_id = a.request_id
                     WHERE sr.status = 'pending' 
                     AND a.request_id IS NULL
                     ORDER BY sr.request_date ASC 
                     LIMIT 5";
$pending_jobs_result = executeQuery($conn, $pending_jobs_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRAD Servicing : Technician Dashboard</title>
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

        /* Main Content */
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

        .tech-badge {
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
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
            margin-bottom: 15px;
            display: block;
        }

        .card-title {
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: 10px;
        }

        .card-count {
            font-size: 2.5rem;
            font-weight: bold;
            color: #50d0e0;
            margin: 10px 0;
        }

        .card-action {
            margin-top: 15px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-family: "Science Gothic", sans-serif;
            font-size: 0.9rem;
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

        .btn-success {
            background: #4CAF50;
            color: white;
            font-weight: bold;
        }

        .btn-success:hover {
            background: #3d8b40;
            transform: translateY(-2px);
        }

        /* Today's Schedule */
        .schedule-section {
            margin: 50px 0;
        }

        .section-title {
            font-size: 1.8rem;
            color: #50d0e0;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(80, 208, 224, 0.3);
        }

        .schedule-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .schedule-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .schedule-card:hover {
            border-color: #50d0e0;
            transform: translateY(-3px);
        }

        .schedule-time {
            display: inline-block;
            background: rgba(80, 208, 224, 0.1);
            color: #50d0e0;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .schedule-customer {
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: 10px;
        }

        .schedule-service {
            color: #aee2ff;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .schedule-address {
            color: #aee2ff;
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        /* Pending Jobs */
        .pending-jobs-section {
            margin: 50px 0;
        }

        .jobs-table {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
        }

        .jobs-table th {
            background: rgba(80, 208, 224, 0.2);
            color: #50d0e0;
            padding: 15px 20px;
            text-align: left;
            font-weight: bold;
        }

        .jobs-table td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .jobs-table tr:last-child td {
            border-bottom: none;
        }

        .jobs-table tr:hover {
            background: rgba(80, 208, 224, 0.05);
        }

        .problem-preview {
            color: #aee2ff;
            font-size: 0.9rem;
            line-height: 1.5;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
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
            font-size: 1.1rem;
            color: #fff;
            margin-bottom: 10px;
        }

        .action-description {
            color: #aee2ff;
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.5;
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

        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
        }

        .status-pending { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .status-assigned { background: rgba(33, 150, 243, 0.2); color: #2196F3; }
        .status-in_progress { background: rgba(255, 152, 0, 0.2); color: #ff9800; }
        .status-completed { background: rgba(76, 175, 80, 0.2); color: #4CAF50; }

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
            
            .schedule-list {
                grid-template-columns: 1fr;
            }
            
            .jobs-table {
                display: block;
                overflow-x: auto;
            }
            
            .welcome-message {
                font-size: 1.8rem;
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
                        <li><a href="dashboard.php" class="active">Dashboard</a></li>
                        <li><a href="jobs.php">My Jobs</a></li>
                        <li><a href="schedule.php">Schedule</a></li>
                        <li><a href="payments.php">Payments</a></li>
                        <li><a href="remittances.php">Remittances</a></li>
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
                <h1 class="welcome-message">Welcome, <?php echo $_SESSION['full_name']; ?>! 👨‍🔧</h1>
                <p class="welcome-subtitle">
                    Manage your service jobs, schedule appointments, record payments, and track your remittances.
                    <span class="tech-badge">Field Technician Dashboard</span>
                </p>
            </div>

            <!-- Dashboard Stats Cards -->
            <div class="dashboard-grid">
                <div class="dashboard-card animate-fade-in-up" style="animation-delay: 0.1s;">
                    <span class="card-icon">🔄</span>
                    <h3 class="card-title">Pending Jobs</h3>
                    <div class="card-count"><?php echo $pending_count; ?></div>
                    <p>Available jobs to accept</p>
                    <div class="card-action">
                        <a href="jobs.php?filter=pending" class="btn btn-primary">View Jobs</a>
                    </div>
                </div>
                
                <div class="dashboard-card animate-fade-in-up" style="animation-delay: 0.2s;">
                    <span class="card-icon">📋</span>
                    <h3 class="card-title">My Assigned Jobs</h3>
                    <div class="card-count"><?php echo $assigned_count; ?></div>
                    <p>Jobs in your queue</p>
                    <div class="card-action">
                        <a href="jobs.php" class="btn btn-primary">View Assigned</a>
                    </div>
                </div>
                
                <div class="dashboard-card animate-fade-in-up" style="animation-delay: 0.3s;">
                    <span class="card-icon">✅</span>
                    <h3 class="card-title">Completed Jobs</h3>
                    <div class="card-count"><?php echo $completed_count; ?></div>
                    <p>Jobs completed this month</p>
                    <div class="card-action">
                        <a href="jobs.php?status=completed" class="btn btn-secondary">View History</a>
                    </div>
                </div>
                
                <div class="dashboard-card animate-fade-in-up" style="animation-delay: 0.4s;">
                    <span class="card-icon">💰</span>
                    <h3 class="card-title">Pending Remittance</h3>
                    <div class="card-count"><?php echo $pending_payments_count; ?></div>
                    <p>Payments to remit</p>
                    <div class="card-action">
                        <a href="remittances.php" class="btn btn-primary">View Payments</a>
                    </div>
                </div>
                
                <div class="dashboard-card animate-fade-in-up" style="animation-delay: 0.5s;">
                    <span class="card-icon">📅</span>
                    <h3 class="card-title">Today's Jobs</h3>
                    <div class="card-count"><?php echo $today_jobs_count; ?></div>
                    <p>Scheduled for today</p>
                    <div class="card-action">
                        <a href="schedule.php" class="btn btn-primary">View Schedule</a>
                    </div>
                </div>
                
                <div class="dashboard-card animate-fade-in-up" style="animation-delay: 0.6s;">
                    <span class="card-icon">🔧</span>
                    <h3 class="card-title">Start New Job</h3>
                    <p>Accept and begin service</p>
                    <div class="card-action" style="margin-top: 20px;">
                        <a href="jobs.php?filter=pending" class="btn btn-success">Accept Job</a>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="schedule-section">
                <h2 class="section-title">Today's Schedule (<?php echo date('F j, Y'); ?>)</h2>
                
                <?php if($today_schedule_result && mysqli_num_rows($today_schedule_result) > 0): ?>
                    <div class="schedule-list">
                        <?php while($schedule = mysqli_fetch_assoc($today_schedule_result)): ?>
                            <div class="schedule-card animate-fade-in-up">
                                <div class="schedule-time">
                                    <?php echo $schedule['preferred_time'] ? date('h:i A', strtotime($schedule['preferred_time'])) : 'Time not set'; ?>
                                </div>
                                <h3 class="schedule-customer">
                                    <?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?>
                                </h3>
                                <p class="schedule-service">
                                    <?php echo htmlspecialchars($schedule['service_name']); ?>
                                </p>
                                <p class="schedule-address">
                                    📍 <?php echo htmlspecialchars($schedule['address']); ?>
                                </p>
                                <div style="display: flex; gap: 10px; margin-top: 15px;">
                                    <a href="job-details.php?id=<?php echo $schedule['request_id']; ?>" 
                                       class="btn btn-primary">View Details</a>
                                    <a href="tel:<?php echo $schedule['phone'] ?? '#'; ?>" 
                                       class="btn btn-secondary">Call Customer</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📅</div>
                        <h3 class="empty-state-title">No Jobs Scheduled Today</h3>
                        <p class="empty-state-description">
                            You don't have any service appointments scheduled for today. 
                            Check pending jobs or update your schedule.
                        </p>
                        <div style="display: flex; gap: 15px; justify-content: center; margin-top: 20px;">
                            <a href="jobs.php?filter=pending" class="btn btn-primary">Accept Pending Jobs</a>
                            <a href="schedule.php" class="btn btn-secondary">View Full Schedule</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pending Jobs Available -->
            <div class="pending-jobs-section">
                <h2 class="section-title">Pending Jobs Available</h2>
                <p style="color: #aee2ff; margin-bottom: 20px; font-size: 1.1rem;">
                    Available service requests waiting for technician assignment
                </p>
                
                <?php if($pending_jobs_result && mysqli_num_rows($pending_jobs_result) > 0): ?>
                    <div class="jobs-table">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Category</th>
                                    <th>Issue Description</th>
                                    <th>Request Date</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($job = mysqli_fetch_assoc($pending_jobs_result)): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($job['service_name']); ?></td>
                                        <td>
                                            <span class="status-badge status-pending"><?php echo htmlspecialchars($job['category']); ?></span>
                                        </td>
                                        <td>
                                            <div class="problem-preview" title="<?php echo htmlspecialchars($job['problem_description']); ?>">
                                                <?php echo substr($job['problem_description'], 0, 100); ?>
                                                <?php if(strlen($job['problem_description']) > 100): ?>
                                                    ...
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d', strtotime($job['request_date'])); ?></td>
                                        <td>
                                            <strong style="color: #4CAF50;">
                                                ₱<?php echo number_format($job['base_price'], 2); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <form method="POST" action="accept-job.php" style="display: inline;">
                                                <input type="hidden" name="request_id" value="<?php echo $job['request_id']; ?>">
                                                <button type="submit" class="btn btn-success">Accept Job</button>
                                            </form>
                                            <a href="job-details.php?id=<?php echo $job['request_id']; ?>" 
                                               class="btn btn-secondary">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="jobs.php?filter=pending" class="btn btn-primary">View All Pending Jobs</a>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🎉</div>
                        <h3 class="empty-state-title">No Pending Jobs Available</h3>
                        <p class="empty-state-description">
                            All current service requests have been assigned to technicians. 
                            Check back later for new service requests.
                        </p>
                        <div style="display: flex; gap: 15px; justify-content: center; margin-top: 20px;">
                            <a href="jobs.php" class="btn btn-primary">View My Assigned Jobs</a>
                            <a href="schedule.php" class="btn btn-secondary">View My Schedule</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="action-card animate-fade-in-up" style="animation-delay: 0.1s;">
                    <span class="action-icon">📞</span>
                    <h4 class="action-title">Contact Customer</h4>
                    <p class="action-description">Call customers to confirm appointments or discuss service details</p>
                    <a href="jobs.php" class="btn btn-secondary" style="margin-top: 10px;">View Customer Contacts</a>
                </div>
                
                <div class="action-card animate-fade-in-up" style="animation-delay: 0.2s;">
                    <span class="action-icon">💰</span>
                    <h4 class="action-title">Record Payment</h4>
                    <p class="action-description">Record cash payments received from customers after service completion</p>
                    <a href="payments.php" class="btn btn-primary" style="margin-top: 10px;">Record Payment</a>
                </div>
                
                <div class="action-card animate-fade-in-up" style="animation-delay: 0.3s;">
                    <span class="action-icon">🏦</span>
                    <h4 class="action-title">Remit to Admin</h4>
                    <p class="action-description">Submit collected payments to administration for processing</p>
                    <a href="remittances.php" class="btn btn-secondary" style="margin-top: 10px;">Remit Payments</a>
                </div>
                
                <div class="action-card animate-fade-in-up" style="animation-delay: 0.4s;">
                    <span class="action-icon">📋</span>
                    <h4 class="action-title">Update Schedule</h4>
                    <p class="action-description">Update service dates and times based on customer availability</p>
                    <a href="schedule.php" class="btn btn-primary" style="margin-top: 10px;">Update Schedule</a>
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
                    <h3>Technician Menu</h3>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="jobs.php">My Jobs</a>
                    <a href="schedule.php">Schedule</a>
                    <a href="payments.php">Payments</a>
                    <a href="remittances.php">Remittances</a>
                    <a href="profile.php">Profile</a>
                </div>
                
                <div class="footer-section">
                    <h3>Technical Services</h3>
                    <a href="#">Computer Diagnostic & Repair</a>
                    <a href="#">Basic Network Configuration</a>
                    <a href="#">IT Consultation</a>
                </div>
                
                <div class="footer-section">
                    <h3>Technician Support</h3>
                    <a href="mailto:techsupport@dradservicing.com">Email Support</a>
                    <a href="tel:+63212345678">Phone Support</a>
                    <a href="#">Service Guidelines</a>
                    <a href="#">Payment Policies</a>
                    <a href="#">Remittance Procedures</a>
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
            // Auto-refresh dashboard every 30 seconds for new jobs
            setTimeout(function() {
                window.location.reload();
            }, 30000);
            
            // Add hover effects to cards
            const cards = document.querySelectorAll('.dashboard-card, .action-card, .schedule-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Update technician avatar with random color if needed
            const avatar = document.querySelector('.user-avatar');
            const colors = ['#50d0e0', '#4CAF50', '#2196F3', '#9C27B0', '#FF9800'];
            if(avatar && !avatar.style.backgroundColor) {
                const randomColor = colors[Math.floor(Math.random() * colors.length)];
                avatar.style.backgroundColor = randomColor;
            }
            
            // Add confirmation for accepting jobs
            const acceptForms = document.querySelectorAll('form[action="accept-job.php"]');
            acceptForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if(!confirm('Are you sure you want to accept this job? You will be responsible for contacting the customer and scheduling the service.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>