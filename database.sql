-- Create database
CREATE DATABASE IF NOT EXISTS mindcare_db;
USE mindcare_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stories table
CREATE TABLE IF NOT EXISTS stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Feed posts table
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Likes table
CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, post_id)
);

-- Create resources table
CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create user_progress table
CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood_rating INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create support_groups table
CREATE TABLE IF NOT EXISTS support_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create group_members table
CREATE TABLE IF NOT EXISTS group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES support_groups(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create mood_tracking table
CREATE TABLE IF NOT EXISTS mood_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood_rating INT NOT NULL CHECK (mood_rating BETWEEN 1 AND 5),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO users (name, email, password, bio) VALUES
('Admin User', 'admin@example.com', '$2y$10$8K1p/a0dL1LXMIgZ5nU0xO0.7q0Z5X5X5X5X5X5X5X5X5X5X5X5X5X', 'Administrator of MindCare'),
('Test User 1', 'user1@example.com', '$2y$10$8K1p/a0dL1LXMIgZ5nU0xO0.7q0Z5X5X5X5X5X5X5X5X5X5X5X5X', 'Mental health enthusiast'),
('Test User 2', 'user2@example.com', '$2y$10$8K1p/a0dL1LXMIgZ5nU0xO0.7q0Z5X5X5X5X5X5X5X5X5X5X5X5X', 'Wellness advocate');

-- Insert sample resources
INSERT INTO resources (title, content, category) VALUES
('Stress Management for Students', 'Learn effective techniques to manage academic stress...', 'students'),
('Work-Life Balance in Tech', 'Tips for maintaining a healthy work-life balance in the tech industry...', 'professionals'),
('Mindfulness Meditation Guide', 'Step-by-step guide to practicing mindfulness meditation...', 'self-care'),
('Exam Preparation Tips', 'Strategies to prepare for exams while maintaining mental health...', 'students'),
('Burnout Prevention', 'Recognize and prevent burnout in the workplace...', 'professionals');

-- Insert sample support groups
INSERT INTO support_groups (name, description) VALUES
('Student Support Network', 'A group for students to share experiences and support each other'),
('Tech Professionals Wellness', 'Support group for professionals in the tech industry'),
('Mindfulness Practitioners', 'Group for practicing and learning mindfulness techniques'); 