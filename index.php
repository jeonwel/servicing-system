<?php
include 'includes/config.php';
include 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRAD Servicing : Networking Solutions - Hire, Succeed, Repeat</title>
    <style>
        @font-face {
            font-family: 'Science Gothic';
            src: url('assets/fonts/ScienceGothic-Medium.ttf') format('truetype');
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
            gap: 30px;
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

        .auth-buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 24px;
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

        /* Hero Section */
        .hero-section {
            padding: 180px 0 100px;
            background: 
                linear-gradient(135deg, rgba(10, 10, 42, 0.85), rgba(26, 26, 58, 0.95)),
                url(assets/images/bg2.jpg);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.2rem;
            color: #50d0e0;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: #aee2ff;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .hero-features {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .hero-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #aee2ff;
            font-size: 1.1rem;
        }

        .hero-feature-icon {
            color: #50d0e0;
            font-size: 1.5rem;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 50px;
        }

        /* Specialization Section */
        .specialization-section {
            padding: 100px 0;
            background: rgba(10, 10, 42, 0.7);
            text-align: center;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: #50d0e0;
            margin-bottom: 20px;
        }

        .section-subtitle {
            text-align: center;
            color: #aee2ff;
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 60px;
            line-height: 1.6;
        }

        .specialization-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }

        .specialization-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            border: 1px solid rgba(80, 208, 224, 0.2);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .specialization-card:hover {
            transform: translateY(-10px);
            border-color: #50d0e0;
            box-shadow: 0 10px 30px rgba(80, 208, 224, 0.2);
        }

        .specialization-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #50d0e0;
        }

        .specialization-title {
            font-size: 1.5rem;
            color: #fff;
            margin-bottom: 15px;
        }

        .specialization-description {
            color: #aee2ff;
            line-height: 1.6;
        }

        /* How It Works - Updated for Networking */
        .process-section {
            padding: 100px 0;
            background: linear-gradient(135deg, rgba(26, 26, 58, 0.9) 0%, rgba(10, 10, 42, 0.8) 100%);
        }

        .process-steps {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            flex-wrap: wrap;
            gap: 30px;
        }

        .process-step {
            flex: 1;
            min-width: 200px;
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: #50d0e0;
            color: #1b1a1a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 20px;
        }

        .step-title {
            font-size: 1.3rem;
            color: #fff;
            margin-bottom: 15px;
        }

        .step-description {
            color: #aee2ff;
            line-height: 1.6;
        }

        /* Services Section - Updated for Networking */
        .services-section {
            padding: 100px 0;
            background: rgba(10, 10, 42, 0.7);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #50d0e0, #2196F3);
        }

        .service-card:hover {
            border-color: #50d0e0;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(80, 208, 224, 0.2);
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .service-name {
            font-size: 1.4rem;
            color: #50d0e0;
        }

        .service-category {
            display: inline-block;
            background: rgba(80, 208, 224, 0.1);
            color: #50d0e0;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .service-description {
            color: #aee2ff;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .service-features {
            margin: 20px 0;
            padding-left: 20px;
        }

        .service-features li {
            color: #aee2ff;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .service-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
        }

        .service-duration {
            color: #aaa;
            font-size: 0.9rem;
        }

        /* Why Choose Us - Technical Focus */
        .expertise-section {
            padding: 100px 0;
            background: linear-gradient(135deg, rgba(26, 26, 58, 0.9) 0%, rgba(10, 10, 42, 0.8) 100%);
        }

        .expertise-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .expertise-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(80, 208, 224, 0.2);
            text-align: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .expertise-card:hover {
            transform: translateY(-5px);
            border-color: #50d0e0;
        }

        .expertise-icon {
            font-size: 2.5rem;
            color: #50d0e0;
            margin-bottom: 20px;
        }

        /* Tech Support CTA */
        .tech-support-cta {
            padding: 80px 0;
            background: rgba(10, 10, 42, 0.7);
            text-align: center;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 2.2rem;
            color: #50d0e0;
            margin-bottom: 20px;
        }

        .cta-description {
            color: #aee2ff;
            font-size: 1.2rem;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        /* Contact Section */
        .contact-section {
            padding: 100px 0;
            background: linear-gradient(135deg, rgba(26, 26, 58, 0.9) 0%, rgba(10, 10, 42, 0.8) 100%);
        }

        /* Footer */
        .main-footer {
            background: rgba(10, 10, 42, 0.9);
            padding: 60px 0 30px;
            margin-top: 0;
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
            .hero-title {
                font-size: 2.8rem;
            }
            
            .nav-menu {
                gap: 15px;
            }
            
            .process-steps {
                gap: 20px;
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
            
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .process-steps {
                flex-direction: column;
            }
            
            .hero-features {
                flex-direction: column;
                gap: 20px;
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

        /* Scroll padding for fixed header */
        main {
            padding-top: 80px;
            background-color: black;
        }

        /* Networking Theme Elements */
        .network-icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(80, 208, 224, 0.1);
            border-radius: 50%;
            line-height: 40px;
            text-align: center;
            margin-right: 10px;
        }

        .tech-badge {
            display: inline-block;
            background: linear-gradient(45deg, #50d0e0, #2196F3);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin: 0 5px 5px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="assets/images/logo-light-transparent.png" alt="DRAD Servicing Logo">
                </div>
                
                <nav>
                    <ul class="nav-menu">
                        <li><a href="#home" class="active">Home</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#specialization">Our Focus</a></li>
                        <li><a href="#how-it-works">Process</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </nav>
                
                <div class="auth-buttons">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] == 'customer'): ?>
                            <a href="customer/dashboard.php" class="btn btn-primary">Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-secondary">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-secondary">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section id="home" class="hero-section">
            <div class="container">
                <div class="hero-content animate-fade-in-up">
                    <h1 class="hero-title">Professional Networking Solutions</h1>
                    <p class="hero-subtitle">
                        Specialized in computer repair, network configuration, and IT troubleshooting. 
                        Describe your issue, hire our certified technicians, and get your systems running smoothly.
                    </p>
                    
                    <div class="hero-features">
                        <div class="hero-feature">
                            <span class="hero-feature-icon">🔧</span>
                            <span>Computer Repair & Troubleshooting</span>
                        </div>
                        <div class="hero-feature">
                            <span class="hero-feature-icon">🌐</span>
                            <span>Network Configuration</span>
                        </div>
                        <div class="hero-feature">
                            <span class="hero-feature-icon">💬</span>
                            <span>Technical Consultation</span>
                        </div>
                    </div>
                    
                    <div class="hero-buttons">
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="register.php" class="btn btn-primary">Get Technical Support</a>
                            <a href="#services" class="btn btn-secondary">View Our Services</a>
                        <?php else: ?>
                            <a href="customer/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                            <a href="customer/book-service.php" class="btn btn-secondary">Request Service</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Specialization Section -->
        <section id="specialization" class="specialization-section">
            <div class="container">
                <h2 class="section-title">Our Networking Specialization</h2>
                <p class="section-subtitle">
                    We focus exclusively on networking and computer systems. Our certified technicians 
                    handle everything from basic computer repair to complex network configurations.
                </p>
                
                <div class="specialization-grid">
                    <div class="specialization-card animate-fade-in-up" style="animation-delay: 0.1s;">
                        <div class="specialization-icon">💻</div>
                        <h3 class="specialization-title">Computer Repair</h3>
                        <p class="specialization-description">
                            Hardware diagnostics, software troubleshooting, virus removal, 
                            and system optimization for desktops and laptops.
                        </p>
                    </div>
                    
                    <div class="specialization-card animate-fade-in-up" style="animation-delay: 0.2s;">
                        <div class="specialization-icon">🌐</div>
                        <h3 class="specialization-title">Network Configuration</h3>
                        <p class="specialization-description">
                            Router setup, WiFi optimization, network security, 
                            LAN/WAN configuration, and connectivity solutions.
                        </p>
                    </div>
                    
                    <div class="specialization-card animate-fade-in-up" style="animation-delay: 0.3s;">
                        <div class="specialization-icon">🔍</div>
                        <h3 class="specialization-title">Technical Consultation</h3>
                        <p class="specialization-description">
                            Expert advice on IT issues, problem diagnosis, 
                            and solution recommendations for networking challenges.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section id="how-it-works" class="process-section">
            <div class="container">
                <h2 class="section-title">How Our Service Works</h2>
                <p class="section-subtitle">Simple process for effective networking solutions</p>
                
                <div class="process-steps">
                    <div class="process-step animate-fade-in-up" style="animation-delay: 0.1s;">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Describe Your Issue</h3>
                        <p class="step-description">
                            Provide detailed information about your computer or 
                            networking problem when booking.
                        </p>
                    </div>
                    
                    <div class="process-step animate-fade-in-up" style="animation-delay: 0.2s;">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Technician Review</h3>
                        <p class="step-description">
                            Our networking expert reviews your issue and prepares 
                            the necessary tools and solutions.
                        </p>
                    </div>
                    
                    <div class="process-step animate-fade-in-up" style="animation-delay: 0.3s;">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Service Dispatch</h3>
                        <p class="step-description">
                            Qualified technician arrives prepared with knowledge 
                            of your specific networking issue.
                        </p>
                    </div>
                    
                    <div class="process-step animate-fade-in-up" style="animation-delay: 0.4s;">
                        <div class="step-number">4</div>
                        <h3 class="step-title">Issue Resolution</h3>
                        <p class="step-description">
                            Expert service delivery with efficient problem-solving 
                            for your computer or network.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="services-section">
            <div class="container">
                <h2 class="section-title">Our Networking Services</h2>
                <p class="section-subtitle">Specialized IT services for all your networking needs</p>
                
                <?php
                // Updated services for networking focus
                $services = [
                    [
                        'name' => 'Computer Diagnostic & Repair',
                        'price' => '1,500',
                        'category' => 'Hardware/Software',
                        'description' => 'Complete computer troubleshooting including hardware diagnostics, software issues, virus removal, and system optimization.',
                        'duration' => '2-3 hours',
                        'features' => ['Hardware testing', 'Software troubleshooting', 'Virus/malware removal', 'System optimization']
                    ],
                    [
                        'name' => 'Basic Network Configuration',
                        'price' => '2,000',
                        'category' => 'Networking',
                        'description' => 'Router setup, WiFi configuration, network security setup, and basic LAN troubleshooting.',
                        'duration' => '2-4 hours',
                        'features' => ['Router setup & configuration', 'WiFi optimization', 'Network security', 'LAN troubleshooting']
                    ],
                    [
                        'name' => 'IT Consultation & Troubleshooting',
                        'price' => '800',
                        'category' => 'Consultation',
                        'description' => 'Expert advice and troubleshooting for specific IT issues, problem diagnosis, and solution recommendations.',
                        'duration' => '1-2 hours',
                        'features' => ['Problem diagnosis', 'Solution recommendations', 'Technical advice', 'Best practices']
                    ]
                ];
                
                if(count($services) > 0): ?>
                    <div class="services-grid">
                        <?php 
                        $delay = 0;
                        foreach($services as $service): 
                            $delay += 0.1;
                        ?>
                            <div class="service-card animate-fade-in-up" style="animation-delay: <?php echo $delay; ?>s;">
                                <div class="service-header">
                                    <h3 class="service-name"><?php echo $service['name']; ?></h3>
                                </div>
                                
                                <span class="service-category"><?php echo $service['category']; ?></span>
                                
                                <p class="service-description"><?php echo $service['description']; ?></p>
                                
                                <ul class="service-features">
                                    <?php foreach($service['features'] as $feature): ?>
                                        <li>✓ <?php echo $feature; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <div class="service-footer">
                                    <span class="service-duration">⏱️ <?php echo $service['duration']; ?></span>
                                    
                                    <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
                                        <a href="customer/book-service.php" 
                                           class="btn btn-primary">Request Service</a>
                                    <?php elseif(isset($_SESSION['user_id'])): ?>
                                        <button class="btn btn-secondary" disabled>Customer Only</button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-primary">Login to Request</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 0;">
                        <p style="font-size: 1.2rem; color: #aee2ff;">No services available at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Expertise Section -->
        <section id="expertise" class="expertise-section">
            <div class="container">
                <h2 class="section-title">Why Choose DRAD Networking?</h2>
                <p class="section-subtitle">Expertise that makes the difference</p>
                
                <div class="expertise-grid">
                    <div class="expertise-card">
                        <div class="expertise-icon">🎯</div>
                        <h3>Specialized Focus</h3>
                        <p>100% focused on networking and computer systems</p>
                    </div>
                    
                    <div class="expertise-card">
                        <div class="expertise-icon">📝</div>
                        <h3>Issue-First Approach</h3>
                        <p>Technicians review your problem before arrival</p>
                    </div>
                    
                    <div class="expertise-card">
                        <div class="expertise-icon">🔧</div>
                        <h3>Certified Technicians</h3>
                        <p>Network-certified professionals only</p>
                    </div>
                    
                    <div class="expertise-card">
                        <div class="expertise-icon">⚡</div>
                        <h3>Efficient Service</h3>
                        <p>Faster resolution with prepared technicians</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tech Support CTA -->
        <section class="tech-support-cta">
            <div class="container">
                <div class="cta-content animate-fade-in-up">
                    <h2 class="cta-title">Need Technical Support?</h2>
                    <p class="cta-description">
                        Describe your computer or networking issue in detail when booking. 
                        Our technicians will arrive prepared with the right knowledge and tools 
                        to solve your specific problem efficiently.
                    </p>
                    <div class="hero-buttons">
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="register.php" class="btn btn-primary">Get Started Now</a>
                        <?php else: ?>
                            <a href="customer/book-service.php" class="btn btn-primary">Request Support</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="contact-section">
            <div class="container">
                <h2 class="section-title">Contact Our Tech Team</h2>
                <p class="section-subtitle">Get in touch with our networking specialists</p>
                
                <div class="specialization-grid">
                    <div class="specialization-card">
                        <div class="specialization-icon">📧</div>
                        <h3 class="specialization-title">Email Support</h3>
                        <p class="specialization-description">
                            techsupport@dradservicing.com<br>
                        </p>
                    </div>
                    
                    <div class="specialization-card">
                        <div class="specialization-icon">📞</div>
                        <h3 class="specialization-title">Phone Support</h3>
                        <p class="specialization-description">
                            (02) 1234-5678<br>
                            Mon-Sat: 8:00 AM - 8:00 PM
                        </p>
                    </div>
                    
                    <div class="specialization-card">
                        <div class="specialization-icon">📍</div>
                        <h3 class="specialization-title">Service Area</h3>
                        <p class="specialization-description">
                            Cebu City & surrounding areas<br>
                            On-site networking support available
                        </p>
                    </div>
                </div>
            </div>
        </section>
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
                    <h3>Technical Services</h3>
                    <a href="#services">Computer Repair</a><br>
                    <a href="#services">Network Configuration</a><br>
                    <a href="#services">IT Consultation & Troubleshooting</a><br>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="#home">Home</a><br>
                    <a href="#services">Services</a><br>
                    <a href="#specialization">Our Focus</a><br>
                    <a href="#how-it-works">Process</a><br>
                    <a href="#contact">Contact</a><br>
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
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add active class to current section in navigation
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-menu a');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                const sectionHeight = section.clientHeight;
                if(scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if(link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if(entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        }, observerOptions);

        // Observe all elements with animation class
        document.querySelectorAll('.animate-fade-in-up').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>