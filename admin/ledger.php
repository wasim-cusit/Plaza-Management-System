<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $customer_id = intval($_POST['customer_id']);
            $agreement_id = !empty($_POST['agreement_id']) ? intval($_POST['agreement_id']) : null;
            $transaction_type = $_POST['transaction_type'];
            $amount = floatval($_POST['amount']);
            $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
            $payment_method = $_POST['payment_method'];
            $description = trim($_POST['description']);
            $status = $_POST['status'];
            $invoice_number = trim($_POST['invoice_number']);

            $stmt = $conn->prepare("INSERT INTO ledger (customer_id, agreement_id, transaction_type, amount, payment_date, payment_method, description, status, invoice_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisdsdsss", $customer_id, $agreement_id, $transaction_type, $amount, $payment_date, $payment_method, $description, $status, $invoice_number);
            
            if ($stmt->execute()) {
                $message = 'Ledger entry added successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error adding ledger entry: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'update') {
            $ledger_id = intval($_POST['ledger_id']);
            $customer_id = intval($_POST['customer_id']);
            $agreement_id = !empty($_POST['agreement_id']) ? intval($_POST['agreement_id']) : null;
            $transaction_type = $_POST['transaction_type'];
            $amount = floatval($_POST['amount']);
            $payment_date = $_POST['payment_date'];
            $payment_method = $_POST['payment_method'];
            $description = trim($_POST['description']);
            $status = $_POST['status'];
            $invoice_number = trim($_POST['invoice_number']);

            $stmt = $conn->prepare("UPDATE ledger SET customer_id = ?, agreement_id = ?, transaction_type = ?, amount = ?, payment_date = ?, payment_method = ?, description = ?, status = ?, invoice_number = ? WHERE ledger_id = ?");
            $stmt->bind_param("iisdsdsssi", $customer_id, $agreement_id, $transaction_type, $amount, $payment_date, $payment_method, $description, $status, $invoice_number, $ledger_id);
            
            if ($stmt->execute()) {
                $message = 'Ledger entry updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating ledger entry: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            $ledger_id = intval($_POST['ledger_id']);
            $stmt = $conn->prepare("DELETE FROM ledger WHERE ledger_id = ?");
            $stmt->bind_param("i", $ledger_id);
            
            if ($stmt->execute()) {
                $message = 'Ledger entry deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting ledger entry: ' . $conn->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// Get filter parameters
$filter_customer = $_GET['customer_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';

$where_clauses = [];
$params = [];
$types = '';

if ($filter_customer) {
    $where_clauses[] = "l.customer_id = ?";
    $params[] = $filter_customer;
    $types .= 'i';
}

if ($filter_status) {
    $where_clauses[] = "l.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($filter_type) {
    $where_clauses[] = "l.transaction_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$query = "SELECT l.*, c.full_name as customer_name, a.agreement_number FROM ledger l 
          JOIN customers c ON l.customer_id = c.customer_id 
          LEFT JOIN agreements a ON l.agreement_id = a.agreement_id 
          $where_sql ORDER BY l.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$ledger_entries = $stmt->get_result();

// Get totals - fix WHERE clause to use proper column names without alias
$total_where_clauses = [];
$total_params = [];
$total_types = '';

if ($filter_customer) {
    $total_where_clauses[] = "customer_id = ?";
    $total_params[] = $filter_customer;
    $total_types .= 'i';
}

if ($filter_status) {
    $total_where_clauses[] = "status = ?";
    $total_params[] = $filter_status;
    $total_types .= 's';
}

if ($filter_type) {
    $total_where_clauses[] = "transaction_type = ?";
    $total_params[] = $filter_type;
    $total_types .= 's';
}

$total_where_sql = !empty($total_where_clauses) ? 'WHERE ' . implode(' AND ', $total_where_clauses) : '';

$total_query = "SELECT 
    SUM(CASE WHEN transaction_type IN ('rent', 'service_charge') THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN transaction_type = 'maintenance' THEN amount ELSE 0 END) as total_expenses,
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
    SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as total_overdue
    FROM ledger $total_where_sql";

$total_stmt = $conn->prepare($total_query);
if (!empty($total_params)) {
    $total_stmt->bind_param($total_types, ...$total_params);
}
$total_stmt->execute();
$totals = $total_stmt->get_result()->fetch_assoc();

$customers = $conn->query("SELECT customer_id, full_name, phone FROM customers WHERE status = 'active' ORDER BY full_name");
$agreements = $conn->query("SELECT agreement_id, agreement_number, customer_id FROM agreements ORDER BY agreement_number");

$page_title = 'Ledger Management - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-book"></i> Ledger Management</h1>
        <button class="btn btn-primary" onclick="document.getElementById('addLedgerModal').style.display='block'">
            <i class="fas fa-plus"></i> Add Entry
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card success">
            <i class="fas fa-arrow-up stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($totals['total_income'] ?? 0); ?></div>
            <div class="stat-label">Total Income</div>
        </div>
        <div class="stat-card danger">
            <i class="fas fa-arrow-down stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($totals['total_expenses'] ?? 0); ?></div>
            <div class="stat-label">Total Expenses</div>
        </div>
        <div class="stat-card success">
            <i class="fas fa-check-circle stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($totals['total_paid'] ?? 0); ?></div>
            <div class="stat-label">Total Paid</div>
        </div>
        <div class="stat-card warning">
            <i class="fas fa-clock stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($totals['total_pending'] ?? 0); ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card danger">
            <i class="fas fa-exclamation-triangle stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($totals['total_overdue'] ?? 0); ?></div>
            <div class="stat-label">Overdue</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
        <div class="form-group" style="flex: 1; min-width: 200px;">
                <label class="form-label">Filter by Customer</label>
                <select class="form-control" name="customer_id">
                    <option value="">All Customers</option>
                    <?php 
                    $customers->data_seek(0);
                    while ($customer = $customers->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $customer['customer_id']; ?>" <?php echo $filter_customer == $customer['customer_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
        </div>
        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label class="form-label">Filter by Status</label>
            <select class="form-control" name="status">
                <option value="">All Status</option>
                <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="overdue" <?php echo $filter_status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
            </select>
        </div>
        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label class="form-label">Filter by Type</label>
            <select class="form-control" name="type">
                <option value="">All Types</option>
                <option value="rent" <?php echo $filter_type === 'rent' ? 'selected' : ''; ?>>Rent</option>
                <option value="maintenance" <?php echo $filter_type === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                <option value="service_charge" <?php echo $filter_type === 'service_charge' ? 'selected' : ''; ?>>Service Charge</option>
                <option value="deposit" <?php echo $filter_type === 'deposit' ? 'selected' : ''; ?>>Deposit</option>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
            <a href="ledger.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
        </div>
    </form>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                            <th>Customer</th>
                    <th>Agreement</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Invoice #</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($ledger_entries->num_rows > 0): ?>
                    <?php while ($entry = $ledger_entries->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($entry['payment_date']); ?></td>
                            <td><?php echo htmlspecialchars($entry['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($entry['agreement_number'] ?? '-'); ?></td>
                            <td><span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $entry['transaction_type'])); ?></span></td>
                            <td><?php echo formatCurrency($entry['amount']); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $entry['payment_method'])); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $entry['status'] === 'paid' ? 'success' : 
                                        ($entry['status'] === 'overdue' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($entry['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($entry['invoice_number'] ?? '-'); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editLedger(<?php echo htmlspecialchars(json_encode($entry)); ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="ledger_id" value="<?php echo $entry['ledger_id']; ?>">
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
                        <td colspan="9" style="text-align: center; color: var(--text-light);">No ledger entries found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Ledger Modal -->
<div id="addLedgerModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
    <div class="card" style="max-width: 600px; margin: 5% auto; position: relative;">
        <div class="card-header">
            <h2 class="card-title" id="modalTitle">Add Ledger Entry</h2>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" id="ledgerForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="ledger_id" id="ledger_id">
            
            <div class="form-group">
                <label class="form-label">Customer *</label>
                <select class="form-control" name="customer_id" id="customer_id" required onchange="updateAgreements()">
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
                <select class="form-control" name="agreement_id" id="agreement_id">
                    <option value="">Select Agreement</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Transaction Type *</label>
                <select class="form-control" name="transaction_type" id="transaction_type" required>
                    <option value="rent">Rent</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="service_charge">Service Charge</option>
                    <option value="deposit">Deposit</option>
                    <option value="refund">Refund</option>
                    <option value="other">Other</option>
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
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Invoice Number</label>
                <input type="text" class="form-control" name="invoice_number" id="invoice_number">
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" id="description" rows="3"></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Entry</button>
            </div>
        </form>
    </div>
</div>

<script>
const agreements = <?php echo json_encode($agreements->fetch_all(MYSQLI_ASSOC)); ?>;

function updateAgreements() {
    const customerId = document.getElementById('customer_id').value;
    const agreementSelect = document.getElementById('agreement_id');
    agreementSelect.innerHTML = '<option value="">Select Agreement</option>';
    
    agreements.filter(a => a.customer_id == customerId).forEach(agreement => {
        const opt = document.createElement('option');
        opt.value = agreement.agreement_id;
        opt.textContent = agreement.agreement_number;
        agreementSelect.appendChild(opt);
    });
}

function closeModal() {
    document.getElementById('addLedgerModal').style.display = 'none';
    document.getElementById('ledgerForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add Ledger Entry';
    document.getElementById('payment_date').value = '<?php echo date('Y-m-d'); ?>';
}

function editLedger(entry) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('ledger_id').value = entry.ledger_id;
    document.getElementById('customer_id').value = entry.customer_id;
    updateAgreements();
    setTimeout(() => {
        document.getElementById('agreement_id').value = entry.agreement_id || '';
    }, 100);
    document.getElementById('transaction_type').value = entry.transaction_type;
    document.getElementById('amount').value = entry.amount;
    document.getElementById('payment_date').value = entry.payment_date;
    document.getElementById('payment_method').value = entry.payment_method;
    document.getElementById('status').value = entry.status;
    document.getElementById('invoice_number').value = entry.invoice_number || '';
    document.getElementById('description').value = entry.description || '';
    document.getElementById('modalTitle').textContent = 'Edit Ledger Entry';
    document.getElementById('addLedgerModal').style.display = 'block';
}

window.onclick = function(event) {
    const modal = document.getElementById('addLedgerModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

