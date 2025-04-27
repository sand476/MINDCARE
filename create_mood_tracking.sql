-- Create mood_tracking table
USE mindcare_db;

CREATE TABLE IF NOT EXISTS mood_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood_rating INT NOT NULL CHECK (mood_rating BETWEEN 1 AND 5),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample mood entries
INSERT INTO mood_tracking (user_id, mood_rating, notes) VALUES
(1, 4, 'Feeling good today, had a productive morning'),
(1, 3, 'A bit stressed with work deadlines'),
(2, 5, 'Great day! Completed all my tasks'),
(2, 2, 'Not feeling well, need to rest'),
(3, 4, 'Meditation helped me feel better'); 