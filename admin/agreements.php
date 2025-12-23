<?php
// Agreements are now created automatically when assigning spaces
// Redirect to spaces page
require_once '../config/config.php';
header('Location: ' . BASE_URL . 'admin/spaces.php');
exit();
?>
