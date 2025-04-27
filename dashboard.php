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

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if ($file['error'] === UPLOAD_ERR_OK) {
        if (!in_array($file['type'], $allowed_types)) {
            $error = "Only JPG, PNG, and GIF files are allowed.";
        } elseif ($file['size'] > $max_size) {
            $error = "File size must be less than 5MB.";
        } else {
            $upload_dir = 'uploads/profile_photos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Update user's profile photo in database
                try {
                    $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                    $stmt->execute([$upload_path, $user_id]);
                    $success = "Profile photo updated successfully!";
                    
                    // Update the user array with new photo path
                    $user['profile_photo'] = $upload_path;
                } catch (PDOException $e) {
                    $error = "Error updating profile photo: " . $e->getMessage();
                }
            } else {
                $error = "Error uploading file.";
            }
        }
    } else {
        $error = "Error uploading file: " . $file['error'];
    }
}

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching user data: " . $e->getMessage();
}

// Get user statistics
try {
    // Get total posts
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_posts FROM posts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_posts = $stmt->fetch()['total_posts'];

    // Get total likes received
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_likes 
        FROM likes l 
        JOIN posts p ON l.post_id = p.id 
        WHERE p.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $total_likes = $stmt->fetch()['total_likes'];

    // Get mood tracking data
    $stmt = $pdo->prepare("
        SELECT mood_rating, COUNT(*) as count 
        FROM mood_tracking 
        WHERE user_id = ? 
        GROUP BY mood_rating 
        ORDER BY mood_rating
    ");
    $stmt->execute([$user_id]);
    $mood_data = $stmt->fetchAll();

    // Get recent posts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_posts = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Error fetching statistics: " . $e->getMessage();
}

// Get user's recent journal entries
try {
    $stmt = $pdo->prepare("
        SELECT * FROM journal_entries 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $recent_journals = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching journal entries: " . $e->getMessage();
}

// Get user's recent mood entries
try {
    $stmt = $pdo->prepare("
        SELECT * FROM mood_tracking 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_moods = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching mood entries: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html ng-app="mindApp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MindApp</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="js/navigation.js" defer></script>
    <script src="js/image-generator.js" defer></script>
    <!-- AngularJS -->
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js"></script>
    <script src="js/app.js"></script>
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .welcome-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-pic {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 1rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .recent-posts {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .post-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .post-item:last-child {
            border-bottom: none;
        }

        .post-content {
            margin-bottom: 0.5rem;
        }

        .post-stats {
            display: flex;
            gap: 1rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .mood-tracker {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .mood-chart {
            height: 200px;
            margin-top: 1rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: var(--card-bg);
            border: none;
            border-radius: var(--border-radius);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .action-btn i {
            font-size: 1.5rem;
            color: var(--accent-color);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px var(--card-shadow);
        }

        .action-btn:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .journal-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-top: 2rem;
        }

        .journal-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .journal-date {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .mood-options {
            display: flex;
            justify-content: space-around;
            margin: 2rem 0;
        }

        .mood-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            border: none;
            background: none;
            cursor: pointer;
            transition: transform 0.3s ease;
            border-radius: 8px;
        }

        .mood-btn:hover {
            transform: scale(1.1);
            background: #f0f0f0;
        }

        .mood-btn i {
            font-size: 2rem;
        }

        .mood-notes {
            margin: 1rem 0;
        }

        .mood-notes textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            min-height: 100px;
        }

        .mood-submit {
            text-align: center;
            margin-top: 1rem;
        }

        .mood-submit button {
            padding: 0.8rem 2rem;
            font-size: 1rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            max-width: 500px;
            margin: 2rem auto;
            position: relative;
        }

        .close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
        }

        #journalForm textarea {
            width: 100%;
            height: 200px;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            resize: vertical;
        }

        .meditation-games {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin: 2rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .game-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .game-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #4a90e2;
        }

        .game-card h3 {
            margin: 0.5rem 0;
            color: #333;
        }

        .game-card p {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .games-grid {
                grid-template-columns: 1fr;
            }
        }

        .calendar-section {
            margin: 2rem 0;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .calendar-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .calendar-nav-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--text-light);
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .calendar-nav-btn:hover {
            background: #f0f0f0;
            color: var(--primary-color);
        }

        .calendar-grid {
            display: grid;
            grid-template-rows: auto 1fr;
            gap: 0.5rem;
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: bold;
            color: var(--text-light);
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }

        .calendar-day {
            aspect-ratio: 1;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid #eee;
        }

        .calendar-day:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .calendar-day.today {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .calendar-day.has-entries {
            background: #f8f9fa;
        }

        .day-number {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .entries-preview {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .entry-time {
            margin-bottom: 0.2rem;
            padding: 0.2rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .entry-time i {
            font-size: 0.9rem;
        }

        .calendar-day.empty {
            background: #f9f9f9;
            cursor: default;
            border: none;
        }

        /* Mood rating colors */
        .color-1 { color: #ff6b6b; }
        .color-2 { color: #ffa502; }
        .color-3 { color: #ffd32a; }
        .color-4 { color: #51cf66; }
        .color-5 { color: #20c997; }

        @media (max-width: 768px) {
            .calendar-day {
                padding: 0.3rem;
            }
            
            .day-number {
                font-size: 0.9rem;
            }
            
            .entries-preview {
                font-size: 0.7rem;
            }
        }

        .profile-photo-section {
            position: relative;
            margin-bottom: 2rem;
        }

        .profile-photo-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }

        .profile-photo {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-photo-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-photo-upload:hover {
            transform: scale(1.1);
            background: var(--primary-dark);
        }

        .profile-photo-upload input[type="file"] {
            display: none;
        }

        .profile-photo-upload i {
            font-size: 1.2rem;
        }

        .upload-progress {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .upload-progress.active {
            opacity: 1;
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
                    <li><a href="index.php">Home</a></li>
                    <li><a href="resources.php">Resources</a></li>
                    <li><a href="self-care.php">Self-Care</a></li>
                    <li><a href="community.php">Community</a></li>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
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
        <div class="dashboard-container">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-header">
                <div class="welcome-section">
                    <div class="profile-photo-container">
                        <img src="<?php echo $user['profile_photo'] ?? 'images/default-avatar.png'; ?>" 
                             alt="Profile Photo" 
                             class="profile-photo"
                             id="profilePhoto">
                        <label class="profile-photo-upload" for="photoUpload">
                            <i class="fas fa-camera"></i>
                            <input type="file" 
                                   id="photoUpload" 
                                   name="profile_photo" 
                                   accept="image/*"
                                   onchange="handlePhotoUpload(this)">
                        </label>
                        <div class="upload-progress" id="uploadProgress">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </div>
                    <div>
                        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                        <p>Here's what's happening in your mental wellness journey</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="edit-profile.php" class="btn btn-secondary">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
            
            <div class="profile-section">
                
                <div class="achievements">
                    <h3>Your Achievements</h3>
                    <div class="badges">
                        <img src="" alt="Achievement 1" class="achievement-badge">
                        <img src="" alt="Achievement 2" class="achievement-badge">
                        <img src="" alt="Achievement 3" class="achievement-badge">
                    </div>
                    </div>
                </div>
                
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-pen-fancy"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_posts; ?></div>
                    <div class="stat-label">Total Posts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_likes; ?></div>
                    <div class="stat-label">Likes Received</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number">3</div>
                    <div class="stat-label">Support Groups</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number">7</div>
                    <div class="stat-label">Days Streak</div>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="meditation-games">
                    <h2>Meditation Games</h2>
                <div class="games-grid">
                        <a href="/mind/breathing.html" class="game-card">
                            <div class="game-icon">
                        <i class="fas fa-wind"></i>
                            </div>
                            <h3>Breathing</h3>
                            <p>Practice mindful breathing exercises</p>
                        </a>
                        <a href="/mind/color-match.html" class="game-card">
                            <div class="game-icon">
                                <i class="fas fa-palette"></i>
                            </div>
                            <h3>Color Match</h3>
                            <p>Match colors to improve focus</p>
                        </a>
                        <a href="/mind/puzzle.html" class="game-card">
                            <div class="game-icon">
                                <i class="fas fa-puzzle-piece"></i>
                            </div>
                            <h3>Puzzle</h3>
                            <p>Solve puzzles to enhance mindfulness</p>
                        </a>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="recent-posts">
                        <h2>Recent Posts</h2>
                        <?php if($recent_posts): ?>
                            <div class="posts-list">
                                <?php foreach($recent_posts as $post): ?>
                                    <div class="post-card">
                                        <div class="post-header">
                                            <img src="<?php echo htmlspecialchars($post['profile_picture']); ?>" alt="Profile" class="profile-pic">
                                            <div class="post-info">
                                                <h3><?php echo htmlspecialchars($post['name']); ?></h3>
                                                <span class="post-date"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        <p><?php echo htmlspecialchars($post['content']); ?></p>
                                        <?php if($post['image']): ?>
                                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="post-image">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No recent posts</p>
                        <?php endif; ?>
                    </div>

                    <div class="journal-section">
                        <div class="calendar-section">
                            <div class="calendar-container">
                                <div class="calendar-header">
                                    <button id="prevMonth" class="calendar-nav-btn">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <h3 id="currentMonth"><?php echo date('F Y'); ?></h3>
                                    <button id="nextMonth" class="calendar-nav-btn">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                                <div class="calendar-grid">
                                    <div class="calendar-weekdays">
                                        <div>Sun</div>
                                        <div>Mon</div>
                                        <div>Tue</div>
                                        <div>Wed</div>
                                        <div>Thu</div>
                                        <div>Fri</div>
                                        <div>Sat</div>
                                    </div>
                                    <div class="calendar-days" id="calendarDays">
                                        <?php
                                        $currentDate = new DateTime();
                                        $firstDayOfMonth = new DateTime($currentDate->format('Y-m-01'));
                                        $lastDayOfMonth = new DateTime($currentDate->format('Y-m-t'));
                                        
                                        // Get all entries for the current month
                                        $stmt = $pdo->prepare("
                                            SELECT 
                                                'journal' as type,
                                                created_at,
                                                mood_rating,
                                                content
                                            FROM journal_entries 
                                            WHERE user_id = ? 
                                            AND DATE(created_at) BETWEEN ? AND ?
                                            
                                            UNION ALL
                                            
                                            SELECT 
                                                'mood' as type,
                                                created_at,
                                                mood_rating,
                                                notes as content
                                            FROM mood_tracking 
                                            WHERE user_id = ? 
                                            AND DATE(created_at) BETWEEN ? AND ?
                                            
                                            ORDER BY created_at ASC
                                        ");
                                        $stmt->execute([
                                            $user_id,
                                            $firstDayOfMonth->format('Y-m-d'),
                                            $lastDayOfMonth->format('Y-m-d'),
                                            $user_id,
                                            $firstDayOfMonth->format('Y-m-d'),
                                            $lastDayOfMonth->format('Y-m-d')
                                        ]);
                                        $monthEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        // Create an array of entries by date
                                        $entriesByDate = [];
                                        foreach ($monthEntries as $entry) {
                                            $date = date('Y-m-d', strtotime($entry['created_at']));
                                            $entriesByDate[$date][] = $entry;
                                        }
                                        
                                        // Print empty cells for days before the first day of the month
                                        $firstDayOfWeek = $firstDayOfMonth->format('w');
                                        for ($i = 0; $i < $firstDayOfWeek; $i++) {
                                            echo '<div class="calendar-day empty"></div>';
                                        }
                                        
                                        // Print days of the month
                                        $currentDay = clone $firstDayOfMonth;
                                        while ($currentDay <= $lastDayOfMonth) {
                                            $dateStr = $currentDay->format('Y-m-d');
                                            $dayClass = $currentDay->format('Y-m-d') === date('Y-m-d') ? 'today' : '';
                                            $hasEntries = isset($entriesByDate[$dateStr]) ? 'has-entries' : '';
                                            
                                            echo '<div class="calendar-day ' . $dayClass . ' ' . $hasEntries . '" data-date="' . $dateStr . '">';
                                            echo '<div class="day-number">' . $currentDay->format('j') . '</div>';
                                            
                                            if (isset($entriesByDate[$dateStr])) {
                                                echo '<div class="entries-preview">';
                                                foreach ($entriesByDate[$dateStr] as $entry) {
                                                    $icon = $entry['type'] === 'journal' ? 'fa-book' : 'fa-smile';
                                                    $color = $entry['mood_rating'] ? 'color-' . $entry['mood_rating'] : '';
                                                    echo '<div class="entry-time ' . $color . '">';
                                                    echo '<i class="fas ' . $icon . '"></i> ';
                                                    echo date('H:i', strtotime($entry['created_at']));
                                                    echo '</div>';
                                                }
                                                echo '</div>';
                                            }
                                            
                                            echo '</div>';
                                            $currentDay->modify('+1 day');
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Journal Entry Modal -->
    <div id="journalModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>New Journal Entry</h2>
            <form id="journalForm" method="POST" action="save_journal.php">
                <textarea name="content" placeholder="How are you feeling today? What's on your mind?" required></textarea>
                <button type="submit" class="btn btn-primary">Save Entry</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        // Mood Chart
        const moodData = <?php echo json_encode($mood_data); ?>;
        const ctx = document.getElementById('moodChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: moodData.map(item => `Day ${item.mood_rating}`),
                datasets: [{
                    label: 'Mood Rating',
                    data: moodData.map(item => item.count),
                    borderColor: '#4CAF50',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        function showJournalModal() {
            document.getElementById('journalModal').style.display = 'block';
        }

        // Close modals when clicking the close button
        document.querySelectorAll('.close').forEach(button => {
            button.onclick = function() {
                this.closest('.modal').style.display = 'none';
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Calendar Navigation
        const prevMonthBtn = document.getElementById('prevMonth');
        const nextMonthBtn = document.getElementById('nextMonth');
        const currentMonthDisplay = document.getElementById('currentMonth');
        const calendarDays = document.getElementById('calendarDays');

        let currentDate = new Date();

        function updateCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // Update month display
            currentMonthDisplay.textContent = new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' });
            
            // Fetch entries for the new month
            fetch(`get_calendar_entries.php?year=${year}&month=${month + 1}`)
                .then(response => response.json())
                .then(entries => {
                    // Clear existing days
                    calendarDays.innerHTML = '';
                    
                    // Add empty cells for days before the first day of the month
                    const firstDay = new Date(year, month, 1);
                    const lastDay = new Date(year, month + 1, 0);
                    const firstDayOfWeek = firstDay.getDay();
                    
                    for (let i = 0; i < firstDayOfWeek; i++) {
                        const emptyDay = document.createElement('div');
                        emptyDay.className = 'calendar-day empty';
                        calendarDays.appendChild(emptyDay);
                    }
                    
                    // Add days of the month
                    for (let day = 1; day <= lastDay.getDate(); day++) {
                        const dayElement = document.createElement('div');
                        const date = new Date(year, month, day);
                        const dateStr = date.toISOString().split('T')[0];
                        
                        dayElement.className = 'calendar-day';
                        if (date.toDateString() === new Date().toDateString()) {
                            dayElement.classList.add('today');
                        }
                        
                        const dayNumber = document.createElement('div');
                        dayNumber.className = 'day-number';
                        dayNumber.textContent = day;
                        dayElement.appendChild(dayNumber);
                        
                        // Add entries if any exist for this date
                        if (entries[dateStr]) {
                            dayElement.classList.add('has-entries');
                            const entriesPreview = document.createElement('div');
                            entriesPreview.className = 'entries-preview';
                            
                            entries[dateStr].forEach(entry => {
                                const entryTime = document.createElement('div');
                                entryTime.className = `entry-time color-${entry.mood_rating || ''}`;
                                entryTime.innerHTML = `
                                    <i class="fas ${entry.type === 'journal' ? 'fa-book' : 'fa-smile'}"></i>
                                    ${new Date(entry.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                `;
                                entriesPreview.appendChild(entryTime);
                            });
                            
                            dayElement.appendChild(entriesPreview);
                        }
                        
                        dayElement.dataset.date = dateStr;
                        calendarDays.appendChild(dayElement);
                    }
                });
        }

        prevMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            updateCalendar();
        });

        nextMonthBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            updateCalendar();
        });

        // Initialize calendar
        updateCalendar();

        function handlePhotoUpload(input) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.enctype = 'multipart/form-data';
            
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.name = 'profile_photo';
            fileInput.files = input.files;
            
            form.appendChild(fileInput);
            
            const progress = document.getElementById('uploadProgress');
            progress.classList.add('active');
            
            const formData = new FormData();
            formData.append('profile_photo', input.files[0]);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                // Refresh the page to show the new photo
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                progress.classList.remove('active');
            });
        }

        // Preview the selected image
        document.getElementById('photoUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePhoto').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
    <div class="footer-bottom">
        <p>&copy; 2025 MindCare. All rights reserved to TEAM SHOURYANGA</p>
    </div>
</body>
</html> 