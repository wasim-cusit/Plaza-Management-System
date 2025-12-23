<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $basement_number = trim($_POST['basement_number']);
            $basement_name = trim($_POST['basement_name']);
            $area_sqft = floatval($_POST['area_sqft']);
            $monthly_rent = floatval($_POST['monthly_rent']);
            $space_type = $_POST['space_type'];
            $status = $_POST['status'];
            $description = trim($_POST['description']);

            $stmt = $conn->prepare("INSERT INTO basements (basement_number, basement_name, area_sqft, monthly_rent, space_type, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddsss", $basement_number, $basement_name, $area_sqft, $monthly_rent, $space_type, $status, $description);
            
            if ($stmt->execute()) {
                $message = 'Basement space added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding basement: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'update') {
            $basement_id = intval($_POST['basement_id']);
            $basement_number = trim($_POST['basement_number']);
            $basement_name = trim($_POST['basement_name']);
            $area_sqft = floatval($_POST['area_sqft']);
            $monthly_rent = floatval($_POST['monthly_rent']);
            $space_type = $_POST['space_type'];
            $status = $_POST['status'];
            $description = trim($_POST['description']);
            $tenant_id = !empty($_POST['tenant_id']) ? intval($_POST['tenant_id']) : null;

            $stmt = $conn->prepare("UPDATE basements SET basement_number = ?, basement_name = ?, area_sqft = ?, monthly_rent = ?, space_type = ?, status = ?, description = ?, tenant_id = ? WHERE basement_id = ?");
            $stmt->bind_param("ssddsssii", $basement_number, $basement_name, $area_sqft, $monthly_rent, $space_type, $status, $description, $tenant_id, $basement_id);
            
            if ($stmt->execute()) {
                $message = 'Basement space updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating basement: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $basement_id = intval($_POST['basement_id']);
            $stmt = $conn->prepare("DELETE FROM basements WHERE basement_id = ?");
            $stmt->bind_param("i", $basement_id);
            
            if ($stmt->execute()) {
                $message = 'Basement space deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting basement: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

$basements = $conn->query("SELECT b.*, u.full_name as tenant_name FROM basements b LEFT JOIN users u ON b.tenant_id = u.user_id ORDER BY b.basement_number");
$tenants = $conn->query("SELECT user_id, full_name, username FROM users WHERE user_type = 'tenant' ORDER BY full_name");

$page_title = 'Basement Management - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-layer-group"></i> Basement Management</h1>
        <button class="btn btn-primary" onclick="document.getElementById('addBasementModal').style.display='block'">
            <i class="fas fa-plus"></i> Add Basement
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
                    <th>Basement Number</th>
                    <th>Basement Name</th>
                    <th>Type</th>
                    <th>Area (sqft)</th>
                    <th>Monthly Rent</th>
                    <th>Tenant</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($basements->num_rows > 0): ?>
                    <?php while ($basement = $basements->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($basement['basement_number']); ?></td>
                            <td><?php echo htmlspecialchars($basement['basement_name'] ?? '-'); ?></td>
                            <td><?php echo ucfirst($basement['space_type']); ?></td>
                            <td><?php echo number_format($basement['area_sqft'], 2); ?></td>
                            <td><?php echo formatCurrency($basement['monthly_rent']); ?></td>
                            <td><?php echo htmlspecialchars($basement['tenant_name'] ?? 'Available'); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $basement['status'] === 'occupied' ? 'success' : 
                                        ($basement['status'] === 'maintenance' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($basement['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editBasement(<?php echo htmlspecialchars(json_encode($basement)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="basement_id" value="<?php echo $basement['basement_id']; ?>">
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
                        <td colspan="8" style="text-align: center; color: var(--text-light);">No basement spaces found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Basement Modal -->
<div id="addBasementModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="card" style="max-width: 600px; margin: 5% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title" id="modalTitle">Add Basement</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" id="basementForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="basement_id" id="basement_id">
            
            <div class="form-group">
                <label class="form-label">Basement Number *</label>
                <input type="text" class="form-control" name="basement_number" id="basement_number" required>
            </div>

            <div class="form-group">
                <label class="form-label">Basement Name</label>
                <input type="text" class="form-control" name="basement_name" id="basement_name">
            </div>

            <div class="form-group">
                <label class="form-label">Space Type *</label>
                <select class="form-control" name="space_type" id="space_type" required>
                    <option value="parking">Parking</option>
                    <option value="storage">Storage</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Area (sqft) *</label>
                <input type="number" step="0.01" class="form-control" name="area_sqft" id="area_sqft" required>
            </div>

            <div class="form-group">
                <label class="form-label">Monthly Rent *</label>
                <input type="number" step="0.01" class="form-control" name="monthly_rent" id="monthly_rent" required>
            </div>

            <div class="form-group">
                <label class="form-label">Status *</label>
                <select class="form-control" name="status" id="status" required>
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Customer (if occupied)</label>
                <select class="form-control" name="tenant_id" id="tenant_id">
                    <option value="">Select Customer</option>
                    <?php 
                    $tenants->data_seek(0);
                    while ($tenant = $tenants->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $tenant['user_id']; ?>">
                            <?php echo htmlspecialchars($tenant['full_name'] . ' (' . $tenant['username'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" id="description" rows="3"></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Basement</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('addBasementModal').style.display = 'none';
    document.getElementById('basementForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add Basement';
}

function editBasement(basement) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('basement_id').value = basement.basement_id;
    document.getElementById('basement_number').value = basement.basement_number;
    document.getElementById('basement_name').value = basement.basement_name || '';
    document.getElementById('space_type').value = basement.space_type;
    document.getElementById('area_sqft').value = basement.area_sqft;
    document.getElementById('monthly_rent').value = basement.monthly_rent;
    document.getElementById('status').value = basement.status;
    document.getElementById('tenant_id').value = basement.tenant_id || '';
    document.getElementById('description').value = basement.description || '';
    document.getElementById('modalTitle').textContent = 'Edit Basement';
    document.getElementById('addBasementModal').style.display = 'block';
}

window.onclick = function(event) {
    const modal = document.getElementById('addBasementModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

