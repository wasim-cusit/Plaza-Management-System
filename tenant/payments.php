<?php
require_once '../config/config.php';
requireTenant();

$conn = getDBConnection();
$tenant_id = $_SESSION['user_id'];

$payments = $conn->query("SELECT p.*, a.agreement_number FROM payments p 
                          LEFT JOIN agreements a ON p.agreement_id = a.agreement_id 
                          WHERE p.tenant_id = $tenant_id 
                          ORDER BY p.created_at DESC");

$page_title = 'My Payments - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-money-bill-wave"></i> My Payments</h1>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Agreement</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Transaction ID</th>
                    <th>Status</th>
                    <th>Receipt</th>
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
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-light);">No payments found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

