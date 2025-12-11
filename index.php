<?php
include 'includes/config.php';
include 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRAD Servicing : Hire, Succeed, Repeat</title>
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
            color: #fff;
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(80, 208, 224, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(80, 208, 224, 0.1) 0%, transparent 20%);
                background-color: black;
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
            padding: 28px 0;
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

        .brand-text h1 {
            font-size: 1.8rem;
            color: #50d0e0;
            margin-bottom: 5px;
        }

        .brand-text .tagline {
            font-size: 0.9rem;
            color: #aee2ff;
            font-style: italic;
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
            background: linear-gradient(135deg, rgba(10, 10, 42, 0.8) 0%, rgba(26, 26, 58, 0.9) 100%);
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
            background: url('assets/images/bg2.jpg');
            opacity: 0.1;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
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

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
        }

        /* Features Section */
        .features-section {
            padding: 100px 0;
            background: rgba(10, 10, 42, 0.7);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: #50d0e0;
            margin-bottom: 60px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            border: 1px solid rgba(80, 208, 224, 0.2);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: #50d0e0;
            box-shadow: 0 10px 30px rgba(80, 208, 224, 0.2);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #50d0e0;
        }

        .feature-title {
            font-size: 1.5rem;
            color: #fff;
            margin-bottom: 15px;
        }

        .feature-description {
            color: #aee2ff;
            line-height: 1.6;
        }

        /* Services Section */
        .services-section {
            padding: 100px 0;
            background: linear-gradient(135deg, rgba(26, 26, 58, 0.9) 0%, rgba(10, 10, 42, 0.8) 100%);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
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
        }

        .service-card:hover {
            border-color: #50d0e0;
            transform: translateY(-5px);
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

        .service-price {
            font-size: 1.2rem;
            color: #4CAF50;
            font-weight: bold;
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
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .service-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .service-duration {
            color: #aaa;
            font-size: 0.9rem;
        }

        /* How It Works */
        .process-section {
            padding: 100px 0;
            background: rgba(10, 10, 42, 0.7);
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

        /* Footer */
        .main-footer {
            background: rgba(10, 10, 42, 0.9);
            padding: 60px 0 30px;
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
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#features">Why DRAD</a></li>
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
                    <h1 class="hero-title">Professional Services at Your Fingertips</h1>
                    <p class="hero-subtitle">
                        DRAD Servicing connects you with skilled professionals for all your service needs. 
                        Experience quality, reliability, and efficiency in every service.
                    </p>
                    
                    <div class="hero-buttons">
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="register.php" class="btn btn-primary">Get Started Free</a>
                            <a href="#services" class="btn btn-secondary">Explore Services</a>
                        <?php else: ?>
                            <a href="customer/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                            <a href="customer/book-service.php" class="btn btn-secondary">Book Service</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features-section">
            <div class="container">
                <h2 class="section-title">Why Choose DRAD?</h2>
                <div class="features-grid">
                    <div class="feature-card animate-fade-in-up" style="animation-delay: 0.1s;">
                        <div class="feature-icon">👨‍🔧</div>
                        <h3 class="feature-title">Hire Experts</h3>
                        <p class="feature-description">
                            Connect with verified professionals who are experts in their fields. 
                            We ensure quality service with every hire.
                        </p>
                    </div>
                    
                    <div class="feature-card animate-fade-in-up" style="animation-delay: 0.2s;">
                        <div class="feature-icon">✅</div>
                        <h3 class="feature-title">Guaranteed Success</h3>
                        <p class="feature-description">
                            Our satisfaction guarantee ensures you get the results you expect. 
                            Quality service, every time.
                        </p>
                    </div>
                    
                    <div class="feature-card animate-fade-in-up" style="animation-delay: 0.3s;">
                        <div class="feature-icon">🔄</div>
                        <h3 class="feature-title">Repeat & Save</h3>
                        <p class="feature-description">
                            Easy rebooking with trusted professionals. Build relationships 
                            with service providers you love.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="services-section">
            <div class="container">
                <h2 class="section-title">Our Services</h2>
                
                <?php
                $sql = "SELECT * FROM services WHERE status='available' LIMIT 6";
                $result = mysqli_query($conn, $sql);
                
                if(mysqli_num_rows($result) > 0): ?>
                    <div class="services-grid">
                        <?php 
                        $delay = 0;
                        while($row = mysqli_fetch_assoc($result)): 
                            $delay += 0.1;
                        ?>
                            <div class="service-card animate-fade-in-up" style="animation-delay: <?php echo $delay; ?>s;">
                                <div class="service-header">
                                    <h3 class="service-name"><?php echo $row['service_name']; ?></h3>
                                    <span class="service-price">₱<?php echo $row['price']; ?></span>
                                </div>
                                
                                <span class="service-category"><?php echo $row['category']; ?></span>
                                
                                <p class="service-description"><?php echo $row['description']; ?></p>
                                
                                <div class="service-footer">
                                    <span class="service-duration">⏱️ <?php echo $row['estimated_duration']; ?></span>
                                    
                                    <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
                                        <a href="customer/book-service.php?id=<?php echo $row['service_id']; ?>" 
                                           class="btn btn-primary">Hire Now</a>
                                    <?php elseif(isset($_SESSION['user_id'])): ?>
                                        <button class="btn btn-secondary" disabled>Customer Only</button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-primary">Login to Hire</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php if(mysqli_num_rows($result) >= 6): ?>
                        <div style="text-align: center; margin-top: 40px;">
                            <a href="#services" class="btn btn-secondary">View All Services</a>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 0;">
                        <p style="font-size: 1.2rem; color: #aee2ff;">No services available at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- How It Works -->
        <section id="how-it-works" class="process-section">
            <div class="container">
                <h2 class="section-title">How DRAD Servicing Works</h2>
                
                <div class="process-steps">
                    <div class="process-step animate-fade-in-up" style="animation-delay: 0.1s;">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Register Account</h3>
                        <p class="step-description">
                            Create your free DRAD account in minutes. No hidden fees, 
                            just access to quality services.
                        </p>
                    </div>
                    
                    <div class="process-step animate-fade-in-up" style="animation-delay: 0.2s;">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Choose Service</h3>
                        <p class="step-description">
                            Browse our catalog of professional services and select 
                            what you need.
                        </p>
                    </div>
                    
                    <div class="process-step animate-fade-in-up" style="animation-delay: 0.3s;">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Book & Schedule</h3>
                        <p class="step-description">
                            Fill out the booking form and choose your preferred 
                            schedule.
                        </p>
                    </div>
                    
                    <div class="process-step animate-fade-in-up" style="animation-delay: 0.4s;">
                        <div class="step-number">4</div>
                        <h3 class="step-title">Get Service</h3>
                        <p class="step-description">
                            Our professional handles your needs efficiently. 
                            Pay in person after service completion.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="features-section">
            <div class="container">
                <h2 class="section-title">Contact Us</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">📧</div>
                        <h3 class="feature-title">Email Support</h3>
                        <p class="feature-description">
                            support@dradservicing.com<br>
                            We respond within 24 hours
                        </p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📞</div>
                        <h3 class="feature-title">Phone Support</h3>
                        <p class="feature-description">
                            (02) 1234-5678<br>
                            Mon-Sat: 8:00 AM - 8:00 PM
                        </p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📍</div>
                        <h3 class="feature-title">Visit Us</h3>
                        <p class="feature-description">
                            DRAD Building<br>
                            Metro Manila, Philippines
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
                    <p>Your trusted partner for professional services. Hire skilled professionals, succeed with quality results, and repeat with confidence.</p>
                    <p class="tagline" style="color: #50d0e0; margin-top: 10px;">Hire, Succeed, Repeat.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="#home">Home</a></p>
                    <p><a href="#services">Services</a></p>
                    <p><a href="#how-it-works">How It Works</a></p>
                    <p><a href="#features">Why Choose Us</a></p>
                    <p><a href="#contact">Contact</a></p>
                </div>
                
                <div class="footer-section">
                    <h3>Account</h3>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <p><a href="customer/dashboard.php">Dashboard</a></p>
                        <p><a href="customer/tickets.php">My Tickets</a></p>
                        <p><a href="customer/profile.php">My Profile</a></p>
                        <p><a href="logout.php">Logout</a></p>
                    <?php else: ?>
                        <p><a href="login.php">Login</a></p>
                        <p><a href="register.php">Register</a></p>
                        <p><a href="login.php">Forgot Password</a></p>
                    <?php endif; ?>
                </div>
                
                <div class="footer-section">
                    <h3>Legal</h3>
                    <p><a href="#">Terms of Service</a></p>
                    <p><a href="#">Privacy Policy</a></p>
                    <p><a href="#">Cookie Policy</a></p>
                    <p><a href="#">Service Agreement</a></p>
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