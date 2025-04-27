<?php
session_start();
require_once 'config.php';

// Get user's name if logged in
$user_name = '';
if(isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if($user) {
            $user_name = $user['name'];
        }
    } catch(PDOException $e) {
        // Handle error silently
    }
}
?>
<!DOCTYPE html>
<html ng-app="mindApp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindCare - Mental Health Support</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AngularJS -->
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js"></script>
    <script src="js/app.js"></script>
    <style>
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
            background: url('https://images.unsplash.com/photo-1506126613408-eca07ce68773?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80') center/cover;
        }
        
        .feature-link {
            text-decoration: none;
            color: inherit;
            display: block;
            padding: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-link:hover {
            transform: translateY(-5px);
        }
        
        .feature-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body ng-controller="MainController">
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <a href="index.php" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <span class="logo-text">MindCare</span>
                </a>
                <ul class="nav-links">
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="resources.php">Resources</a></li>
                    <li><a href="self-care.php">Self-Care</a></li>
                    <li><a href="community.php">Community</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="feed.php">Feed</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="signup.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1>
                    <?php if($user_name): ?>
                        Welcome, <?php echo htmlspecialchars($user_name); ?>!
                    <?php else: ?>
                        Welcome to MindCare
                    <?php endif; ?>
                </h1>
                <p>Your journey to better mental health starts here</p>
                <div class="hero-buttons">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="login.php" class="btn btn-primary">Get Started</a>
                        <a href="about.html" class="btn btn-secondary">Learn More</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                        <a href="feed.php" class="btn btn-secondary">View Feed</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="features">
            <div class="container">
                <h2>Why Choose MindCare?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <a href="https://www.thelivelovelaughfoundation.org/find-help/therapist" class="feature-link">
                            <i class="fas fa-brain"></i>
                            <h3>Professional Support</h3>
                            <p>Connect with licensed therapists and mental health professionals</p>
                        </a>
                    </div>
                    <div class="feature-card">
                        <a href="https://www.helpguide.org/mental-health/stress/social-support-for-stress-relief" class="feature-link">
                            <i class="fas fa-users"></i>
                            <h3>Community</h3>
                            <p>Join a supportive community of people on similar journeys</p>
                        </a>
                    </div>
                    <div class="feature-card">
                        <a href="https://health.google/mental-health/" class="feature-link">
                            <i class="fas fa-book"></i>
                            <h3>Resources</h3>
                            <p>Access helpful articles, videos, and tools for mental wellness</p>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="testimonials">
            <div class="container">
                <h2>What Our Community Says</h2>
                <div class="testimonials-grid">
                    <div class="testimonial-card">
                        <p>"MindCare has been a game-changer for my mental health journey."</p>
                        <span class="author">- Sarah M.</span>
                    </div>
                    <div class="testimonial-card">
                        <p>"The resources and community support have been invaluable."</p>
                        <span class="author">- John D.</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>MindCare</h3>
                    <p>Supporting mental health and well-being for everyone.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="resources.php">Resources</a></li>
                        <li><a href="self-care.php">Self-Care</a></li>
                        <li><a href="community.php">Community</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p>Email: support@mindcare.com</p>
                    <p>Phone: 9398598790</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 MindCare. All rights reserved to TEAM SHOURYANGA</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            const icon = mobileMenuBtn.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });

        // Smooth Scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Parallax Effect
        window.addEventListener('scroll', () => {
            const hero = document.querySelector('.hero');
            const scrolled = window.pageYOffset;
            hero.style.backgroundPositionY = -(scrolled * 0.5) + 'px';
        });

        // Animate Features on Scroll
        const observerOptions = {
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card').forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html> 