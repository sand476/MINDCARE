<?php
session_start();
require_once 'config.php';

// Fetch resources from database
try {
    $stmt = $pdo->query("SELECT * FROM resources ORDER BY created_at DESC");
    $resources = $stmt->fetchAll();
} catch (PDOException $e) {
    $resources = [];
    error_log("Error fetching resources: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - MindCare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <li><a href="resources.php" class="active">Resources</a></li>
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
        <div class="container">
            <section class="resources-header">
                <h1>Mental Health Resources</h1>
                <p>Access helpful articles, videos, and tools to support your mental health journey.</p>
            </section>

            <section class="resources-grid">
                <?php foreach ($resources as $resource): ?>
                    <div class="resource-card" data-category="<?php echo htmlspecialchars($resource['category']); ?>">
                        <div class="resource-icon">
                            <?php if ($resource['category'] === 'articles'): ?>
                                <i class="fas fa-book"></i>
                            <?php elseif ($resource['category'] === 'videos'): ?>
                                <i class="fas fa-video"></i>
                            <?php else: ?>
                                <i class="fas fa-tools"></i>
                            <?php endif; ?>
                        </div>
                        <div class="resource-content">
                            <h3><?php echo htmlspecialchars($resource['title']); ?></h3>
                            <p><?php echo htmlspecialchars($resource['content']); ?></p>
                            <div class="resource-meta">
                                <span class="category"><?php echo htmlspecialchars($resource['category']); ?></span>
                                <span class="date"><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="resource-categories">
                <h2>Resource Categories</h2>
                <div class="category-grid">
                    <a href="https://telanganatoday.com/heavy-news-consumption-triggers-stress-anxiety-poor-physical-health?utm_source=chatgpt.com" class="category-card">
                        <i class="fas fa-book"></i>
                        <h3>Articles</h3>
                        <p>Read informative articles about mental health and wellness.</p>
                    </a>
                    <a href="https://www.youtube.com/watch?v=ZvTL2Z1Jm9A" class="category-card">
                        <i class="fas fa-video"></i>
                        <h3>Videos</h3>
                        <p>Watch educational videos and guided exercises.</p>
                    </a>
                    <a href="https://www.youtube.com/watch?v=iS2h9BbB1dc" class="category-card">
                        <i class="fas fa-podcast"></i>
                        <h3>Podcasts</h3>
                        <p>Listen to expert discussions and personal stories.</p>
                    </a>
                </div>
            </section>
        </div>
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
    </script>
</body>
</html> 