<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$active_tab = $_GET['tab'] ?? 'shops';
$message = '';
$message_type = '';

// Handle form submissions for all space types
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $space_type = $_POST['space_type'] ?? '';
    
    if ($action === 'add' || $action === 'update') {
        if ($space_type === 'shop') {
            $shop_number = trim($_POST['shop_number']);
            $shop_name = trim($_POST['shop_name'] ?? '');
            $floor_number = intval($_POST['floor_number']);
            $area_sqft = floatval($_POST['area_sqft']);
            $monthly_rent = floatval($_POST['monthly_rent']);
            $status = $_POST['status'];
            $description = trim($_POST['description'] ?? '');
            
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO shops (shop_number, shop_name, floor_number, area_sqft, monthly_rent, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiddss", $shop_number, $shop_name, $floor_number, $area_sqft, $monthly_rent, $status, $description);
            } else {
                $shop_id = intval($_POST['space_id']);
                $stmt = $conn->prepare("UPDATE shops SET shop_number = ?, shop_name = ?, floor_number = ?, area_sqft = ?, monthly_rent = ?, status = ?, description = ? WHERE shop_id = ?");
                $stmt->bind_param("ssiddssi", $shop_number, $shop_name, $floor_number, $area_sqft, $monthly_rent, $status, $description, $shop_id);
            }
            
            if ($stmt->execute()) {
                $message = 'Shop ' . ($action === 'add' ? 'added' : 'updated') . ' successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($space_type === 'room') {
            $room_number = trim($_POST['room_number']);
            $room_name = trim($_POST['room_name'] ?? '');
            $floor_number = intval($_POST['floor_number']);
            $area_sqft = floatval($_POST['area_sqft']);
            $monthly_rent = floatval($_POST['monthly_rent']);
            $status = $_POST['status'];
            $description = trim($_POST['description'] ?? '');
            
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_name, floor_number, area_sqft, monthly_rent, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiddss", $room_number, $room_name, $floor_number, $area_sqft, $monthly_rent, $status, $description);
            } else {
                $room_id = intval($_POST['space_id']);
                $stmt = $conn->prepare("UPDATE rooms SET room_number = ?, room_name = ?, floor_number = ?, area_sqft = ?, monthly_rent = ?, status = ?, description = ? WHERE room_id = ?");
                $stmt->bind_param("ssiddssi", $room_number, $room_name, $floor_number, $area_sqft, $monthly_rent, $status, $description, $room_id);
            }
            
            if ($stmt->execute()) {
                $message = 'Room ' . ($action === 'add' ? 'added' : 'updated') . ' successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($space_type === 'basement') {
            $basement_number = trim($_POST['basement_number']);
            $basement_name = trim($_POST['basement_name'] ?? '');
            $area_sqft = floatval($_POST['area_sqft']);
            $monthly_rent = floatval($_POST['monthly_rent']);
            $space_type_val = $_POST['space_type_val'];
            $status = $_POST['status'];
            $description = trim($_POST['description'] ?? '');
            
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO basements (basement_number, basement_name, area_sqft, monthly_rent, space_type, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssddsss", $basement_number, $basement_name, $area_sqft, $monthly_rent, $space_type_val, $status, $description);
            } else {
                $basement_id = intval($_POST['space_id']);
                $stmt = $conn->prepare("UPDATE basements SET basement_number = ?, basement_name = ?, area_sqft = ?, monthly_rent = ?, space_type = ?, status = ?, description = ? WHERE basement_id = ?");
                $stmt->bind_param("ssddsssi", $basement_number, $basement_name, $area_sqft, $monthly_rent, $space_type_val, $status, $description, $basement_id);
            }
            
            if ($stmt->execute()) {
                $message = 'Basement ' . ($action === 'add' ? 'added' : 'updated') . ' successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $space_id = intval($_POST['space_id']);
        $space_type = $_POST['space_type'];
        
        if ($space_type === 'shop') {
            $stmt = $conn->prepare("DELETE FROM shops WHERE shop_id = ?");
        } elseif ($space_type === 'room') {
            $stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
        } elseif ($space_type === 'basement') {
            $stmt = $conn->prepare("DELETE FROM basements WHERE basement_id = ?");
        }
        
        $stmt->bind_param("i", $space_id);
        if ($stmt->execute()) {
            $message = ucfirst($space_type) . ' deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error: ' . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// Get data based on active tab
$shops = $conn->query("SELECT * FROM shops ORDER BY shop_number");
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number");
$basements = $conn->query("SELECT * FROM basements ORDER BY basement_number");

$page_title = 'Settings - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-cog"></i> Settings</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="settings-tabs">
        <button class="tab-btn <?php echo $active_tab === 'shops' ? 'active' : ''; ?>" onclick="window.location.href='?tab=shops'">
            <i class="fas fa-store"></i> Shops
        </button>
        <button class="tab-btn <?php echo $active_tab === 'rooms' ? 'active' : ''; ?>" onclick="window.location.href='?tab=rooms'">
            <i class="fas fa-door-open"></i> Rooms
        </button>
        <button class="tab-btn <?php echo $active_tab === 'basements' ? 'active' : ''; ?>" onclick="window.location.href='?tab=basements'">
            <i class="fas fa-layer-group"></i> Basements
        </button>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
        <?php if ($active_tab === 'shops'): ?>
            <div class="tab-pane active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2>Shop Management</h2>
                    <button class="btn btn-primary" onclick="openSpaceModal('shop', 'add')">
                        <i class="fas fa-plus"></i> Add Shop
                    </button>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Shop Number</th>
                                <th>Shop Name</th>
                                <th>Floor</th>
                                <th>Area (sqft)</th>
                                <th>Monthly Rent</th>
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
                                                <button class="btn btn-sm btn-primary" onclick="openSpaceModal('shop', 'edit', <?php echo htmlspecialchars(json_encode($shop)); ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="space_type" value="shop">
                                                    <input type="hidden" name="space_id" value="<?php echo $shop['shop_id']; ?>">
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
                                    <td colspan="7" style="text-align: center; color: var(--text-light);">No shops found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($active_tab === 'rooms'): ?>
            <div class="tab-pane active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2>Room Management</h2>
                    <button class="btn btn-primary" onclick="openSpaceModal('room', 'add')">
                        <i class="fas fa-plus"></i> Add Room
                    </button>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Room Name</th>
                                <th>Floor</th>
                                <th>Area (sqft)</th>
                                <th>Monthly Rent</th>
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
                                                <button class="btn btn-sm btn-primary" onclick="openSpaceModal('room', 'edit', <?php echo htmlspecialchars(json_encode($room)); ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="space_type" value="room">
                                                    <input type="hidden" name="space_id" value="<?php echo $room['room_id']; ?>">
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
                                    <td colspan="7" style="text-align: center; color: var(--text-light);">No rooms found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($active_tab === 'basements'): ?>
            <div class="tab-pane active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2>Basement Management</h2>
                    <button class="btn btn-primary" onclick="openSpaceModal('basement', 'add')">
                        <i class="fas fa-plus"></i> Add Basement
                    </button>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Basement Number</th>
                                <th>Basement Name</th>
                                <th>Type</th>
                                <th>Area (sqft)</th>
                                <th>Monthly Rent</th>
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
                                                <button class="btn btn-sm btn-primary" onclick="openSpaceModal('basement', 'edit', <?php echo htmlspecialchars(json_encode($basement)); ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="space_type" value="basement">
                                                    <input type="hidden" name="space_id" value="<?php echo $basement['basement_id']; ?>">
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
                                    <td colspan="7" style="text-align: center; color: var(--text-light);">No basements found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Space Modal -->
<div id="spaceModal" class="modal" style="display: none;">
    <div class="card" style="max-width: 600px; margin: 5% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title" id="modalTitle">Add Space</h2>
            <button onclick="closeSpaceModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" id="spaceForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="space_type" id="spaceType">
            <input type="hidden" name="space_id" id="spaceId">
            
            <div id="spaceFormContent"></div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeSpaceModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSpaceModal(type, action, data = null) {
    document.getElementById('spaceType').value = type;
    document.getElementById('formAction').value = action;
    document.getElementById('spaceId').value = data ? (data[type + '_id'] || '') : '';
    
    const title = action === 'add' ? 'Add ' + type.charAt(0).toUpperCase() + type.slice(1) : 'Edit ' + type.charAt(0).toUpperCase() + type.slice(1);
    document.getElementById('modalTitle').textContent = title;
    
    let formContent = '';
    
    if (type === 'shop' || type === 'room') {
        formContent = `
            <div class="form-group">
                <label class="form-label">${type.charAt(0).toUpperCase() + type.slice(1)} Number *</label>
                <input type="text" class="form-control" name="${type}_number" id="${type}_number" required>
            </div>
            <div class="form-group">
                <label class="form-label">${type.charAt(0).toUpperCase() + type.slice(1)} Name</label>
                <input type="text" class="form-control" name="${type}_name" id="${type}_name">
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
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" id="description" rows="3"></textarea>
            </div>
        `;
    } else if (type === 'basement') {
        formContent = `
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
                <select class="form-control" name="space_type_val" id="space_type_val" required>
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
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" id="description" rows="3"></textarea>
            </div>
        `;
    }
    
    document.getElementById('spaceFormContent').innerHTML = formContent;
    
    if (data) {
        // Populate form with data
        Object.keys(data).forEach(key => {
            const field = document.getElementById(key);
            if (field) field.value = data[key] || '';
        });
    }
    
    document.getElementById('spaceModal').style.display = 'block';
}

function closeSpaceModal() {
    document.getElementById('spaceModal').style.display = 'none';
    document.getElementById('spaceForm').reset();
}

window.onclick = function(event) {
    const modal = document.getElementById('spaceModal');
    if (event.target == modal) {
        closeSpaceModal();
    }
}
</script>

<style>
.settings-tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid var(--border-color);
    margin-bottom: 2rem;
}

.tab-btn {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 1rem;
    color: var(--text-light);
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tab-btn:hover {
    color: var(--primary-color);
    background-color: var(--light-color);
}

.tab-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
    font-weight: 600;
}

.tab-content {
    min-height: 400px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}
</style>

<?php include '../includes/footer.php'; ?>

