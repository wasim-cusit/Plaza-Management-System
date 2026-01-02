<?php
require_once '../config/config.php';
requireAdmin();

header('Content-Type: application/json');

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $alternate_phone = trim($_POST['alternate_phone'] ?? '');
    $cnic = trim($_POST['cnic'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? 'Pakistan');
    $occupation = trim($_POST['occupation'] ?? '');
    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone = trim($_POST['emergency_contact_phone'] ?? '');
    $reference_name = trim($_POST['reference_name'] ?? '');
    $reference_phone = trim($_POST['reference_phone'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate required fields
    if (empty($full_name) || empty($phone) || empty($gender)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields (Name, Phone, Gender).'
        ]);
        exit;
    }
    
    // Check if CNIC already exists (if provided)
    if ($cnic) {
        $check = $conn->query("SELECT customer_id FROM customers WHERE cnic = '" . $conn->real_escape_string($cnic) . "'");
        if ($check->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'CNIC already exists in the system.'
            ]);
            exit;
        }
    }
    
    // Insert customer
    $stmt = $conn->prepare("INSERT INTO customers (full_name, gender, email, phone, alternate_phone, cnic, address, city, country, occupation, emergency_contact_name, emergency_contact_phone, reference_name, reference_phone, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssssssss", $full_name, $gender, $email, $phone, $alternate_phone, $cnic, $address, $city, $country, $occupation, $emergency_contact_name, $emergency_contact_phone, $reference_name, $reference_phone, $status, $notes);
    
    if ($stmt->execute()) {
        $customer_id = $conn->insert_id;
        
        // Get the created customer data
        $customer_result = $conn->query("SELECT customer_id, full_name, email, phone, gender FROM customers WHERE customer_id = $customer_id");
        $customer = $customer_result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Customer added successfully!',
            'customer' => $customer
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error adding customer: ' . $conn->error
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}

$conn->close();
?>

