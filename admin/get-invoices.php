<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();
$agreement_id = intval($_GET['agreement_id'] ?? 0);
$customer_id = intval($_GET['customer_id'] ?? 0);

if (!$agreement_id || !$customer_id) {
    echo '<div class="alert alert-danger">Invalid request.</div>';
    exit();
}

// Get all invoices/ledger entries for this agreement
$invoices = $conn->query("SELECT l.*, a.agreement_number 
                         FROM ledger l 
                         LEFT JOIN agreements a ON l.agreement_id = a.agreement_id 
                         WHERE l.agreement_id = $agreement_id AND l.customer_id = $customer_id 
                         ORDER BY l.created_at DESC");

// Get agreement info
$agreement = $conn->query("SELECT * FROM agreements WHERE agreement_id = $agreement_id")->fetch_assoc();
$customer = $conn->query("SELECT full_name, phone, email FROM customers WHERE customer_id = $customer_id")->fetch_assoc();
?>

<div style="margin-bottom: 1.5rem;">
    <h3>Agreement: <?php echo htmlspecialchars($agreement['agreement_number']); ?></h3>
    <p><strong>Customer:</strong> <?php echo htmlspecialchars($customer['full_name']); ?></p>
</div>

<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Status</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($invoices->num_rows > 0): ?>
                <?php while ($invoice = $invoices->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($invoice['invoice_number'] ?: '-'); ?></td>
                        <td><?php echo formatDate($invoice['payment_date']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo ucfirst(str_replace('_', ' ', $invoice['transaction_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo formatCurrency($invoice['amount']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $invoice['payment_method'])); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $invoice['status'] === 'paid' ? 'success' : 
                                    ($invoice['status'] === 'overdue' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($invoice['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($invoice['description'] ?: '-'); ?></td>
                        <td>
                            <a href="print-invoice.php?ledger_id=<?php echo $invoice['ledger_id']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fas fa-print"></i> Print
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-light);">No invoices found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
    <a href="ledger.php?customer_id=<?php echo $customer_id; ?>&agreement_id=<?php echo $agreement_id; ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Invoice
    </a>
</div>
