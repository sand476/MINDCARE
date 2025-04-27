<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

try {
    // Get all entries for the specified month
    $stmt = $pdo->prepare("
        SELECT 
            'journal' as type,
            created_at,
            mood_rating,
            content
        FROM journal_entries 
        WHERE user_id = ? 
        AND YEAR(created_at) = ?
        AND MONTH(created_at) = ?
        
        UNION ALL
        
        SELECT 
            'mood' as type,
            created_at,
            mood_rating,
            notes as content
        FROM mood_tracking 
        WHERE user_id = ? 
        AND YEAR(created_at) = ?
        AND MONTH(created_at) = ?
        
        ORDER BY created_at ASC
    ");
    
    $stmt->execute([$user_id, $year, $month, $user_id, $year, $month]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize entries by date
    $entriesByDate = [];
    foreach ($entries as $entry) {
        $date = date('Y-m-d', strtotime($entry['created_at']));
        if (!isset($entriesByDate[$date])) {
            $entriesByDate[$date] = [];
        }
        $entriesByDate[$date][] = $entry;
    }
    
    header('Content-Type: application/json');
    echo json_encode($entriesByDate);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 