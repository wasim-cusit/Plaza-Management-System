<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $room_number = trim($_POST['room_number']);
            $room_name = trim($_POST['room_name']);
            $floor_number = intval($_POST['floor_number']);
            $area_sqft = floatval($_POST['area_sqft']);
            $monthly_rent = floatval($_POST['monthly_rent']);
            $status = $_POST['status'];
            $description = trim($_POST['description']);

            $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_name, floor_number, area_sqft, monthly_rent, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiddss", $room_number, $room_name, $floor_number, $area_sqft, $monthly_rent, $status, $description);
            
            if ($stmt->execute()) {
                $message = 'Room added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding room: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'update') {
            $room_id = intval($_POST['room_id']);
            $room_number = trim($_POST['room_number']);
            $room_name = trim($_POST['room_name']);
            $floor_number = intval($_POST['floor_number']);
            $area_sqft = floatval($_POST['area_sqft']);
            $monthly_rent = floatval($_POST['monthly_rent']);
            $status = $_POST['status'];
            $description = trim($_POST['description']);
            $tenant_id = !empty($_POST['tenant_id']) ? intval($_POST['tenant_id']) : null;

            $stmt = $conn->prepare("UPDATE rooms SET room_number = ?, room_name = ?, floor_number = ?, area_sqft = ?, monthly_rent = ?, status = ?, description = ?, tenant_id = ? WHERE room_id = ?");
            $stmt->bind_param("ssiddssii", $room_number, $room_name, $floor_number, $area_sqft, $monthly_rent, $status, $description, $tenant_id, $room_id);
            
            if ($stmt->execute()) {
                $message = 'Room updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating room: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $room_id = intval($_POST['room_id']);
            $stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
            $stmt->bind_param("i", $room_id);
            
            if ($stmt->execute()) {
                $message = 'Room deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting room: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

$rooms = $conn->query("SELECT r.*, u.full_name as tenant_name FROM rooms r LEFT JOIN users u ON r.tenant_id = u.user_id ORDER BY r.room_number");
$tenants = $conn->query("SELECT user_id, full_name, username FROM users WHERE user_type = 'tenant' ORDER BY full_name");

$page_title = 'Room Management - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-door-open"></i> Room Management</h1>
        <button class="btn btn-primary" onclick="document.getElementById('addRoomModal').style.display='block'">
            <i class="fas fa-plus"></i> Add Room
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
                    <th>Room Number</th>
                    <th>Room Name</th>
                    <th>Floor</th>
                    <th>Area (sqft)</th>
                    <th>Monthly Rent</th>
                    <th>Tenant</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rooms->num_rows > 0): ?>
                    <?php while ($room = $rooms->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                            <td><?php echo htmlspecialchars($room['room_name'] ?? '-'); ?></td>
                            <td><?php echo $room['floor_number']; ?></td>
                            <td><?php echo number_format($room['area_sqft'], 2); ?></td>
                            <td><?php echo formatCurrency($room['monthly_rent']); ?></td>
                            <td><?php echo htmlspecialchars($room['tenant_name'] ?? 'Available'); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $room['status'] === 'occupied' ? 'success' : 
                                        ($room['status'] === 'maintenance' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($room['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
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
                        <td colspan="8" style="text-align: center; color: var(--text-light);">No rooms found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Room Modal -->
<div id="addRoomModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="card" style="max-width: 600px; margin: 5% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title" id="modalTitle">Add Room</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" id="roomForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="room_id" id="room_id">
            
            <div class="form-group">
                <label class="form-label">Room Number *</label>
                <input type="text" class="form-control" name="room_number" id="room_number" required>
            </div>

            <div class="form-group">
                <label class="form-label">Room Name</label>
                <input type="text" class="form-control" name="room_name" id="room_name">
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
                <button type="submit" class="btn btn-primary">Save Room</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('addRoomModal').style.display = 'none';
    document.getElementById('roomForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add Room';
}

function editRoom(room) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('room_id').value = room.room_id;
    document.getElementById('room_number').value = room.room_number;
    document.getElementById('room_name').value = room.room_name || '';
    document.getElementById('floor_number').value = room.floor_number;
    document.getElementById('area_sqft').value = room.area_sqft;
    document.getElementById('monthly_rent').value = room.monthly_rent;
    document.getElementById('status').value = room.status;
    document.getElementById('tenant_id').value = room.tenant_id || '';
    document.getElementById('description').value = room.description || '';
    document.getElementById('modalTitle').textContent = 'Edit Room';
    document.getElementById('addRoomModal').style.display = 'block';
}

window.onclick = function(event) {
    const modal = document.getElementById('addRoomModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

