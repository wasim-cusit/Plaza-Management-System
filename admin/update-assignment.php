<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agreement_id = intval($_POST['agreement_id'] ?? 0);
    $space_id = intval($_POST['space_id']);
    $space_type = $_POST['space_type'];
    $customer_id = intval($_POST['customer_id']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $monthly_rent = floatval($_POST['monthly_rent']);
    $security_deposit = floatval($_POST['security_deposit'] ?? 0);
    $status = $_POST['status'];
    $terms = trim($_POST['terms'] ?? '');
    
    // Handle document upload
    $document_file = null;
    if (isset($_FILES['agreement_document']) && $_FILES['agreement_document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = AGREEMENT_UPLOAD_DIR;
        $file_ext = pathinfo($_FILES['agreement_document']['name'], PATHINFO_EXTENSION);
        $document_file = 'agreement_' . time() . '_' . uniqid() . '.' . $file_ext;
        $upload_path = $upload_dir . $document_file;
        
        if (move_uploaded_file($_FILES['agreement_document']['tmp_name'], $upload_path)) {
            // Update agreement with new document
            $stmt = $conn->prepare("UPDATE agreements SET start_date = ?, end_date = ?, monthly_rent = ?, security_deposit = ?, terms = ?, status = ?, document_file = ? WHERE agreement_id = ?");
            $stmt->bind_param("ssddsssi", $start_date, $end_date, $monthly_rent, $security_deposit, $terms, $status, $document_file, $agreement_id);
        } else {
            $_SESSION['error'] = 'Error uploading document file.';
            header('Location: assigned-spaces.php');
            exit();
        }
    } else {
        // Update without changing document
        $stmt = $conn->prepare("UPDATE agreements SET start_date = ?, end_date = ?, monthly_rent = ?, security_deposit = ?, terms = ?, status = ? WHERE agreement_id = ?");
        $stmt->bind_param("ssddssi", $start_date, $end_date, $monthly_rent, $security_deposit, $terms, $status, $agreement_id);
    }
    
    if ($stmt->execute()) {
        // Update space rent if changed
        if ($space_type === 'shop') {
            $conn->query("UPDATE shops SET monthly_rent = $monthly_rent WHERE shop_id = $space_id");
        } elseif ($space_type === 'room') {
            $conn->query("UPDATE rooms SET monthly_rent = $monthly_rent WHERE room_id = $space_id");
        } elseif ($space_type === 'basement') {
            $conn->query("UPDATE basements SET monthly_rent = $monthly_rent WHERE basement_id = $space_id");
        }
        
        $_SESSION['success'] = 'Assignment updated successfully!';
    } else {
        $_SESSION['error'] = 'Error updating assignment: ' . $conn->error;
    }
    $stmt->close();
    
    header('Location: assigned-spaces.php');
    exit();
} else {
    header('Location: assigned-spaces.php');
    exit();
}
?>

