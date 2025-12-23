<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $agreement_number = trim($_POST['agreement_number']);
            $tenant_id = intval($_POST['tenant_id']);
            $space_type = $_POST['space_type'];
            $space_id = intval($_POST['space_id']);
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $monthly_rent = floatval($_POST['monthly_rent']);
            $security_deposit = floatval($_POST['security_deposit']);
            $terms = trim($_POST['terms']);
            $status = $_POST['status'];

            // Handle file upload
            $document_file = null;
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = AGREEMENT_UPLOAD_DIR;
                $file_ext = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
                $document_file = 'agreement_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $document_file;
                
                if (move_uploaded_file($_FILES['document_file']['tmp_name'], $upload_path)) {
                    // File uploaded successfully
                } else {
                    $message = 'Error uploading document file.';
                    $message_type = 'danger';
                }
            }

            if (!$message) {
                $stmt = $conn->prepare("INSERT INTO agreements (agreement_number, tenant_id, space_type, space_id, start_date, end_date, monthly_rent, security_deposit, terms, status, document_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sissiiddsss", $agreement_number, $tenant_id, $space_type, $space_id, $start_date, $end_date, $monthly_rent, $security_deposit, $terms, $status, $document_file);
                
                if ($stmt->execute()) {
                    // Update space status to occupied
                    if ($space_type === 'shop') {
                        $conn->query("UPDATE shops SET status = 'occupied', tenant_id = $tenant_id WHERE shop_id = $space_id");
                    } elseif ($space_type === 'room') {
                        $conn->query("UPDATE rooms SET status = 'occupied', tenant_id = $tenant_id WHERE room_id = $space_id");
                    } elseif ($space_type === 'basement') {
                        $conn->query("UPDATE basements SET status = 'occupied', tenant_id = $tenant_id WHERE basement_id = $space_id");
                    }

                    $message = 'Agreement created successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error creating agreement: ' . $conn->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'update') {
            $agreement_id = intval($_POST['agreement_id']);
            $agreement_number = trim($_POST['agreement_number']);
            $tenant_id = intval($_POST['tenant_id']);
            $space_type = $_POST['space_type'];
            $space_id = intval($_POST['space_id']);
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $monthly_rent = floatval($_POST['monthly_rent']);
            $security_deposit = floatval($_POST['security_deposit']);
            $terms = trim($_POST['terms']);
            $status = $_POST['status'];

            // Handle file upload
            $document_file = null;
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = AGREEMENT_UPLOAD_DIR;
                $file_ext = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
                $document_file = 'agreement_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $document_file;
                
                if (move_uploaded_file($_FILES['document_file']['tmp_name'], $upload_path)) {
                    // Delete old file if exists
                    $old_agreement = $conn->query("SELECT document_file FROM agreements WHERE agreement_id = $agreement_id")->fetch_assoc();
                    if ($old_agreement && $old_agreement['document_file']) {
                        $old_file = $upload_dir . $old_agreement['document_file'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                } else {
                    $message = 'Error uploading document file.';
                    $message_type = 'danger';
                }
            } else {
                // Keep existing file
                $existing = $conn->query("SELECT document_file FROM agreements WHERE agreement_id = $agreement_id")->fetch_assoc();
                $document_file = $existing['document_file'];
            }

            if (!$message) {
                if ($document_file) {
                    $stmt = $conn->prepare("UPDATE agreements SET agreement_number = ?, tenant_id = ?, space_type = ?, space_id = ?, start_date = ?, end_date = ?, monthly_rent = ?, security_deposit = ?, terms = ?, status = ?, document_file = ? WHERE agreement_id = ?");
                    $stmt->bind_param("sissiiddsssi", $agreement_number, $tenant_id, $space_type, $space_id, $start_date, $end_date, $monthly_rent, $security_deposit, $terms, $status, $document_file, $agreement_id);
                } else {
                    $stmt = $conn->prepare("UPDATE agreements SET agreement_number = ?, tenant_id = ?, space_type = ?, space_id = ?, start_date = ?, end_date = ?, monthly_rent = ?, security_deposit = ?, terms = ?, status = ? WHERE agreement_id = ?");
                    $stmt->bind_param("sissiiddssi", $agreement_number, $tenant_id, $space_type, $space_id, $start_date, $end_date, $monthly_rent, $security_deposit, $terms, $status, $agreement_id);
                }
                
                if ($stmt->execute()) {
                    $message = 'Agreement updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating agreement: ' . $conn->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $agreement_id = intval($_POST['agreement_id']);
            
            // Delete document file
            $agreement = $conn->query("SELECT document_file FROM agreements WHERE agreement_id = $agreement_id")->fetch_assoc();
            if ($agreement && $agreement['document_file']) {
                $file_path = AGREEMENT_UPLOAD_DIR . $agreement['document_file'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            $stmt = $conn->prepare("DELETE FROM agreements WHERE agreement_id = ?");
            $stmt->bind_param("i", $agreement_id);
            
            if ($stmt->execute()) {
                $message = 'Agreement deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting agreement: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

$agreements = $conn->query("SELECT a.*, u.full_name as tenant_name, u.username FROM agreements a JOIN users u ON a.tenant_id = u.user_id ORDER BY a.created_at DESC");
$tenants = $conn->query("SELECT user_id, full_name, username FROM users WHERE user_type = 'tenant' ORDER BY full_name");
$shops = $conn->query("SELECT shop_id, shop_number, shop_name FROM shops WHERE status = 'available' OR status = 'occupied' ORDER BY shop_number");
$rooms = $conn->query("SELECT room_id, room_number, room_name FROM rooms WHERE status = 'available' OR status = 'occupied' ORDER BY room_number");
$basements = $conn->query("SELECT basement_id, basement_number, basement_name FROM basements WHERE status = 'available' OR status = 'occupied' ORDER BY basement_number");

$page_title = 'Agreement Management - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-file-contract"></i> Agreement Management</h1>
        <button class="btn btn-primary" onclick="document.getElementById('addAgreementModal').style.display='block'">
            <i class="fas fa-plus"></i> Create Agreement
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
                    <th>Agreement #</th>
                            <th>Customer</th>
                    <th>Space Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Monthly Rent</th>
                    <th>Status</th>
                    <th>Document</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($agreements->num_rows > 0): ?>
                    <?php while ($agreement = $agreements->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($agreement['agreement_number']); ?></td>
                            <td><?php echo htmlspecialchars($agreement['tenant_name']); ?></td>
                            <td><span class="badge badge-info"><?php echo ucfirst($agreement['space_type']); ?></span></td>
                            <td><?php echo formatDate($agreement['start_date']); ?></td>
                            <td><?php echo formatDate($agreement['end_date']); ?></td>
                            <td><?php echo formatCurrency($agreement['monthly_rent']); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $agreement['status'] === 'active' ? 'success' : 
                                        ($agreement['status'] === 'expired' ? 'danger' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($agreement['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($agreement['document_file']): ?>
                                    <a href="<?php echo BASE_URL; ?>uploads/agreements/<?php echo htmlspecialchars($agreement['document_file']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> View
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">No document</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editAgreement(<?php echo htmlspecialchars(json_encode($agreement)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="agreement_id" value="<?php echo $agreement['agreement_id']; ?>">
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
                        <td colspan="9" style="text-align: center; color: var(--text-light);">No agreements found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Agreement Modal -->
<div id="addAgreementModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 700px; margin: 3% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title" id="modalTitle">Create Agreement</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" id="agreementForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="agreement_id" id="agreement_id">
            
            <div class="form-group">
                <label class="form-label">Agreement Number *</label>
                <input type="text" class="form-control" name="agreement_number" id="agreement_number" required>
            </div>

            <div class="form-group">
                <label class="form-label">Customer *</label>
                <select class="form-control" name="tenant_id" id="tenant_id" required>
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
                <label class="form-label">Space Type *</label>
                <select class="form-control" name="space_type" id="space_type" required onchange="updateSpaceOptions()">
                    <option value="">Select Type</option>
                    <option value="shop">Shop</option>
                    <option value="room">Room</option>
                    <option value="basement">Basement</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Space *</label>
                <select class="form-control" name="space_id" id="space_id" required>
                    <option value="">Select Space</option>
                </select>
            </div>

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
                    <input type="number" step="0.01" class="form-control" name="monthly_rent" id="monthly_rent" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Security Deposit</label>
                    <input type="number" step="0.01" class="form-control" name="security_deposit" id="security_deposit" value="0">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Status *</label>
                <select class="form-control" name="status" id="status" required>
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="terminated">Terminated</option>
                    <option value="renewed">Renewed</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Terms & Conditions</label>
                <textarea class="form-control" name="terms" id="terms" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Agreement Document (PDF/DOC)</label>
                <input type="file" class="form-control" name="document_file" id="document_file" accept=".pdf,.doc,.docx">
                <small style="color: var(--text-light);">Max file size: 5MB</small>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Agreement</button>
            </div>
        </form>
    </div>
</div>

<script>
const shops = <?php echo json_encode($shops->fetch_all(MYSQLI_ASSOC)); ?>;
const rooms = <?php echo json_encode($rooms->fetch_all(MYSQLI_ASSOC)); ?>;
const basements = <?php echo json_encode($basements->fetch_all(MYSQLI_ASSOC)); ?>;

function updateSpaceOptions() {
    const spaceType = document.getElementById('space_type').value;
    const spaceSelect = document.getElementById('space_id');
    spaceSelect.innerHTML = '<option value="">Select Space</option>';
    
    let options = [];
    if (spaceType === 'shop') {
        options = shops;
    } else if (spaceType === 'room') {
        options = rooms;
    } else if (spaceType === 'basement') {
        options = basements;
    }
    
    options.forEach(option => {
        const opt = document.createElement('option');
        opt.value = option[spaceType + '_id'];
        opt.textContent = option[spaceType + '_number'] + (option[spaceType + '_name'] ? ' - ' + option[spaceType + '_name'] : '');
        spaceSelect.appendChild(opt);
    });
}

function closeModal() {
    document.getElementById('addAgreementModal').style.display = 'none';
    document.getElementById('agreementForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Create Agreement';
    document.getElementById('space_id').innerHTML = '<option value="">Select Space</option>';
}

function editAgreement(agreement) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('agreement_id').value = agreement.agreement_id;
    document.getElementById('agreement_number').value = agreement.agreement_number;
    document.getElementById('tenant_id').value = agreement.tenant_id;
    document.getElementById('space_type').value = agreement.space_type;
    updateSpaceOptions();
    setTimeout(() => {
        document.getElementById('space_id').value = agreement.space_id;
    }, 100);
    document.getElementById('start_date').value = agreement.start_date;
    document.getElementById('end_date').value = agreement.end_date;
    document.getElementById('monthly_rent').value = agreement.monthly_rent;
    document.getElementById('security_deposit').value = agreement.security_deposit;
    document.getElementById('status').value = agreement.status;
    document.getElementById('terms').value = agreement.terms || '';
    document.getElementById('modalTitle').textContent = 'Edit Agreement';
    document.getElementById('addAgreementModal').style.display = 'block';
}

window.onclick = function(event) {
    const modal = document.getElementById('addAgreementModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

