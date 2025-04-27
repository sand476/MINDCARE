<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Add this function at the top of the file after the session start
function getRandomSticker() {
    $stickers = [
        '😊', '🌟', '🎨', '🎭', '🎪', '🎯', '🎲', '🎮', '🎵', '🎸',
        '🎹', '🎺', '🎻', '🎼', '🎧', '🎤', '🎬', '🎨', '🎭', '🎪',
        '🎯', '🎲', '🎮', '🎵', '🎸', '🎹', '🎺', '🎻', '🎼', '🎧',
        '🎤', '🎬', '🎨', '🎭', '🎪', '🎯', '🎲', '🎮', '🎵', '🎸'
    ];
    return $stickers[array_rand($stickers)];
}

// Handle post submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_post':
                $content = htmlspecialchars(trim($_POST['content']), ENT_QUOTES, 'UTF-8');
                $image_path = null;

                if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/posts/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($file_extension, $allowed_extensions)) {
                        $file_name = uniqid() . '.' . $file_extension;
                        $target_path = $upload_dir . $file_name;

                        if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_path)) {
                            $image_path = $target_path;
                        } else {
                            $error = "Failed to upload image.";
                        }
                    } else {
                        $error = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
                    }
                }

                if (empty($error)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$user_id, $content, $image_path]);
                        $success = "Post created successfully!";
                    } catch (PDOException $e) {
                        $error = "Error creating post: " . $e->getMessage();
                    }
                }
                break;

            case 'like_post':
                $post_id = $_POST['post_id'];
                try {
                    $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
                    $stmt->execute([$user_id, $post_id]);
                    echo json_encode(['success' => true]);
                    exit;
                } catch (PDOException $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                    exit;
                }
                break;

            case 'add_comment':
                $post_id = $_POST['post_id'];
                $comment = htmlspecialchars(trim($_POST['comment']), ENT_QUOTES, 'UTF-8');
                try {
                    $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $post_id, $comment]);
                    echo json_encode(['success' => true]);
                    exit;
                } catch (PDOException $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                    exit;
                }
                break;
        }
    }
}

// Get user info
try {
    $stmt = $pdo->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching user data: " . $e->getMessage();
}

// Get feed posts with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$posts = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name, u.profile_picture, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user_id, $limit, $offset]);
    $posts = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error fetching posts: " . $e->getMessage();
}

// Get stories (last 24 hours)
try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.name, u.profile_picture 
        FROM stories s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching stories: " . $e->getMessage();
}

// Get mood entries
$mood_entries = [];
try {
    $stmt = $pdo->prepare("
        SELECT mt.*, u.name, u.profile_picture 
        FROM mood_tracking mt 
        JOIN users u ON mt.user_id = u.id 
        ORDER BY mt.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $mood_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching mood entries: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Feed - MindCare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .feed-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .create-post {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .post-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .post-input {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            resize: vertical;
            min-height: 100px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .post-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .image-upload {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            display: none;
            border-radius: var(--border-radius);
            object-fit: cover;
        }

        .post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .upload-btn {
            background: var(--light-bg);
            color: var(--text-color);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .upload-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .post-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .post-btn:hover {
            background: var(--primary-dark);
        }

        .stories-container {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding: 1rem 0;
            margin-bottom: 2rem;
            scrollbar-width: thin;
        }

        .story {
            flex: 0 0 auto;
            width: 100px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .story:hover {
            transform: scale(1.05);
        }

        .story-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            padding: 2px;
        }

        .story-name {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .feed-posts {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .post {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .post-profile-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .sticker {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .post-user-info {
            flex: 1;
        }

        .post-user-name {
            font-weight: bold;
            color: var(--text-color);
            margin: 0;
        }

        .post-time {
            font-size: 0.8rem;
            color: var(--text-light);
            margin: 0;
        }

        .post-content {
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .post-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .post-actions {
            display: flex;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: var(--light-bg);
            color: var(--primary-color);
        }

        .like-btn.liked {
            color: #ff4757;
        }

        .comments-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .comment {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .comment-content {
            background: var(--light-bg);
            padding: 0.8rem;
            border-radius: var(--border-radius);
            flex: 1;
        }

        .comment-form {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .comment-input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
        }

        .load-more {
            text-align: center;
            margin: 2rem 0;
        }

        .load-more-btn {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .load-more-btn:hover {
            background: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .feed-container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }

            .create-post {
                padding: 1rem;
            }

            .post {
                padding: 1rem;
            }

            .post-actions {
                flex-wrap: wrap;
            }

            .action-btn {
                flex: 1;
                justify-content: center;
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
                    <li><a href="community.php">Community</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="feed.php" class="active">Feed</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>

    <main>
        <div class="feed-container">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="create-post">
                <form class="post-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_post">
                    <textarea class="post-input" name="content" placeholder="What's on your mind?" required></textarea>
                    <div class="image-upload">
                        <label class="upload-btn">
                            <i class="fas fa-image"></i> Add Image
                            <input type="file" name="post_image" accept="image/*" style="display: none;">
                        </label>
                        <img class="image-preview" src="#" alt="Preview">
                    </div>
                    <div class="post-actions">
                        <button type="submit" class="post-btn">Post</button>
                    </div>
                </form>
            </div>

            <div class="stories-container">
                <?php foreach ($stories as $story): ?>
                    <div class="story">
                        <img src="<?php echo htmlspecialchars($story['profile_picture']); ?>" alt="Story" class="story-image">
                        <div class="story-name"><?php echo htmlspecialchars($story['name']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="feed-posts" id="feed-posts">
                <?php foreach ($posts as $post): ?>
                    <div class="post" data-post-id="<?php echo $post['id']; ?>">
                        <div class="post-header">
                            <div class="post-profile-photo">
                                <div class="sticker"><?php echo getRandomSticker(); ?></div>
                            </div>
                            <div class="post-user-info">
                                <h3 class="post-user-name"><?php echo htmlspecialchars($post['name']); ?></h3>
                                <p class="post-time"><?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="post-content">
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            <?php if ($post['image']): ?>
                                <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="post-image">
                            <?php endif; ?>
                        </div>
                        <div class="post-actions">
                            <button class="like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                    data-post-id="<?php echo $post['id']; ?>">
                                <i class="fas fa-heart"></i>
                                <span class="like-count"><?php echo $post['like_count']; ?></span>
                            </button>
                            <button class="comment-btn" data-post-id="<?php echo $post['id']; ?>">
                                <i class="fas fa-comment"></i>
                                <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                            </button>
                        </div>
                        <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                            <div class="comments-list"></div>
                            <form class="comment-form" data-post-id="<?php echo $post['id']; ?>">
                                <input type="text" class="comment-input" placeholder="Write a comment..." required>
                                <button type="submit" class="action-btn">Post</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="load-more">
                <button class="load-more-btn" id="load-more">Load More Posts</button>
            </div>
        </div>
    </main>

    <div class="footer-bottom">
        <p>&copy; 2025 MindCare. All rights reserved to TEAM SHOURYANGA</p>
    </div>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                const icon = mobileMenuBtn.querySelector('i');
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            });
        }

        // Image Preview
        const imageInput = document.querySelector('input[name="post_image"]');
        const imagePreview = document.querySelector('.image-preview');

        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Like Button
        const likeButtons = document.querySelectorAll('.like-btn');
        likeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.dataset.postId;
                const likeCount = this.querySelector('.like-count');
                
                fetch('feed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=like_post&post_id=${postId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.classList.toggle('liked');
                        const currentCount = parseInt(likeCount.textContent);
                        likeCount.textContent = this.classList.contains('liked') ? currentCount + 1 : currentCount - 1;
                    }
                });
            });
        });

        // Comments
        const commentButtons = document.querySelectorAll('.comment-btn');
        commentButtons.forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.dataset.postId;
                const commentsSection = document.getElementById(`comments-${postId}`);
                commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
            });
        });

        // Comment Form
        const commentForms = document.querySelectorAll('.comment-form');
        commentForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                const commentInput = this.querySelector('.comment-input');
                const comment = commentInput.value;

                fetch('feed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add_comment&post_id=${postId}&comment=${encodeURIComponent(comment)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const commentsList = this.previousElementSibling;
                        const newComment = document.createElement('div');
                        newComment.className = 'comment';
                        newComment.innerHTML = `
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" class="post-user-image">
                            <div class="comment-content">
                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                <p>${comment}</p>
                            </div>
                        `;
                        commentsList.appendChild(newComment);
                        commentInput.value = '';
                    }
                });
            });
        });

        // Load More Posts
        let currentPage = <?php echo $page; ?>;
        const loadMoreBtn = document.getElementById('load-more');
        const feedPosts = document.getElementById('feed-posts');

        loadMoreBtn.addEventListener('click', function() {
            currentPage++;
            fetch(`feed.php?page=${currentPage}`)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newPosts = doc.querySelectorAll('.post');
                    
                    if (newPosts.length === 0) {
                        loadMoreBtn.style.display = 'none';
                    } else {
                        newPosts.forEach(post => {
                            feedPosts.appendChild(post);
                        });
                    }
                });
        });
    </script>
</body>
</html> 