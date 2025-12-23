<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $space_id = intval($_POST['space_id']);
    $space_type = $_POST['space_type'];
    $customer_id = intval($_POST['customer_id']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $monthly_rent = floatval($_POST['monthly_rent']);
    $security_deposit = floatval($_POST['security_deposit'] ?? 0);
    $terms = trim($_POST['terms'] ?? '');
    
    // Generate agreement number
    $agreement_number = 'AGR-' . strtoupper(substr($space_type, 0, 1)) . '-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Handle document upload
    $document_file = null;
    if (isset($_FILES['agreement_document']) && $_FILES['agreement_document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = AGREEMENT_UPLOAD_DIR;
        $file_ext = pathinfo($_FILES['agreement_document']['name'], PATHINFO_EXTENSION);
        $document_file = 'agreement_' . time() . '_' . uniqid() . '.' . $file_ext;
        $upload_path = $upload_dir . $document_file;
        
        if (!move_uploaded_file($_FILES['agreement_document']['tmp_name'], $upload_path)) {
            $_SESSION['error'] = 'Error uploading document file.';
            header('Location: spaces.php');
            exit();
        }
    }
    
    // Create agreement
    $stmt = $conn->prepare("INSERT INTO agreements (agreement_number, customer_id, space_type, space_id, start_date, end_date, monthly_rent, security_deposit, terms, status, document_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
    $stmt->bind_param("sissiiddss", $agreement_number, $customer_id, $space_type, $space_id, $start_date, $end_date, $monthly_rent, $security_deposit, $terms, $document_file);
    
    if ($stmt->execute()) {
        $agreement_id = $conn->insert_id;
        
        // Update space status to occupied
        if ($space_type === 'shop') {
            $conn->query("UPDATE shops SET status = 'occupied', customer_id = $customer_id WHERE shop_id = $space_id");
        } elseif ($space_type === 'room') {
            $conn->query("UPDATE rooms SET status = 'occupied', customer_id = $customer_id WHERE room_id = $space_id");
        } elseif ($space_type === 'basement') {
            $conn->query("UPDATE basements SET status = 'occupied', customer_id = $customer_id WHERE basement_id = $space_id");
        }
        
        // Create initial invoice for security deposit
        if ($security_deposit > 0) {
            $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
            $ledger_stmt = $conn->prepare("INSERT INTO ledger (customer_id, agreement_id, transaction_type, amount, payment_date, payment_method, description, status, invoice_number) VALUES (?, ?, 'deposit', ?, CURDATE(), 'cash', 'Security Deposit - Agreement: $agreement_number', 'pending', ?)");
            $ledger_stmt->bind_param("iids", $customer_id, $agreement_id, $security_deposit, $invoice_number);
            $ledger_stmt->execute();
            $ledger_id = $conn->insert_id;
            $ledger_stmt->close();
        }
        
        // Create initial invoice for first month rent
        $invoice_number_rent = 'INV-RENT-' . date('Ymd') . '-' . rand(1000, 9999);
        $ledger_rent_stmt = $conn->prepare("INSERT INTO ledger (customer_id, agreement_id, transaction_type, amount, payment_date, payment_method, description, status, invoice_number) VALUES (?, ?, 'rent', ?, ?, 'cash', 'Monthly Rent - Agreement: $agreement_number', 'pending', ?)");
        $ledger_rent_stmt->bind_param("iidss", $customer_id, $agreement_id, $monthly_rent, $start_date, $invoice_number_rent);
        $ledger_rent_stmt->execute();
        $ledger_rent_stmt->close();
        
        $_SESSION['success'] = 'Space assigned successfully! Agreement and invoices created.';
        $_SESSION['new_agreement_id'] = $agreement_id;
        header('Location: customer-details.php?customer_id=' . $customer_id);
        exit();
    } else {
        $_SESSION['error'] = 'Error creating agreement: ' . $conn->error;
        header('Location: spaces.php');
        exit();
    }
    $stmt->close();
} else {
    header('Location: spaces.php');
    exit();
}
?>

