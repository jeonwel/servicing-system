<?php
include '../includes/config.php';
include '../includes/functions.php';

// Check if user is customer
if(!isLoggedIn() || !isCustomer()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Get user info
$user_sql = "SELECT * FROM users WHERE user_id='$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Get available networking services WITH PRICES
$services_sql = "SELECT * FROM services WHERE status='available' AND category IN ('Hardware/Software', 'Networking', 'Consultation')";
$services_result = mysqli_query($conn, $services_sql);

// Handle form submission
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $middle_name = sanitize($_POST['middle_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $contact_address = sanitize($_POST['contact_address']);
    $landmark = sanitize($_POST['landmark']);
    $service_id = sanitize($_POST['service_id']);
    $category = sanitize($_POST['category']);
    $description = sanitize($_POST['description']);
    
    // Validation
    $errors = [];
    
    if(empty($first_name)) $errors[] = "First name is required";
    if(empty($last_name)) $errors[] = "Last name is required";
    if(empty($phone)) $errors[] = "Phone number is required";
    if(empty($address)) $errors[] = "Address is required";
    if(empty($service_id)) $errors[] = "Please select a service";
    if(empty($category)) $errors[] = "Please select a category";
    if(empty($description)) $errors[] = "Please describe your technical issue";
    
    if(strlen($description) < 20) $errors[] = "Please provide a more detailed description (minimum 20 characters)";
    if(strlen($description) > 2000) $errors[] = "Description is too long (maximum 2000 characters)";
    
    // Payment method validation - set default if not provided
    if(empty($payment_method)) {
        $payment_method = 'cash_in_person'; // Set default value
    }
    
    // Get service price from database
    $price_sql = "SELECT service_name, base_price FROM services WHERE service_id='$service_id'";
    $price_result = mysqli_query($conn, $price_sql);
    
    if(mysqli_num_rows($price_result) == 0) {
        $errors[] = "Selected service is not available";
    } else {
        $service_data = mysqli_fetch_assoc($price_result);
        $service_name = $service_data['service_name'];
        $total_amount = $service_data['base_price'];
    }
    
    if(empty($errors)) {
        // Insert service request WITH PAYMENT DETAILS
        $sql = "INSERT INTO service_requests (
            customer_id, 
            service_id, 
            first_name, 
            last_name, 
            middle_name,
            phone, 
            address, 
            contact_address, 
            landmark, 
            category,
            problem_description,
            total_amount, 
            status, 
            payment_status,
            payment_method
        ) VALUES (
            '$user_id', 
            '$service_id', 
            '$first_name', 
            '$last_name', 
            '$middle_name',
            '$phone', 
            '$address', 
            '$contact_address', 
            '$landmark', 
            '$category',
            '$description',
            '$total_amount', 
            'pending', 
            'pending',
            '$payment_method'
        )";
        
        if(mysqli_query($conn, $sql)) {
            $request_id = mysqli_insert_id($conn);
            
            // Generate ticket number
            $ticket_code = "DRAD-" . date('Ymd') . "-" . str_pad($request_id, 4, '0', STR_PAD_LEFT);
            $ticket_sql = "INSERT INTO ticket_numbers (request_id, ticket_code) VALUES ('$request_id', '$ticket_code')";
            mysqli_query($conn, $ticket_sql);
            
            // Prepare success message with payment information
            $formatted_amount = number_format($total_amount, 2);
            $_SESSION['success'] = "
                <div style='text-align: center;'>
                    <h3 style='color: #4CAF50; margin-bottom: 15px;'>Service Request Submitted Successfully!</h3>
                    <div style='background: rgba(76, 175, 80, 0.1); padding: 20px; border-radius: 10px; border-left: 4px solid #4CAF50;'>
                        <p><strong>Ticket Number:</strong> <span style='color: #50d0e0; font-size: 1.2em;'>$ticket_code</span></p>
                        <p><strong>Service:</strong> $service_name</p>
                        <p><strong>Amount to Pay:</strong> <span style='color: #4CAF50; font-weight: bold; font-size: 1.2em;'>₱$formatted_amount</span></p>
                        <p><strong>Payment Method:</strong> Cash-in-Person (Pay technician after service completion)</p>
                        <p><strong>Scheduling:</strong> A technician will contact you to schedule the service</p>
                    </div>
                    <p style='margin-top: 20px; color: #666;'>
                        <strong>Note:</strong> Please prepare ₱$formatted_amount in cash. A technician will contact you to schedule the service.
                    </p>
                </div>
            ";
            
            redirect('tickets.php');
        } else {
            $error = "Error submitting request: " . mysqli_error($conn);
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
    <title>DRAD Servicing : Request Networking Service</title>
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
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            color: #50d0e0;
            margin-bottom: 15px;
        }

        .page-subtitle {
            color: #aee2ff;
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .network-badge {
            display: inline-block;
            background: linear-gradient(45deg, #50d0e0, #2196F3);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 15px;
        }

        /* Booking Form */
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            margin-bottom: 60px;
        }

        @media (max-width: 1024px) {
            .booking-container {
                grid-template-columns: 1fr;
            }
        }

        .form-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .form-section-title {
            font-size: 1.4rem;
            color: #50d0e0;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(80, 208, 224, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-title i {
            font-size: 1.5rem;
        }

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
        .form-select,
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

        .form-select {
            background-color: #1a1a3a;  /* Dark blue background */
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 20px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 45px;
            color: white;  /* White text */
        }

        /* Style for dropdown options */
        .form-select option {
            background-color: #1a1a3a;  /* Same dark background */
            color: white;  /* White text */
            padding: 10px;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #50d0e0;
            box-shadow: 0 0 0 3px rgba(80, 208, 224, 0.1);
            background: rgba(255, 255, 255, 0.15);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .datetime-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Service Preview */
        .service-preview {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 140px;
            height: fit-content;
        }

        .preview-title {
            font-size: 1.4rem;
            color: #50d0e0;
            margin-bottom: 25px;
            text-align: center;
        }

        .selected-service {
            background: rgba(80, 208, 224, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #50d0e0;
        }

        .selected-service h4 {
            color: #50d0e0;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .service-category-badge {
            display: inline-block;
            background: rgba(80, 208, 224, 0.2);
            color: #50d0e0;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }

        .service-description {
            color: #aee2ff;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .preview-info {
            margin-top: 25px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #aee2ff;
        }

        .info-value {
            color: #fff;
            font-weight: bold;
        }

        .preview-note {
            margin-top: 25px;
            padding: 15px;
            background: rgba(255, 193, 7, 0.1);
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }

        .preview-note p {
            color: #ffc107;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn {
            padding: 14px 30px;
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
            flex: 1;
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

        .alert-error {
            background: rgba(244, 67, 54, 0.1);
            border-color: #f44336;
            color: #ff6b6b;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            border-color: #4CAF50;
            color: #4CAF50;
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
            
            .service-preview {
                position: static;
                margin-top: 30px;
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

        /* Character Counter */
        .char-counter {
            text-align: right;
            font-size: 0.85rem;
            color: #aaa;
            margin-top: 5px;
        }

        .char-counter.warning {
            color: #ffc107;
        }

        .char-counter.error {
            color: #f44336;
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
                        <li><a href="book-service.php" class="active">Request Service</a></li>
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
            <!-- Page Header -->
            <div class="page-header animate-fade-in-up">
                <h1 class="page-title">Request Networking Service</h1>
                <p class="page-subtitle">
                    Describe your technical issue in detail so our networking experts can arrive prepared. 
                    The more information you provide, the better we can serve you.
                    <span class="network-badge">Detailed Issue Description Required</span>
                </p>
            </div>

            <!-- Error/Success Messages -->
            <?php if($error): ?>
                <div class="alert alert-error animate-fade-in-up">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success animate-fade-in-up">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Booking Form -->
            <form method="POST" action="" id="bookingForm">
                <div class="booking-container">
                    <!-- Left Column: Form -->
                    <div class="form-column">
                        <!-- Personal Information Section -->
                        <div class="form-section animate-fade-in-up" style="animation-delay: 0.1s;">
                            <h3 class="form-section-title">
                                <span>👤</span> Personal Information
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">First Name <span class="required">*</span></label>
                                    <input type="text" name="first_name" class="form-input" 
                                           value="<?php echo $user['full_name']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Last Name <span class="required">*</span></label>
                                    <input type="text" name="last_name" class="form-input" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-input">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Phone Number <span class="required">*</span></label>
                                    <input type="tel" name="phone" class="form-input" 
                                           value="<?php echo $user['phone'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information Section -->
                        <div class="form-section animate-fade-in-up" style="animation-delay: 0.2s;">
                            <h3 class="form-section-title">
                                <span>📍</span> Service Location
                            </h3>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label class="form-label">Complete Address <span class="required">*</span></label>
                                    <textarea name="address" class="form-textarea" rows="3" required><?php echo $user['address'] ?? ''; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Landmark</label>
                                    <input type="text" name="landmark" class="form-input" 
                                           placeholder="Nearby landmarks for easy location">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Contact Address (if different)</label>
                                    <input type="text" name="contact_address" class="form-input">
                                </div>
                            </div>
                        </div>

                        <!-- Service Details Section -->
                        <div class="form-section animate-fade-in-up" style="animation-delay: 0.3s;">
                            <h3 class="form-section-title">
                                <span>🔧</span> Technical & Payment Details
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Service Type <span class="required">*</span></label>
                                    <select name="service_id" class="form-select" required id="serviceSelect">
                                        <option value="">Select a networking service</option>
                                        <?php 
                                        if(mysqli_num_rows($services_result) > 0) {
                                            while($service = mysqli_fetch_assoc($services_result)) {
                                                $price = number_format($service['base_price'], 2);
                                                $displayName = $service['service_name'] . ' - ₱' . $price;

                                                echo '<option value="' . $service['service_id'] . '" 
                                                      data-category="' . $service['category'] . '"
                                                      data-description="' . htmlspecialchars($service['description']) . '"
                                                      data-price="' . $service['base_price'] . '">' 
                                                      . $displayName . '</option>';
                                            }
                                            // Reset pointer for later use
                                            mysqli_data_seek($services_result, 0);
                                        } else {
                                            echo '<option value="">No services available</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                    
                                <div class="form-group">
                                    <label class="form-label">Category <span class="required">*</span></label>
                                    <select name="category" class="form-select" required id="categorySelect">
                                        <option value="">Select category</option>
                                        <option value="Computer Repair">Computer Repair</option>
                                        <option value="Network Configuration">Network Configuration</option>
                                        <option value="IT Consultation">IT Consultation</option>
                                        <option value="Other">Other Networking Issue</option>
                                    </select>
                                </div>
                                    
                                <div class="form-group full-width">
                                    <label class="form-label">Payment Method <span class="required">*</span></label>
                                    <select name="payment_method" class="form-select" required id="paymentMethodSelect">
                                        <option value="">Select payment method</option>
                                        <option value="cash_in_person" selected>Cash-in-Person (Pay technician after service)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Issue Description Section -->
                        <div class="form-section animate-fade-in-up" style="animation-delay: 0.4s;">
                            <h3 class="form-section-title">
                                <span>📝</span> Describe Your Technical Issue
                            </h3>
                            <div class="form-group">
                                <label class="form-label">
                                    Detailed Problem Description <span class="required">*</span>
                                    <span style="font-size: 0.9rem; color: #50d0e0; display: block; margin-top: 5px;">
                                        Be specific: What's not working? When did it start? Any error messages?
                                    </span>
                                </label>
                                <textarea name="description" class="form-textarea" rows="6" required 
                                          placeholder="Example: My computer won't turn on. It started yesterday after a power outage. There's no light or sound when I press the power button. I've tried different power outlets with no success."
                                          id="issueDescription"></textarea>
                                <div class="char-counter" id="charCounter">0/500 characters</div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions animate-fade-in-up" style="animation-delay: 0.5s;">
                            <button type="submit" class="btn btn-primary">
                                <span>📨</span> Submit Service Request
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <span>↩️</span> Back to Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Right Column: Service Preview -->
                    <div class="service-preview animate-fade-in-up" style="animation-delay: 0.2s;">
                    <h3 class="preview-title">Service & Payment Details</h3>
                                                    
                    <div class="selected-service" id="selectedServicePreview">
                        <h4>No service selected</h4>
                        <p class="service-description">Select a service from the dropdown to see details here.</p>
                    </div>
                                                    
                    <div class="preview-info">
                        <div class="info-item">
                            <span class="info-label">Category:</span>
                            <span class="info-value" id="previewCategory">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Service Fee:</span>
                            <span class="info-value" id="previewPrice" style="color: #4CAF50; font-size: 1.1em;">₱0.00</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Scheduling:</span>
                            <span class="info-value" style="color: #50d0e0;">Technician will schedule</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment Method:</span>
                            <span class="info-value" style="color: #ff9800; font-weight: bold;" id="previewPaymentMethod">
                                Cash-In-Person
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment Status:</span>
                            <span class="info-value" style="color: #ffc107; font-weight: bold;">Pending Payment</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Service Status:</span>
                            <span class="info-value" style="color: #ffc107;">Pending Technician Assignment</span>
                        </div>
                    </div>
                                                    
                    <div class="preview-note">
                        <p><strong>Payment Instructions:</strong> 
                        Pay the technician <strong>in cash</strong> after service completion. 
                        The technician will provide a receipt for your payment.</p>
                        <p style="margin-top: 8px;"><strong>Scheduling Note:</strong> 
                        A technician will contact you to schedule the service date and time.</p>
                    </div>
                </div>
                </div>
            </form>
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
        // Update service preview in real-time
        document.addEventListener('DOMContentLoaded', function() {
            const serviceSelect = document.getElementById('serviceSelect');
            const categorySelect = document.getElementById('categorySelect');
            const paymentMethodSelect = document.getElementById('paymentMethodSelect');
            const previewPaymentMethod = document.getElementById('previewPaymentMethod');
            const issueDescription = document.getElementById('issueDescription');
            const charCounter = document.getElementById('charCounter');

            const previewService = document.getElementById('selectedServicePreview');
            const previewCategory = document.getElementById('previewCategory');
            const previewPrice = document.getElementById('previewPrice'); // Added price element
            const previewDate = document.getElementById('previewDate');
            const previewTime = document.getElementById('previewTime');

            // Character counter for issue description
            issueDescription.addEventListener('input', function() {
                const length = this.value.length;
                charCounter.textContent = `${length}/500 characters`;

                // Update counter color based on length
                charCounter.className = 'char-counter';
                if (length > 400) {
                    charCounter.classList.add('warning');
                }
                if (length > 500) {
                    charCounter.classList.add('error');
                }
            });

            // Update service preview when service is selected
            serviceSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value) {
                    const serviceName = selectedOption.text;
                    const category = selectedOption.getAttribute('data-category');
                    const description = selectedOption.getAttribute('data-description');
                    const price = selectedOption.getAttribute('data-price') || '0.00';

                    // Format price with PHP peso format
                    const formattedPrice = formatPrice(price);

                    // Update service preview
                    previewService.innerHTML = `
                        <h4>${serviceName.split(' - ₱')[0]}</h4> <!-- Remove price from title -->
                        <span class="service-category-badge">${category}</span>
                        <p class="service-description">${description}</p>
                    `;

                    // Update price display
                    previewPrice.textContent = formattedPrice;
                    previewPrice.style.color = '#4CAF50';
                    previewPrice.style.fontSize = '1.1em';
                    previewPrice.style.fontWeight = 'bold';

                    // Auto-select matching category
                    if (category) {
                        for (let option of categorySelect.options) {
                            if (option.value.includes(category.split('/')[0]) || 
                                category.includes(option.value)) {
                                option.selected = true;
                                previewCategory.textContent = option.value;
                                break;
                            }
                        }
                    }
                } else {
                    previewService.innerHTML = `
                        <h4>No service selected</h4>
                        <p class="service-description">Select a service from the dropdown to see details here.</p>
                    `;
                    previewCategory.textContent = '-';
                    previewPrice.textContent = '₱0.00';
                    previewPrice.style.color = '#4CAF50';
                }
            });

            // Helper function to format price
            function formatPrice(price) {
                const numPrice = parseFloat(price);
                if (isNaN(numPrice)) return '₱0.00';

                // Format with commas for thousands
                return '₱' + numPrice.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            }

            // Update category preview
            categorySelect.addEventListener('change', function() {
                previewCategory.textContent = this.value || '-';
            });

            // Form validation
            const bookingForm = document.getElementById('bookingForm');
            bookingForm.addEventListener('submit', function(e) {
                const description = issueDescription.value.trim();
                if (description.length < 20) {
                    e.preventDefault();
                    alert('Please provide a more detailed description of your technical issue (minimum 20 characters).');
                    issueDescription.focus();
                    return false;
                }

                if (description.length > 500) {
                    e.preventDefault();
                    alert('Please keep your description under 500 characters.');
                    issueDescription.focus();
                    return false;
                }

                // Check if service is selected
                if (!serviceSelect.value) {
                    e.preventDefault();
                    alert('Please select a service.');
                    serviceSelect.focus();
                    return false;
                }

                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<span>⏳</span> Submitting...';
                submitBtn.disabled = true;
            });

            // Auto-fill last name from full name if empty
            const firstNameInput = document.querySelector('input[name="first_name"]');
            const lastNameInput = document.querySelector('input[name="last_name"]');

            if (firstNameInput && !lastNameInput.value) {
                const fullName = firstNameInput.value.trim();
                const nameParts = fullName.split(' ');
                if (nameParts.length > 1) {
                    lastNameInput.value = nameParts[nameParts.length - 1];
                    firstNameInput.value = nameParts.slice(0, -1).join(' ');
                }
            }

            // Update payment method preview
            paymentMethodSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption.value === 'cash_in_person') {
                    previewPaymentMethod.innerHTML = 'Cash-In-Person';
                    previewPaymentMethod.style.color = '#ff9800';
                }
            });

        });
    </script>
</body>
</html>