<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $tenant_id = intval($_POST['tenant_id']);
            $agreement_id = !empty($_POST['agreement_id']) ? intval($_POST['agreement_id']) : null;
            $ledger_id = !empty($_POST['ledger_id']) ? intval($_POST['ledger_id']) : null;
            $amount = floatval($_POST['amount']);
            $payment_date = $_POST['payment_date'];
            $payment_method = $_POST['payment_method'];
            $transaction_id = trim($_POST['transaction_id']);
            $status = $_POST['status'];
            $notes = trim($_POST['notes']);

            // Handle receipt file upload
            $receipt_file = null;
            if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = RECEIPT_UPLOAD_DIR;
                $file_ext = pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION);
                $receipt_file = 'receipt_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $receipt_file;
                
                if (!move_uploaded_file($_FILES['receipt_file']['tmp_name'], $upload_path)) {
                    $message = 'Error uploading receipt file.';
                    $message_type = 'danger';
                }
            }

            if (!$message) {
                $stmt = $conn->prepare("INSERT INTO payments (tenant_id, agreement_id, ledger_id, amount, payment_date, payment_method, transaction_id, receipt_file, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiissssss", $tenant_id, $agreement_id, $ledger_id, $amount, $payment_date, $payment_method, $transaction_id, $receipt_file, $status, $notes);
                
                if ($stmt->execute()) {
                    // Update ledger status if ledger_id is provided
                    if ($ledger_id) {
                        $conn->query("UPDATE ledger SET status = 'paid' WHERE ledger_id = $ledger_id");
                    }
                    
                    $message = 'Payment recorded successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error recording payment: ' . $conn->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'update') {
            $payment_id = intval($_POST['payment_id']);
            $tenant_id = intval($_POST['tenant_id']);
            $agreement_id = !empty($_POST['agreement_id']) ? intval($_POST['agreement_id']) : null;
            $ledger_id = !empty($_POST['ledger_id']) ? intval($_POST['ledger_id']) : null;
            $amount = floatval($_POST['amount']);
            $payment_date = $_POST['payment_date'];
            $payment_method = $_POST['payment_method'];
            $transaction_id = trim($_POST['transaction_id']);
            $status = $_POST['status'];
            $notes = trim($_POST['notes']);

            // Handle receipt file upload
            $receipt_file = null;
            if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = RECEIPT_UPLOAD_DIR;
                $file_ext = pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION);
                $receipt_file = 'receipt_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $receipt_file;
                
                if (move_uploaded_file($_FILES['receipt_file']['tmp_name'], $upload_path)) {
                    // Delete old file
                    $old_payment = $conn->query("SELECT receipt_file FROM payments WHERE payment_id = $payment_id")->fetch_assoc();
                    if ($old_payment && $old_payment['receipt_file']) {
                        $old_file = $upload_dir . $old_payment['receipt_file'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                }
            } else {
                $existing = $conn->query("SELECT receipt_file FROM payments WHERE payment_id = $payment_id")->fetch_assoc();
                $receipt_file = $existing['receipt_file'];
            }

            if ($receipt_file) {
                $stmt = $conn->prepare("UPDATE payments SET tenant_id = ?, agreement_id = ?, ledger_id = ?, amount = ?, payment_date = ?, payment_method = ?, transaction_id = ?, receipt_file = ?, status = ?, notes = ? WHERE payment_id = ?");
                $stmt->bind_param("iiiissssssi", $tenant_id, $agreement_id, $ledger_id, $amount, $payment_date, $payment_method, $transaction_id, $receipt_file, $status, $notes, $payment_id);
            } else {
                $stmt = $conn->prepare("UPDATE payments SET tenant_id = ?, agreement_id = ?, ledger_id = ?, amount = ?, payment_date = ?, payment_method = ?, transaction_id = ?, status = ?, notes = ? WHERE payment_id = ?");
                $stmt->bind_param("iiiisssssi", $tenant_id, $agreement_id, $ledger_id, $amount, $payment_date, $payment_method, $transaction_id, $status, $notes, $payment_id);
            }
            
            if ($stmt->execute()) {
                $message = 'Payment updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating payment: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

$payments = $conn->query("SELECT p.*, u.full_name as tenant_name, a.agreement_number FROM payments p 
                          JOIN users u ON p.tenant_id = u.user_id 
                          LEFT JOIN agreements a ON p.agreement_id = a.agreement_id 
                          ORDER BY p.created_at DESC");

$tenants = $conn->query("SELECT user_id, full_name, username FROM users WHERE user_type = 'tenant' ORDER BY full_name");
$agreements = $conn->query("SELECT agreement_id, agreement_number, tenant_id FROM agreements ORDER BY agreement_number");
$ledger_entries = $conn->query("SELECT ledger_id, invoice_number, amount, tenant_id FROM ledger WHERE status != 'paid' ORDER BY created_at DESC");

$page_title = 'Payment Management - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-money-bill-wave"></i> Payment Management</h1>
        <button class="btn btn-primary" onclick="document.getElementById('addPaymentModal').style.display='block'">
            <i class="fas fa-plus"></i> Record Payment
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
                            <th>Customer</th>
                    <th>Agreement</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Transaction ID</th>
                    <th>Status</th>
                    <th>Receipt</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments->num_rows > 0): ?>
                    <?php while ($payment = $payments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($payment['payment_date']); ?></td>
                            <td><?php echo htmlspecialchars($payment['tenant_name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['agreement_number'] ?? '-'); ?></td>
                            <td><?php echo formatCurrency($payment['amount']); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                            <td><?php echo htmlspecialchars($payment['transaction_id'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $payment['status'] === 'completed' ? 'success' : 
                                        ($payment['status'] === 'failed' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($payment['receipt_file']): ?>
                                    <a href="<?php echo BASE_URL; ?>uploads/receipts/<?php echo htmlspecialchars($payment['receipt_file']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> View
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editPayment(<?php echo htmlspecialchars(json_encode($payment)); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-light);">No payments found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Payment Modal -->
<div id="addPaymentModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 600px; margin: 5% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title" id="modalTitle">Record Payment</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" id="paymentForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="payment_id" id="payment_id">
            
            <div class="form-group">
                <label class="form-label">Customer *</label>
                <select class="form-control" name="tenant_id" id="tenant_id" required onchange="updateOptions()">
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
                <label class="form-label">Agreement (Optional)</label>
                <select class="form-control" name="agreement_id" id="agreement_id">
                    <option value="">Select Agreement</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Ledger Entry (Optional)</label>
                <select class="form-control" name="ledger_id" id="ledger_id">
                    <option value="">Select Ledger Entry</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Amount *</label>
                    <input type="number" step="0.01" class="form-control" name="amount" id="amount" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Date *</label>
                    <input type="date" class="form-control" name="payment_date" id="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Payment Method *</label>
                    <select class="form-control" name="payment_method" id="payment_method" required>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="online">Online</option>
                        <option value="check">Check</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select class="form-control" name="status" id="status" required>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Transaction ID</label>
                <input type="text" class="form-control" name="transaction_id" id="transaction_id">
            </div>

            <div class="form-group">
                <label class="form-label">Receipt File</label>
                <input type="file" class="form-control" name="receipt_file" id="receipt_file" accept=".pdf,.jpg,.jpeg,.png">
                <small style="color: var(--text-light);">PDF or Image file</small>
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
const agreements = <?php echo json_encode($agreements->fetch_all(MYSQLI_ASSOC)); ?>;
const ledgerEntries = <?php echo json_encode($ledger_entries->fetch_all(MYSQLI_ASSOC)); ?>;

function updateOptions() {
    const tenantId = document.getElementById('tenant_id').value;
    const agreementSelect = document.getElementById('agreement_id');
    const ledgerSelect = document.getElementById('ledger_id');
    
    agreementSelect.innerHTML = '<option value="">Select Agreement</option>';
    ledgerSelect.innerHTML = '<option value="">Select Ledger Entry</option>';
    
    agreements.filter(a => a.tenant_id == tenantId).forEach(agreement => {
        const opt = document.createElement('option');
        opt.value = agreement.agreement_id;
        opt.textContent = agreement.agreement_number;
        agreementSelect.appendChild(opt);
    });
    
    ledgerEntries.filter(l => l.tenant_id == tenantId).forEach(entry => {
        const opt = document.createElement('option');
        opt.value = entry.ledger_id;
        opt.textContent = entry.invoice_number + ' - ' + entry.amount;
        ledgerSelect.appendChild(opt);
    });
}

function closeModal() {
    document.getElementById('addPaymentModal').style.display = 'none';
    document.getElementById('paymentForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Record Payment';
    document.getElementById('payment_date').value = '<?php echo date('Y-m-d'); ?>';
}

function editPayment(payment) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('payment_id').value = payment.payment_id;
    document.getElementById('tenant_id').value = payment.tenant_id;
    updateOptions();
    setTimeout(() => {
        document.getElementById('agreement_id').value = payment.agreement_id || '';
        document.getElementById('ledger_id').value = payment.ledger_id || '';
    }, 100);
    document.getElementById('amount').value = payment.amount;
    document.getElementById('payment_date').value = payment.payment_date;
    document.getElementById('payment_method').value = payment.payment_method;
    document.getElementById('status').value = payment.status;
    document.getElementById('transaction_id').value = payment.transaction_id || '';
    document.getElementById('notes').value = payment.notes || '';
    document.getElementById('modalTitle').textContent = 'Edit Payment';
    document.getElementById('addPaymentModal').style.display = 'block';
}

window.onclick = function(event) {
    const modal = document.getElementById('addPaymentModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

