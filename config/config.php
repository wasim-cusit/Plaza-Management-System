<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration - Auto-detect for local and online
function getBaseUrl() {
    // Check if BASE_URL is already defined (for manual override)
    if (defined('BASE_URL')) {
        return BASE_URL;
    }
    
    // Check if running from command line (CLI)
    if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
        // Default to localhost for CLI or when server vars not available
        return 'http://localhost/plaza_ms/';
    }
    
    // Detect protocol
    $protocol = 'http://';
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) {
        $protocol = 'https://';
    }
    
    $host = $_SERVER['HTTP_HOST'];
    
    // Get document root and config file path
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')) : '';
    $configFile = str_replace('\\', '/', __FILE__); // Full path to config.php
    
    // Calculate base path: path from document root to project root
    // config.php is in config/ directory, so project root is one level up
    if ($documentRoot && strpos($configFile, $documentRoot) === 0) {
        // Get the path from document root to config directory
        $configPath = str_replace($documentRoot, '', dirname($configFile));
        // Go up one level from config/ to get project root
        $basePath = dirname($configPath);
        
        // Clean up the path
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = trim($basePath, '/');
        
        // Ensure we have the correct path format
        if ($basePath === '' || $basePath === '.') {
            $basePath = '/';
        } else {
            $basePath = '/' . $basePath . '/';
        }
    } else {
        // Fallback: use SCRIPT_NAME to determine base path
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
        
        // Remove filename from script name to get directory
        $basePath = dirname($scriptName);
        
        // If we're in a subdirectory (like admin/ or tenant/), go up to root
        if (strpos($basePath, '/admin') !== false || strpos($basePath, '/tenant') !== false) {
            $basePath = dirname($basePath);
        }
        
        // Clean up the path
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = rtrim($basePath, '/');
        
        // Ensure we have the correct path format
        if ($basePath === '.' || $basePath === '' || $basePath === '/') {
            $basePath = '/';
        } else {
            $basePath = '/' . ltrim($basePath, '/') . '/';
        }
    }
    
    return $protocol . $host . $basePath;
}

define('BASE_URL', getBaseUrl());

// Upload directories
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('AGREEMENT_UPLOAD_DIR', UPLOAD_DIR . 'agreements/');
define('RECEIPT_UPLOAD_DIR', UPLOAD_DIR . 'receipts/');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
if (!file_exists(AGREEMENT_UPLOAD_DIR)) {
    mkdir(AGREEMENT_UPLOAD_DIR, 0777, true);
}
if (!file_exists(RECEIPT_UPLOAD_DIR)) {
    mkdir(RECEIPT_UPLOAD_DIR, 0777, true);
}

// Include database connection
require_once __DIR__ . '/database.php';

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isTenant() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'tenant';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

function requireTenant() {
    requireLogin();
    if (!isTenant()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

function formatCurrency($amount) {
    return 'Rs ' . number_format($amount, 2);
}

function formatDate($date) {
    if (empty($date) || $date === null || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '-';
    }
    return date('d/m/Y', $timestamp);
}

// Helper function to get asset URL (CSS, JS, images)
function assetUrl($path) {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    return BASE_URL . $path;
}
?>

