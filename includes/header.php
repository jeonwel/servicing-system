<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServicePro - Professional Service System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">ServicePro</a>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] == 'customer'): ?>
                        <li><a href="customer/dashboard.php">Dashboard</a></li>
                        <li><a href="customer/tickets.php">My Tickets</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout (<?php echo $_SESSION['full_name']; ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <main class="container">