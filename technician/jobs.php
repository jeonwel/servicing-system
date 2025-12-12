<?php
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is technician
if(!isLoggedIn() || !isTechnician()) {
    redirect('../login.php');
}

$technician_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Handle job acceptance
if(isset($_GET['accept']) && is_numeric($_GET['accept'])) {
    $request_id = sanitize($_GET['accept']);
    
    // Check if job is still available
    $check_sql = "SELECT * FROM service_requests 
                  WHERE request_id = ? 
                  AND (status = 'pending' OR status IS NULL)
                  AND (current_request_id IS NULL OR current_request_id = 0)";
    
    $check_result = executeQuery($conn, $check_sql, [$request_id], "i");
    
    if($check_result && mysqli_num_rows($check_result) > 0) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Update service request
            $update_sql = "UPDATE service_requests 
                           SET status = 'assigned', 
                               current_request_id = ? 
                           WHERE request_id = ?";
            
            if(executeUpdate($conn, $update_sql, [$technician_id, $request_id], "ii")) {
                
                // 2. Create assignment record
                $assignment_sql = "INSERT INTO assignments (technician_id, request_id, assigned_date) 
                                   VALUES (?, ?, NOW())";
                
                $assignment_id = executeInsert($conn, $assignment_sql, [$technician_id, $request_id], "ii");
                
                if($assignment_id) {
                    // 3. Update technician availability
                    $tech_update_sql = "UPDATE users SET is_available = 0 WHERE user_id = ?";
                    
                    if(executeUpdate($conn, $tech_update_sql, [$technician_id], "i")) {
                        mysqli_commit($conn);
                        $_SESSION['success'] = "Job accepted successfully! Please contact the customer to schedule the service.";
                        redirect('jobs.php');
                    }
                }
            }
            mysqli_rollback($conn);
            $error = "Error accepting job. Please try again.";
        } catch(Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Job is no longer available or has already been assigned.";
    }
}

// Handle job status update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $request_id = sanitize($_POST['request_id']);
    $new_status = sanitize($_POST['status']);
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Check if technician owns this job
    $check_sql = "SELECT status FROM service_requests 
                  WHERE request_id = ? AND current_request_id = ?";
    $check_result = executeQuery($conn, $check_sql, [$request_id, $technician_id], "ii");
    
    if($check_result && mysqli_num_rows($check_result) > 0) {
        $current_status = mysqli_fetch_assoc($check_result)['status'];
        
        // Define allowed status transitions
        $allowed_transitions = [
            'assigned' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [], // No further transitions from completed
            'cancelled' => []  // No further transitions from cancelled
        ];
        
        if(isset($allowed_transitions[$current_status]) && 
           (empty($allowed_transitions[$current_status]) || 
            in_array($new_status, $allowed_transitions[$current_status]))) {
            
            $update_sql = "UPDATE service_requests SET status = ?";
            $params = [$new_status];
            $types = "s";
            
            if($new_status == 'completed') {
                $update_sql .= ", completed_date = NOW()";
                
                // Update assignment completed date
                $assign_update_sql = "UPDATE assignments SET completed_date = NOW() 
                                      WHERE request_id = ? AND technician_id = ?";
                executeUpdate($conn, $assign_update_sql, [$request_id, $technician_id], "ii");
                
                // Make technician available again
                $tech_avail_sql = "UPDATE users SET is_available = 1 WHERE user_id = ?";
                executeUpdate($conn, $tech_avail_sql, [$technician_id], "i");
            }
            
            if($new_status == 'cancelled') {
                // Remove technician assignment
                $update_sql .= ", current_request_id = NULL";
                
                // Make technician available again
                $tech_avail_sql = "UPDATE users SET is_available = 1 WHERE user_id = ?";
                executeUpdate($conn, $tech_avail_sql, [$technician_id], "i");
            }
            
            $update_sql .= " WHERE request_id = ?";
            $params[] = $request_id;
            $types .= "i";
            
            if(executeUpdate($conn, $update_sql, $params, $types)) {
                $_SESSION['success'] = "Job status updated to " . ucfirst(str_replace('_', ' ', $new_status));
                redirect('jobs.php' . (!empty($status_filter) ? "?status=$status_filter" : ''));
            } else {
                $error = "Error updating job status.";
            }
        } else {
            $error = "Invalid status transition from " . ucfirst($current_status);
        }
    } else {
        $error = "Job not found or you don't have permission to update it.";
    }
}

// Build queries based on filters
$where_clause = "WHERE sr.current_request_id = '$technician_id'";
if(!empty($status_filter) && $status_filter != 'all') {
    $where_clause .= " AND sr.status='$status_filter'";
}
if(!empty($category_filter) && $category_filter != 'all') {
    $where_clause .= " AND sr.category='$category_filter'";
}

// Get technician's jobs
$jobs_sql = "SELECT 
                sr.request_id,
                sr.first_name,
                sr.last_name,
                sr.phone,
                sr.category,
                sr.problem_description,
                sr.status,
                sr.request_date,
                sr.preferred_date,
                sr.preferred_time,
                sr.completed_date,
                sr.address,
                sr.landmark,
                s.service_name,
                s.base_price,
                tn.ticket_code,
                a.assigned_date,
                a.completed_date as assignment_completed
             FROM service_requests sr
             JOIN services s ON sr.service_id = s.service_id
             LEFT JOIN ticket_numbers tn ON sr.request_id = tn.request_id
             LEFT JOIN assignments a ON sr.request_id = a.request_id
             $where_clause
             ORDER BY 
                 CASE sr.status
                     WHEN 'in_progress' THEN 1
                     WHEN 'assigned' THEN 2
                     WHEN 'pending' THEN 3
                     WHEN 'completed' THEN 4
                     WHEN 'cancelled' THEN 5
                     ELSE 6
                 END,
                 sr.preferred_date ASC,
                 sr.preferred_time ASC";

$jobs_result = executeQuery($conn, $jobs_sql);
$total_jobs = $jobs_result ? mysqli_num_rows($jobs_result) : 0;

// Get job stats
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
              FROM service_requests 
              WHERE current_request_id = '$technician_id'";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get available pending jobs
$pending_jobs_sql = "SELECT 
                      sr.request_id,
                      sr.first_name,
                      sr.last_name,
                      sr.category,
                      sr.problem_description,
                      sr.request_date,
                      sr.address,
                      s.service_name,
                      s.base_price,
                      tn.ticket_code
                     FROM service_requests sr
                     JOIN services s ON sr.service_id = s.service_id
                     LEFT JOIN ticket_numbers tn ON sr.request_id = tn.request_id
                     WHERE sr.status = 'pending' 
                     AND (sr.current_request_id IS NULL OR sr.current_request_id = 0)
                     ORDER BY sr.request_date ASC 
                     LIMIT 10";
$pending_jobs_result = executeQuery($conn, $pending_jobs_sql);

// Get unique categories for filter
$categories_sql = "SELECT DISTINCT category FROM service_requests 
                   WHERE current_request_id = '$technician_id' 
                   ORDER BY category";
$categories_result = mysqli_query($conn, $categories_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRAD Servicing : My Jobs</title>
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

        .tech-badge {
            display: inline-block;
            background: linear-gradient(45deg, #50d0e0, #2196F3);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            text-align: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #50d0e0;
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            display: block;
            color: #50d0e0;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #50d0e0;
            margin: 10px 0;
        }

        .stat-label {
            color: #aee2ff;
            font-size: 0.95rem;
        }

        /* Filters Section */
        .filters-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .filters-title {
            font-size: 1.2rem;
            color: #50d0e0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            color: #aee2ff;
            font-size: 0.95rem;
        }

        .filter-select {
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(80, 208, 224, 0.3);
            border-radius: 8px;
            color: #fff;
            font-family: "Science Gothic", sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #50d0e0;
            box-shadow: 0 0 0 3px rgba(80, 208, 224, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            align-items: flex-end;
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

        .btn-success {
            background: #4CAF50;
            color: white;
            font-weight: bold;
        }

        .btn-success:hover {
            background: #3d8b40;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ff9800;
            color: white;
            font-weight: bold;
        }

        .btn-warning:hover {
            background: #e68900;
            transform: translateY(-2px);
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        /* Jobs Section */
        .jobs-section {
            margin-top: 40px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 1.8rem;
            color: #50d0e0;
        }

        .job-count {
            color: #aee2ff;
            font-size: 1rem;
        }

        /* Jobs Table */
        .jobs-table-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
        }

        .jobs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .jobs-table th {
            background: rgba(80, 208, 224, 0.2);
            color: #50d0e0;
            padding: 18px 20px;
            text-align: left;
            font-weight: bold;
            font-size: 0.95rem;
        }

        .jobs-table td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            vertical-align: top;
        }

        .jobs-table tr:last-child td {
            border-bottom: none;
        }

        .jobs-table tr:hover {
            background: rgba(80, 208, 224, 0.05);
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }

        .status-pending { 
            background: rgba(255, 193, 7, 0.2); 
            color: #ffc107; 
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        .status-assigned { 
            background: rgba(33, 150, 243, 0.2); 
            color: #2196F3; 
            border: 1px solid rgba(33, 150, 243, 0.3);
        }
        .status-in_progress { 
            background: rgba(255, 152, 0, 0.2); 
            color: #ff9800; 
            border: 1px solid rgba(255, 152, 0, 0.3);
        }
        .status-completed { 
            background: rgba(76, 175, 80, 0.2); 
            color: #4CAF50; 
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        .status-cancelled { 
            background: rgba(244, 67, 54, 0.2); 
            color: #f44336; 
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        /* Category Badge */
        .category-badge {
            display: inline-block;
            background: rgba(80, 208, 224, 0.1);
            color: #50d0e0;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        /* Problem Description Preview */
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 150px;
        }

        .action-link {
            color: #50d0e0;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .action-link:hover {
            color: #aee2ff;
            text-decoration: underline;
        }

        /* Status Update Form */
        .status-form {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
        }
        
        .status-select {
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(80, 208, 224, 0.3);
            border-radius: 8px;
            color: #fff;
            font-family: "Science Gothic", sans-serif;
            font-size: 0.9rem;
            min-width: 160px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .status-select:focus {
            outline: none;
            border-color: #50d0e0;
            box-shadow: 0 0 0 3px rgba(80, 208, 224, 0.1);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .status-select option {
            background: rgba(10, 10, 42, 0.95);
            color: #fff;
            padding: 10px;
        }
        
        .status-select:hover {
            border-color: #50d0e0;
        }

        /* Available Jobs Section */
        .available-jobs-section {
            margin-top: 60px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
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

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid;
            animation: fadeInUp 0.6s ease-out;
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
        @media (max-width: 1024px) {
            .nav-menu {
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .jobs-table {
                display: block;
                overflow-x: auto;
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
            
            main {
                padding-top: 160px;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                margin-top: 15px;
            }
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .action-buttons {
                min-width: auto;
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

        /* Loading State */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Payment Status */
        .payment-status {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .payment-pending { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .payment-paid { background: rgba(76, 175, 80, 0.2); color: #4CAF50; }
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
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="jobs.php" class="active">My Jobs</a></li>
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
            <!-- Page Header -->
            <div class="page-header animate-fade-in-up">
                <h1 class="page-title">My Service Jobs</h1>
                <p class="page-subtitle">
                    Manage your assigned service jobs, update statuses, and track your work progress.
                    <span class="tech-badge">Job Management System</span>
                </p>
            </div>

            <!-- Success/Error Messages -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card animate-fade-in-up" style="animation-delay: 0.1s;">
                    <span class="stat-icon">📊</span>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Jobs</div>
                </div>
                
                <div class="stat-card animate-fade-in-up" style="animation-delay: 0.2s;">
                    <span class="stat-icon">📋</span>
                    <div class="stat-number"><?php echo $stats['assigned']; ?></div>
                    <div class="stat-label">Assigned</div>
                </div>
                
                <div class="stat-card animate-fade-in-up" style="animation-delay: 0.3s;">
                    <span class="stat-icon">🔧</span>
                    <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                
                <div class="stat-card animate-fade-in-up" style="animation-delay: 0.4s;">
                    <span class="stat-icon">✅</span>
                    <div class="stat-number"><?php echo $stats['completed']; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                
                <div class="stat-card animate-fade-in-up" style="animation-delay: 0.5s;">
                    <span class="stat-icon">💰</span>
                    <div class="stat-number">0</div>
                    <div class="stat-label">Pending Payment</div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section animate-fade-in-up" style="animation-delay: 0.3s;">
                <h3 class="filters-title">
                    <span>🔍</span> Filter Jobs
                </h3>
                <form method="GET" action="" id="filterForm">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label class="filter-label">Status</label>
                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="all" <?php echo empty($status_filter) || $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="assigned" <?php echo $status_filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Category</label>
                            <select name="category" class="filter-select" onchange="this.form.submit()">
                                <option value="all" <?php echo empty($category_filter) || $category_filter == 'all' ? 'selected' : ''; ?>>All Categories</option>
                                <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $cat['category']; ?>" 
                                        <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['category']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <a href="jobs.php" class="btn btn-secondary">
                                <span>🔄</span> Clear Filters
                            </a>
                            <a href="dashboard.php" class="btn btn-primary">
                                <span>📊</span> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- My Jobs Section -->
            <div class="jobs-section">
                <div class="section-header animate-fade-in-up" style="animation-delay: 0.4s;">
                    <div>
                        <h2 class="section-title">My Assigned Jobs</h2>
                        <p class="job-count">Showing <?php echo $total_jobs; ?> job(s)</p>
                    </div>
                </div>

                <?php if($total_jobs > 0): ?>
                    <div class="jobs-table-container animate-fade-in-up" style="animation-delay: 0.5s;">
                        <table class="jobs-table">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Category</th>
                                    <th>Issue</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $delay = 0.5;
                                while($job = mysqli_fetch_assoc($jobs_result)): 
                                    $delay += 0.05;
                                    $status_class = str_replace(' ', '_', strtolower($job['status']));
                                ?>
                                    <tr style="animation-delay: <?php echo $delay; ?>s;" class="animate-fade-in-up">
                                        <td>
                                            <strong><?php echo $job['ticket_code'] ?? 'N/A'; ?></strong><br>
                                            <small style="color: #aaa; font-size: 0.85rem;">
                                                <?php echo date('M d', strtotime($job['request_date'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?></strong><br>
                                            <small style="color: #50d0e0; font-size: 0.85rem;">
                                                📞 <?php echo htmlspecialchars($job['phone']); ?>
                                            </small><br>
                                            <small style="color: #aaa; font-size: 0.8rem;">
                                                📍 <?php echo htmlspecialchars(substr($job['address'], 0, 30)); ?>...
                                            </small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($job['service_name']); ?></strong><br>
                                            <small style="color: #4CAF50; font-size: 0.9rem;">
                                                ₱<?php echo number_format($job['base_price'], 2); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="category-badge"><?php echo htmlspecialchars($job['category']); ?></span>
                                        </td>
                                        <td>
                                            <div class="problem-preview" title="<?php echo htmlspecialchars($job['problem_description']); ?>">
                                                <?php echo substr($job['problem_description'], 0, 100); ?>
                                                <?php if(strlen($job['problem_description']) > 100): ?>
                                                    ...
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($job['preferred_date']): ?>
                                                <?php echo date('M d, Y', strtotime($job['preferred_date'])); ?>
                                                <?php if($job['preferred_time']): ?>
                                                    <br><small style="color: #aaa;"><?php echo date('h:i A', strtotime($job['preferred_time'])); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #ff9800;">Not scheduled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="job-details.php?id=<?php echo $job['request_id']; ?>" class="action-link">
                                                    <span>👁️</span> View Details
                                                </a>
                                                
                                                <a href="tel:<?php echo $job['phone']; ?>" class="action-link">
                                                    <span>📞</span> Call Customer
                                                </a>

                                                <?php if($job['status'] == 'assigned'): ?>
                                                    <form method="POST" class="status-form">
                                                        <input type="hidden" name="request_id" value="<?php echo $job['request_id']; ?>">
                                                        <select name="status" class="status-select" onchange="if(this.value) this.form.submit()">
                                                            <option value="">Update Status</option>
                                                            <option value="in_progress">Start Service</option>
                                                            <option value="cancelled">Cancel Job</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                    </form>
                                                <?php elseif($job['status'] == 'in_progress'): ?>
                                                    <form method="POST" class="status-form">
                                                        <input type="hidden" name="request_id" value="<?php echo $job['request_id']; ?>">
                                                        <select name="status" class="status-select" onchange="if(this.value) this.form.submit()">
                                                            <option value="">Update Status</option>
                                                            <option value="completed">Mark as Completed</option>
                                                            <option value="cancelled">Cancel Service</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                    </form>
                                                <?php elseif($job['status'] == 'completed'): ?>
                                                    <span class="status-badge status-completed" style="font-size: 0.8rem; padding: 4px 10px;">
                                                        ✅ Completed
                                                    </span>
                                                    <a href="payments.php?job=<?php echo $job['request_id']; ?>" class="action-link">
                                                        <span>💰</span> Record Payment
                                                    </a>
                                                <?php elseif($job['status'] == 'cancelled'): ?>
                                                    <span class="status-badge status-cancelled" style="font-size: 0.8rem; padding: 4px 10px;">
                                                        ❌ Cancelled
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state animate-fade-in-up" style="animation-delay: 0.5s;">
                        <div class="empty-state-icon">📭</div>
                        <h3 class="empty-state-title">No Jobs Assigned</h3>
                        <p class="empty-state-description">
                            <?php if(!empty($status_filter) || !empty($category_filter)): ?>
                                No jobs match your current filters. Try changing your filter settings or 
                                <a href="jobs.php" style="color: #50d0e0;">view all jobs</a>.
                            <?php else: ?>
                                You don't have any assigned jobs yet. Check available jobs below or wait for new assignments.
                            <?php endif; ?>
                        </p>
                        <a href="#available-jobs" class="btn btn-primary">
                            <span>🔍</span> View Available Jobs
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Available Jobs Section -->
            <div class="available-jobs-section animate-fade-in-up" style="animation-delay: 0.6s;" id="available-jobs">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">Available Jobs</h2>
                        <p class="job-count">Pending service requests waiting for technician assignment</p>
                    </div>
                </div>

                <?php if($pending_jobs_result && mysqli_num_rows($pending_jobs_result) > 0): ?>
                    <div class="jobs-table-container" style="margin-top: 20px;">
                        <table class="jobs-table">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Category</th>
                                    <th>Issue</th>
                                    <th>Location</th>
                                    <th>Request Date</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($job = mysqli_fetch_assoc($pending_jobs_result)): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $job['ticket_code'] ?? 'N/A'; ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($job['service_name']); ?></td>
                                        <td>
                                            <span class="category-badge"><?php echo htmlspecialchars($job['category']); ?></span>
                                        </td>
                                        <td>
                                            <div class="problem-preview" title="<?php echo htmlspecialchars($job['problem_description']); ?>">
                                                <?php echo substr($job['problem_description'], 0, 80); ?>
                                                <?php if(strlen($job['problem_description']) > 80): ?>
                                                    ...
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small style="color: #aaa; font-size: 0.85rem;">
                                                <?php echo htmlspecialchars(substr($job['address'], 0, 25)); ?>...
                                            </small>
                                        </td>
                                        <td><?php echo date('M d', strtotime($job['request_date'])); ?></td>
                                        <td>
                                            <strong style="color: #4CAF50;">
                                                ₱<?php echo number_format($job['base_price'], 2); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <a href="?accept=<?php echo $job['request_id']; ?>" 
                                               class="btn btn-success btn-small"
                                               onclick="return confirm('Are you sure you want to accept this job? You will be responsible for contacting the customer and scheduling the service.')">
                                               <span>✅</span> Accept Job
                                            </a>
                                            <a href="job-details.php?id=<?php echo $job['request_id']; ?>" 
                                               class="btn btn-secondary btn-small">
                                               <span>👁️</span> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="dashboard.php" class="btn btn-primary">
                            <span>🔄</span> Check Dashboard for More Jobs
                        </a>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-state" style="padding: 40px 20px;">
                        <div class="empty-state-icon">🎉</div>
                        <h3 class="empty-state-title">No Available Jobs</h3>
                        <p class="empty-state-description">
                            All pending service requests have been assigned to technicians. 
                            Check back later for new service requests or contact admin for assignments.
                        </p>
                    </div>
                <?php endif; ?>
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
            // Confirm job acceptance
            const acceptLinks = document.querySelectorAll('a[href*="accept="]');
            acceptLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if(!confirm('Are you sure you want to accept this job? You will be responsible for contacting the customer and scheduling the service.')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Confirm status changes
            const statusSelects = document.querySelectorAll('.status-select');
            statusSelects.forEach(select => {
                select.addEventListener('change', function() {
                    if(this.value === 'cancelled') {
                        if(!confirm('Are you sure you want to cancel this job? This action cannot be undone.')) {
                            this.value = '';
                            return false;
                        }
                    }
                    if(this.value === 'completed') {
                        if(!confirm('Mark this job as completed? Make sure you have completed all service requirements and recorded any payments.')) {
                            this.value = '';
                            return false;
                        }
                    }
                    if(this.value) {
                        this.form.submit();
                    }
                });
            });
            
            // Add loading state to forms
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if(submitBtn) {
                        submitBtn.innerHTML = '<span>⏳</span> Processing...';
                        submitBtn.disabled = true;
                    }
                });
            });
            
            // Highlight current filter
            const currentUrl = new URL(window.location.href);
            const statusFilter = currentUrl.searchParams.get('status');
            const categoryFilter = currentUrl.searchParams.get('category');
            
            if(statusFilter || categoryFilter) {
                const clearBtn = document.querySelector('a[href="jobs.php"]');
                if(clearBtn) {
                    clearBtn.style.background = 'rgba(80, 208, 224, 0.1)';
                    clearBtn.style.borderColor = '#50d0e0';
                }
            }
            
            // Auto-refresh available jobs every 60 seconds
            setTimeout(function() {
                if(document.querySelector('#available-jobs')) {
                    location.reload();
                }
            }, 60000);
            
            // Add hover effect to table rows
            const tableRows = document.querySelectorAll('.jobs-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                    this.style.transition = 'transform 0.3s ease';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>