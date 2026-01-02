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

// Calculate total security deposit from agreements
$security_deposit_result = $conn->query("SELECT SUM(security_deposit) as total_security_deposit FROM agreements WHERE customer_id = $customer_id");
$security_deposit_data = $security_deposit_result->fetch_assoc();
$total_security_deposit = $security_deposit_data['total_security_deposit'] ?? 0;

$page_title = 'Customer Details - ' . htmlspecialchars($customer['full_name']);
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="card-title"><i class="fas fa-user"></i> Customer Details</h1>
            <p style="margin: 0.5rem 0 0 0; color: var(--text-light);" class="no-print">
                <a href="customers.php" style="color: var(--primary-color); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Back to Customers
                </a>
            </p>
        </div>
        <div class="no-print">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    
    <!-- Print Header (only visible when printing) -->
    <div class="print-header" style="display: none;">
        <h1>Customer Details Report</h1>
        <p>Customer: <?php echo htmlspecialchars($customer['full_name']); ?> | Generated on <?php echo date('d/m/Y H:i'); ?></p>
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
                <div><strong>Security Deposit:</strong> <span style="color: var(--primary-color); font-weight: bold;"><?php echo formatCurrency($total_security_deposit); ?></span></div>
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
    @page {
        size: A4;
        margin: 0.5cm;
    }
    
    /* Prevent empty first page */
    html, body {
        height: auto !important;
        overflow: visible !important;
    }
    
    * {
        margin: 0;
        padding: 0;
    }
    
    body {
        background: white !important;
        font-size: 9px;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* Remove top margin from first element */
    .card:first-child,
    .print-header + *,
    .customer-info-section:first-child {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    
    /* Ensure content starts immediately after header */
    .print-header ~ .customer-info-section,
    .print-header ~ .card {
        margin-top: 0.4rem !important;
    }
    
    /* Hide empty alert in print */
    .alert {
        display: none !important;
    }
    
    /* Remove any top padding from main container */
    .main-content > .card {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    
    /* Ensure first visible element has proper spacing */
    .print-header + * {
        margin-top: 0.4rem !important;
        padding-top: 0 !important;
    }
    
    /* Prevent page breaks right after header */
    .print-header {
        page-break-after: avoid;
    }
    
    .print-header + .customer-info-section {
        page-break-before: avoid;
    }
    
    /* Remove all top margins from body and first elements */
    body > div:first-child,
    .main-content:first-child,
    .app-container:first-child,
    .main-content > div:first-child {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    
    /* Ensure print header is at the very top */
    .print-header {
        margin-top: 0 !important;
        padding-top: 0 !important;
        position: relative !important;
    }
    
    /* Ensure no empty space before content */
    .card:has(.card-header) + .print-header,
    .print-header:first-child {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }
    
    /* Hide navigation and UI elements */
    .sidebar,
    .top-header,
    .mobile-overlay,
    .card-header button,
    .action-buttons,
    nav,
    .alert,
    .no-print,
    .footer {
        display: none !important;
    }
    
    /* Adjust layout for print */
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    
    .app-container {
        display: block !important;
        width: 100% !important;
    }
    
    /* Print header - professional and compact - at top of page */
    .print-header {
        display: block !important;
        position: relative !important;
        text-align: center;
        margin: 0 0 0.5rem 0 !important;
        padding: 0.2rem 0 0.2rem 0 !important;
        border-bottom: 2px solid #2563eb;
        page-break-after: avoid;
        page-break-inside: avoid;
        height: auto !important;
        min-height: auto !important;
        max-height: 1.8cm !important;
    }
    
    .print-header h1 {
        font-size: 0.95rem;
        color: #2563eb;
        margin: 0 0 0.1rem 0;
        padding: 0;
        font-weight: bold;
    }
    
    .print-header p {
        font-size: 0.65rem;
        color: #6b7280;
        margin: 0;
        padding: 0;
    }
    
    /* Convert everything to simple table format */
    .card {
        page-break-inside: avoid;
        break-inside: avoid;
        margin-top: 0;
        margin-bottom: 0.5rem;
        border: none;
        padding: 0;
        box-shadow: none;
        background: white;
    }
    
    .card:first-of-type,
    .print-header ~ * .card:first-of-type {
        margin-top: 0.4rem !important;
    }
    
    /* Main card container */
    .card:has(.card-header) {
        margin-top: 0 !important;
        padding-top: 0 !important;
        border: none;
        padding: 0;
    }
    
    /* Ensure first content element has no top margin */
    .print-header + .customer-info-section,
    .print-header + * {
        margin-top: 0.4rem !important;
    }
    
    .card-header {
        display: none !important;
    }
    
    /* Convert info cards to tables - side by side */
    div[style*="grid-template-columns"],
    .customer-info-section {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 0.6rem !important;
        margin-top: 0.3rem !important;
        margin-bottom: 0.6rem !important;
        page-break-inside: avoid;
    }
    
    div[style*="grid-template-columns"] > .card,
    .customer-info-section > .card {
        margin: 0 !important;
        page-break-inside: avoid;
        border: none;
        padding: 0.6rem;
        background: white;
        border-left: 3px solid #2563eb;
    }
    
    .customer-info-section > .card:first-child {
        margin-top: 0 !important;
    }
    
    /* If there's a third card (Contact Information), put it below */
    .customer-info-section > .card:nth-child(3) {
        grid-column: 1 / -1;
        margin-top: 0.4rem !important;
    }
    
    /* Info sections - clean professional list format */
    .card h3 {
        font-size: 0.8rem;
        margin-bottom: 0.4rem;
        color: #2563eb !important;
        border-bottom: 2px solid #2563eb;
        padding-bottom: 0.2rem;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .card > div[style*="flex-direction: column"] {
        display: block !important;
        width: 100%;
        margin-bottom: 0;
        font-size: 0.75rem;
    }
    
    .card > div[style*="flex-direction: column"] > div {
        display: flex;
        flex-direction: row;
        font-size: 0.75rem;
        line-height: 1.6;
        margin-bottom: 0.3rem;
        padding: 0.15rem 0;
        border-bottom: 1px dotted #e5e7eb;
    }
    
    .card > div[style*="flex-direction: column"] > div:last-child {
        border-bottom: none;
    }
    
    .card > div[style*="flex-direction: column"] > div strong {
        display: inline-block;
        padding: 0;
        width: 40%;
        font-weight: 600;
        color: #374151;
        flex-shrink: 0;
    }
    
    .card > div[style*="flex-direction: column"] > div > *:not(strong):not(.badge) {
        display: inline-block;
        padding: 0;
        color: #1f2937;
        flex: 1;
    }
    
    .card > div[style*="flex-direction: column"] > div > .badge {
        display: inline-block;
        margin-left: 0.3rem;
        padding: 0.1rem 0.4rem;
        border-radius: 0.2rem;
        background: #f3f4f6 !important;
        color: #1f2937 !important;
        border: 1px solid #d1d5db;
    }
    
    /* Badge styling for print */
    .badge {
        padding: 0.1rem 0.3rem;
        font-size: 0.6rem;
        border: 1px solid #ccc;
        background: #f3f4f6 !important;
        color: #1f2937 !important;
        display: inline-block;
    }
    
    /* Table styling for print */
    .table-container {
        overflow: visible !important;
        margin-bottom: 0.5rem;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.65rem;
        margin-bottom: 0.5rem;
    }
    
    .table thead th {
        background: #2563eb !important;
        color: white !important;
        padding: 0.3rem 0.4rem;
        border: 1px solid #1e40af;
        font-weight: 600;
        font-size: 0.7rem;
    }
    
    .table tbody td {
        padding: 0.25rem 0.4rem;
        border: 1px solid #e5e7eb;
        font-size: 0.65rem;
    }
    
    .table tbody tr {
        page-break-inside: avoid;
    }
    
    .table tbody tr:nth-child(even) {
        background: #f9fafb;
    }
    
    /* Preserve important colors in print */
    span[style*="color: var(--primary-color)"] {
        color: #2563eb !important;
        font-weight: bold;
    }
    
    span[style*="color: var(--success-color)"] {
        color: #059669 !important;
        font-weight: bold;
    }
    
    span[style*="color: var(--warning-color)"] {
        color: #d97706 !important;
        font-weight: bold;
    }
    
    span[style*="color: var(--danger-color)"] {
        color: #dc2626 !important;
        font-weight: bold;
    }
    
    /* Hide links in print */
    a {
        color: #1f2937 !important;
        text-decoration: none !important;
    }
    
    /* Compact space info - convert to table */
    .space-info-compact {
        display: table !important;
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0.5rem;
        font-size: 0.7rem;
        border: 1px solid #e5e7eb;
    }
    
    .space-info-compact > div {
        display: table-row;
    }
    
    .space-info-compact > div strong {
        display: table-cell;
        padding: 0.2rem 0.5rem;
        width: 40%;
        border: 1px solid #e5e7eb;
        background: #f3f4f6;
        font-weight: 600;
    }
    
    .space-info-compact > div:after {
        content: '';
        display: table-cell;
        padding: 0.2rem 0.5rem;
        border: 1px solid #e5e7eb;
    }
    
    /* Fix for info sections that have nested content like badges */
    .card > div[style*="flex-direction: column"] > div {
        display: table-row !important;
    }
    
    .card > div[style*="flex-direction: column"] > div > span.badge {
        display: inline-block;
    }
    
    /* Prevent empty pages */
    .card:empty,
    div:empty {
        display: none !important;
    }
    
    /* Remove excessive margins */
    div[style*="margin-bottom: 2rem"] {
        margin-bottom: 0.5rem !important;
    }
    
    /* Ensure no orphaned content */
    .card {
        orphans: 3;
        widows: 3;
    }
    
    /* Section titles */
    .card h2.card-title {
        font-size: 0.8rem;
        margin-bottom: 0.3rem;
        color: #2563eb !important;
        border-bottom: 1px solid #2563eb;
        padding-bottom: 0.2rem;
        page-break-after: avoid;
    }
}
</style>

<script>
// Show print header when printing
window.addEventListener('beforeprint', function() {
    const printHeader = document.querySelector('.print-header');
    if (printHeader) {
        printHeader.style.display = 'block';
    }
});

window.addEventListener('afterprint', function() {
    const printHeader = document.querySelector('.print-header');
    if (printHeader) {
        printHeader.style.display = 'none';
    }
});
</script>

<?php include '../includes/footer.php'; ?>

