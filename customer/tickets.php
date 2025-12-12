<?php
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is customer
if(!isLoggedIn() || !isCustomer()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Build query with filters
$where_clause = "WHERE sr.customer_id='$user_id'";
if(!empty($status_filter) && $status_filter != 'all') {
    $where_clause .= " AND sr.status='$status_filter'";
}
if(!empty($category_filter) && $category_filter != 'all') {
    $where_clause .= " AND sr.category='$category_filter'";
}

// Handle ticket deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $request_id = sanitize($_GET['delete']);
    
    // Check if ticket belongs to user and is pending
    $check_sql = "SELECT status FROM service_requests WHERE request_id='$request_id' AND customer_id='$user_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if(mysqli_num_rows($check_result) > 0) {
        $ticket = mysqli_fetch_assoc($check_result);
        
        if($ticket['status'] == 'pending') {
            // Delete the ticket
            $delete_sql = "DELETE FROM service_requests WHERE request_id='$request_id'";
            if(mysqli_query($conn, $delete_sql)) {
                $_SESSION['success'] = "Ticket deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting ticket: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error'] = "Cannot delete ticket that is already in progress!";
        }
    } else {
        $_SESSION['error'] = "Ticket not found or you don't have permission!";
    }
    
    // Redirect to clear GET parameters
    redirect('tickets.php');
}

// Handle status update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $request_id = sanitize($_POST['request_id']);
    $new_status = sanitize($_POST['status']);
    
    if(empty($new_status)) {
        $_SESSION['error'] = "Please select a status option!";
        redirect('tickets.php' . (!empty($status_filter) ? "?status=$status_filter" : ''));
    }
    
    // Check if ticket belongs to user
    $check_sql = "SELECT status FROM service_requests WHERE request_id='$request_id' AND customer_id='$user_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if(mysqli_num_rows($check_result) > 0) {
        $current_status = mysqli_fetch_assoc($check_result)['status'];
        
        // Define allowed status transitions for customers
        $allowed_transitions = [
            'pending' => ['cancelled'],           // Pending can only be cancelled
            'assigned' => ['cancelled'],          // Assigned can only be cancelled
            'in_progress' => ['completed', 'cancelled'] // In progress can be completed or cancelled
        ];
        
        // Check if this transition is allowed
        if(isset($allowed_transitions[$current_status]) && 
           in_array($new_status, $allowed_transitions[$current_status])) {
            
            $update_sql = "UPDATE service_requests SET status='$new_status'";
            
            // Add completed date if marking as completed
            if($new_status == 'completed') {
                $update_sql .= ", completed_date=NOW()";
                
                // Also update payment status to pending (since service is done, payment expected)
                $update_sql .= ", payment_status='pending'";
            }
            
            $update_sql .= " WHERE request_id='$request_id'";
            
            if(mysqli_query($conn, $update_sql)) {
                $status_message = ucfirst(str_replace('_', ' ', $new_status));
                $_SESSION['success'] = "Ticket status updated to $status_message!";
                
                // If marked as completed, add payment reminder
                if($new_status == 'completed') {
                    $_SESSION['success'] .= " Please pay the technician in person.";
                }
            } else {
                $_SESSION['error'] = "Error updating status: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error'] = "Invalid status transition! You cannot change from " . 
                                ucfirst($current_status) . " to " . ucfirst($new_status);
        }
    } else {
        $_SESSION['error'] = "Ticket not found or you don't have permission!";
    }
    
    redirect('tickets.php' . (!empty($status_filter) ? "?status=$status_filter" : ''));
}

// Get all tickets for this customer
$sql = "SELECT 
            sr.request_id,
            sr.service_id,
            sr.first_name,
            sr.last_name,
            sr.category,
            sr.problem_description,
            sr.status,
            sr.request_date,
            sr.preferred_date,
            sr.preferred_time,
            sr.completed_date,
            s.service_name,
            tn.ticket_code
        FROM service_requests sr
        JOIN services s ON sr.service_id = s.service_id
        LEFT JOIN ticket_numbers tn ON sr.request_id = tn.request_id
        $where_clause
        ORDER BY sr.request_date DESC";

$result = mysqli_query($conn, $sql);
$total_tickets = mysqli_num_rows($result);

// Get stats for filter badges
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('pending', 'assigned', 'in_progress') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
              FROM service_requests 
              WHERE customer_id='$user_id'";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get unique categories for filter
$categories_sql = "SELECT DISTINCT category FROM service_requests WHERE customer_id='$user_id' ORDER BY category";
$categories_result = mysqli_query($conn, $categories_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRAD Servicing : My Service Tickets</title>
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

        .network-badge {
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

        .btn-small {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        /* Tickets Table */
        .tickets-section {
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

        .ticket-count {
            color: #aee2ff;
            font-size: 1rem;
        }

        .tickets-table-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
        }

        .tickets-table {
            width: 100%;
            border-collapse: collapse;
        }

        .tickets-table th {
            background: rgba(80, 208, 224, 0.2);
            color: #50d0e0;
            padding: 18px 20px;
            text-align: left;
            font-weight: bold;
            font-size: 0.95rem;
        }

        .tickets-table td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            vertical-align: top;
        }

        .tickets-table tr:last-child td {
            border-bottom: none;
        }

        .tickets-table tr:hover {
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

        /* Status Update Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
        }

        .badge.success {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .badge.error {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .badge.warning {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .badge.info {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
            border: 1px solid rgba(33, 150, 243, 0.3);
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

        .action-link.delete {
            color: #ff6b6b;
        }

        .action-link.delete:hover {
            color: #ff5252;
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
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2350d0e0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 15px;
        padding-right: 35px;
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
    
    /* Status Update Button */
    .status-update-btn {
        padding: 8px 15px;
        background: rgba(80, 208, 224, 0.2);
        color: #50d0e0;
        border: 1px solid #50d0e0;
        border-radius: 8px;
        cursor: pointer;
        font-family: "Science Gothic", sans-serif;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    
    .status-update-btn:hover {
        background: rgba(80, 208, 224, 0.3);
        transform: translateY(-1px);
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
            
            .tickets-table {
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
                        <li><a href="tickets.php" class="active">My Tickets</a></li>
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
                <h1 class="page-title">My Service Tickets</h1>
                <p class="page-subtitle">
                    Track all your networking and computer service requests in one place.
                    <span class="network-badge">Technical Support History</span>
                </p>
            </div>

            <!-- Success/Error Messages -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card animate-fade-in-up" style="animation-delay: 0.1s;">
                    <span class="stat-icon">📊</span>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Tickets</div>
                </div>
                
                <div class="stat-card animate-fade-in-up" style="animation-delay: 0.2s;">
                    <span class="stat-icon">🔄</span>
                    <div class="stat-number"><?php echo $stats['active']; ?></div>
                    <div class="stat-label">Active Issues</div>
                </div>
                
                <div class="stat-card animate-fade-in-up" style="animation-delay: 0.3s;">
                    <span class="stat-icon">✅</span>
                    <div class="stat-number"><?php echo $stats['completed']; ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
                
                <div class="stat-card animate-fade-in-up" style="animation-delay: 0.4s;">
                    <span class="stat-icon">❌</span>
                    <div class="stat-number"><?php echo $stats['cancelled']; ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section animate-fade-in-up" style="animation-delay: 0.3s;">
                <h3 class="filters-title">
                    <span>🔍</span> Filter Tickets
                </h3>
                <form method="GET" action="" id="filterForm">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label class="filter-label">Status</label>
                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="all" <?php echo empty($status_filter) || $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
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
                            <a href="tickets.php" class="btn btn-secondary">
                                <span>🔄</span> Clear Filters
                            </a>
                            <a href="book-service.php" class="btn btn-primary">
                                <span>➕</span> New Request
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tickets Section -->
            <div class="tickets-section">
                <div class="section-header animate-fade-in-up" style="animation-delay: 0.4s;">
                    <div>
                        <h2 class="section-title">Service Tickets</h2>
                        <p class="ticket-count">Showing <?php echo $total_tickets; ?> ticket(s)</p>
                    </div>
                </div>

                <?php if($total_tickets > 0): ?>
                    <div class="tickets-table-container animate-fade-in-up" style="animation-delay: 0.5s;">
                        <table class="tickets-table">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Service</th>
                                    <th>Category</th>
                                    <th>Issue Description</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $delay = 0.5;
                                while($ticket = mysqli_fetch_assoc($result)): 
                                    $delay += 0.05;
                                    $status_class = str_replace(' ', '_', strtolower($ticket['status']));
                                ?>
                                    <tr style="animation-delay: <?php echo $delay; ?>s;" class="animate-fade-in-up">
                                        <td>
                                            <strong><?php echo $ticket['ticket_code'] ?? 'N/A'; ?></strong>
                                        </td>
                                        <td>
                                            <strong><?php echo $ticket['service_name']; ?></strong><br>
                                            <small style="color: #aaa; font-size: 0.85rem;">
                                                <?php echo date('M d, Y', strtotime($ticket['request_date'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="category-badge"><?php echo $ticket['category']; ?></span>
                                        </td>
                                        <td>
                                            <div class="problem-preview" title="<?php echo htmlspecialchars($ticket['problem_description']); ?>">
                                                <?php echo substr($ticket['problem_description'], 0, 100); ?>
                                                <?php if(strlen($ticket['problem_description']) > 100): ?>
                                                    ...
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($ticket['preferred_date']): ?>
                                                <?php echo date('M d', strtotime($ticket['preferred_date'])); ?>
                                                <?php if($ticket['preferred_time']): ?>
                                                    <br><small style="color: #aaa;"><?php echo date('h:i A', strtotime($ticket['preferred_time'])); ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Not specified
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="ticket-details.php?id=<?php echo $ticket['request_id']; ?>" class="action-link">
                                                    <span>👁️</span> View Details
                                                </a>

                                                <?php 
                                                // Show status update options based on current status
                                                $current_status = $ticket['status'];

                                                if($current_status == 'pending'): 
                                                    // Pending tickets can only be cancelled (not completed yet)
                                                    ?>
                                                    <form method="POST" class="status-form">
                                                        <input type="hidden" name="request_id" value="<?php echo $ticket['request_id']; ?>">
                                                        <select name="status" class="status-select" onchange="if(this.value) this.form.submit()">
                                                            <option value="">Update Status</option>
                                                            <option value="cancelled">Cancel Ticket</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                    </form>
                                                <?php elseif($current_status == 'assigned'): 
                                                    // Assigned tickets - technician is assigned but service not started
                                                    ?>
                                                    <form method="POST" class="status-form">
                                                        <input type="hidden" name="request_id" value="<?php echo $ticket['request_id']; ?>">
                                                        <select name="status" class="status-select" onchange="if(this.value) this.form.submit()">
                                                            <option value="">Update Status</option>
                                                            <option value="cancelled">Cancel Ticket</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                    </form>
                                                <?php elseif($current_status == 'in_progress'): 
                                                    // Service is in progress - customer can mark as completed OR cancel
                                                    ?>
                                                    <form method="POST" class="status-form">
                                                        <input type="hidden" name="request_id" value="<?php echo $ticket['request_id']; ?>">
                                                        <select name="status" class="status-select" onchange="if(this.value) this.form.submit()">
                                                            <option value="">Update Status</option>
                                                            <option value="completed">Mark as Completed</option>
                                                            <option value="cancelled">Cancel Service</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                    </form>
                                                <?php elseif($current_status == 'completed'): 
                                                    // Already completed - show badge
                                                    ?>
                                                    <span class="badge success" style="padding: 5px 10px; border-radius: 4px; font-size: 0.85rem;">
                                                        ✅ Service Completed
                                                    </span>
                                                <?php elseif($current_status == 'cancelled'): 
                                                    // Already cancelled - show badge
                                                    ?>
                                                    <span class="badge error" style="padding: 5px 10px; border-radius: 4px; font-size: 0.85rem;">
                                                        ❌ Ticket Cancelled
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if($ticket['status'] == 'pending'): ?>
                                                    <a href="?delete=<?php echo $ticket['request_id']; ?>" 
                                                       class="action-link delete"
                                                       onclick="return confirm('Are you sure you want to delete this ticket? This action cannot be undone.')">
                                                        <span>🗑️</span> Delete Ticket
                                                    </a>
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
                        <h3 class="empty-state-title">No Service Tickets Found</h3>
                        <p class="empty-state-description">
                            <?php if(!empty($status_filter) || !empty($category_filter)): ?>
                                No tickets match your current filters. Try changing your filter settings or 
                                <a href="tickets.php" style="color: #50d0e0;">view all tickets</a>.
                            <?php else: ?>
                                You haven't created any service tickets yet. Start by requesting a networking or computer service.
                            <?php endif; ?>
                        </p>
                        <a href="book-service.php" class="btn btn-primary">
                            <span>🔧</span> Request Your First Service
                        </a>
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
            // Confirm deletion
            const deleteLinks = document.querySelectorAll('.action-link.delete');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if(!confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Auto-submit status update when select changes
            const statusSelects = document.querySelectorAll('.status-select');
            statusSelects.forEach(select => {
                select.addEventListener('change', function() {
                    if(this.value) {
                        this.closest('form').submit();
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
                const clearBtn = document.querySelector('a[href="tickets.php"]');
                if(clearBtn) {
                    clearBtn.style.background = 'rgba(80, 208, 224, 0.1)';
                    clearBtn.style.borderColor = '#50d0e0';
                }
            }
            
            // Add animation to table rows on hover
            const tableRows = document.querySelectorAll('.tickets-table tbody tr');
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