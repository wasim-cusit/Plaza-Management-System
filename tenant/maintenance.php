<?php
require_once '../config/config.php';
requireTenant();

$conn = getDBConnection();
$tenant_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $space_type = $_POST['space_type'];
    $space_id = intval($_POST['space_id']);
    $issue_type = trim($_POST['issue_type']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];

    $stmt = $conn->prepare("INSERT INTO maintenance_requests (tenant_id, space_type, space_id, issue_type, description, priority) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisss", $tenant_id, $space_type, $space_id, $issue_type, $description, $priority);
    
    if ($stmt->execute()) {
        $message = 'Maintenance request submitted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Error submitting request: ' . $conn->error;
        $message_type = 'danger';
    }
    $stmt->close();
}

// Get tenant's agreements to populate space options
$agreements = $conn->query("SELECT * FROM agreements WHERE tenant_id = $tenant_id AND status = 'active'");

$requests = $conn->query("SELECT * FROM maintenance_requests WHERE tenant_id = $tenant_id ORDER BY created_at DESC");

$page_title = 'Maintenance Requests - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-tools"></i> Maintenance Requests</h1>
        <button class="btn btn-primary" onclick="document.getElementById('addRequestModal').style.display='block'">
            <i class="fas fa-plus"></i> Submit Request
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
                    <th>Date</th>
                    <th>Space</th>
                    <th>Issue Type</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Cost</th>
                    <th>Completed Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests->num_rows > 0): ?>
                    <?php while ($request = $requests->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($request['created_at']); ?></td>
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
                            <td><?php echo $request['completed_date'] ? formatDate($request['completed_date']) : '-'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-light);">No maintenance requests found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Request Modal -->
<div id="addRequestModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 600px; margin: 5% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title">Submit Maintenance Request</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" id="requestForm">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label class="form-label">Space Type *</label>
                <select class="form-control" name="space_type" id="space_type" required>
                    <option value="">Select Type</option>
                    <option value="shop">Shop</option>
                    <option value="room">Room</option>
                    <option value="basement">Basement</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Space ID *</label>
                <input type="number" class="form-control" name="space_id" id="space_id" required placeholder="Enter space ID">
            </div>

            <div class="form-group">
                <label class="form-label">Issue Type *</label>
                <select class="form-control" name="issue_type" id="issue_type" required>
                    <option value="">Select Issue Type</option>
                    <option value="plumbing">Plumbing</option>
                    <option value="electrical">Electrical</option>
                    <option value="cleaning">Cleaning</option>
                    <option value="heating">Heating/Cooling</option>
                    <option value="security">Security</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Priority *</label>
                <select class="form-control" name="priority" id="priority" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Description *</label>
                <textarea class="form-control" name="description" id="description" rows="4" required placeholder="Describe the issue in detail..."></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('addRequestModal').style.display = 'none';
    document.getElementById('requestForm').reset();
}

window.onclick = function(event) {
    const modal = document.getElementById('addRequestModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

