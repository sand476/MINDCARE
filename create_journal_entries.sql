-- Create journal_entries table
USE mindcare_db;

CREATE TABLE IF NOT EXISTS journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255),
    content TEXT NOT NULL,
    mood_rating INT CHECK (mood_rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample journal entries
INSERT INTO journal_entries (user_id, title, content, mood_rating) VALUES
(1, 'First Day of Meditation', 'Today I started my meditation journey. It was challenging at first, but I feel more relaxed now.', 4),
(1, 'Work Stress', 'Had a tough day at work. Need to practice more mindfulness techniques.', 2),
(2, 'Gratitude Entry', 'I am grateful for my supportive friends and family. They help me through tough times.', 5),
(2, 'Self-Reflection', 'Realized I need to take better care of my mental health. Starting with daily journaling.', 3),
(3, 'Progress Update', 'Completed my first week of consistent meditation. Feeling more focused and calm.', 4); 