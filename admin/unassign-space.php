<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();

$space_id = intval($_GET['space_id'] ?? 0);
$space_type = $_GET['space_type'] ?? '';

if ($space_id && $space_type) {
    // Get space info
    if ($space_type === 'shop') {
        $space = $conn->query("SELECT customer_id FROM shops WHERE shop_id = $space_id")->fetch_assoc();
        $conn->query("UPDATE shops SET status = 'available', customer_id = NULL WHERE shop_id = $space_id");
    } elseif ($space_type === 'room') {
        $space = $conn->query("SELECT customer_id FROM rooms WHERE room_id = $space_id")->fetch_assoc();
        $conn->query("UPDATE rooms SET status = 'available', customer_id = NULL WHERE room_id = $space_id");
    } elseif ($space_type === 'basement') {
        $space = $conn->query("SELECT customer_id FROM basements WHERE basement_id = $space_id")->fetch_assoc();
        $conn->query("UPDATE basements SET status = 'available', customer_id = NULL WHERE basement_id = $space_id");
    }
    
    // Update related agreements to expired
    if ($space && $space['customer_id']) {
        $conn->query("UPDATE agreements SET status = 'terminated' WHERE space_id = $space_id AND space_type = '$space_type' AND status = 'active'");
    }
    
    $_SESSION['success'] = 'Space unassigned successfully!';
} else {
    $_SESSION['error'] = 'Invalid space information.';
}

header('Location: spaces.php');
exit();
?>

