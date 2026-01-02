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
    
    // Get old values to compare
    $old_agreement = $conn->query("SELECT monthly_rent, security_deposit FROM agreements WHERE agreement_id = $agreement_id")->fetch_assoc();
    $old_monthly_rent = $old_agreement['monthly_rent'] ?? 0;
    $old_security_deposit = $old_agreement['security_deposit'] ?? 0;
    
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
        
        // Update related pending ledger entries if rent or security deposit changed
        // Only update pending/overdue entries, not paid ones (historical payments should remain unchanged)
        $agreement_number = $conn->query("SELECT agreement_number FROM agreements WHERE agreement_id = $agreement_id")->fetch_assoc()['agreement_number'];
        
        // Update pending rent ledger entries if monthly rent changed
        if (abs($old_monthly_rent - $monthly_rent) > 0.01) { // Use small threshold to avoid floating point issues
            $rent_updated = $conn->query("UPDATE ledger SET amount = $monthly_rent, 
                         description = CONCAT('Monthly Rent - Agreement: ', '$agreement_number', ' (Updated)') 
                         WHERE agreement_id = $agreement_id 
                         AND transaction_type = 'rent' 
                         AND status IN ('pending', 'overdue')");
            
            // If no pending rent entry exists but rent changed, create one for future months
            $pending_rent_count = $conn->query("SELECT COUNT(*) as cnt FROM ledger WHERE agreement_id = $agreement_id AND transaction_type = 'rent' AND status IN ('pending', 'overdue')")->fetch_assoc()['cnt'];
            if ($pending_rent_count == 0 && $monthly_rent > 0) {
                $invoice_number_rent = 'INV-RENT-' . date('Ymd') . '-' . rand(1000, 9999);
                $conn->query("INSERT INTO ledger (customer_id, agreement_id, transaction_type, amount, payment_date, payment_method, description, status, invoice_number) 
                             VALUES ($customer_id, $agreement_id, 'rent', $monthly_rent, '$start_date', 'cash', 'Monthly Rent - Agreement: $agreement_number (Updated)', 'pending', '$invoice_number_rent')");
            }
        }
        
        // Update pending security deposit ledger entries if security deposit changed
        if (abs($old_security_deposit - $security_deposit) > 0.01) {
            // Check if deposit ledger entry exists
            $deposit_entry = $conn->query("SELECT ledger_id, amount FROM ledger WHERE agreement_id = $agreement_id AND transaction_type = 'deposit' AND status IN ('pending', 'overdue') LIMIT 1")->fetch_assoc();
            
            if ($deposit_entry) {
                if ($security_deposit > 0) {
                    // Update existing deposit entry
                    $conn->query("UPDATE ledger SET amount = $security_deposit, 
                                 description = CONCAT('Security Deposit - Agreement: ', '$agreement_number', ' (Updated)') 
                                 WHERE ledger_id = " . $deposit_entry['ledger_id']);
                } else {
                    // If security deposit removed, mark existing entry as cancelled/refunded
                    $conn->query("UPDATE ledger SET status = 'paid', 
                                 description = CONCAT('Security Deposit - Refunded/Cancelled - Agreement: ', '$agreement_number') 
                                 WHERE ledger_id = " . $deposit_entry['ledger_id']);
                }
            } elseif ($security_deposit > 0) {
                // Create new deposit entry if security deposit was added
                $invoice_number = 'INV-DEP-' . date('Ymd') . '-' . rand(1000, 9999);
                $conn->query("INSERT INTO ledger (customer_id, agreement_id, transaction_type, amount, payment_date, payment_method, description, status, invoice_number) 
                             VALUES ($customer_id, $agreement_id, 'deposit', $security_deposit, CURDATE(), 'cash', 'Security Deposit - Agreement: $agreement_number (Added)', 'pending', '$invoice_number')");
            }
        }
        
        $_SESSION['success'] = 'Assignment updated successfully! ' . 
            (($old_monthly_rent != $monthly_rent || $old_security_deposit != $security_deposit) ? 
            'Related pending ledger entries have been updated.' : '');
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

