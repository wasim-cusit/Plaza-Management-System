<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = $_SESSION['success'] ?? $_SESSION['error'] ?? '';
$message_type = isset($_SESSION['success']) ? 'success' : (isset($_SESSION['error']) ? 'danger' : '');
unset($_SESSION['success'], $_SESSION['error']);

// Get all assigned spaces with complete data
$assigned_spaces = [];

// Get shops with customer and agreement info
$shops_query = "SELECT 
    'shop' as space_type,
    s.shop_id as space_id,
    s.shop_number as space_number,
    s.shop_name as space_name,
    s.floor_number,
    s.area_sqft,
    s.monthly_rent,
    s.status as space_status,
    s.customer_id,
    c.full_name as customer_name,
    c.phone as customer_phone,
    c.email as customer_email,
    c.gender as customer_gender,
    a.agreement_id,
    a.agreement_number,
    a.start_date,
    a.end_date,
    a.monthly_rent as agreement_rent,
    a.security_deposit,
    a.status as agreement_status,
    a.document_file,
    a.terms,
    a.created_at as agreement_date
FROM shops s
LEFT JOIN customers c ON s.customer_id = c.customer_id
LEFT JOIN agreements a ON s.customer_id = a.customer_id AND a.space_type = 'shop' AND a.space_id = s.shop_id AND a.status = 'active'
WHERE s.status = 'occupied' AND s.customer_id IS NOT NULL
ORDER BY s.created_at DESC";

$shops_result = $conn->query($shops_query);
while ($row = $shops_result->fetch_assoc()) {
    $assigned_spaces[] = $row;
}

// Get rooms with customer and agreement info
$rooms_query = "SELECT 
    'room' as space_type,
    r.room_id as space_id,
    r.room_number as space_number,
    r.room_name as space_name,
    r.floor_number,
    r.area_sqft,
    r.monthly_rent,
    r.status as space_status,
    r.customer_id,
    c.full_name as customer_name,
    c.phone as customer_phone,
    c.email as customer_email,
    c.gender as customer_gender,
    a.agreement_id,
    a.agreement_number,
    a.start_date,
    a.end_date,
    a.monthly_rent as agreement_rent,
    a.security_deposit,
    a.status as agreement_status,
    a.document_file,
    a.terms,
    a.created_at as agreement_date
FROM rooms r
LEFT JOIN customers c ON r.customer_id = c.customer_id
LEFT JOIN agreements a ON r.customer_id = a.customer_id AND a.space_type = 'room' AND a.space_id = r.room_id AND a.status = 'active'
WHERE r.status = 'occupied' AND r.customer_id IS NOT NULL
ORDER BY r.created_at DESC";

$rooms_result = $conn->query($rooms_query);
while ($row = $rooms_result->fetch_assoc()) {
    $assigned_spaces[] = $row;
}

// Get basements with customer and agreement info
$basements_query = "SELECT 
    'basement' as space_type,
    b.basement_id as space_id,
    b.basement_number as space_number,
    b.basement_name as space_name,
    NULL as floor_number,
    b.area_sqft,
    b.monthly_rent,
    b.status as space_status,
    b.customer_id,
    c.full_name as customer_name,
    c.phone as customer_phone,
    c.email as customer_email,
    c.gender as customer_gender,
    a.agreement_id,
    a.agreement_number,
    a.start_date,
    a.end_date,
    a.monthly_rent as agreement_rent,
    a.security_deposit,
    a.status as agreement_status,
    a.document_file,
    a.terms,
    a.created_at as agreement_date
FROM basements b
LEFT JOIN customers c ON b.customer_id = c.customer_id
LEFT JOIN agreements a ON b.customer_id = a.customer_id AND a.space_type = 'basement' AND a.space_id = b.basement_id AND a.status = 'active'
WHERE b.status = 'occupied' AND b.customer_id IS NOT NULL
ORDER BY b.created_at DESC";

$basements_result = $conn->query($basements_query);
while ($row = $basements_result->fetch_assoc()) {
    $assigned_spaces[] = $row;
}

$page_title = 'Assigned Spaces - Plaza Management System';
include '../includes/header.php';
?>

<div class="card" style="display: flex; flex-direction: column; height: calc(100vh - 200px); max-height: calc(100vh - 200px); overflow: hidden;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
        <h1 class="card-title"><i class="fas fa-list"></i> All Assigned Spaces</h1>
        <a href="spaces.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Spaces
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>" style="flex-shrink: 0; margin: 0;">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="table-container" style="flex: 1; overflow: auto; min-height: 0; position: relative;">
        <table class="table" style="min-width: 1400px; width: 100%; margin: 0; border-collapse: separate; border-spacing: 0;">
            <thead>
                <tr>
                    <th style="min-width: 100px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Space Type</th>
                    <th style="min-width: 120px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Space Number</th>
                    <th style="min-width: 120px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Space Name</th>
                    <th style="min-width: 80px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Floor</th>
                    <th style="min-width: 100px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Area (sqft)</th>
                    <th style="min-width: 180px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Customer</th>
                    <th style="min-width: 140px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Agreement #</th>
                    <th style="min-width: 110px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Start Date</th>
                    <th style="min-width: 110px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">End Date</th>
                    <th style="min-width: 120px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Monthly Rent</th>
                    <th style="min-width: 130px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Security Deposit</th>
                    <th style="min-width: 100px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Status</th>
                    <th style="min-width: 200px; color: white; font-weight: 600; padding: 1rem 0.75rem; border-bottom: 2px solid rgba(255,255,255,0.2);">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($assigned_spaces) > 0): ?>
                    <?php foreach ($assigned_spaces as $space): ?>
                        <tr>
                            <td>
                                <span class="badge badge-info"><?php echo ucfirst($space['space_type']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($space['space_number']); ?></td>
                            <td><?php echo htmlspecialchars($space['space_name'] ?: '-'); ?></td>
                            <td><?php echo $space['floor_number'] ?: '-'; ?></td>
                            <td><?php echo number_format($space['area_sqft'], 2); ?></td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($space['customer_name']); ?></strong>
                                    <br>
                                    <small style="color: var(--text-light);">
                                        <?php echo htmlspecialchars($space['customer_phone']); ?>
                                        <?php if ($space['customer_email']): ?>
                                            <br><?php echo htmlspecialchars($space['customer_email']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <?php if ($space['agreement_number']): ?>
                                    <a href="print-agreement.php?id=<?php echo $space['agreement_id']; ?>" target="_blank" style="color: var(--primary-color);">
                                        <?php echo htmlspecialchars($space['agreement_number']); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">No Agreement</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $space['start_date'] ? formatDate($space['start_date']) : '-'; ?></td>
                            <td><?php echo $space['end_date'] ? formatDate($space['end_date']) : '-'; ?></td>
                            <td><?php echo formatCurrency($space['agreement_rent'] ?: $space['monthly_rent']); ?></td>
                            <td><?php echo formatCurrency($space['security_deposit'] ?: 0); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $space['agreement_status'] === 'active' ? 'success' : 
                                        ($space['agreement_status'] === 'expired' ? 'danger' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($space['agreement_status'] ?: 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons" style="display: flex; gap: 0.25rem; flex-wrap: nowrap; white-space: nowrap;">
                                    <button class="btn btn-sm btn-primary" onclick="editAssignment(<?php echo htmlspecialchars(json_encode($space)); ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($space['agreement_id']): ?>
                                        <a href="print-agreement.php?id=<?php echo $space['agreement_id']; ?>" target="_blank" class="btn btn-sm btn-secondary" title="View Agreement">
                                            <i class="fas fa-file-contract"></i>
                                        </a>
                                        <?php if ($space['document_file']): ?>
                                            <a href="<?php echo BASE_URL; ?>uploads/agreements/<?php echo htmlspecialchars($space['document_file']); ?>" download class="btn btn-sm btn-info" title="Download Agreement">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-success" onclick="viewInvoices(<?php echo $space['agreement_id']; ?>, <?php echo $space['customer_id']; ?>)" title="View Invoices">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </button>
                                    <?php endif; ?>
                                    <a href="customer-details.php?customer_id=<?php echo $space['customer_id']; ?>" class="btn btn-sm btn-warning" title="View Customer">
                                        <i class="fas fa-user"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13" style="text-align: center; color: var(--text-light); padding: 2rem;">
                            <i class="fas fa-building" style="font-size: 3rem; color: var(--text-light); margin-bottom: 1rem; display: block;"></i>
                            No spaces assigned yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Assignment Modal -->
<div id="editModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 700px; margin: 3% auto; position: relative; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h2 class="card-title">Edit Assignment</h2>
            <button onclick="closeEditModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-color);">&times;</button>
        </div>
        <form method="POST" action="update-assignment.php" id="editForm">
            <input type="hidden" name="agreement_id" id="edit_agreement_id">
            <input type="hidden" name="space_id" id="edit_space_id">
            <input type="hidden" name="space_type" id="edit_space_type">
            <input type="hidden" name="customer_id" id="edit_customer_id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Start Date *</label>
                    <input type="date" class="form-control" name="start_date" id="edit_start_date" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End Date *</label>
                    <input type="date" class="form-control" name="end_date" id="edit_end_date" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Monthly Rent *</label>
                    <input type="number" step="0.01" class="form-control" name="monthly_rent" id="edit_monthly_rent" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Security Deposit</label>
                    <input type="number" step="0.01" class="form-control" name="security_deposit" id="edit_security_deposit" value="0">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Agreement Status *</label>
                <select class="form-control" name="status" id="edit_status" required>
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="terminated">Terminated</option>
                    <option value="renewed">Renewed</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Terms & Conditions</label>
                <textarea class="form-control" name="terms" id="edit_terms" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Agreement Document (PDF/DOC)</label>
                <input type="file" class="form-control" name="agreement_document" accept=".pdf,.doc,.docx">
                <small style="color: var(--text-light);">Upload new document to replace existing</small>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Assignment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Invoices Modal -->
<div id="invoicesModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 900px; margin: 3% auto; position: relative; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h2 class="card-title">Invoices</h2>
            <button onclick="closeInvoicesModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-color);">&times;</button>
        </div>
        <div id="invoicesContent" style="padding: 1.5rem;">
            <!-- Invoices will be loaded here -->
        </div>
    </div>
</div>

<script>
function editAssignment(space) {
    document.getElementById('edit_agreement_id').value = space.agreement_id || '';
    document.getElementById('edit_space_id').value = space.space_id;
    document.getElementById('edit_space_type').value = space.space_type;
    document.getElementById('edit_customer_id').value = space.customer_id;
    document.getElementById('edit_start_date').value = space.start_date || '';
    document.getElementById('edit_end_date').value = space.end_date || '';
    document.getElementById('edit_monthly_rent').value = space.agreement_rent || space.monthly_rent || '';
    document.getElementById('edit_security_deposit').value = space.security_deposit || '0';
    document.getElementById('edit_status').value = space.agreement_status || 'active';
    document.getElementById('edit_terms').value = space.terms || '';
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('editForm').reset();
}

function viewInvoices(agreementId, customerId) {
    // Load invoices via AJAX
    fetch('get-invoices.php?agreement_id=' + agreementId + '&customer_id=' + customerId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('invoicesContent').innerHTML = html;
            document.getElementById('invoicesModal').style.display = 'block';
        })
        .catch(error => {
            document.getElementById('invoicesContent').innerHTML = '<div class="alert alert-danger">Error loading invoices.</div>';
            document.getElementById('invoicesModal').style.display = 'block';
        });
}

function closeInvoicesModal() {
    document.getElementById('invoicesModal').style.display = 'none';
    document.getElementById('invoicesContent').innerHTML = '';
}

window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const invoicesModal = document.getElementById('invoicesModal');
    if (event.target == editModal) {
        closeEditModal();
    }
    if (event.target == invoicesModal) {
        closeInvoicesModal();
    }
}
</script>

<style>
.action-buttons .btn {
    min-width: 35px;
}

.table-container {
    position: relative;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    background: white;
    width: 100%;
    height: 100%;
}

/* Table header sticky */
.table-container thead {
    position: sticky;
    top: 0;
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    color: white;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.table-container table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.table-container thead th {
    white-space: nowrap;
    padding: 1rem 0.75rem;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
}

.table-container tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color);
    background: white;
}

.table-container tbody tr:hover td {
    background: var(--light-color);
}


.table-container tbody tr:last-child td {
    border-bottom: none;
}

/* Custom scrollbar */
.table-container::-webkit-scrollbar {
    height: 10px;
    width: 10px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 5px;
}

.table-container::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 5px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}

/* Prevent page movement */
body {
    overflow-x: hidden;
}

.main-wrapper {
    overflow: hidden;
}

.card {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Ensure no layout shift */
.table-container tbody tr {
    transition: none;
}
</style>

<?php include '../includes/footer.php'; ?>

