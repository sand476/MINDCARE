<?php
// Database configuration
$host = "localhost";
$dbname = "mindcare_db";
$username = "root";
$password = "";

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Log error and display user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
define('BASE_PATH', dirname(__FILE__));

// Define site URL and paths
define('SITE_URL', 'http://localhost/mind');
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('IMAGE_PATH', BASE_PATH . '/images/');

// Create required directories if they don't exist
$directories = [
    UPLOAD_PATH,
    IMAGE_PATH
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Function to handle errors
function handleError($error) {
    error_log($error);
    return "An error occurred. Please try again later.";
}

// Function to get absolute path
function getAbsolutePath($relativePath) {
    return BASE_PATH . '/' . ltrim($relativePath, '/');
}

// Function to get URL path
function getUrlPath($relativePath) {
    return SITE_URL . '/' . ltrim($relativePath, '/');
}
?> 