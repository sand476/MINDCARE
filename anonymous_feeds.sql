CREATE TABLE IF NOT EXISTS anonymous_feeds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    mood_rating INT CHECK (mood_rating >= 1 AND mood_rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_approved BOOLEAN DEFAULT FALSE
); 