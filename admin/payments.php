<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_payment') {
            $customer_id = intval($_POST['customer_id']);
            $agreement_id = !empty($_POST['agreement_id']) ? intval($_POST['agreement_id']) : null;
            $ledger_ids_str = $_POST['ledger_ids'] ?? '';
            $amount = floatval($_POST['amount']);
            $payment_date = $_POST['payment_date'];
            $payment_method = $_POST['payment_method'];
            $transaction_id = trim($_POST['transaction_id'] ?? '');
            $status = $_POST['status'];
            $notes = trim($_POST['notes'] ?? '');

            // Parse ledger IDs (comma-separated)
            $ledger_ids = [];
            if ($ledger_ids_str) {
                $ledger_ids = array_map('intval', explode(',', $ledger_ids_str));
                $ledger_ids = array_filter($ledger_ids); // Remove empty values
            }

            // Handle receipt file upload
            $receipt_file = null;
            if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = RECEIPT_UPLOAD_DIR;
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_ext = pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION);
                $receipt_file = 'receipt_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $receipt_file;
                
                if (!move_uploaded_file($_FILES['receipt_file']['tmp_name'], $upload_path)) {
                    $message = 'Error uploading receipt file.';
                    $message_type = 'danger';
                }
            }

            if (!$message) {
                // Use first ledger_id for the payment record (for backward compatibility)
                $primary_ledger_id = !empty($ledger_ids) ? $ledger_ids[0] : null;
                
                // Store all ledger IDs in notes for reference
                $notes_with_ledger_ids = $notes;
                if (!empty($ledger_ids)) {
                    $notes_with_ledger_ids = ($notes ? $notes . "\n\n" : '') . 'Ledger IDs: ' . implode(',', $ledger_ids);
                }
                
                $stmt = $conn->prepare("INSERT INTO payments (customer_id, agreement_id, ledger_id, amount, payment_date, payment_method, transaction_id, receipt_file, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiissssss", $customer_id, $agreement_id, $primary_ledger_id, $amount, $payment_date, $payment_method, $transaction_id, $receipt_file, $status, $notes_with_ledger_ids);
                
                if ($stmt->execute()) {
                    $payment_id = $conn->insert_id;
                    
                    // Update all selected ledger entries to paid status
                    if (!empty($ledger_ids)) {
                        $ledger_ids_str_safe = implode(',', array_map('intval', $ledger_ids));
                        $conn->query("UPDATE ledger SET status = 'paid' WHERE ledger_id IN ($ledger_ids_str_safe)");
                    }
                    
                    $message = 'Payment recorded successfully! ' . (count($ledger_ids) > 1 ? '(' . count($ledger_ids) . ' items combined)' : '');
                    $message_type = 'success';
                } else {
                    $message = 'Error recording payment: ' . $conn->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
    }
}

// Get all completed payments
$payments = $conn->query("SELECT p.*, c.full_name as customer_name, a.agreement_number FROM payments p 
                          JOIN customers c ON p.customer_id = c.customer_id 
                          LEFT JOIN agreements a ON p.agreement_id = a.agreement_id 
                          ORDER BY p.created_at DESC");

// Get pending/remaining balances (ledger entries with status pending or overdue)
$pending_balances = $conn->query("SELECT l.*, c.full_name as customer_name, a.agreement_number FROM ledger l 
                                  JOIN customers c ON l.customer_id = c.customer_id 
                                  LEFT JOIN agreements a ON l.agreement_id = a.agreement_id 
                                  WHERE l.status IN ('pending', 'overdue')
                                  ORDER BY l.payment_date ASC, l.created_at DESC");

// Get customers, agreements, and ledger entries for forms
$customers = $conn->query("SELECT customer_id, full_name, phone FROM customers WHERE status = 'active' ORDER BY full_name");
$agreements = $conn->query("SELECT agreement_id, agreement_number, customer_id FROM agreements ORDER BY agreement_number");
$ledger_entries = $conn->query("SELECT ledger_id, invoice_number, amount, customer_id, status, transaction_type FROM ledger WHERE status IN ('pending', 'overdue') ORDER BY created_at DESC");

$page_title = 'Payments - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-money-bill-wave"></i> Payments</h1>
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

    <!-- Pending/Remaining Balances Section -->
    <?php if ($pending_balances->num_rows > 0): ?>
    <div class="card" style="margin-bottom: 2rem; border-left: 4px solid var(--warning-color);">
        <div class="card-header" style="background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%); color: white; border-bottom: none; padding: 1rem 1.5rem;">
            <h2 class="card-title" style="color: white; margin: 0; font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-exclamation-triangle"></i> Pending/Remaining Balances
            </h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Agreement</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Invoice #</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($balance = $pending_balances->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($balance['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($balance['agreement_number'] ?? '-'); ?></td>
                            <td><span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $balance['transaction_type'])); ?></span></td>
                            <td><strong><?php echo formatCurrency($balance['amount']); ?></strong></td>
                            <td><?php echo formatDate($balance['payment_date']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $balance['status'] === 'overdue' ? 'danger' : 'warning'; ?>">
                                    <?php echo ucfirst($balance['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($balance['invoice_number'] ?? '-'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="payRemainingBalance(<?php echo htmlspecialchars(json_encode($balance)); ?>)" title="Pay Now">
                                    <i class="fas fa-money-bill"></i> Pay Now
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Payments Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-check-circle"></i> All Payments</h2>
        </div>
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
                                <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
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
                                        <a href="<?php echo BASE_URL; ?>uploads/receipts/<?php echo htmlspecialchars($payment['receipt_file']); ?>" target="_blank" class="btn btn-sm btn-primary" title="View Receipt">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($payment['ledger_id']): ?>
                                            <a href="<?php echo BASE_URL; ?>admin/print-invoice.php?ledger_id=<?php echo $payment['ledger_id']; ?>" target="_blank" class="btn btn-sm btn-primary" title="Print Invoice">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
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
</div>

<!-- Add/Edit Payment Modal -->
<div id="addPaymentModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 600px; margin: 5% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title" id="paymentModalTitle">Record Payment</h2>
            <button onclick="closePaymentModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" id="paymentForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="paymentFormAction" value="add_payment">
            <input type="hidden" name="payment_id" id="payment_id">
            
            <div class="form-group">
                <label class="form-label">Customer *</label>
                <select class="form-control" name="customer_id" id="payment_customer_id" required onchange="updatePaymentOptions()">
                    <option value="">Select Customer</option>
                    <?php 
                    $customers->data_seek(0);
                    while ($customer = $customers->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $customer['customer_id']; ?>">
                            <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['phone'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Agreement (Optional)</label>
                <select class="form-control" name="agreement_id" id="payment_agreement_id">
                    <option value="">Select Agreement</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Select Items to Pay (Select multiple for combined payment)</label>
                <div id="ledger_items_container" style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 0.375rem; padding: 0.75rem;">
                    <p style="color: var(--text-light); font-size: 0.875rem; margin: 0;">Select a customer first</p>
                </div>
                <input type="hidden" name="ledger_ids" id="payment_ledger_ids" value="">
                <small style="color: var(--text-light);">You can select multiple items (e.g., Rent + Security Deposit) to combine in one payment</small>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Total Amount *</label>
                    <input type="number" step="0.01" class="form-control" name="amount" id="payment_amount" required readonly style="background-color: var(--light-color); font-weight: bold;">
                    <small style="color: var(--text-light);">Auto-calculated from selected items</small>
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
                    <select class="form-control" name="status" id="payment_status" required>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Transaction ID</label>
                <input type="text" class="form-control" name="transaction_id" id="payment_transaction_id">
            </div>

            <div class="form-group">
                <label class="form-label">Receipt File</label>
                <input type="file" class="form-control" name="receipt_file" id="receipt_file" accept=".pdf,.jpg,.jpeg,.png">
                <small style="color: var(--text-light);">PDF or Image file</small>
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" id="payment_notes" rows="3"></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
const agreements = <?php echo json_encode($agreements->fetch_all(MYSQLI_ASSOC)); ?>;
const ledgerEntries = <?php echo json_encode($ledger_entries->fetch_all(MYSQLI_ASSOC)); ?>;

// Store ledger entries data for current customer
let currentLedgerEntries = [];

function updatePaymentOptions() {
    const customerId = document.getElementById('payment_customer_id').value;
    const agreementSelect = document.getElementById('payment_agreement_id');
    const ledgerContainer = document.getElementById('ledger_items_container');
    
    agreementSelect.innerHTML = '<option value="">Select Agreement</option>';
    ledgerContainer.innerHTML = '';
    currentLedgerEntries = [];
    
    if (!customerId) {
        ledgerContainer.innerHTML = '<p style="color: var(--text-light); font-size: 0.875rem; margin: 0;">Select a customer first</p>';
        updateTotalAmount();
        return;
    }
    
    agreements.filter(a => a.customer_id == customerId).forEach(agreement => {
        const opt = document.createElement('option');
        opt.value = agreement.agreement_id;
        opt.textContent = agreement.agreement_number;
        agreementSelect.appendChild(opt);
    });
    
    const filteredEntries = ledgerEntries.filter(l => l.customer_id == customerId && (l.status === 'pending' || l.status === 'overdue'));
    currentLedgerEntries = filteredEntries;
    
    if (filteredEntries.length === 0) {
        ledgerContainer.innerHTML = '<p style="color: var(--text-light); font-size: 0.875rem; margin: 0;">No pending balances found</p>';
    } else {
        filteredEntries.forEach(entry => {
            const div = document.createElement('div');
            div.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem; border-bottom: 1px solid var(--border-color);';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = entry.ledger_id;
            checkbox.id = 'ledger_' + entry.ledger_id;
            checkbox.setAttribute('data-amount', entry.amount);
            checkbox.setAttribute('data-type', entry.transaction_type || 'other');
            checkbox.addEventListener('change', updateTotalAmount);
            
            const label = document.createElement('label');
            label.htmlFor = 'ledger_' + entry.ledger_id;
            label.style.cssText = 'flex: 1; cursor: pointer; margin: 0;';
            label.innerHTML = `
                <strong>${entry.invoice_number || 'INV-' + entry.ledger_id}</strong> - 
                <span style="color: var(--primary-color); font-weight: 600;">${formatCurrency(entry.amount)}</span>
                <span style="color: var(--text-light); font-size: 0.875rem;">(${entry.transaction_type ? entry.transaction_type.replace('_', ' ') : 'Other'})</span>
            `;
            
            div.appendChild(checkbox);
            div.appendChild(label);
            ledgerContainer.appendChild(div);
        });
    }
    
    updateTotalAmount();
}

function updateTotalAmount() {
    const checkboxes = document.querySelectorAll('#ledger_items_container input[type="checkbox"]:checked');
    const ledgerIdsInput = document.getElementById('payment_ledger_ids');
    const amountInput = document.getElementById('payment_amount');
    
    let total = 0;
    const selectedIds = [];
    
    checkboxes.forEach(checkbox => {
        const amount = parseFloat(checkbox.getAttribute('data-amount')) || 0;
        total += amount;
        selectedIds.push(checkbox.value);
    });
    
    ledgerIdsInput.value = selectedIds.join(',');
    amountInput.value = total.toFixed(2);
}

function payRemainingBalance(balance) {
    document.getElementById('payment_customer_id').value = balance.customer_id;
    updatePaymentOptions();
    setTimeout(() => {
        // Check the specific ledger entry
        const checkbox = document.getElementById('ledger_' + balance.ledger_id);
        if (checkbox) {
            checkbox.checked = true;
            updateTotalAmount();
        }
        if (balance.agreement_id) {
            document.getElementById('payment_agreement_id').value = balance.agreement_id;
        }
    }, 100);
    document.getElementById('paymentModalTitle').textContent = 'Pay Remaining Balance';
    document.getElementById('addPaymentModal').style.display = 'block';
}

function closePaymentModal() {
    document.getElementById('addPaymentModal').style.display = 'none';
    document.getElementById('paymentForm').reset();
    document.getElementById('paymentFormAction').value = 'add_payment';
    document.getElementById('paymentModalTitle').textContent = 'Record Payment';
    document.getElementById('payment_date').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('ledger_items_container').innerHTML = '<p style="color: var(--text-light); font-size: 0.875rem; margin: 0;">Select a customer first</p>';
    document.getElementById('payment_ledger_ids').value = '';
    document.getElementById('payment_amount').value = '';
    currentLedgerEntries = [];
}

function formatCurrency(amount) {
    return 'Rs ' + parseFloat(amount).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

window.onclick = function(event) {
    const paymentModal = document.getElementById('addPaymentModal');
    if (event.target == paymentModal) {
        closePaymentModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
