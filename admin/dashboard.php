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

// Total Customers
$result = $conn->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
$stats['total_customers'] = $result->fetch_assoc()['total'];

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
$recent_payments = $conn->query("SELECT p.*, c.full_name FROM payments p JOIN customers c ON p.customer_id = c.customer_id ORDER BY p.created_at DESC LIMIT 5");

// Expiring Agreements (next 30 days)
$expiring_agreements = $conn->query("SELECT a.*, c.full_name FROM agreements a JOIN customers c ON a.customer_id = c.customer_id WHERE a.status = 'active' AND a.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY a.end_date ASC LIMIT 5");

// Revenue data for last 6 months
$revenue_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed' AND DATE_FORMAT(payment_date, '%Y-%m') = '$month'");
    $revenue_data[] = [
        'month' => date('M Y', strtotime("-$i months")),
        'revenue' => floatval($result->fetch_assoc()['total'] ?? 0)
    ];
}

// Space occupancy data
$total_spaces = $stats['total_shops'] + $stats['total_rooms'] + $stats['total_basements'];
$occupied_spaces = $stats['occupied_shops'] + $stats['occupied_rooms'] + $stats['occupied_basements'];
$available_spaces = $total_spaces - $occupied_spaces;

// Payment status distribution
$payment_status = [];
$result = $conn->query("SELECT status, SUM(amount) as total FROM ledger GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $payment_status[] = [
        'status' => ucfirst($row['status']),
        'amount' => floatval($row['total'] ?? 0)
    ];
}

// Space type distribution
$space_types = [
    ['type' => 'Shops', 'total' => $stats['total_shops'], 'occupied' => $stats['occupied_shops']],
    ['type' => 'Rooms', 'total' => $stats['total_rooms'], 'occupied' => $stats['occupied_rooms']],
    ['type' => 'Basements', 'total' => $stats['total_basements'], 'occupied' => $stats['occupied_basements']]
];

$page_title = 'Admin Dashboard - Plaza Management System';
include '../includes/header.php';
?>

<div class="card" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; border: none; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3); margin-bottom: 1.5rem;">
    <div class="card-header" style="border-bottom: none; padding: 0.35rem 1rem; display: flex; align-items: center; justify-content: space-between; min-height: auto; line-height: 1.1;">
        <h1 class="card-title" style="color: white; margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
            <i class="fas fa-tachometer-alt" style="font-size: 1.15rem;"></i> Dashboard
        </h1>
        <span style="font-weight: 400; font-size: 0.95rem; color: rgba(255, 255, 255, 0.9);">
            Welcome back, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>!
        </span>
    </div>
</div>

<div class="stats-grid">
    <a href="settings.php?tab=shops" class="stat-card-link">
        <div class="stat-card">
            <i class="fas fa-store stat-icon"></i>
            <div class="stat-value"><?php echo $stats['total_shops']; ?></div>
            <div class="stat-label">Total Shops</div>
            <div style="margin-top: 0.25rem; color: var(--text-light); font-size: 0.75rem;">
                <?php echo $stats['occupied_shops']; ?> Occupied
            </div>
        </div>
    </a>

    <a href="settings.php?tab=rooms" class="stat-card-link">
        <div class="stat-card">
            <i class="fas fa-door-open stat-icon"></i>
            <div class="stat-value"><?php echo $stats['total_rooms']; ?></div>
            <div class="stat-label">Total Rooms</div>
            <div style="margin-top: 0.25rem; color: var(--text-light); font-size: 0.75rem;">
                <?php echo $stats['occupied_rooms']; ?> Occupied
            </div>
        </div>
    </a>

    <a href="settings.php?tab=basements" class="stat-card-link">
        <div class="stat-card">
            <i class="fas fa-layer-group stat-icon"></i>
            <div class="stat-value"><?php echo $stats['total_basements']; ?></div>
            <div class="stat-label">Total Basements</div>
            <div style="margin-top: 0.25rem; color: var(--text-light); font-size: 0.75rem;">
                <?php echo $stats['occupied_basements']; ?> Occupied
            </div>
        </div>
    </a>

    <a href="customers.php" class="stat-card-link">
        <div class="stat-card success">
            <i class="fas fa-users stat-icon"></i>
            <div class="stat-value"><?php echo $stats['total_customers']; ?></div>
            <div class="stat-label">Total Customers</div>
        </div>
    </a>

    <a href="assigned-spaces.php" class="stat-card-link">
        <div class="stat-card success">
            <i class="fas fa-dollar-sign stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($stats['monthly_revenue']); ?></div>
            <div class="stat-label">Monthly Revenue</div>
        </div>
    </a>

    <a href="ledger.php?status=pending" class="stat-card-link">
        <div class="stat-card warning">
            <i class="fas fa-exclamation-triangle stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($stats['pending_payments']); ?></div>
            <div class="stat-label">Pending Payments</div>
        </div>
    </a>
</div>

<!-- Charts Section -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-chart-line"></i> Revenue Trend (Last 6 Months)</h2>
        </div>
        <div style="padding: 1.5rem;">
            <canvas id="revenueChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-chart-pie"></i> Space Occupancy</h2>
        </div>
        <div style="padding: 1.5rem;">
            <canvas id="occupancyChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-chart-bar"></i> Space Type Distribution</h2>
        </div>
        <div style="padding: 1.5rem;">
            <canvas id="spaceTypeChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-chart-pie"></i> Payment Status</h2>
        </div>
        <div style="padding: 1.5rem;">
            <canvas id="paymentStatusChart" style="max-height: 300px;"></canvas>
        </div>
    </div>
</div>

<!-- Recent Activity Section -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
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

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Revenue Trend Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($revenue_data, 'month')); ?>,
        datasets: [{
            label: 'Revenue',
            data: <?php echo json_encode(array_column($revenue_data, 'revenue')); ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#2563eb',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 },
                callbacks: {
                    label: function(context) {
                        return 'Rs ' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rs ' + value.toLocaleString();
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Space Occupancy Chart
const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
new Chart(occupancyCtx, {
    type: 'doughnut',
    data: {
        labels: ['Occupied', 'Available'],
        datasets: [{
            data: [<?php echo $occupied_spaces; ?>, <?php echo $available_spaces; ?>],
            backgroundColor: ['#10b981', '#e5e7eb'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 13 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Space Type Distribution Chart
const spaceTypeCtx = document.getElementById('spaceTypeChart').getContext('2d');
new Chart(spaceTypeCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($space_types, 'type')); ?>,
        datasets: [
            {
                label: 'Total',
                data: <?php echo json_encode(array_column($space_types, 'total')); ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 8
            },
            {
                label: 'Occupied',
                data: <?php echo json_encode(array_column($space_types, 'occupied')); ?>,
                backgroundColor: '#10b981',
                borderRadius: 8
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    padding: 15,
                    font: { size: 13 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Payment Status Chart
const paymentStatusCtx = document.getElementById('paymentStatusChart').getContext('2d');
new Chart(paymentStatusCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($payment_status, 'status')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($payment_status, 'amount')); ?>,
            backgroundColor: [
                '#10b981',
                '#f59e0b',
                '#ef4444'
            ],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 13 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        return label + ': Rs ' + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>

