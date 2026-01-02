<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = $_SESSION['success'] ?? $_SESSION['error'] ?? '';
$message_type = isset($_SESSION['success']) ? 'success' : (isset($_SESSION['error']) ? 'danger' : '');
unset($_SESSION['success'], $_SESSION['error']);

// Get filter
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';

// Get all spaces
$spaces = [];

// Get shops
$shop_query = "SELECT 'shop' as space_type, shop_id as space_id, shop_number as space_number, shop_name as space_name, floor_number, area_sqft, monthly_rent, status, description, customer_id, created_at FROM shops";
if ($filter_status) {
    $shop_query .= " WHERE status = '$filter_status'";
}
$shops_result = $conn->query($shop_query);
while ($row = $shops_result->fetch_assoc()) {
    $spaces[] = $row;
}

// Get rooms
$room_query = "SELECT 'room' as space_type, room_id as space_id, room_number as space_number, room_name as space_name, floor_number, area_sqft, monthly_rent, status, description, customer_id, created_at FROM rooms";
if ($filter_status) {
    $room_query .= " WHERE status = '$filter_status'";
}
$rooms_result = $conn->query($room_query);
while ($row = $rooms_result->fetch_assoc()) {
    $spaces[] = $row;
}

// Get basements
$basement_query = "SELECT 'basement' as space_type, basement_id as space_id, basement_number as space_number, basement_name as space_name, NULL as floor_number, area_sqft, monthly_rent, status, description, customer_id, created_at FROM basements";
if ($filter_status) {
    $basement_query .= " WHERE status = '$filter_status'";
}
$basements_result = $conn->query($basement_query);
while ($row = $basements_result->fetch_assoc()) {
    $spaces[] = $row;
}

// Sort by created date
usort($spaces, function($a, $b) {
    return strtotime($a['created_at']) - strtotime($b['created_at']);
});

// Filter by type if selected
if ($filter_type) {
    $spaces = array_filter($spaces, function($space) use ($filter_type) {
        return $space['space_type'] === $filter_type;
    });
}

// Get all customers for assignment
$customers = $conn->query("SELECT customer_id, full_name, email, phone, gender FROM customers WHERE status = 'active' ORDER BY full_name");

$page_title = 'Spaces Management - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-building"></i> Spaces Management</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <div class="form-group" style="flex: 1; min-width: 200px;">
            <label class="form-label">Filter by Type</label>
            <select class="form-control" onchange="window.location.href='?type=' + this.value + '&status=<?php echo $filter_status; ?>'">
                <option value="">All Types</option>
                <option value="shop" <?php echo $filter_type === 'shop' ? 'selected' : ''; ?>>Shops</option>
                <option value="room" <?php echo $filter_type === 'room' ? 'selected' : ''; ?>>Rooms</option>
                <option value="basement" <?php echo $filter_type === 'basement' ? 'selected' : ''; ?>>Basements</option>
            </select>
        </div>
        <div class="form-group" style="flex: 1; min-width: 200px;">
            <label class="form-label">Filter by Status</label>
            <select class="form-control" onchange="window.location.href='?type=<?php echo $filter_type; ?>&status=' + this.value">
                <option value="">All Status</option>
                <option value="available" <?php echo $filter_status === 'available' ? 'selected' : ''; ?>>Available</option>
                <option value="occupied" <?php echo $filter_status === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                <option value="maintenance" <?php echo $filter_status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
            </select>
        </div>
        <div class="form-group" style="display: flex; align-items: end;">
            <a href="spaces.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear Filters</a>
        </div>
    </div>

    <!-- Spaces Grid -->
    <div class="spaces-grid">
        <?php if (count($spaces) > 0): ?>
            <?php foreach ($spaces as $space): ?>
                <?php
                $customer_info = null;
                if ($space['customer_id']) {
                    $customer_result = $conn->query("SELECT full_name, email, phone, gender FROM customers WHERE customer_id = " . $space['customer_id']);
                    $customer_info = $customer_result->fetch_assoc();
                }
                ?>
                <div class="space-card">
                    <div class="space-card-header">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.15rem;">
                                <span class="badge badge-info" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;"><?php echo ucfirst($space['space_type']); ?></span>
                                <strong style="font-size: 0.9rem; font-weight: 600;"><?php echo htmlspecialchars($space['space_number']); ?></strong>
                            </div>
                            <?php if ($space['space_name']): ?>
                                <p style="color: rgba(255, 255, 255, 0.9); margin: 0; font-size: 0.75rem;"><?php echo htmlspecialchars($space['space_name']); ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="badge badge-<?php 
                            echo $space['status'] === 'occupied' ? 'success' : 
                                ($space['status'] === 'maintenance' ? 'warning' : 'secondary'); 
                        ?>" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                            <?php echo ucfirst($space['status']); ?>
                        </span>
                    </div>
                    
                    <div class="space-card-body">
                        <div class="space-info">
                            <?php if ($space['floor_number']): ?>
                                <div><i class="fas fa-layer-group"></i> Floor: <?php echo $space['floor_number']; ?></div>
                            <?php endif; ?>
                            <div><i class="fas fa-ruler-combined"></i> Area: <?php echo number_format($space['area_sqft'], 2); ?> sqft</div>
                            <div><i class="fas fa-dollar-sign"></i> Rent: <?php echo formatCurrency($space['monthly_rent']); ?>/month</div>
                            <div><i class="fas fa-calendar"></i> Added: <?php echo formatDate($space['created_at']); ?></div>
                        </div>
                        
                        <?php if ($customer_info): ?>
                            <div class="space-tenant">
                                <strong>Assigned to:</strong>
                                <div><?php echo htmlspecialchars($customer_info['full_name']); ?></div>
                                <div style="font-size: 0.875rem; color: var(--text-light);">
                                    <?php echo htmlspecialchars($customer_info['email'] ?: $customer_info['phone']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="space-card-footer">
                        <?php if ($space['status'] === 'available'): ?>
                            <button class="btn btn-primary btn-sm" onclick="assignSpace(<?php echo htmlspecialchars(json_encode($space)); ?>)" title="Assign to Customer">
                                <i class="fas fa-user-plus"></i>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>admin/customer-details.php?customer_id=<?php echo $space['customer_id']; ?>" class="btn btn-primary btn-sm" title="View Customer">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-warning btn-sm" onclick="unassignSpace(<?php echo $space['space_id']; ?>, '<?php echo $space['space_type']; ?>')" title="Unassign">
                                <i class="fas fa-user-minus"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-building"></i>
                <p>No spaces found</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Assign Space Modal -->
<div id="assignModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 700px; margin: 3% auto; position: relative; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h2 class="card-title">Assign Space to Customer</h2>
            <button onclick="closeAssignModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-color);">&times;</button>
        </div>
        <form method="POST" action="<?php echo BASE_URL; ?>admin/assign-space.php" id="assignForm" enctype="multipart/form-data">
            <input type="hidden" name="space_id" id="assign_space_id">
            <input type="hidden" name="space_type" id="assign_space_type">
            
            <div class="form-group">
                <label class="form-label">Select Customer *</label>
                <select class="form-control" name="customer_id" id="customer_id" required onchange="loadCustomerDetails()">
                    <option value="">Select Customer</option>
                    <?php 
                    $customers->data_seek(0);
                    while ($customer = $customers->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $customer['customer_id']; ?>" 
                                data-name="<?php echo htmlspecialchars($customer['full_name']); ?>"
                                data-email="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>"
                                data-phone="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                            <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['phone'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Or Create New Customer</label>
                <button type="button" class="btn btn-secondary" onclick="openAddCustomerModal()">
                    <i class="fas fa-plus"></i> Add New Customer
                </button>
            </div>

            <h3 style="margin: 1.5rem 0 1rem 0; border-top: 2px solid var(--border-color); padding-top: 1rem;">Agreement Details</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Start Date *</label>
                    <input type="date" class="form-control" name="start_date" id="start_date" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End Date *</label>
                    <input type="date" class="form-control" name="end_date" id="end_date" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Monthly Rent *</label>
                    <input type="number" step="0.01" class="form-control" name="monthly_rent" id="monthly_rent" required oninput="calculateTotalDue()">
                </div>
                <div class="form-group">
                    <label class="form-label">Security Deposit</label>
                    <input type="number" step="0.01" class="form-control" name="security_deposit" id="security_deposit" value="0" oninput="calculateTotalDue()">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Terms & Conditions</label>
                <textarea class="form-control" name="terms" id="terms" rows="4" placeholder="Enter agreement terms..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Agreement Document (PDF/DOC)</label>
                <input type="file" class="form-control" name="agreement_document" accept=".pdf,.doc,.docx">
                <small style="color: var(--text-light);">Upload agreement document if available</small>
            </div>

            <h3 style="margin: 1.5rem 0 1rem 0; border-top: 2px solid var(--border-color); padding-top: 1rem;">Initial Payment</h3>
            
            <div class="form-group">
                <label class="form-label">Total Amount Due</label>
                <input type="number" step="0.01" class="form-control" id="total_amount_due" readonly style="background-color: var(--light-color); font-weight: bold;">
                <small style="color: var(--text-light);">Security Deposit + First Month Rent</small>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Amount Paying Now</label>
                    <input type="number" step="0.01" class="form-control" name="initial_payment_amount" id="initial_payment_amount" value="0" oninput="calculateRemaining()">
                    <small style="color: var(--text-light);">Enter 0 if paying later</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select class="form-control" name="initial_payment_method" id="initial_payment_method">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="online">Online</option>
                        <option value="check">Check</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Remaining Balance</label>
                <input type="number" step="0.01" class="form-control" id="remaining_balance" readonly style="background-color: var(--light-color); font-weight: bold; color: var(--warning-color);">
                <small style="color: var(--text-light);">This will be added to pending payments</small>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Assign & Create Agreement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Customer Modal -->
<div id="addCustomerModal" class="modal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 800px; margin: 2% auto; position: relative; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h2 class="card-title">Add New Customer</h2>
            <button onclick="closeAddCustomerModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-color);">&times;</button>
        </div>
        <form id="addCustomerForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" name="full_name" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Gender *</label>
                    <select class="form-control" name="gender" required>
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
                    <input type="text" class="form-control" name="phone" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Alternate Phone</label>
                    <input type="text" class="form-control" name="alternate_phone">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email">
                </div>

                <div class="form-group">
                    <label class="form-label">CNIC</label>
                    <input type="text" class="form-control" name="cnic" placeholder="12345-1234567-1">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" rows="2"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" class="form-control" name="city">
                </div>

                <div class="form-group">
                    <label class="form-label">Country</label>
                    <input type="text" class="form-control" name="country" value="Pakistan">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Occupation</label>
                <input type="text" class="form-control" name="occupation">
            </div>

            <h3 style="margin: 1.5rem 0 1rem 0; border-top: 2px solid var(--border-color); padding-top: 1rem; font-size: 1.1rem;">Emergency Contact</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Emergency Contact Name</label>
                    <input type="text" class="form-control" name="emergency_contact_name">
                </div>

                <div class="form-group">
                    <label class="form-label">Emergency Contact Phone</label>
                    <input type="text" class="form-control" name="emergency_contact_phone">
                </div>
            </div>

            <h3 style="margin: 1.5rem 0 1rem 0; border-top: 2px solid var(--border-color); padding-top: 1rem; font-size: 1.1rem;">Reference</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Reference Name</label>
                    <input type="text" class="form-control" name="reference_name">
                </div>

                <div class="form-group">
                    <label class="form-label">Reference Phone</label>
                    <input type="text" class="form-control" name="reference_phone">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Status</label>
                <select class="form-control" name="status">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="3"></textarea>
            </div>

            <div id="addCustomerMessage" style="display: none; margin: 1rem 0;"></div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary" onclick="closeAddCustomerModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Add Customer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function assignSpace(space) {
    document.getElementById('assign_space_id').value = space.space_id;
    document.getElementById('assign_space_type').value = space.space_type;
    document.getElementById('monthly_rent').value = space.monthly_rent;
    document.getElementById('security_deposit').value = 0;
    document.getElementById('initial_payment_amount').value = 0;
    
    // Set default dates
    const today = new Date();
    const oneYearLater = new Date();
    oneYearLater.setFullYear(today.getFullYear() + 1);
    
    document.getElementById('start_date').value = today.toISOString().split('T')[0];
    document.getElementById('end_date').value = oneYearLater.toISOString().split('T')[0];
    
    // Calculate total amount due
    calculateTotalDue();
    
    document.getElementById('assignModal').style.display = 'block';
}

function calculateTotalDue() {
    const monthlyRent = parseFloat(document.getElementById('monthly_rent').value) || 0;
    const securityDeposit = parseFloat(document.getElementById('security_deposit').value) || 0;
    const totalDue = monthlyRent + securityDeposit;
    document.getElementById('total_amount_due').value = totalDue.toFixed(2);
    calculateRemaining();
}

function calculateRemaining() {
    const totalDue = parseFloat(document.getElementById('total_amount_due').value) || 0;
    const initialPayment = parseFloat(document.getElementById('initial_payment_amount').value) || 0;
    const remaining = Math.max(0, totalDue - initialPayment);
    document.getElementById('remaining_balance').value = remaining.toFixed(2);
}

function closeAssignModal() {
    document.getElementById('assignModal').style.display = 'none';
    document.getElementById('assignForm').reset();
    // Reset form fields
    document.getElementById('assign_space_id').value = '';
    document.getElementById('assign_space_type').value = '';
    document.getElementById('customer_id').value = '';
}

function loadCustomerDetails() {
    const select = document.getElementById('customer_id');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        // Customer details can be loaded here if needed
    }
}

function openAddCustomerModal() {
    document.getElementById('addCustomerModal').style.display = 'block';
    document.getElementById('addCustomerForm').reset();
    document.getElementById('addCustomerMessage').style.display = 'none';
}

function closeAddCustomerModal() {
    document.getElementById('addCustomerModal').style.display = 'none';
    document.getElementById('addCustomerForm').reset();
    document.getElementById('addCustomerMessage').style.display = 'none';
}

// Handle add customer form submission
document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messageDiv = document.getElementById('addCustomerMessage');
    messageDiv.style.display = 'none';
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    
    fetch('<?php echo BASE_URL; ?>admin/add-customer-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add customer to dropdown
            const customerSelect = document.getElementById('customer_id');
            const option = document.createElement('option');
            option.value = data.customer.customer_id;
            option.textContent = data.customer.full_name + ' (' + data.customer.phone + ')';
            option.setAttribute('data-name', data.customer.full_name);
            option.setAttribute('data-email', data.customer.email || '');
            option.setAttribute('data-phone', data.customer.phone);
            customerSelect.appendChild(option);
            
            // Select the newly added customer
            customerSelect.value = data.customer.customer_id;
            
            // Show success message
            messageDiv.className = 'alert alert-success';
            messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            messageDiv.style.display = 'block';
            
            // Close modal after a short delay
            setTimeout(() => {
                closeAddCustomerModal();
            }, 1500);
        } else {
            // Show error message
            messageDiv.className = 'alert alert-danger';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
            messageDiv.style.display = 'block';
        }
        
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    })
    .catch(error => {
        messageDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> An error occurred. Please try again.';
        messageDiv.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Close add customer modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('addCustomerModal');
    if (event.target == modal) {
        closeAddCustomerModal();
    }
});

function unassignSpace(spaceId, spaceType) {
    if (confirm('Are you sure you want to unassign this space?')) {
        window.location.href = 'unassign-space.php?space_id=' + spaceId + '&space_type=' + spaceType;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('assignModal');
    if (event.target == modal) {
        closeAssignModal();
    }
}

// Form validation
document.getElementById('assignForm').addEventListener('submit', function(e) {
    const customerId = document.getElementById('customer_id').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    if (!customerId) {
        e.preventDefault();
        alert('Please select a customer.');
        return false;
    }
    
    if (new Date(endDate) <= new Date(startDate)) {
        e.preventDefault();
        alert('End date must be after start date.');
        return false;
    }
    
    return true;
});
</script>

<style>
.spaces-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.space-card {
    background: white;
    border-radius: 0.5rem;
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
}

.space-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.space-card-header {
    padding: 0.6rem 0.875rem;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-height: auto;
}

.space-card-header h3 {
    margin: 0;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.space-card-body {
    padding: 1rem;
}

.space-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.space-info > div {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-color);
}

.space-info i {
    color: var(--primary-color);
    width: 20px;
}

.space-tenant {
    padding: 1rem;
    background: var(--light-color);
    border-radius: 0.375rem;
    margin-top: 1rem;
    border-left: 3px solid var(--success-color);
}

.space-card-footer {
    padding: 0.75rem 1rem;
    background: var(--light-color);
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .spaces-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/footer.php'; ?>

