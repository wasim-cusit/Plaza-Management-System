<?php
require_once 'config/config.php';
requireLogin();

header('Location: ' . BASE_URL . (isAdmin() ? 'admin/' : 'tenant/') . 'dashboard.php');
exit();
?>

