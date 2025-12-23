<?php
require_once '../config/config.php';
requireTenant();

$conn = getDBConnection();
$tenant_id = $_SESSION['user_id'];

$ledger_entries = $conn->query("SELECT l.*, a.agreement_number FROM ledger l 
                                LEFT JOIN agreements a ON l.agreement_id = a.agreement_id 
                                WHERE l.tenant_id = $tenant_id 
                                ORDER BY l.created_at DESC");

// Get summary
$summary = $conn->query("SELECT 
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
    SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as total_overdue
    FROM ledger WHERE tenant_id = $tenant_id")->fetch_assoc();

$page_title = 'My Ledger - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-book"></i> My Ledger</h1>
    </div>

    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card success">
            <i class="fas fa-check-circle stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($summary['total_paid'] ?? 0); ?></div>
            <div class="stat-label">Total Paid</div>
        </div>
        <div class="stat-card warning">
            <i class="fas fa-clock stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($summary['total_pending'] ?? 0); ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card danger">
            <i class="fas fa-exclamation-triangle stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($summary['total_overdue'] ?? 0); ?></div>
            <div class="stat-label">Overdue</div>
        </div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Agreement</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Invoice #</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($ledger_entries->num_rows > 0): ?>
                    <?php while ($entry = $ledger_entries->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($entry['payment_date']); ?></td>
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
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo htmlspecialchars($entry['description'] ?? '-'); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-light);">No ledger entries found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

