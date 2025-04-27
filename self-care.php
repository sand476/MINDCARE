<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Care - MindCare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Self-Care Specific Styles */
        .self-care-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 4rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .self-care-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../images/self-care-pattern.png') repeat;
            opacity: 0.1;
        }

        .self-care-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .self-care-hero p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .self-care-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .self-care-card {
            background: var(--white);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .self-care-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .self-care-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-color);
        }

        .self-care-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .self-care-card h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .self-care-card p {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .self-care-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .self-care-btn {
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .self-care-btn.primary {
            background: var(--primary-color);
            color: white;
        }

        .self-care-btn.secondary {
            background: var(--light-bg);
            color: var(--text-color);
        }

        .self-care-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .self-care-btn i {
            font-size: 1.1rem;
        }

        .self-care-section {
            padding: 4rem 0;
            background: var(--light-bg);
        }

        .self-care-section h2 {
            text-align: center;
            margin-bottom: 3rem;
            color: var(--text-color);
        }

        @media (max-width: 768px) {
            .self-care-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .self-care-hero h1 {
                font-size: 2rem;
            }

            .self-care-hero p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
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
                    <li><a href="index.php">Home</a></li>
                    <li><a href="resources.php">Resources</a></li>
                    <li><a href="self-care.php" class="active">Self-Care</a></li>
                    <li><a href="community.php">Community</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="feed.php">Feed</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>

    <main>
        <section class="self-care-hero">
            <h1>Self-Care Activities</h1>
            <p>Take time for yourself with these mindful activities</p>
        </section>

        <section class="self-care-section">
            <h2>Choose Your Activity</h2>
            <div class="self-care-grid">
                <div class="self-care-card">
                    <div class="self-care-icon">
                        <i class="fas fa-wind"></i>
                    </div>
                    <h3>Breathing Exercises</h3>
                    <p>Practice mindful breathing techniques to reduce stress and anxiety.</p>
                    <div class="self-care-actions">
                        <a href="breathing.html" class="self-care-btn primary">
                            <i class="fas fa-play"></i> Start
                        </a>
                        <button class="self-care-btn secondary">
                            <i class="fas fa-info-circle"></i> Learn More
                        </button>
                    </div>
                </div>

                <div class="self-care-card">
                    <div class="self-care-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3>Color Matching</h3>
                    <p>Improve focus and mindfulness through color matching exercises.</p>
                    <div class="self-care-actions">
                        <a href="color-match.html" class="self-care-btn primary">
                            <i class="fas fa-play"></i> Start
                        </a>
                        <button class="self-care-btn secondary">
                            <i class="fas fa-info-circle"></i> Learn More
                        </button>
                    </div>
                </div>

                <div class="self-care-card">
                    <div class="self-care-icon">
                        <i class="fas fa-puzzle-piece"></i>
                    </div>
                    <h3>Puzzle Games</h3>
                    <p>Challenge your mind with relaxing puzzle games.</p>
                    <div class="self-care-actions">
                        <a href="puzzle.html" class="self-care-btn primary">
                            <i class="fas fa-play"></i> Start
                        </a>
                        <button class="self-care-btn secondary">
                            <i class="fas fa-info-circle"></i> Learn More
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About MindCare</h3>
                    <p>Your mental health companion for a better tomorrow.</p>
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

        // Add hover effect to self-care cards
        document.querySelectorAll('.self-care-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html> 