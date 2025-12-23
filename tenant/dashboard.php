<?php
require_once '../config/config.php';
requireTenant();

$conn = getDBConnection();
$tenant_id = $_SESSION['user_id'];

// Get tenant statistics
$stats = [];

// Active Agreements
$result = $conn->query("SELECT COUNT(*) as total FROM agreements WHERE tenant_id = $tenant_id AND status = 'active'");
$stats['active_agreements'] = $result->fetch_assoc()['total'];

// Total Paid
$result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE tenant_id = $tenant_id AND status = 'completed'");
$stats['total_paid'] = $result->fetch_assoc()['total'] ?? 0;

// Pending Amount
$result = $conn->query("SELECT SUM(amount) as total FROM ledger WHERE tenant_id = $tenant_id AND status = 'pending'");
$stats['pending_amount'] = $result->fetch_assoc()['total'] ?? 0;

// Overdue Amount
$result = $conn->query("SELECT SUM(amount) as total FROM ledger WHERE tenant_id = $tenant_id AND status = 'overdue'");
$stats['overdue_amount'] = $result->fetch_assoc()['total'] ?? 0;

// Pending Maintenance
$result = $conn->query("SELECT COUNT(*) as total FROM maintenance_requests WHERE tenant_id = $tenant_id AND status = 'pending'");
$stats['pending_maintenance'] = $result->fetch_assoc()['total'];

// Recent Payments
$recent_payments = $conn->query("SELECT * FROM payments WHERE tenant_id = $tenant_id ORDER BY created_at DESC LIMIT 5");

// Active Agreements
$active_agreements = $conn->query("SELECT * FROM agreements WHERE tenant_id = $tenant_id AND status = 'active' ORDER BY end_date ASC");

// Recent Maintenance Requests
$recent_maintenance = $conn->query("SELECT * FROM maintenance_requests WHERE tenant_id = $tenant_id ORDER BY created_at DESC LIMIT 5");

$page_title = 'Customer Dashboard - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card success">
        <i class="fas fa-file-contract stat-icon"></i>
        <div class="stat-value"><?php echo $stats['active_agreements']; ?></div>
        <div class="stat-label">Active Agreements</div>
    </div>

    <div class="stat-card success">
        <i class="fas fa-check-circle stat-icon"></i>
        <div class="stat-value"><?php echo formatCurrency($stats['total_paid']); ?></div>
        <div class="stat-label">Total Paid</div>
    </div>

    <div class="stat-card warning">
        <i class="fas fa-clock stat-icon"></i>
        <div class="stat-value"><?php echo formatCurrency($stats['pending_amount']); ?></div>
        <div class="stat-label">Pending Amount</div>
    </div>

    <div class="stat-card danger">
        <i class="fas fa-exclamation-triangle stat-icon"></i>
        <div class="stat-value"><?php echo formatCurrency($stats['overdue_amount']); ?></div>
        <div class="stat-label">Overdue Amount</div>
    </div>

    <div class="stat-card">
        <i class="fas fa-tools stat-icon"></i>
        <div class="stat-value"><?php echo $stats['pending_maintenance']; ?></div>
        <div class="stat-label">Pending Maintenance</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-file-contract"></i> Active Agreements</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Agreement #</th>
                        <th>Space Type</th>
                        <th>End Date</th>
                        <th>Monthly Rent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($active_agreements->num_rows > 0): ?>
                        <?php while ($agreement = $active_agreements->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agreement['agreement_number']); ?></td>
                                <td><span class="badge badge-info"><?php echo ucfirst($agreement['space_type']); ?></span></td>
                                <td><?php echo formatDate($agreement['end_date']); ?></td>
                                <td><?php echo formatCurrency($agreement['monthly_rent']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-light);">No active agreements</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-money-bill-wave"></i> Recent Payments</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_payments->num_rows > 0): ?>
                        <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
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
                            <td colspan="4" style="text-align: center; color: var(--text-light);">No payments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-tools"></i> Recent Maintenance Requests</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Issue</th>
                        <th>Priority</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_maintenance->num_rows > 0): ?>
                        <?php while ($request = $recent_maintenance->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatDate($request['created_at']); ?></td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($request['description']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $request['priority'] === 'urgent' ? 'danger' : 
                                            ($request['priority'] === 'high' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst($request['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $request['status'] === 'completed' ? 'success' : 
                                            ($request['status'] === 'in_progress' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-light);">No maintenance requests</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

