<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $shop_number = trim($_POST['shop_number']);
            $shop_name = trim($_POST['shop_name']);
            $floor_number = intval($_POST['floor_number']);
            $area_sqft = floatval($_POST['area_sqft']);
            $monthly_rent = floatval($_POST['monthly_rent']);
            $status = $_POST['status'];
            $description = trim($_POST['description']);

            $stmt = $conn->prepare("INSERT INTO shops (shop_number, shop_name, floor_number, area_sqft, monthly_rent, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiddss", $shop_number, $shop_name, $floor_number, $area_sqft, $monthly_rent, $status, $description);
            
            if ($stmt->execute()) {
                $message = 'Shop added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding shop: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'update') {
            $shop_id = intval($_POST['shop_id']);
            $shop_number = trim($_POST['shop_number']);
            $shop_name = trim($_POST['shop_name']);
            $floor_number = intval($_POST['floor_number']);
            $area_sqft = floatval($_POST['area_sqft']);
            $monthly_rent = floatval($_POST['monthly_rent']);
            $status = $_POST['status'];
            $description = trim($_POST['description']);
            $tenant_id = !empty($_POST['tenant_id']) ? intval($_POST['tenant_id']) : null;

            $stmt = $conn->prepare("UPDATE shops SET shop_number = ?, shop_name = ?, floor_number = ?, area_sqft = ?, monthly_rent = ?, status = ?, description = ?, tenant_id = ? WHERE shop_id = ?");
            $stmt->bind_param("ssiddssii", $shop_number, $shop_name, $floor_number, $area_sqft, $monthly_rent, $status, $description, $tenant_id, $shop_id);
            
            if ($stmt->execute()) {
                $message = 'Shop updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating shop: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $shop_id = intval($_POST['shop_id']);
            $stmt = $conn->prepare("DELETE FROM shops WHERE shop_id = ?");
            $stmt->bind_param("i", $shop_id);
            
            if ($stmt->execute()) {
                $message = 'Shop deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting shop: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// Get all shops with tenant info
$shops = $conn->query("SELECT s.*, u.full_name as tenant_name, u.username as tenant_username FROM shops s LEFT JOIN users u ON s.tenant_id = u.user_id ORDER BY s.shop_number");

// Get all tenants for dropdown
$tenants = $conn->query("SELECT user_id, full_name, username FROM users WHERE user_type = 'tenant' ORDER BY full_name");

$page_title = 'Shop Management - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-store"></i> Shop Management</h1>
        <button class="btn btn-primary" onclick="document.getElementById('addShopModal').style.display='block'">
            <i class="fas fa-plus"></i> Add Shop
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
                    <th>Shop Number</th>
                    <th>Shop Name</th>
                    <th>Floor</th>
                    <th>Area (sqft)</th>
                    <th>Monthly Rent</th>
                    <th>Tenant</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($shops->num_rows > 0): ?>
                    <?php while ($shop = $shops->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($shop['shop_number']); ?></td>
                            <td><?php echo htmlspecialchars($shop['shop_name'] ?? '-'); ?></td>
                            <td><?php echo $shop['floor_number']; ?></td>
                            <td><?php echo number_format($shop['area_sqft'], 2); ?></td>
                            <td><?php echo formatCurrency($shop['monthly_rent']); ?></td>
                            <td><?php echo htmlspecialchars($shop['tenant_name'] ?? 'Available'); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $shop['status'] === 'occupied' ? 'success' : 
                                        ($shop['status'] === 'maintenance' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($shop['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editShop(<?php echo htmlspecialchars(json_encode($shop)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this shop?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="shop_id" value="<?php echo $shop['shop_id']; ?>">
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
                        <td colspan="8" style="text-align: center; color: var(--text-light);">No shops found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Shop Modal -->
<div id="addShopModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="card" style="max-width: 600px; margin: 5% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title" id="modalTitle">Add Shop</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-color);">&times;</button>
        </div>
        <form method="POST" id="shopForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="shop_id" id="shop_id">
            
            <div class="form-group">
                <label class="form-label">Shop Number *</label>
                <input type="text" class="form-control" name="shop_number" id="shop_number" required>
            </div>

            <div class="form-group">
                <label class="form-label">Shop Name</label>
                <input type="text" class="form-control" name="shop_name" id="shop_name">
            </div>

            <div class="form-group">
                <label class="form-label">Floor Number *</label>
                <input type="number" class="form-control" name="floor_number" id="floor_number" required>
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
                <button type="submit" class="btn btn-primary">Save Shop</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('addShopModal').style.display = 'none';
    document.getElementById('shopForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add Shop';
}

function editShop(shop) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('shop_id').value = shop.shop_id;
    document.getElementById('shop_number').value = shop.shop_number;
    document.getElementById('shop_name').value = shop.shop_name || '';
    document.getElementById('floor_number').value = shop.floor_number;
    document.getElementById('area_sqft').value = shop.area_sqft;
    document.getElementById('monthly_rent').value = shop.monthly_rent;
    document.getElementById('status').value = shop.status;
    document.getElementById('tenant_id').value = shop.tenant_id || '';
    document.getElementById('description').value = shop.description || '';
    document.getElementById('modalTitle').textContent = 'Edit Shop';
    document.getElementById('addShopModal').style.display = 'block';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addShopModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<style>
.modal {
    overflow: auto;
}
</style>

<?php include '../includes/footer.php'; ?>

