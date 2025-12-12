<?php
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is customer
if(!isLoggedIn() || !isCustomer()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Get ticket ID from URL
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid ticket ID";
    redirect('tickets.php');
}

$request_id = sanitize($_GET['id']);

// Main ticket query with correct column names
$ticket_sql = "SELECT 
                sr.*, 
                s.service_name, 
                s.base_price,
                s.description as service_description,
                tn.ticket_code,
                u.email,
                u.full_name as customer_full_name,
                sr.payment_status,
                sr.payment_method,
                sr.total_amount
               FROM service_requests sr
               JOIN services s ON sr.service_id = s.service_id
               LEFT JOIN ticket_numbers tn ON sr.request_id = tn.request_id
               LEFT JOIN users u ON sr.customer_id = u.user_id
               WHERE sr.request_id = ? AND sr.customer_id = ?
               LIMIT 1";

$ticket_result = executeQuery($conn, $ticket_sql, [$request_id, $user_id], "ii");

if(!$ticket_result || mysqli_num_rows($ticket_result) == 0) {
    $_SESSION['error'] = "Ticket not found or you don't have permission to view it";
    redirect('tickets.php');
}

$ticket = mysqli_fetch_assoc($ticket_result);

// Calculate service fee - use total_amount from service_requests or base_price
$service_fee = $ticket['total_amount'] ?? $ticket['base_price'];
$formatted_amount = number_format($service_fee, 2);

// Get technician assignment from assignments table
$technician = null;
$technician_sql = "SELECT 
                    a.technician_id,
                    a.assigned_date,
                    a.completed_date,
                    a.notes as assignment_notes,
                    t.full_name as technician_name,
                    t.phone as technician_phone,
                    t.email as technician_email
                   FROM assignments a
                   JOIN users t ON a.technician_id = t.user_id
                   WHERE a.request_id = ?";
$technician_result = executeQuery($conn, $technician_sql, [$request_id], "i");
if($technician_result && mysqli_num_rows($technician_result) > 0) {
    $technician = mysqli_fetch_assoc($technician_result);
}

// Get payment information from payments table
$payment = null;
$payment_sql = "SELECT 
                 amount_paid as payment_amount,
                 payment_date,
                 receipt_number,
                 payment_confirm_date,
                 remittance_status,
                 remittance_date,
                 notes as payment_notes
                FROM payments 
                WHERE request_id = ?";
$payment_result = executeQuery($conn, $payment_sql, [$request_id], "i");
if($payment_result && mysqli_num_rows($payment_result) > 0) {
    $payment = mysqli_fetch_assoc($payment_result);
}

// Handle status update if form submitted
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = sanitize($_POST['status']);
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Validate status transition
    $allowed_transitions = [
        'pending' => ['cancelled'],
        'assigned' => ['cancelled'],
        'in_progress' => ['completed', 'cancelled']
    ];
    
    if(isset($allowed_transitions[$ticket['status']]) && 
       in_array($new_status, $allowed_transitions[$ticket['status']])) {
        
        // Update ticket status
        $update_sql = "UPDATE service_requests SET status = ?";
        $params = [$new_status];
        $types = "s";
        
        if($new_status == 'completed') {
            $update_sql .= ", completed_date = NOW()";
        }
        
        $update_sql .= " WHERE request_id = ?";
        $params[] = $request_id;
        $types .= "i";
        
        if(executeQuery($conn, $update_sql, $params, $types)) {
            $_SESSION['success'] = "Ticket status updated to " . ucfirst(str_replace('_', ' ', $new_status));
            
            // Refresh ticket data
            $ticket_result = executeQuery($conn, $ticket_sql, [$request_id, $user_id], "ii");
            $ticket = mysqli_fetch_assoc($ticket_result);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRAD Servicing : Ticket #<?php echo $ticket['ticket_code'] ?? $request_id; ?></title>
    <style>
        /* Use the exact same CSS from the previous ticket-details.php */
        /* Only showing CSS that might need adjustment */
        
        /* Remittance Status Badges */
        .remittance-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
        }
        
        .remittance-not_remitted { 
            background: rgba(255, 193, 7, 0.2); 
            color: #ffc107; 
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .remittance-remitted { 
            background: rgba(33, 150, 243, 0.2); 
            color: #2196F3; 
            border: 1px solid rgba(33, 150, 243, 0.3);
        }
        
        .remittance-verified { 
            background: rgba(76, 175, 80, 0.2); 
            color: #4CAF50; 
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        /* Rest of the CSS remains the same as the working version */
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

        /* Header Styles (same as other pages) */
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

        /* Ticket Header */
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .ticket-title {
            font-size: 2rem;
            color: #50d0e0;
        }

        .ticket-code {
            background: rgba(80, 208, 224, 0.1);
            padding: 10px 20px;
            border-radius: 10px;
            border-left: 4px solid #50d0e0;
            font-size: 1.2rem;
            font-weight: bold;
        }

        /* Ticket Layout */
        .ticket-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .ticket-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Ticket Card */
        .ticket-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
            margin-bottom: 25px;
        }

        .ticket-card:last-child {
            margin-bottom: 0;
        }

        .card-title {
            font-size: 1.4rem;
            color: #50d0e0;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(80, 208, 224, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            color: #aee2ff;
            font-size: 0.95rem;
            margin-bottom: 5px;
            display: block;
        }

        .info-value {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Status Badge */
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            display: inline-block;
            text-align: center;
            min-width: 120px;
        }

        .status-pending { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .status-assigned { background: rgba(33, 150, 243, 0.2); color: #2196F3; }
        .status-in_progress { background: rgba(255, 152, 0, 0.2); color: #ff9800; }
        .status-completed { background: rgba(76, 175, 80, 0.2); color: #4CAF50; }
        .status-cancelled { background: rgba(244, 67, 54, 0.2); color: #f44336; }

        /* Problem Description */
        .problem-description {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #50d0e0;
            margin-top: 15px;
            line-height: 1.6;
            color: #aee2ff;
        }

        /* Status History */
        .status-timeline {
            position: relative;
            padding-left: 30px;
            margin-top: 20px;
        }

        .status-timeline:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: rgba(80, 208, 224, 0.3);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }

        .timeline-item:before {
            content: '';
            position: absolute;
            left: -36px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #50d0e0;
        }

        .timeline-date {
            color: #aee2ff;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .timeline-status {
            font-weight: bold;
            color: #fff;
            margin-bottom: 5px;
        }

        .timeline-notes {
            color: #aee2ff;
            font-size: 0.9rem;
            font-style: italic;
        }

        /* Technician Card */
        .technician-card {
            background: rgba(80, 208, 224, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
            border-left: 4px solid #50d0e0;
        }

        .technician-avatar {
            width: 60px;
            height: 60px;
            background: #50d0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #1b1a1a;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        /* Payment Card */
        .payment-card {
            background: rgba(76, 175, 80, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
            border-left: 4px solid #4CAF50;
        }

        .payment-amount {
            font-size: 2rem;
            color: #4CAF50;
            font-weight: bold;
            margin: 10px 0;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
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

        .btn-danger {
            background: transparent;
            color: #ff6b6b;
            border: 2px solid #ff6b6b;
        }

        .btn-danger:hover {
            background: rgba(255, 107, 107, 0.1);
        }

        /* Status Update Form */
        .status-form {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            color: #aee2ff;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-select, .form-textarea {
            width: 100%;
            padding: 10px 15px;
            background-color: #1a1a3a;
            border: 1px solid rgba(80, 208, 224, 0.3);
            border-radius: 8px;
            color: #fff;
            font-family: "Science Gothic", sans-serif;
            font-size: 1rem;
        }

        .form-textarea {
            min-height: 80px;
            resize: vertical;
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
            
            .ticket-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
    </style>
</head>
<body>
    <!-- Header - Same as before -->
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

            <!-- Ticket Header -->
            <div class="ticket-header">
                <div>
                    <h1 class="ticket-title">Service Ticket Details</h1>
                    <p style="color: #aee2ff; margin-top: 10px;">View and manage your service request</p>
                </div>
                <div class="ticket-code">
                    <?php echo $ticket['ticket_code'] ?? 'TICKET-' . $request_id; ?>
                </div>
            </div>

            <div class="ticket-layout">
                <!-- Left Column: Main Ticket Info -->
                <div class="main-content">
                    <!-- Service Information Card -->
                    <div class="ticket-card">
                        <h3 class="card-title">
                            <span>🔧</span> Service Information
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Service Type</span>
                                <span class="info-value"><?php echo htmlspecialchars($ticket['service_name']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Category</span>
                                <span class="info-value"><?php echo htmlspecialchars($ticket['category']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Request Date</span>
                                <span class="info-value"><?php echo date('F j, Y \a\t h:i A', strtotime($ticket['request_date'])); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Current Status</span>
                                <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                </span>
                            </div>
                            
                            <?php if($ticket['preferred_date']): ?>
                            <div class="info-item">
                                <span class="info-label">Preferred Date</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($ticket['preferred_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if($ticket['preferred_time']): ?>
                            <div class="info-item">
                                <span class="info-label">Preferred Time</span>
                                <span class="info-value"><?php echo date('h:i A', strtotime($ticket['preferred_time'])); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if($ticket['completed_date']): ?>
                            <div class="info-item">
                                <span class="info-label">Completed Date</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($ticket['completed_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Problem Description -->
                        <div style="margin-top: 25px;">
                            <span class="info-label">Problem Description</span>
                            <div class="problem-description">
                                <?php echo nl2br(htmlspecialchars($ticket['problem_description'])); ?>
                            </div>
                        </div>
                        
                        <!-- Service Description -->
                        <?php if(!empty($ticket['service_description'])): ?>
                        <div style="margin-top: 25px;">
                            <span class="info-label">Service Details</span>
                            <div class="problem-description">
                                <?php echo nl2br(htmlspecialchars($ticket['service_description'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Customer Information Card -->
                    <div class="ticket-card">
                        <h3 class="card-title">
                            <span>👤</span> Customer Information
                        </h3>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Name</span>
                                <span class="info-value">
                                    <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($ticket['phone']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($ticket['email'] ?? $ticket['customer_full_name']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Address</span>
                                <span class="info-value"><?php echo htmlspecialchars($ticket['address']); ?></span>
                            </div>
                            
                            <?php if(!empty($ticket['landmark'])): ?>
                            <div class="info-item">
                                <span class="info-label">Landmark</span>
                                <span class="info-value"><?php echo htmlspecialchars($ticket['landmark']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Sidebar -->
                <div class="sidebar">
                    <!-- Payment Information Card -->
                    <div class="ticket-card">
                        <h3 class="card-title">
                            <span>💰</span> Payment Information
                        </h3>
                        
                        <div class="payment-card">
                            <div class="info-item">
                                <span class="info-label">Service Fee</span>
                                <div class="payment-amount">₱<?php echo $formatted_amount; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Payment Status</span>
                                <span class="info-value" style="color: <?php echo ($ticket['payment_status'] == 'paid') ? '#4CAF50' : '#ffc107'; ?>;">
                                    <?php echo ucfirst($ticket['payment_status'] ?? 'pending'); ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Payment Method</span>
                                <span class="info-value"><?php echo ucfirst(str_replace('_', ' ', $ticket['payment_method'] ?? 'cash_in_person')); ?></span>
                            </div>
                            
                            <!-- Payment Details from payments table -->
                            <?php if($payment): ?>
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                                <h4 style="color: #50d0e0; margin-bottom: 15px; font-size: 1.1rem;">Payment Receipt Details</h4>
                                
                                <div class="info-item">
                                    <span class="info-label">Amount Paid</span>
                                    <span class="info-value" style="color: #4CAF50; font-weight: bold;">
                                        ₱<?php echo number_format($payment['payment_amount'], 2); ?>
                                    </span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Payment Date</span>
                                    <span class="info-value"><?php echo date('F j, Y \a\t h:i A', strtotime($payment['payment_date'])); ?></span>
                                </div>
                                
                                <?php if($payment['receipt_number']): ?>
                                <div class="info-item">
                                    <span class="info-label">Receipt Number</span>
                                    <span class="info-value" style="font-family: monospace; color: #50d0e0;">
                                        <?php echo htmlspecialchars($payment['receipt_number']); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($payment['payment_confirm_date']): ?>
                                <div class="info-item">
                                    <span class="info-label">Confirmed On</span>
                                    <span class="info-value"><?php echo date('F j, Y', strtotime($payment['payment_confirm_date'])); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <span class="info-label">Remittance Status</span>
                                    <span class="remittance-badge remittance-<?php echo $payment['remittance_status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $payment['remittance_status'])); ?>
                                    </span>
                                </div>
                                
                                <?php if($payment['remittance_date']): ?>
                                <div class="info-item">
                                    <span class="info-label">Remittance Date</span>
                                    <span class="info-value"><?php echo date('F j, Y', strtotime($payment['remittance_date'])); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($payment['payment_notes']): ?>
                                <div class="info-item">
                                    <span class="info-label">Payment Notes</span>
                                    <div style="color: #aee2ff; font-size: 0.9rem; margin-top: 5px;">
                                        <?php echo nl2br(htmlspecialchars($payment['payment_notes'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Technician Assignment Card -->
                    <?php if($technician): ?>
                    <div class="ticket-card">
                        <h3 class="card-title">
                            <span>👨‍💻</span> Assigned Technician
                        </h3>
                        
                        <div class="technician-card">
                            <div class="technician-avatar">
                                <?php 
                                $tech_initials = '';
                                $tech_name_parts = explode(' ', $technician['technician_name']);
                                foreach($tech_name_parts as $part) {
                                    $tech_initials .= strtoupper(substr($part, 0, 1));
                                }
                                echo substr($tech_initials, 0, 2);
                                ?>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Technician</span>
                                <span class="info-value"><?php echo htmlspecialchars($technician['technician_name']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Contact</span>
                                <span class="info-value"><?php echo htmlspecialchars($technician['technician_phone']); ?></span>
                            </div>
                            
                            <?php if($technician['technician_email']): ?>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($technician['technician_email']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <span class="info-label">Assigned On</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($technician['assigned_date'])); ?></span>
                            </div>
                            
                            <?php if($technician['completed_date']): ?>
                            <div class="info-item">
                                <span class="info-label">Completed On</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($technician['completed_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if($technician['assignment_notes']): ?>
                            <div class="info-item">
                                <span class="info-label">Assignment Notes</span>
                                <div style="color: #aee2ff; font-size: 0.9rem; margin-top: 5px;">
                                    <?php echo nl2br(htmlspecialchars($technician['assignment_notes'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Status Update Card (if allowed) -->
                    <?php 
                    $current_status = $ticket['status'];
                    $allowed_transitions = [
                        'pending' => ['cancelled'],
                        'assigned' => ['cancelled'],
                        'in_progress' => ['completed', 'cancelled']
                    ];
                    
                    if(isset($allowed_transitions[$current_status])): 
                    ?>
                    <div class="ticket-card">
                        <h3 class="card-title">
                            <span>⚡</span> Update Status
                        </h3>
                        
                        <form method="POST" class="status-form">
                            <div class="form-group">
                                <label class="form-label">Change Status To</label>
                                <select name="status" class="form-select" required>
                                    <option value="">Select new status</option>
                                    <?php foreach($allowed_transitions[$current_status] as $status): ?>
                                        <option value="<?php echo $status; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea name="notes" class="form-textarea" 
                                          placeholder="Add any notes about this status change..."></textarea>
                            </div>
                            
                            <button type="submit" name="update_status" value="1" class="btn btn-primary" style="width: 100%;">
                                <span>🔄</span> Update Status
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="tickets.php" class="btn btn-secondary">
                    <span>←</span> Back to All Tickets
                </a>
                
                <a href="book-service.php" class="btn btn-primary">
                    <span>➕</span> Request New Service
                </a>
                
                <?php if($ticket['status'] == 'pending'): ?>
                <a href="tickets.php?delete=<?php echo $request_id; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this ticket? This action cannot be undone.')">
                    <span>🗑️</span> Delete Ticket
                </a>
                <?php endif; ?>
                
                <!-- Add Review Button if ticket is completed and no review exists -->
                <?php 
                // Check if review exists
                $review_check_sql = "SELECT review_id FROM reviews WHERE request_id = ?";
                $review_check_result = executeQuery($conn, $review_check_sql, [$request_id], "i");
                $has_review = $review_check_result && mysqli_num_rows($review_check_result) > 0;
                
                if($ticket['status'] == 'completed' && !$has_review && $technician): ?>
                <a href="write-review.php?ticket=<?php echo $request_id; ?>" class="btn btn-primary">
                    <span>⭐</span> Write a Review
                </a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer - Same as before -->
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
            /*
            // Add print functionality
            const printBtn = document.createElement('button');
            printBtn.innerHTML = '<span>🖨️</span> Print Ticket';
            printBtn.className = 'btn btn-secondary';
            printBtn.style.marginLeft = '15px';
            printBtn.onclick = function() {
                window.print();
            };
            
            document.querySelector('.action-buttons').appendChild(printBtn);
            */
            
            // Add confirmation for status updates
            const statusForm = document.querySelector('.status-form');
            if(statusForm) {
                statusForm.addEventListener('submit', function(e) {
                    const statusSelect = this.querySelector('select[name="status"]');
                    if(statusSelect.value === 'cancelled') {
                        if(!confirm('Are you sure you want to cancel this service request? This action cannot be undone.')) {
                            e.preventDefault();
                        }
                    }
                });
            }
            
            // Animate status badge
            const statusBadge = document.querySelector('.status-badge');
            if(statusBadge) {
                statusBadge.style.transition = 'all 0.3s ease';
                statusBadge.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                });
                statusBadge.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            }
        });
    </script>
</body>
</html>