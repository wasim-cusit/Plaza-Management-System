<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();

// Get statistics
$stats = [];

// Total Shops
$result = $conn->query("SELECT COUNT(*) as total FROM shops");
$stats['total_shops'] = $result->fetch_assoc()['total'];

// Occupied Shops
$result = $conn->query("SELECT COUNT(*) as total FROM shops WHERE status = 'occupied'");
$stats['occupied_shops'] = $result->fetch_assoc()['total'];

// Total Rooms
$result = $conn->query("SELECT COUNT(*) as total FROM rooms");
$stats['total_rooms'] = $result->fetch_assoc()['total'];

// Occupied Rooms
$result = $conn->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied'");
$stats['occupied_rooms'] = $result->fetch_assoc()['total'];

// Total Basements
$result = $conn->query("SELECT COUNT(*) as total FROM basements");
$stats['total_basements'] = $result->fetch_assoc()['total'];

// Occupied Basements
$result = $conn->query("SELECT COUNT(*) as total FROM basements WHERE status = 'occupied'");
$stats['occupied_basements'] = $result->fetch_assoc()['total'];

// Total Tenants
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'tenant'");
$stats['total_tenants'] = $result->fetch_assoc()['total'];

// Active Agreements
$result = $conn->query("SELECT COUNT(*) as total FROM agreements WHERE status = 'active'");
$stats['active_agreements'] = $result->fetch_assoc()['total'];

// Total Revenue (this month)
$result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed' AND MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
$stats['monthly_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Pending Payments
$result = $conn->query("SELECT SUM(amount) as total FROM ledger WHERE status = 'pending'");
$stats['pending_payments'] = $result->fetch_assoc()['total'] ?? 0;

// Pending Maintenance
$result = $conn->query("SELECT COUNT(*) as total FROM maintenance_requests WHERE status = 'pending'");
$stats['pending_maintenance'] = $result->fetch_assoc()['total'];

// Recent Payments
$recent_payments = $conn->query("SELECT p.*, u.full_name, u.username FROM payments p JOIN users u ON p.tenant_id = u.user_id ORDER BY p.created_at DESC LIMIT 5");

// Expiring Agreements (next 30 days)
$expiring_agreements = $conn->query("SELECT a.*, u.full_name, u.username FROM agreements a JOIN users u ON a.tenant_id = u.user_id WHERE a.status = 'active' AND a.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY a.end_date ASC LIMIT 5");

$page_title = 'Admin Dashboard - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-store stat-icon"></i>
        <div class="stat-value"><?php echo $stats['total_shops']; ?></div>
        <div class="stat-label">Total Shops</div>
        <div style="margin-top: 0.5rem; color: var(--text-light); font-size: 0.875rem;">
            <?php echo $stats['occupied_shops']; ?> Occupied
        </div>
    </div>

    <div class="stat-card">
        <i class="fas fa-door-open stat-icon"></i>
        <div class="stat-value"><?php echo $stats['total_rooms']; ?></div>
        <div class="stat-label">Total Rooms</div>
        <div style="margin-top: 0.5rem; color: var(--text-light); font-size: 0.875rem;">
            <?php echo $stats['occupied_rooms']; ?> Occupied
        </div>
    </div>

    <div class="stat-card">
        <i class="fas fa-layer-group stat-icon"></i>
        <div class="stat-value"><?php echo $stats['total_basements']; ?></div>
        <div class="stat-label">Total Basements</div>
        <div style="margin-top: 0.5rem; color: var(--text-light); font-size: 0.875rem;">
            <?php echo $stats['occupied_basements']; ?> Occupied
        </div>
    </div>

    <div class="stat-card success">
        <i class="fas fa-users stat-icon"></i>
        <div class="stat-value"><?php echo $stats['total_tenants']; ?></div>
        <div class="stat-label">Total Customers</div>
    </div>

    <div class="stat-card success">
        <i class="fas fa-file-contract stat-icon"></i>
        <div class="stat-value"><?php echo $stats['active_agreements']; ?></div>
        <div class="stat-label">Active Agreements</div>
    </div>

    <div class="stat-card success">
        <i class="fas fa-dollar-sign stat-icon"></i>
        <div class="stat-value"><?php echo formatCurrency($stats['monthly_revenue']); ?></div>
        <div class="stat-label">Monthly Revenue</div>
    </div>

    <div class="stat-card warning">
        <i class="fas fa-exclamation-triangle stat-icon"></i>
        <div class="stat-value"><?php echo formatCurrency($stats['pending_payments']); ?></div>
        <div class="stat-label">Pending Payments</div>
    </div>

    <div class="stat-card danger">
        <i class="fas fa-tools stat-icon"></i>
        <div class="stat-value"><?php echo $stats['pending_maintenance']; ?></div>
        <div class="stat-label">Pending Maintenance</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-money-bill-wave"></i> Recent Payments</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                </thead>
                <tbody>
                    <?php if ($recent_payments->num_rows > 0): ?>
                        <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td><span class="badge badge-success"><?php echo ucfirst($payment['status']); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-light);">No recent payments</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-calendar-alt"></i> Expiring Agreements</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Agreement #</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                </thead>
                <tbody>
                    <?php if ($expiring_agreements->num_rows > 0): ?>
                        <?php while ($agreement = $expiring_agreements->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agreement['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($agreement['agreement_number']); ?></td>
                                <td><?php echo formatDate($agreement['end_date']); ?></td>
                                <td><span class="badge badge-warning">Expiring Soon</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-light);">No expiring agreements</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

