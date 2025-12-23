<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $full_name = trim($_POST['full_name']);
        $gender = $_POST['gender'];
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone']);
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
        
        // Validate
        if (empty($full_name) || empty($phone) || empty($gender)) {
            $message = 'Please fill in all required fields (Name, Phone, Gender).';
            $message_type = 'danger';
        } else {
            // Check if CNIC already exists (if provided)
            if ($cnic) {
                $check = $conn->query("SELECT customer_id FROM customers WHERE cnic = '$cnic'");
                if ($check->num_rows > 0) {
                    $message = 'CNIC already exists in the system.';
                    $message_type = 'danger';
                } else {
                    $stmt = $conn->prepare("INSERT INTO customers (full_name, gender, email, phone, alternate_phone, cnic, address, city, country, occupation, emergency_contact_name, emergency_contact_phone, reference_name, reference_phone, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssssssssssss", $full_name, $gender, $email, $phone, $alternate_phone, $cnic, $address, $city, $country, $occupation, $emergency_contact_name, $emergency_contact_phone, $reference_name, $reference_phone, $status, $notes);
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO customers (full_name, gender, email, phone, alternate_phone, cnic, address, city, country, occupation, emergency_contact_name, emergency_contact_phone, reference_name, reference_phone, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssssssssss", $full_name, $gender, $email, $phone, $alternate_phone, $cnic, $address, $city, $country, $occupation, $emergency_contact_name, $emergency_contact_phone, $reference_name, $reference_phone, $status, $notes);
            }
            
            if (isset($stmt) && $stmt->execute()) {
                $message = 'Customer added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding customer: ' . $conn->error;
                $message_type = 'danger';
            }
            if (isset($stmt)) $stmt->close();
        }
    } elseif ($_POST['action'] === 'update') {
        $customer_id = intval($_POST['customer_id']);
        $full_name = trim($_POST['full_name']);
        $gender = $_POST['gender'];
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone']);
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
        
        // Check if CNIC already exists (if provided and different)
        if ($cnic) {
            $check = $conn->query("SELECT customer_id FROM customers WHERE cnic = '$cnic' AND customer_id != $customer_id");
            if ($check->num_rows > 0) {
                $message = 'CNIC already exists for another customer.';
                $message_type = 'danger';
            } else {
                $stmt = $conn->prepare("UPDATE customers SET full_name = ?, gender = ?, email = ?, phone = ?, alternate_phone = ?, cnic = ?, address = ?, city = ?, country = ?, occupation = ?, emergency_contact_name = ?, emergency_contact_phone = ?, reference_name = ?, reference_phone = ?, status = ?, notes = ? WHERE customer_id = ?");
                $stmt->bind_param("ssssssssssssssssi", $full_name, $gender, $email, $phone, $alternate_phone, $cnic, $address, $city, $country, $occupation, $emergency_contact_name, $emergency_contact_phone, $reference_name, $reference_phone, $status, $notes, $customer_id);
            }
        } else {
            $stmt = $conn->prepare("UPDATE customers SET full_name = ?, gender = ?, email = ?, phone = ?, alternate_phone = ?, cnic = ?, address = ?, city = ?, country = ?, occupation = ?, emergency_contact_name = ?, emergency_contact_phone = ?, reference_name = ?, reference_phone = ?, status = ?, notes = ? WHERE customer_id = ?");
            $stmt->bind_param("ssssssssssssssssi", $full_name, $gender, $email, $phone, $alternate_phone, $cnic, $address, $city, $country, $occupation, $emergency_contact_name, $emergency_contact_phone, $reference_name, $reference_phone, $status, $notes, $customer_id);
        }
        
        if (isset($stmt) && $stmt->execute()) {
            $message = 'Customer updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating customer: ' . $conn->error;
            $message_type = 'danger';
        }
        if (isset($stmt)) $stmt->close();
    } elseif ($_POST['action'] === 'delete') {
        $customer_id = intval($_POST['customer_id']);
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        
        if ($stmt->execute()) {
            $message = 'Customer deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error deleting customer: ' . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$where_clause = "WHERE 1=1";
if ($search) {
    $search_term = $conn->real_escape_string($search);
    $where_clause .= " AND (full_name LIKE '%$search_term%' OR phone LIKE '%$search_term%' OR email LIKE '%$search_term%' OR cnic LIKE '%$search_term%')";
}

$customers = $conn->query("SELECT * FROM customers $where_clause ORDER BY created_at DESC");

$page_title = 'Customers - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h1 class="card-title"><i class="fas fa-users"></i> Customers</h1>
        <button class="btn btn-primary" onclick="openCustomerModal('add')">
            <i class="fas fa-plus"></i> Add Customer
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Search -->
    <form method="GET" style="margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 1rem;">
            <div class="form-group" style="flex: 1;">
                <input type="text" class="form-control" name="search" placeholder="Search by name, phone, email, or CNIC..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="customers.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>CNIC</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($customers->num_rows > 0): ?>
                    <?php while ($customer = $customers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo ucfirst($customer['gender']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($customer['cnic'] ?: '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $customer['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($customer['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="customer-details.php?customer_id=<?php echo $customer['customer_id']; ?>" class="btn btn-sm btn-primary" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-secondary" onclick="openCustomerModal('edit', <?php echo htmlspecialchars(json_encode($customer)); ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-light);">No customers found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Customer Modal -->
<div id="customerModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 800px; margin: 2% auto; position: relative; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h2 class="card-title" id="modalTitle">Add Customer</h2>
            <button onclick="closeCustomerModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-color);">&times;</button>
        </div>
        <form method="POST" id="customerForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="customer_id" id="customerId">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" name="full_name" id="full_name" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Gender *</label>
                    <select class="form-control" name="gender" id="gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Phone *</label>
                    <input type="text" class="form-control" name="phone" id="phone" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Alternate Phone</label>
                    <input type="text" class="form-control" name="alternate_phone" id="alternate_phone">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email">
                </div>

                <div class="form-group">
                    <label class="form-label">CNIC</label>
                    <input type="text" class="form-control" name="cnic" id="cnic" placeholder="12345-1234567-1">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" id="address" rows="2"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" class="form-control" name="city" id="city">
                </div>

                <div class="form-group">
                    <label class="form-label">Country</label>
                    <input type="text" class="form-control" name="country" id="country" value="Pakistan">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Occupation</label>
                <input type="text" class="form-control" name="occupation" id="occupation">
            </div>

            <h3 style="margin: 1.5rem 0 1rem 0; border-top: 2px solid var(--border-color); padding-top: 1rem; font-size: 1.1rem;">Emergency Contact</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Emergency Contact Name</label>
                    <input type="text" class="form-control" name="emergency_contact_name" id="emergency_contact_name">
                </div>

                <div class="form-group">
                    <label class="form-label">Emergency Contact Phone</label>
                    <input type="text" class="form-control" name="emergency_contact_phone" id="emergency_contact_phone">
                </div>
            </div>

            <h3 style="margin: 1.5rem 0 1rem 0; border-top: 2px solid var(--border-color); padding-top: 1rem; font-size: 1.1rem;">Reference</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Reference Name</label>
                    <input type="text" class="form-control" name="reference_name" id="reference_name">
                </div>

                <div class="form-group">
                    <label class="form-label">Reference Phone</label>
                    <input type="text" class="form-control" name="reference_phone" id="reference_phone">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select class="form-control" name="status" id="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Any additional notes about this customer..."></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary" onclick="closeCustomerModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCustomerModal(action, data = null) {
    document.getElementById('formAction').value = action;
    document.getElementById('modalTitle').textContent = action === 'add' ? 'Add Customer' : 'Edit Customer';
    
    if (action === 'edit' && data) {
        document.getElementById('customerId').value = data.customer_id;
        document.getElementById('full_name').value = data.full_name || '';
        document.getElementById('gender').value = data.gender || '';
        document.getElementById('phone').value = data.phone || '';
        document.getElementById('alternate_phone').value = data.alternate_phone || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('cnic').value = data.cnic || '';
        document.getElementById('address').value = data.address || '';
        document.getElementById('city').value = data.city || '';
        document.getElementById('country').value = data.country || 'Pakistan';
        document.getElementById('occupation').value = data.occupation || '';
        document.getElementById('emergency_contact_name').value = data.emergency_contact_name || '';
        document.getElementById('emergency_contact_phone').value = data.emergency_contact_phone || '';
        document.getElementById('reference_name').value = data.reference_name || '';
        document.getElementById('reference_phone').value = data.reference_phone || '';
        document.getElementById('status').value = data.status || 'active';
        document.getElementById('notes').value = data.notes || '';
    } else {
        document.getElementById('customerForm').reset();
        document.getElementById('customerId').value = '';
        document.getElementById('country').value = 'Pakistan';
    }
    
    document.getElementById('customerModal').style.display = 'block';
}

function closeCustomerModal() {
    document.getElementById('customerModal').style.display = 'none';
    document.getElementById('customerForm').reset();
}

window.onclick = function(event) {
    const modal = document.getElementById('customerModal');
    if (event.target == modal) {
        closeCustomerModal();
    }
}
</script>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow: auto;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
</style>

<?php include '../includes/footer.php'; ?>
