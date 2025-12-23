<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$customer_id = intval($_GET['customer_id'] ?? 0);

// Handle session messages
$message = $_SESSION['success'] ?? $_SESSION['error'] ?? '';
$message_type = isset($_SESSION['success']) ? 'success' : (isset($_SESSION['error']) ? 'danger' : '');
unset($_SESSION['success'], $_SESSION['error']);

if (!$customer_id) {
    header('Location: customers.php');
    exit();
}

// Get customer info
$customer = $conn->query("SELECT * FROM customers WHERE customer_id = $customer_id")->fetch_assoc();

if (!$customer) {
    $_SESSION['error'] = 'Customer not found.';
    header('Location: customers.php');
    exit();
}

// Get customer agreements
$agreements = $conn->query("SELECT * FROM agreements WHERE customer_id = $customer_id ORDER BY created_at DESC");

// Get customer ledger
$ledger = $conn->query("SELECT l.*, a.agreement_number FROM ledger l 
                       LEFT JOIN agreements a ON l.agreement_id = a.agreement_id 
                       WHERE l.customer_id = $customer_id 
                       ORDER BY l.created_at DESC");

// Get customer payments
$payments = $conn->query("SELECT p.*, a.agreement_number FROM payments p 
                         LEFT JOIN agreements a ON p.agreement_id = a.agreement_id 
                         WHERE p.customer_id = $customer_id 
                         ORDER BY p.created_at DESC");

// Get assigned spaces
$spaces = [];
$shop_spaces = $conn->query("SELECT 'shop' as type, shop_id as id, shop_number as number, shop_name as name, status FROM shops WHERE customer_id = $customer_id");
while ($row = $shop_spaces->fetch_assoc()) {
    $spaces[] = $row;
}
$room_spaces = $conn->query("SELECT 'room' as type, room_id as id, room_number as number, room_name as name, status FROM rooms WHERE customer_id = $customer_id");
while ($row = $room_spaces->fetch_assoc()) {
    $spaces[] = $row;
}
$basement_spaces = $conn->query("SELECT 'basement' as type, basement_id as id, basement_number as number, basement_name as name, status FROM basements WHERE customer_id = $customer_id");
while ($row = $basement_spaces->fetch_assoc()) {
    $spaces[] = $row;
}

// Calculate summary
$summary = $conn->query("SELECT 
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
    SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as total_overdue
    FROM ledger WHERE customer_id = $customer_id")->fetch_assoc();

$page_title = 'Customer Details - ' . htmlspecialchars($customer['full_name']);
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="card-title"><i class="fas fa-user"></i> Customer Details</h1>
            <p style="margin: 0.5rem 0 0 0; color: var(--text-light);">
                <a href="customers.php" style="color: var(--primary-color); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Customers
                </a>
            </p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Customer Information -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card" style="margin: 0;">
            <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Personal Information</h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div><strong>Name:</strong> <?php echo htmlspecialchars($customer['full_name']); ?></div>
                <div><strong>Gender:</strong> <span class="badge badge-info"><?php echo ucfirst($customer['gender']); ?></span></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($customer['email'] ?: '-'); ?></div>
                <div><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></div>
                <?php if ($customer['alternate_phone']): ?>
                    <div><strong>Alternate Phone:</strong> <?php echo htmlspecialchars($customer['alternate_phone']); ?></div>
                <?php endif; ?>
                <?php if ($customer['cnic']): ?>
                    <div><strong>CNIC:</strong> <?php echo htmlspecialchars($customer['cnic']); ?></div>
                <?php endif; ?>
                <div><strong>Address:</strong> <?php echo htmlspecialchars($customer['address'] ?: '-'); ?></div>
                <?php if ($customer['city']): ?>
                    <div><strong>City:</strong> <?php echo htmlspecialchars($customer['city']); ?></div>
                <?php endif; ?>
                <?php if ($customer['occupation']): ?>
                    <div><strong>Occupation:</strong> <?php echo htmlspecialchars($customer['occupation']); ?></div>
                <?php endif; ?>
                <div>
                    <strong>Status:</strong> 
                    <span class="badge badge-<?php echo $customer['status'] === 'active' ? 'success' : 'danger'; ?>">
                        <?php echo ucfirst($customer['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card" style="margin: 0;">
            <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Financial Summary</h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div><strong>Total Paid:</strong> <span style="color: var(--success-color); font-weight: bold;"><?php echo formatCurrency($summary['total_paid'] ?? 0); ?></span></div>
                <div><strong>Pending:</strong> <span style="color: var(--warning-color); font-weight: bold;"><?php echo formatCurrency($summary['total_pending'] ?? 0); ?></span></div>
                <div><strong>Overdue:</strong> <span style="color: var(--danger-color); font-weight: bold;"><?php echo formatCurrency($summary['total_overdue'] ?? 0); ?></span></div>
            </div>
        </div>
        
        <?php if ($customer['emergency_contact_name'] || $customer['reference_name']): ?>
        <div class="card" style="margin: 0;">
            <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Contact Information</h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php if ($customer['emergency_contact_name']): ?>
                    <div><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($customer['emergency_contact_name']); ?> - <?php echo htmlspecialchars($customer['emergency_contact_phone']); ?></div>
                <?php endif; ?>
                <?php if ($customer['reference_name']): ?>
                    <div><strong>Reference:</strong> <?php echo htmlspecialchars($customer['reference_name']); ?> - <?php echo htmlspecialchars($customer['reference_phone']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Assigned Spaces -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-building"></i> Assigned Spaces</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Number</th>
                        <th>Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($spaces) > 0): ?>
                        <?php foreach ($spaces as $space): ?>
                            <tr>
                                <td><span class="badge badge-info"><?php echo ucfirst($space['type']); ?></span></td>
                                <td><?php echo htmlspecialchars($space['number']); ?></td>
                                <td><?php echo htmlspecialchars($space['name'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $space['status'] === 'occupied' ? 'success' : 
                                            ($space['status'] === 'maintenance' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($space['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-light);">No spaces assigned</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Agreements -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-file-contract"></i> Agreements</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Agreement #</th>
                        <th>Space Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Monthly Rent</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($agreements->num_rows > 0): ?>
                        <?php while ($agreement = $agreements->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agreement['agreement_number']); ?></td>
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
                                    <div class="action-buttons">
                                        <a href="print-agreement.php?id=<?php echo $agreement['agreement_id']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-print"></i> Print
                                        </a>
                                        <?php if ($agreement['document_file']): ?>
                                            <a href="<?php echo BASE_URL; ?>uploads/agreements/<?php echo htmlspecialchars($agreement['document_file']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-light);">No agreements found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ledger -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-book"></i> Customer Ledger</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Agreement</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Invoice #</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ledger->num_rows > 0): ?>
                        <?php while ($entry = $ledger->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($entry['payment_date']); ?></td>
                                <td><?php echo htmlspecialchars($entry['agreement_number'] ?? '-'); ?></td>
                                <td><span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $entry['transaction_type'])); ?></span></td>
                                <td><?php echo formatCurrency($entry['amount']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $entry['status'] === 'paid' ? 'success' : 
                                            ($entry['status'] === 'overdue' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($entry['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($entry['invoice_number'] ?? '-'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-light);">No ledger entries found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payments -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-money-bill-wave"></i> Payment History</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Agreement</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payments->num_rows > 0): ?>
                        <?php while ($payment = $payments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td><?php echo htmlspecialchars($payment['agreement_number'] ?? '-'); ?></td>
                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $payment['status'] === 'completed' ? 'success' : 
                                            ($payment['status'] === 'failed' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-light);">No payments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .card-header button,
    .action-buttons,
    nav,
    .sidebar,
    .top-header button {
        display: none !important;
    }
    
    .card {
        page-break-inside: avoid;
    }
}
</style>

<?php include '../includes/footer.php'; ?>

