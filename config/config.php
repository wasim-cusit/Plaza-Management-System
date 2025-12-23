<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration
define('BASE_URL', 'http://localhost/plaza_ms/');

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
    return '$' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}
?>

