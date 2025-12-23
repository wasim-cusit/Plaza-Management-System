<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update') {
            $request_id = intval($_POST['request_id']);
            $status = $_POST['status'];
            $assigned_to = trim($_POST['assigned_to']);
            $cost = floatval($_POST['cost']);
            $notes = trim($_POST['notes']);
            
            if ($status === 'completed') {
                $completed_date = date('Y-m-d');
                $stmt = $conn->prepare("UPDATE maintenance_requests SET status = ?, assigned_to = ?, cost = ?, notes = ?, completed_date = ? WHERE request_id = ?");
                $stmt->bind_param("ssdssi", $status, $assigned_to, $cost, $notes, $completed_date, $request_id);
            } else {
                $stmt = $conn->prepare("UPDATE maintenance_requests SET status = ?, assigned_to = ?, cost = ?, notes = ? WHERE request_id = ?");
                $stmt->bind_param("ssdsi", $status, $assigned_to, $cost, $notes, $request_id);
            }
            
            if ($stmt->execute()) {
                $message = 'Maintenance request updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating request: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $request_id = intval($_POST['request_id']);
            $stmt = $conn->prepare("DELETE FROM maintenance_requests WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            
            if ($stmt->execute()) {
                $message = 'Maintenance request deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting request: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

$filter_status = $_GET['status'] ?? '';
$where_clause = $filter_status ? "WHERE m.status = '$filter_status'" : '';

$requests = $conn->query("SELECT m.*, u.full_name as tenant_name FROM maintenance_requests m 
                          JOIN users u ON m.tenant_id = u.user_id 
                          $where_clause ORDER BY m.created_at DESC");

$page_title = 'Maintenance Management - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-tools"></i> Maintenance Management</h1>
        <form method="GET" style="display: inline;">
            <select class="form-control" name="status" onchange="this.form.submit()" style="display: inline-block; width: auto;">
                <option value="">All Status</option>
                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
        </form>
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
                    <th>Date</th>
                            <th>Customer</th>
                    <th>Space</th>
                    <th>Issue Type</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests->num_rows > 0): ?>
                    <?php while ($request = $requests->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($request['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($request['tenant_name']); ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo ucfirst($request['space_type']); ?></span>
                                #<?php echo $request['space_id']; ?>
                            </td>
                            <td><?php echo htmlspecialchars($request['issue_type'] ?? '-'); ?></td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo htmlspecialchars($request['description']); ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $request['priority'] === 'urgent' ? 'danger' : 
                                        ($request['priority'] === 'high' ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo ucfirst($request['priority']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $request['status'] === 'completed' ? 'success' : 
                                        ($request['status'] === 'in_progress' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($request['assigned_to'] ?? '-'); ?></td>
                            <td><?php echo formatCurrency($request['cost']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editRequest(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
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
                        <td colspan="10" style="text-align: center; color: var(--text-light);">No maintenance requests found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Update Request Modal -->
<div id="updateRequestModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 600px; margin: 5% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title">Update Maintenance Request</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" id="requestForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="request_id" id="request_id">
            
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select class="form-control" name="status" id="status" required>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Assigned To</label>
                <input type="text" class="form-control" name="assigned_to" id="assigned_to" placeholder="Staff name or department">
            </div>

            <div class="form-group">
                <label class="form-label">Cost</label>
                <input type="number" step="0.01" class="form-control" name="cost" id="cost" value="0">
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" id="notes" rows="4"></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('updateRequestModal').style.display = 'none';
    document.getElementById('requestForm').reset();
}

function editRequest(request) {
    document.getElementById('request_id').value = request.request_id;
    document.getElementById('status').value = request.status;
    document.getElementById('assigned_to').value = request.assigned_to || '';
    document.getElementById('cost').value = request.cost || 0;
    document.getElementById('notes').value = request.notes || '';
    document.getElementById('updateRequestModal').style.display = 'block';
}

window.onclick = function(event) {
    const modal = document.getElementById('updateRequestModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

