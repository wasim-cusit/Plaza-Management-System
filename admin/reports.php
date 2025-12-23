<?php
require_once '../config/config.php';
requireAdmin();

$conn = getDBConnection();

// Get filter parameters
$report_type = $_GET['type'] ?? 'financial';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$page_title = 'Reports - Plaza Management System';
include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title"><i class="fas fa-chart-bar"></i> Reports</h1>
    </div>

    <!-- Report Filters -->
    <form method="GET" style="margin-bottom: 2rem; padding: 1.5rem; background: var(--light-color); border-radius: 0.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div class="form-group">
                <label class="form-label">Report Type</label>
                <select class="form-control" name="type" onchange="this.form.submit()">
                    <option value="financial" <?php echo $report_type === 'financial' ? 'selected' : ''; ?>>Financial Report</option>
                    <option value="tenant" <?php echo $report_type === 'tenant' ? 'selected' : ''; ?>>Customer Report</option>
                    <option value="lease" <?php echo $report_type === 'lease' ? 'selected' : ''; ?>>Lease Report</option>
                    <option value="maintenance" <?php echo $report_type === 'maintenance' ? 'selected' : ''; ?>>Maintenance Report</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="form-group" style="display: flex; align-items: end;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Generate</button>
            </div>
        </div>
    </form>

    <?php if ($report_type === 'financial'): ?>
        <!-- Financial Report -->
        <?php
        $financial_query = "SELECT 
            SUM(CASE WHEN transaction_type IN ('rent', 'service_charge') THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN transaction_type = 'maintenance' THEN amount ELSE 0 END) as total_expenses,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
            SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as total_overdue,
            COUNT(*) as total_transactions
            FROM ledger WHERE payment_date BETWEEN ? AND ?";
        
        $stmt = $conn->prepare($financial_query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $financial_summary = $stmt->get_result()->fetch_assoc();
        
        $detailed_query = "SELECT l.*, u.full_name as tenant_name, a.agreement_number 
                          FROM ledger l 
                          JOIN users u ON l.tenant_id = u.user_id 
                          LEFT JOIN agreements a ON l.agreement_id = a.agreement_id 
                          WHERE l.payment_date BETWEEN ? AND ? 
                          ORDER BY l.payment_date DESC";
        $stmt2 = $conn->prepare($detailed_query);
        $stmt2->bind_param("ss", $start_date, $end_date);
        $stmt2->execute();
        $detailed_entries = $stmt2->get_result();
        ?>
        
        <div class="stats-grid" style="margin-bottom: 2rem;">
            <div class="stat-card success">
                <i class="fas fa-arrow-up stat-icon"></i>
                <div class="stat-value"><?php echo formatCurrency($financial_summary['total_income'] ?? 0); ?></div>
                <div class="stat-label">Total Income</div>
            </div>
            <div class="stat-card danger">
                <i class="fas fa-arrow-down stat-icon"></i>
                <div class="stat-value"><?php echo formatCurrency($financial_summary['total_expenses'] ?? 0); ?></div>
                <div class="stat-label">Total Expenses</div>
            </div>
            <div class="stat-card success">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-value"><?php echo formatCurrency($financial_summary['total_paid'] ?? 0); ?></div>
                <div class="stat-label">Total Paid</div>
            </div>
            <div class="stat-card warning">
                <i class="fas fa-clock stat-icon"></i>
                <div class="stat-value"><?php echo formatCurrency($financial_summary['total_pending'] ?? 0); ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card danger">
                <i class="fas fa-exclamation-triangle stat-icon"></i>
                <div class="stat-value"><?php echo formatCurrency($financial_summary['total_overdue'] ?? 0); ?></div>
                <div class="stat-label">Overdue</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-list stat-icon"></i>
                <div class="stat-value"><?php echo $financial_summary['total_transactions']; ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Detailed Financial Report</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Agreement</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($detailed_entries->num_rows > 0): ?>
                            <?php while ($entry = $detailed_entries->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo formatDate($entry['payment_date']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['tenant_name']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['agreement_number'] ?? '-'); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $entry['transaction_type'])); ?></td>
                                    <td><?php echo formatCurrency($entry['amount']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $entry['status'] === 'paid' ? 'success' : 
                                                ($entry['status'] === 'overdue' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($entry['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-light);">No transactions found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($report_type === 'tenant'): ?>
        <!-- Customer Report -->
        <?php
        $tenant_query = "SELECT u.*, 
            COUNT(DISTINCT a.agreement_id) as total_agreements,
            COUNT(DISTINCT CASE WHEN a.status = 'active' THEN a.agreement_id END) as active_agreements,
            SUM(CASE WHEN l.status = 'paid' THEN l.amount ELSE 0 END) as total_paid,
            SUM(CASE WHEN l.status = 'pending' THEN l.amount ELSE 0 END) as pending_amount
            FROM users u
            LEFT JOIN agreements a ON u.user_id = a.tenant_id
            LEFT JOIN ledger l ON u.user_id = l.tenant_id AND l.payment_date BETWEEN ? AND ?
            WHERE u.user_type = 'tenant'
            GROUP BY u.user_id
            ORDER BY u.full_name";
        
        $stmt = $conn->prepare($tenant_query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $tenant_report = $stmt->get_result();
        ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Customer Report</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Total Agreements</th>
                            <th>Active Agreements</th>
                            <th>Total Paid</th>
                            <th>Pending Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tenant_report->num_rows > 0): ?>
                            <?php while ($tenant = $tenant_report->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tenant['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($tenant['email']); ?></td>
                                    <td><?php echo htmlspecialchars($tenant['phone'] ?? '-'); ?></td>
                                    <td><?php echo $tenant['total_agreements']; ?></td>
                                    <td><?php echo $tenant['active_agreements']; ?></td>
                                    <td><?php echo formatCurrency($tenant['total_paid'] ?? 0); ?></td>
                                    <td><?php echo formatCurrency($tenant['pending_amount'] ?? 0); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $tenant['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($tenant['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-light);">No customers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($report_type === 'lease'): ?>
        <!-- Lease Report -->
        <?php
        $lease_query = "SELECT a.*, u.full_name as tenant_name,
            CASE 
                WHEN a.end_date < CURDATE() THEN 'expired'
                WHEN a.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                ELSE 'active'
            END as lease_status
            FROM agreements a
            JOIN users u ON a.tenant_id = u.user_id
            WHERE a.start_date <= ? AND a.end_date >= ?
            ORDER BY a.end_date ASC";
        
        $stmt = $conn->prepare($lease_query);
        $stmt->bind_param("ss", $end_date, $start_date);
        $stmt->execute();
        $lease_report = $stmt->get_result();
        ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Lease Report</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Agreement #</th>
                            <th>Customer</th>
                            <th>Space Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Monthly Rent</th>
                            <th>Status</th>
                            <th>Lease Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($lease_report->num_rows > 0): ?>
                            <?php while ($lease = $lease_report->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($lease['agreement_number']); ?></td>
                                    <td><?php echo htmlspecialchars($lease['tenant_name']); ?></td>
                                    <td><span class="badge badge-info"><?php echo ucfirst($lease['space_type']); ?></span></td>
                                    <td><?php echo formatDate($lease['start_date']); ?></td>
                                    <td><?php echo formatDate($lease['end_date']); ?></td>
                                    <td><?php echo formatCurrency($lease['monthly_rent']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $lease['status'] === 'active' ? 'success' : 
                                                ($lease['status'] === 'expired' ? 'danger' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst($lease['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $lease['lease_status'] === 'expired' ? 'danger' : 
                                                ($lease['lease_status'] === 'expiring_soon' ? 'warning' : 'success'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $lease['lease_status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-light);">No leases found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($report_type === 'maintenance'): ?>
        <!-- Maintenance Report -->
        <?php
        $maintenance_query = "SELECT m.*, u.full_name as tenant_name
            FROM maintenance_requests m
            JOIN users u ON m.tenant_id = u.user_id
            WHERE DATE(m.created_at) BETWEEN ? AND ?
            ORDER BY m.created_at DESC";
        
        $stmt = $conn->prepare($maintenance_query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $maintenance_report = $stmt->get_result();
        
        $maintenance_summary = $conn->query("SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            SUM(cost) as total_cost
            FROM maintenance_requests 
            WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'")->fetch_assoc();
        ?>
        
        <div class="stats-grid" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <i class="fas fa-tools stat-icon"></i>
                <div class="stat-value"><?php echo $maintenance_summary['total_requests']; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card success">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-value"><?php echo $maintenance_summary['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card warning">
                <i class="fas fa-clock stat-icon"></i>
                <div class="stat-value"><?php echo $maintenance_summary['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card danger">
                <i class="fas fa-dollar-sign stat-icon"></i>
                <div class="stat-value"><?php echo formatCurrency($maintenance_summary['total_cost'] ?? 0); ?></div>
                <div class="stat-label">Total Cost</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Maintenance Report</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Space</th>
                            <th>Issue Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Cost</th>
                            <th>Completed Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($maintenance_report->num_rows > 0): ?>
                            <?php while ($request = $maintenance_report->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo formatDate($request['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($request['tenant_name']); ?></td>
                                    <td><span class="badge badge-info"><?php echo ucfirst($request['space_type']); ?></span> #<?php echo $request['space_id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['issue_type'] ?? '-'); ?></td>
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
                                    <td><?php echo formatCurrency($request['cost']); ?></td>
                                    <td><?php echo $request['completed_date'] ? formatDate($request['completed_date']) : '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-light);">No maintenance requests found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

