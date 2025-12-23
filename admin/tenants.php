<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $full_name = trim($_POST['full_name']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            $status = $_POST['status'];

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, address, user_type, status) VALUES (?, ?, ?, ?, ?, ?, 'tenant', ?)");
            $stmt->bind_param("sssssss", $username, $email, $password, $full_name, $phone, $address, $status);
            
            if ($stmt->execute()) {
                $message = 'Customer added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding customer: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'update') {
            $user_id = intval($_POST['user_id']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $full_name = trim($_POST['full_name']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            $status = $_POST['status'];
            
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, full_name = ?, phone = ?, address = ?, status = ? WHERE user_id = ?");
                $stmt->bind_param("sssssssi", $username, $email, $password, $full_name, $phone, $address, $status, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, address = ?, status = ? WHERE user_id = ?");
                $stmt->bind_param("ssssssi", $username, $email, $full_name, $phone, $address, $status, $user_id);
            }
            
            if ($stmt->execute()) {
                $message = 'Customer updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating customer: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $user_id = intval($_POST['user_id']);
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND user_type = 'tenant'");
            $stmt->bind_param("i", $user_id);
            
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
}

$tenants = $conn->query("SELECT * FROM users WHERE user_type = 'tenant' ORDER BY full_name");

$page_title = 'Customer Management - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-users"></i> Customer Management</h1>
        <button class="btn btn-primary" onclick="document.getElementById('addTenantModal').style.display='block'">
            <i class="fas fa-plus"></i> Add Customer
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tenants->num_rows > 0): ?>
                    <?php while ($tenant = $tenants->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tenant['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($tenant['username']); ?></td>
                            <td><?php echo htmlspecialchars($tenant['email']); ?></td>
                            <td><?php echo htmlspecialchars($tenant['phone'] ?? '-'); ?></td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo htmlspecialchars($tenant['address'] ?? '-'); ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $tenant['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($tenant['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editTenant(<?php echo htmlspecialchars(json_encode($tenant)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $tenant['user_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
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

<!-- Add/Edit Tenant Modal -->
<div id="addTenantModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 600px; margin: 5% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title" id="modalTitle">Add Customer</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" id="tenantForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="user_id" id="user_id">
            
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" class="form-control" name="full_name" id="full_name" required>
            </div>

            <div class="form-group">
                <label class="form-label">Username *</label>
                <input type="text" class="form-control" name="username" id="username" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" class="form-control" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label class="form-label" id="passwordLabel">Password *</label>
                <input type="password" class="form-control" name="password" id="password">
                <small id="passwordHelp" style="color: var(--text-light);">Leave blank to keep current password when editing</small>
            </div>

            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" name="phone" id="phone">
            </div>

            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" id="address" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Status *</label>
                <select class="form-control" name="status" id="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('addTenantModal').style.display = 'none';
    document.getElementById('tenantForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add Customer';
    document.getElementById('passwordLabel').textContent = 'Password *';
    document.getElementById('password').required = true;
    document.getElementById('passwordHelp').style.display = 'none';
}

function editTenant(tenant) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('user_id').value = tenant.user_id;
    document.getElementById('full_name').value = tenant.full_name;
    document.getElementById('username').value = tenant.username;
    document.getElementById('email').value = tenant.email;
    document.getElementById('phone').value = tenant.phone || '';
    document.getElementById('address').value = tenant.address || '';
    document.getElementById('status').value = tenant.status;
    document.getElementById('passwordLabel').textContent = 'Password (Leave blank to keep current)';
    document.getElementById('password').required = false;
    document.getElementById('passwordHelp').style.display = 'block';
    document.getElementById('modalTitle').textContent = 'Edit Customer';
    document.getElementById('addTenantModal').style.display = 'block';
}

window.onclick = function(event) {
    const modal = document.getElementById('addTenantModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

