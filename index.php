<?php
require_once 'config/config.php';

// Redirect to login if not logged in, otherwise redirect to appropriate dashboard
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
} else {
    if (isAdmin()) {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'tenant/dashboard.php');
    }
    exit();
}
?>

