<?php
require_once '../config/config.php';
// Redirect to customers.php for backward compatibility
header('Location: ' . BASE_URL . 'admin/customers.php');
exit();
?>
