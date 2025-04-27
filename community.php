<?php
session_start();
require_once 'config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $mood_rating = intval($_POST['mood_rating'] ?? 0);

    if (!empty($message) && $mood_rating >= 1 && $mood_rating <= 5) {
        // Insert message with is_approved set to TRUE
        $stmt = $pdo->prepare("INSERT INTO anonymous_feeds (message, mood_rating, is_approved) VALUES (?, ?, TRUE)");
        $stmt->execute([$message, $mood_rating]);
        $success_message = "Your message has been shared with the community.";
        
        // Add a small delay to simulate processing
        usleep(2000000); // 2 seconds delay
    } else {
        $error_message = "Please provide a valid message and mood rating.";
    }
}

// Fetch all approved messages
$stmt = $pdo->prepare("SELECT * FROM anonymous_feeds WHERE is_approved = TRUE ORDER BY created_at DESC");
$stmt->execute();
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - MindCare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .community-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 4rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .community-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../images/community-pattern.png') repeat;
            opacity: 0.1;
        }

        .community-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .message-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--light-bg);
            border-radius: 8px;
            resize: vertical;
            min-height: 150px;
            font-family: inherit;
        }

        .mood-rating {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .mood-rating input[type="radio"] {
            display: none;
        }

        .mood-rating label {
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: var(--light-bg);
            transition: all 0.3s ease;
        }

        .mood-rating input[type="radio"]:checked + label {
            background: var(--primary-color);
            color: white;
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .messages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .message-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .message-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-color);
        }

        .message-mood {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .message-text {
            color: var(--text-color);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .message-date {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .community-container {
                padding: 1rem;
            }

            .messages-grid {
                grid-template-columns: 1fr;
            }
        }

        .anonymous-section {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 2px solid var(--light-bg);
        }

        .anonymous-section h2 {
            color: var(--text-color);
            margin-bottom: 1rem;
            text-align: center;
            font-size: 2rem;
        }

        .anonymous-description {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .anonymous-messages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .anonymous-message-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--light-bg);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .anonymous-message-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .anonymous-message-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--secondary-color);
        }

        .anonymous-mood {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 1rem;
            margin-bottom: 1rem;
            color: white;
            font-weight: 500;
        }

        .anonymous-text {
            color: var(--text-color);
            line-height: 1.8;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-style: italic;
        }

        .anonymous-date {
            color: var(--text-light);
            font-size: 0.9rem;
            text-align: right;
            font-style: italic;
        }

        .no-messages {
            text-align: center;
            padding: 2rem;
            background: var(--light-bg);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .anonymous-messages-grid {
                grid-template-columns: 1fr;
            }
            
            .anonymous-message-card {
                padding: 1.5rem;
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
                    <li><a href="self-care.php">Self-Care</a></li>
                    <li><a href="community.php" class="active">Community</a></li>
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
        <section class="community-hero">
            <h1>Community Support</h1>
            <p>Share your thoughts and connect with others</p>
        </section>

        <div class="community-container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form class="message-form" method="POST" action="">
                <div class="form-group">
                    <label for="message">Your Anonymous Message</label>
                    <textarea id="message" name="message" required placeholder="Share your thoughts..."></textarea>
                </div>

                <div class="form-group">
                    <label>How are you feeling?</label>
                    <div class="mood-rating">
                        <input type="radio" id="mood1" name="mood_rating" value="1" required>
                        <label for="mood1">😢</label>
                        <input type="radio" id="mood2" name="mood_rating" value="2">
                        <label for="mood2">😔</label>
                        <input type="radio" id="mood3" name="mood_rating" value="3">
                        <label for="mood3">😐</label>
                        <input type="radio" id="mood4" name="mood_rating" value="4">
                        <label for="mood4">😊</label>
                        <input type="radio" id="mood5" name="mood_rating" value="5">
                        <label for="mood5">😄</label>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Share Anonymously
                </button>
            </form>

            <div class="anonymous-section">
                <h2>Anonymous Messages</h2>
                <p class="anonymous-description">Read messages from others who have shared their thoughts anonymously. Your identity remains private.</p>
                
                <div class="anonymous-messages-grid">
                    <?php if (empty($messages)): ?>
                        <div class="no-messages">
                            <i class="fas fa-comments" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>No messages yet. Be the first to share!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="anonymous-message-card">
                                <div class="anonymous-mood" style="background: <?php 
                                    echo match($message['mood_rating']) {
                                        1 => '#ff6b6b',
                                        2 => '#ffa07a',
                                        3 => '#ffd700',
                                        4 => '#90ee90',
                                        5 => '#4caf50',
                                        default => '#f0f0f0'
                                    };
                                ?>">
                                    <?php echo str_repeat('⭐', $message['mood_rating']); ?>
                                </div>
                                <p class="anonymous-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                <div class="anonymous-date">
                                    <?php echo date('M j, Y', strtotime($message['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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

        // Loading animation for message submission
        const messageForm = document.querySelector('.message-form');
        const submitBtn = document.querySelector('.submit-btn');
        const messagesGrid = document.querySelector('.anonymous-messages-grid');
        const noMessagesDiv = document.querySelector('.no-messages');

        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sharing...';
            
            // Submit the form after a short delay
            setTimeout(() => {
                messageForm.submit();
            }, 2000);
        });

        // Function to create a new message card
        function createMessageCard(message, moodRating, date) {
            const card = document.createElement('div');
            card.className = 'anonymous-message-card';
            
            const moodColors = {
                1: '#ff6b6b',
                2: '#ffa07a',
                3: '#ffd700',
                4: '#90ee90',
                5: '#4caf50'
            };
            
            card.innerHTML = `
                <div class="anonymous-mood" style="background: ${moodColors[moodRating]}">
                    ${'⭐'.repeat(moodRating)}
                </div>
                <p class="anonymous-text">${message}</p>
                <div class="anonymous-date">${date}</div>
            `;
            
            return card;
        }

        // If there's a success message, it means a new message was added
        <?php if (isset($success_message) && !empty($messages)): ?>
            // Remove the "no messages" div if it exists
            if (noMessagesDiv) {
                noMessagesDiv.remove();
            }
            
            // Create and add the new message card
            const newMessage = <?php echo json_encode(end($messages)); ?>;
            const newCard = createMessageCard(
                newMessage.message,
                newMessage.mood_rating,
                new Date(newMessage.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
            );
            
            // Add the new card with a fade-in animation
            newCard.style.opacity = '0';
            messagesGrid.insertBefore(newCard, messagesGrid.firstChild);
            
            // Trigger the animation
            setTimeout(() => {
                newCard.style.transition = 'opacity 0.5s ease';
                newCard.style.opacity = '1';
            }, 100);
        <?php endif; ?>
    </script>
</body>
</html> 